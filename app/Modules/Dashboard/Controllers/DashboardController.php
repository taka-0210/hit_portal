<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Platform\Auth\AuthService;
use App\Platform\Storage\JsonStore;
use App\Platform\View\View;

final class DashboardController
{
    public function index(): void
    {
        $store = new JsonStore();
        $user = (new AuthService())->user();
        $portalSettings = $this->portalSettings($store);
        $cleanup = $this->cleanupCompletedTodos($store->all('grid_sections'), (int) ($portalSettings['completed_todo_delete_days'] ?? 5));
        $grids = $cleanup['grids'];
        if ($cleanup['changed']) {
            $this->writeGridSections($grids);
        }

        $gridAreas = $this->gridAreas($grids, $user, $store->all('departments'), $store->all('grid_layouts'));

        View::render('dashboard/index', [
            'gridAreas' => $gridAreas,
            'gridColumns' => $this->mergeGridAreaColumns($gridAreas),
            'portalSettings' => $portalSettings,
            'user' => $user,
        ]);
    }

    public function guide(): void
    {
        $settings = $this->portalSettings(new JsonStore());

        View::render('guide/index', [
            'title' => (string) ($settings['guide_title'] ?? 'HIT Portal 取扱説明'),
            'body' => (string) ($settings['guide_body'] ?? $this->defaultGuideBody()),
        ]);
    }

    public function file(): void
    {
        $gridId = (int) ($_GET['grid_id'] ?? 0);
        $fileId = (string) ($_GET['file_id'] ?? '');
        $store = new JsonStore();
        $user = (new AuthService())->user();
        $departments = $store->all('departments');

        foreach ($store->all('grid_sections') as $grid) {
            if ((int) ($grid['id'] ?? 0) !== $gridId || !$this->canSeeGrid($grid, $user, $this->departmentNames($departments))) {
                continue;
            }

            foreach (($grid['groups'] ?? []) as $group) {
                foreach (($group['entries'] ?? []) as $entry) {
                    if (!$this->canUseGridEntry($grid, $entry, $user)) {
                        continue;
                    }

                    if (($entry['file_id'] ?? '') !== $fileId) {
                        continue;
                    }

                    $path = BASE_PATH . '/' . ltrim((string) ($entry['storage_path'] ?? ''), '/\\');
                    if (!is_file($path)) {
                        http_response_code(404);
                        exit('File not found.');
                    }

                    header('Content-Type: ' . ($entry['mime_type'] ?? 'application/octet-stream'));
                    header('Content-Length: ' . filesize($path));
                    header('Content-Disposition: inline; filename="' . rawurlencode((string) ($entry['original_name'] ?? $entry['label'] ?? 'file')) . '"');
                    readfile($path);
                    exit;
                }
            }
        }

        http_response_code(404);
        exit('File not found.');
    }

    public function entryStore(): void
    {
        verify_csrf();

        $gridId = (int) ($_POST['grid_id'] ?? 0);
        $store = new JsonStore();
        $user = (new AuthService())->user();
        $departments = $store->all('departments');
        $departmentNames = $this->departmentNames($departments);
        $grids = $store->all('grid_sections');

        foreach ($grids as $gridIndex => $grid) {
            if ((int) ($grid['id'] ?? 0) !== $gridId) {
                continue;
            }

            if (($grid['status'] ?? 'published') !== 'published' || !$this->canSeeGrid($grid, $user, $departmentNames)) {
                http_response_code(403);
                exit('Forbidden.');
            }

            $registrationType = (string) ($grid['registration_type'] ?? 'links');
            $entry = $this->entryPayload($registrationType);
            if ($entry === null) {
                redirect('dashboard');
            }
            if (($grid['scope_type'] ?? '') === 'store_shared') {
                $entry['store_id'] = (int) ($user['department2_id'] ?? 0);
            }

            $groupLabel = trim((string) ($_POST['entry_group'] ?? ''));
            $grids[$gridIndex] = $this->appendEntryToGrid($grid, $groupLabel, $entry);
            $this->writeGridSections($grids);
            redirect('dashboard');
        }

        http_response_code(404);
        exit('Grid not found.');
    }

    public function todoProgress(): void
    {
        verify_csrf();

        $gridId = (int) ($_POST['grid_id'] ?? 0);
        $todoIndex = (int) ($_POST['todo_index'] ?? -1);
        $progress = (string) ($_POST['progress'] ?? 'not_started');
        $allowedProgress = ['not_started', 'in_progress', 'done'];
        if (!in_array($progress, $allowedProgress, true)) {
            $progress = 'not_started';
        }

        $store = new JsonStore();
        $user = (new AuthService())->user();
        $departments = $store->all('departments');
        $grids = $store->all('grid_sections');

        foreach ($grids as $gridIndex => $grid) {
            if ((int) ($grid['id'] ?? 0) !== $gridId || ($grid['registration_type'] ?? '') !== 'todo') {
                continue;
            }

            if (($grid['status'] ?? 'published') !== 'published' || !$this->canSeeGrid($grid, $user, $this->departmentNames($departments))) {
                http_response_code(403);
                exit('Forbidden.');
            }

            $todoRefs = $this->todoEntryRefsNewestFirst($grid, (int) ($user['department2_id'] ?? 0), $user);
            if (isset($todoRefs[$todoIndex])) {
                $groupIndex = $todoRefs[$todoIndex]['group_index'];
                $entryIndex = $todoRefs[$todoIndex]['entry_index'];
                $now = date('Y-m-d H:i:s');
                $previousProgress = (string) ($grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['progress'] ?? 'not_started');
                $grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['progress'] = $progress;
                $grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['updated_at'] = $now;
                if ($progress === 'done') {
                    if ($previousProgress !== 'done' || empty($grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['completed_at'])) {
                        $grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['completed_at'] = $now;
                    }
                } else {
                    unset($grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['completed_at']);
                }

                $this->writeGridSections($grids);
                if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                    $response = ['ok' => true, 'progress' => $progress];
                    if ($progress === 'done' && $previousProgress !== 'done') {
                        $days = $this->completedTodoDeleteDays($store);
                        $response['completed_at'] = $grids[$gridIndex]['groups'][$groupIndex]['entries'][$entryIndex]['completed_at'];
                        $response['completion_message'] = $this->todoCompletionMessage($days);
                    }

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                redirect('dashboard');
            }
        }

        http_response_code(404);
        exit('TO DO not found.');
    }

    private function gridAreas(array $grids, ?array $user, array $departments, array $layouts = []): array
    {
        $areas = [
            'common' => [1 => [], 2 => [], 3 => []],
            'store_shared' => [1 => [], 2 => [], 3 => []],
            'store' => [1 => [], 2 => [], 3 => []],
        ];
        $departmentNames = $this->departmentNames($departments);
        $storeId = (int) ($user['department2_id'] ?? 0);
        if ($storeId > 0) {
            $grids = $this->applyStoreLayouts($grids, $layouts, $storeId);
        }

        foreach ($grids as $grid) {
            if (($grid['status'] ?? 'published') !== 'published') {
                continue;
            }

            if (!$this->canSeeGrid($grid, $user, $departmentNames)) {
                continue;
            }

            $grid = $this->filterGridEntriesForStore($grid, $storeId, $user);
            $grid = $this->sortGridEntriesNewestFirst($grid);
            $area = $this->gridArea($grid);
            $column = min(3, max(1, (int) ($grid['column'] ?? 1)));
            $areas[$area][$column][] = $grid;
        }

        foreach ($areas as &$columns) {
            foreach ($columns as &$columnGrids) {
                usort($columnGrids, fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
            }
            unset($columnGrids);
        }
        unset($columns);

        return [
            'common' => array_values($areas['common']),
            'store_shared' => array_values($areas['store_shared']),
            'store' => array_values($areas['store']),
        ];
    }

    private function applyStoreLayouts(array $grids, array $layouts, int $storeId): array
    {
        $storeLayouts = [];
        foreach ($layouts as $layout) {
            if (($layout['scope_type'] ?? '') !== 'store' || (int) ($layout['scope_target_id'] ?? 0) !== $storeId) {
                continue;
            }

            $storeLayouts[(int) ($layout['grid_id'] ?? 0)] = $layout;
        }

        if ($storeLayouts === []) {
            return $grids;
        }

        foreach ($grids as &$grid) {
            $gridId = (int) ($grid['id'] ?? 0);
            if (!isset($storeLayouts[$gridId])) {
                continue;
            }

            $grid['column'] = min(3, max(1, (int) ($storeLayouts[$gridId]['column'] ?? $grid['column'] ?? 1)));
            $grid['sort_order'] = (int) ($storeLayouts[$gridId]['sort_order'] ?? $grid['sort_order'] ?? 0);
        }
        unset($grid);

        return $grids;
    }

    private function mergeGridAreaColumns(array $areas): array
    {
        $columns = [[], [], []];
        foreach (['common', 'store_shared', 'store'] as $area) {
            foreach (($areas[$area] ?? []) as $index => $column) {
                $columns[$index] = array_merge($columns[$index] ?? [], $column);
            }
        }

        return $columns;
    }

    private function portalSettings(JsonStore $store): array
    {
        return $store->find('portal_settings', 1) ?? [
            'hero_message' => '',
            'new_entry_days' => 5,
            'completed_todo_delete_days' => 5,
            'guide_title' => 'HIT Portal 取扱説明',
            'guide_body' => $this->defaultGuideBody(),
        ];
    }

    private function defaultGuideBody(): string
    {
        return implode("\n\n", [
            'このページは、HIT Portalを使うための取扱説明です。',
            "1. HIT Portalとは\n店舗ごとの情報共有をひとつにまとめるポータルです。お知らせ、リンク、ファイル、TO DOを確認できます。紙や個別チャットに分かれていた情報を、店舗ごとに見やすく整理するための場所です。",
            "2. ログイン\n店舗アカウントでログインすると、自店舗向けの情報が表示されます。管理者アカウントではグリッドの作成や編集ができます。通常の店舗スタッフは、ポータル閲覧、投稿、TO DOのステータス変更を行います。",
            "3. グリッドとは？\nポータルTOPに並んでいる四角い情報のまとまりを「グリッド」と呼びます。リンク集、ファイル、手入力のお知らせ、TO DOなど、目的ごとに登録方法が分かれています。各グリッド右上の＋ボタンから、そのグリッドに新しい情報を投稿できます。",
            "4. グリッドの表示エリアについて\n共通グリッドは全店舗共通の情報です。店舗共通グリッドは全店舗に表示されますが、投稿データは店舗ごとに分かれます。店舗専用グリッドは特定店舗向けの情報です。表示エリアは上から「共通グリッド」「店舗共通グリッド」「店舗専用グリッド」の順に並びます。",
            "5. グリッドの作成方法\n管理者は管理画面のグリッド管理から新規作成できます。グリッド名、色、登録方法、表示方法、展開パターンなどを設定します。登録方法は一度選択すると、保存後は変更できません。登録データの持ち方が変わるためです。",
            "6. グリッドの移動について\n管理画面では、グリッドを上下左右のボタンで移動できます。▶は右隣、◀は左隣、▲は上、▼は下のグリッドと入れ替えます。列移動ボタンでは、左列または右列へ移動できます。公開グリッドと非公開グリッドは別エリアで管理され、非公開グリッドは公開エリアへは移動しません。",
            "7. 投稿方法\n各グリッド右上の＋ボタンから投稿できます。リンク、ファイル、TO DOなど、グリッドごとの形式に沿って登録してください。投稿した内容は、基本的に新しいものが上に表示されます。新着期間内の投稿にはNEWマークが表示されます。",
            "8. TO DO\nステータスは未着手、進行中、完了から選びます。写真が登録されている場合はクリックで拡大表示できます。完了にすると「作業お疲れ様でした！完了した投稿は5日後に自動削除されます。」という案内が表示され、設定された日数の後に自動削除されます。",
            "9. 展開ボタンについて\nグリッドの内容が多い場合、最初は閉じた状態で表示できます。▼で展開、▲で閉じる操作ができます。メールアドレス一覧や電話番号一覧など、縦に長くなりやすい情報に使います。",
            "10. 実証実験中のお願い\n分かりにくい点、足りない項目、表示の違和感、新機能実装のアイデアがあれば高見へ共有してください。",
        ]);
    }

    private function completedTodoDeleteDays(JsonStore $store): int
    {
        $settings = $this->portalSettings($store);
        return max(0, (int) ($settings['completed_todo_delete_days'] ?? 5));
    }

    private function todoCompletionMessage(int $days): string
    {
        if ($days <= 0) {
            return '作業お疲れ様でした！完了した投稿は自動削除されません。';
        }

        return "作業お疲れ様でした！完了した投稿は{$days}日後に自動削除されます。";
    }

    private function cleanupCompletedTodos(array $grids, int $days): array
    {
        if ($days <= 0) {
            return ['grids' => $grids, 'changed' => false];
        }

        $changed = false;
        $cutoff = strtotime('-' . $days . ' days');
        if ($cutoff === false) {
            return ['grids' => $grids, 'changed' => false];
        }

        foreach ($grids as $gridIndex => $grid) {
            if (($grid['registration_type'] ?? '') !== 'todo') {
                continue;
            }

            foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
                $entries = [];
                foreach (($group['entries'] ?? []) as $entry) {
                    $completedAt = strtotime((string) ($entry['completed_at'] ?? ''));
                    if (($entry['progress'] ?? '') === 'done' && $completedAt !== false && $completedAt <= $cutoff) {
                        $changed = true;
                        continue;
                    }

                    $entries[] = $entry;
                }

                $grids[$gridIndex]['groups'][$groupIndex]['entries'] = $entries;
            }
        }

        return ['grids' => $grids, 'changed' => $changed];
    }

    private function entryPayload(string $registrationType): ?array
    {
        if ($registrationType === 'links') {
            $label = trim((string) ($_POST['entry_label'] ?? ''));
            $url = trim((string) ($_POST['entry_url'] ?? ''));
            if ($label === '' && $url === '') {
                return null;
            }

            return [
                'label' => $label !== '' ? $label : $url,
                'url' => $url !== '' ? $url : '#',
            ];
        }

        if ($registrationType === 'files') {
            $label = trim((string) ($_POST['entry_label'] ?? ''));
            $upload = $_FILES['entry_file'] ?? [];
            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return null;
            }

            $saved = $this->saveGridFile($upload);
            return array_merge([
                'label' => $label !== '' ? $label : $saved['original_name'],
                'url' => '#',
            ], $saved);
        }

        if ($registrationType === 'todo') {
            $label = trim((string) ($_POST['todo_field1'] ?? ''));
            $description = trim((string) ($_POST['todo_field2'] ?? ''));
            $progress = trim((string) ($_POST['todo_progress'] ?? 'not_started'));
            $allowedProgress = ['not_started', 'in_progress', 'done'];
            if (!in_array($progress, $allowedProgress, true)) {
                $progress = 'not_started';
            }

            $entry = [
                'label' => $label,
                'url' => '#',
                'description' => $description,
                'progress' => $progress,
            ];
            if ($progress === 'done') {
                $entry['completed_at'] = date('Y-m-d H:i:s');
            }

            $upload = $_FILES['todo_image'] ?? [];
            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $entry = array_merge($entry, $this->saveGridImage($upload));
            }

            return ($label === '' && $description === '' && !isset($entry['file_id'])) ? null : $entry;
        }

        $label = trim((string) ($_POST['entry_content'] ?? ''));
        if ($label === '') {
            return null;
        }

        return [
            'label' => $label,
            'url' => '#',
        ];
    }

    private function appendEntryToGrid(array $grid, string $groupLabel, array $entry): array
    {
        $now = date('Y-m-d H:i:s');
        $entry['created_at'] = $entry['created_at'] ?? $now;
        $entry['updated_at'] = $now;

        $groupKey = $groupLabel !== '' ? $groupLabel : 'default';
        $groups = $grid['groups'] ?? [];

        foreach ($groups as $index => $group) {
            $currentKey = ((string) ($group['label'] ?? '')) !== '' ? (string) $group['label'] : 'default';
            if ($currentKey === $groupKey) {
                array_unshift($groups[$index]['entries'], $entry);
                $grid['groups'] = $groups;
                return $grid;
            }
        }

        $groups[] = [
            'label' => $groupLabel,
            'entries' => [$entry],
        ];
        $grid['groups'] = $groups;
        return $grid;
    }

    private function gridArea(array $grid): string
    {
        $scope = (string) ($grid['scope_type'] ?? 'all');
        if ($scope === 'store_shared') {
            return 'store_shared';
        }
        if ($scope === 'store') {
            return 'store';
        }

        return 'common';
    }

    private function filterGridEntriesForStore(array $grid, int $storeId, ?array $user): array
    {
        if (($grid['scope_type'] ?? '') !== 'store_shared' || ($user['role'] ?? '') === 'system_admin') {
            return $grid;
        }

        foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
            $entries = array_values(array_filter(
                $group['entries'] ?? [],
                static fn (array $entry): bool => (int) ($entry['store_id'] ?? 0) === $storeId
            ));
            $grid['groups'][$groupIndex]['entries'] = $entries;
        }

        return $grid;
    }

    private function sortGridEntriesNewestFirst(array $grid): array
    {
        foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
            $entries = $group['entries'] ?? [];
            usort($entries, fn (array $a, array $b): int => $this->entryTimestamp($b) <=> $this->entryTimestamp($a));
            $grid['groups'][$groupIndex]['entries'] = $entries;
        }

        return $grid;
    }

    private function todoEntryRefsNewestFirst(array $grid, int $storeId = 0, ?array $user = null): array
    {
        $refs = [];
        foreach (($grid['groups'] ?? []) as $groupIndex => $group) {
            foreach (($group['entries'] ?? []) as $entryIndex => $entry) {
                if (($grid['scope_type'] ?? '') === 'store_shared' && ($user['role'] ?? '') !== 'system_admin' && (int) ($entry['store_id'] ?? 0) !== $storeId) {
                    continue;
                }

                $refs[] = [
                    'group_index' => $groupIndex,
                    'entry_index' => $entryIndex,
                    'timestamp' => $this->entryTimestamp($entry),
                ];
            }
        }

        usort($refs, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);
        return $refs;
    }

    private function entryTimestamp(array $entry): int
    {
        $createdAt = strtotime((string) ($entry['created_at'] ?? ''));
        return $createdAt === false ? 0 : $createdAt;
    }

    private function canUseGridEntry(array $grid, array $entry, ?array $user): bool
    {
        if (($grid['scope_type'] ?? '') !== 'store_shared' || ($user['role'] ?? '') === 'system_admin') {
            return true;
        }

        return (int) ($entry['store_id'] ?? 0) === (int) ($user['department2_id'] ?? 0);
    }

    private function saveGridFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('ファイルのアップロードに失敗しました。');
        }

        if ((int) ($file['size'] ?? 0) > 52428800) {
            throw new \RuntimeException('アップロードできるファイルサイズは 50MB までです。');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov', 'txt', 'csv'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('このファイル形式はまだ対応していません。');
        }

        $directory = BASE_PATH . '/storage/private/grids';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fileId = bin2hex(random_bytes(12));
        $storedName = $fileId . '.' . $extension;
        $targetPath = $directory . '/' . $storedName;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
            throw new \RuntimeException('ファイルを保存できませんでした。');
        }

        $mimeType = mime_content_type($targetPath);

        return [
            'file_id' => $fileId,
            'original_name' => $originalName,
            'storage_path' => 'storage/private/grids/' . $storedName,
            'mime_type' => $mimeType !== false ? $mimeType : 'application/octet-stream',
            'file_size' => (int) ($file['size'] ?? 0),
        ];
    }

    private function saveGridImage(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('画像のアップロードに失敗しました。');
        }

        if ((int) ($file['size'] ?? 0) > 10485760) {
            throw new \RuntimeException('アップロードできる画像サイズは 10MB までです。');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('TO DO画像は jpg, png, gif, webp のみ対応しています。');
        }

        if (!function_exists('imagewebp')) {
            throw new \RuntimeException('画像変換に必要なGD WebPが有効ではありません。');
        }

        $sourceData = file_get_contents((string) ($file['tmp_name'] ?? ''));
        if ($sourceData === false) {
            throw new \RuntimeException('画像を読み込めませんでした。');
        }

        $sourceImage = imagecreatefromstring($sourceData);
        if ($sourceImage === false) {
            throw new \RuntimeException('画像を変換できませんでした。');
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $maxSide = 1200;
        $scale = min(1, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $directory = BASE_PATH . '/storage/private/grids';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fileId = bin2hex(random_bytes(12));
        $storedName = $fileId . '.webp';
        $targetPath = $directory . '/' . $storedName;

        if (!imagewebp($targetImage, $targetPath, 80)) {
            imagedestroy($sourceImage);
            imagedestroy($targetImage);
            throw new \RuntimeException('WebP画像を保存できませんでした。');
        }

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        return [
            'file_id' => $fileId,
            'original_name' => $originalName,
            'storage_path' => 'storage/private/grids/' . $storedName,
            'mime_type' => 'image/webp',
            'file_size' => is_file($targetPath) ? filesize($targetPath) : 0,
        ];
    }

    private function canSeeGrid(array $grid, ?array $user, array $departmentNames): bool
    {
        if ($user === null) {
            return ($grid['scope_type'] ?? 'all') === 'all';
        }

        if (($user['role'] ?? '') === 'system_admin') {
            return true;
        }

        $scope = $grid['scope_type'] ?? 'all';
        if ($scope === 'all') {
            return true;
        }
        if ($scope === 'store_shared') {
            return (int) ($user['department2_id'] ?? 0) > 0;
        }

        $target = trim((string) ($grid['scope_target'] ?? ''));
        if ($target === '') {
            return $scope === 'store'
                ? (int) ($user['department2_id'] ?? 0) > 0
                : (int) ($user['department1_id'] ?? $user['department_id'] ?? 0) > 0;
        }

        $userDepartmentName = $departmentNames[(int) ($user['department1_id'] ?? $user['department_id'] ?? 0)] ?? '';
        $userStoreName = $departmentNames[(int) ($user['department2_id'] ?? 0)] ?? '';

        return $scope === 'store'
            ? $target === $userStoreName
            : $target === $userDepartmentName;
    }

    private function writeGridSections(array $grids): void
    {
        $path = BASE_PATH . '/storage/data/grid_sections.json';
        file_put_contents($path, json_encode(array_values($grids), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function departmentNames(array $departments): array
    {
        $names = [];
        foreach ($departments as $department) {
            $names[(int) $department['id']] = (string) $department['name'];
        }

        return $names;
    }
}
