<?php

declare(strict_types=1);

namespace App\Modules\Administration\Controllers;

use App\Platform\Storage\JsonStore;
use App\Platform\Auth\AuthService;
use App\Platform\View\View;

final class AdminController
{
    private JsonStore $store;
    private AuthService $auth;

    public function __construct()
    {
        $this->store = new JsonStore();
        $this->auth = new AuthService();
    }

    public function users(): void
    {
        $departments = $this->store->all('departments');

        View::render('admin/users', [
            'users' => $this->store->all('users'),
            'companyLevel' => $this->departmentsByType($departments, 'company'),
            'departmentLevel2' => $this->departmentsByType($departments, 'store'),
            'departmentNames' => $this->departmentNames($departments),
            'roles' => $this->store->all('roles'),
        ]);
    }

    public function createUser(): void
    {
        $departments = $this->store->all('departments');

        View::render('admin/user-form', [
            'mode' => 'create',
            'user' => null,
            'companyLevel' => $this->departmentsByType($departments, 'company'),
            'departmentLevel2' => $this->departmentsByType($departments, 'store'),
            'roles' => $this->store->all('roles'),
        ]);
    }

    public function storeUser(): void
    {
        verify_csrf();

        $record = $this->userPayload();
        $password = trim($_POST['password'] ?? '');
        $record['password_hash'] = password_hash($password !== '' ? $password : 'password', PASSWORD_DEFAULT);
        $record['must_change_password'] = true;

        $this->store->save('users', $record);
        redirect('admin.users');
    }

    public function editUser(): void
    {
        $user = $this->store->find('users', (int) ($_GET['id'] ?? 0));
        if ($user === null) {
            http_response_code(404);
            exit('Account not found.');
        }

        $departments = $this->store->all('departments');

        View::render('admin/user-form', [
            'mode' => 'edit',
            'user' => $user,
            'companyLevel' => $this->departmentsByType($departments, 'company'),
            'departmentLevel2' => $this->departmentsByType($departments, 'store'),
            'roles' => $this->store->all('roles'),
        ]);
    }

    public function updateUser(): void
    {
        verify_csrf();

        $current = $this->store->find('users', (int) ($_POST['id'] ?? 0));
        if ($current === null) {
            http_response_code(404);
            exit('Account not found.');
        }

        $record = array_merge($current, $this->userPayload());
        $password = trim($_POST['password'] ?? '');
        if ($password !== '') {
            $record['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $record['must_change_password'] = true;
        }

        $this->store->save('users', $record);
        redirect('admin.users');
    }

    public function stores(): void
    {
        $departments = $this->store->all('departments');

        View::render('admin/departments', [
            'mode' => 'store',
            'editItem' => null,
            'items' => $this->departmentsByType($departments, 'store', false),
            'companies' => $this->departmentsByType($departments, 'company'),
            'companyNames' => $this->departmentNames($departments),
        ]);
    }

    public function storeStore(): void
    {
        verify_csrf();

        $this->store->save('departments', $this->affiliationPayload('store'));

        redirect('admin.stores');
    }

    public function editStore(): void
    {
        $departments = $this->store->all('departments');
        $store = $this->store->find('departments', (int) ($_GET['id'] ?? 0));
        if ($store === null || $this->normalizeAffiliationType((string) ($store['level'] ?? '')) !== 'store') {
            http_response_code(404);
            exit('Store not found.');
        }

        View::render('admin/departments', [
            'mode' => 'store',
            'editItem' => $store,
            'items' => $this->departmentsByType($departments, 'store', false),
            'companies' => $this->departmentsByType($departments, 'company'),
            'companyNames' => $this->departmentNames($departments),
        ]);
    }

    public function updateStore(): void
    {
        verify_csrf();

        $current = $this->store->find('departments', (int) ($_POST['id'] ?? 0));
        if ($current === null || $this->normalizeAffiliationType((string) ($current['level'] ?? '')) !== 'store') {
            http_response_code(404);
            exit('Store not found.');
        }

        $payload = array_merge($current, $this->affiliationPayload('store'));
        $payload['id'] = (int) ($current['id'] ?? 0);

        $this->store->save('departments', $payload);

        redirect('admin.stores');
    }

    public function companies(): void
    {
        $departments = $this->store->all('departments');

        View::render('admin/departments', [
            'mode' => 'company',
            'editItem' => null,
            'items' => $this->departmentsByType($departments, 'company', false),
            'companies' => [],
            'companyNames' => $this->departmentNames($departments),
        ]);
    }

    public function storeCompany(): void
    {
        verify_csrf();

        $payload = $this->affiliationPayload('company');
        if (!empty($_FILES['logo']['name'])) {
            $payload['logo_path'] = $this->saveCompanyLogo($_FILES['logo']);
        }

        $this->store->save('departments', $payload);

        redirect('admin.companies');
    }

    public function showCompany(): void
    {
        $departments = $this->store->all('departments');
        $company = $this->store->find('departments', (int) ($_GET['id'] ?? 0));
        if ($company === null || $this->normalizeAffiliationType((string) ($company['level'] ?? '')) !== 'company') {
            http_response_code(404);
            exit('Company not found.');
        }

        $companyId = (int) ($company['id'] ?? 0);
        $stores = array_values(array_filter(
            $this->departmentsByType($departments, 'store', false),
            static fn (array $store): bool => (int) ($store['parent_id'] ?? 0) === $companyId
        ));
        $accounts = array_values(array_filter(
            $this->store->all('users'),
            static fn (array $user): bool => (int) ($user['department1_id'] ?? 0) === $companyId
        ));
        $roles = array_values(array_filter(
            $this->store->all('roles'),
            static fn (array $role): bool => ($role['key'] ?? '') !== 'system_admin'
        ));

        View::render('admin/company-detail', [
            'company' => $company,
            'stores' => $stores,
            'accounts' => $accounts,
            'roles' => $roles,
            'departmentNames' => $this->departmentNames($departments),
        ]);
    }

    public function storeCompanyStore(): void
    {
        verify_csrf();

        $company = $this->store->find('departments', (int) ($_POST['company_id'] ?? 0));
        if ($company === null || $this->normalizeAffiliationType((string) ($company['level'] ?? '')) !== 'company') {
            http_response_code(404);
            exit('Company not found.');
        }

        $payload = $this->affiliationPayload('store');
        $payload['parent_id'] = (int) ($company['id'] ?? 0);

        $this->store->save('departments', $payload);

        redirect('admin.companies.show', ['id' => (int) ($company['id'] ?? 0)]);
    }

    public function storeCompanyUser(): void
    {
        verify_csrf();

        $company = $this->store->find('departments', (int) ($_POST['company_id'] ?? 0));
        if ($company === null || $this->normalizeAffiliationType((string) ($company['level'] ?? '')) !== 'company') {
            http_response_code(404);
            exit('Company not found.');
        }

        $record = $this->userPayload();
        $record['department1_id'] = (int) ($company['id'] ?? 0);
        if ($record['role'] === 'company_admin') {
            $record['department2_id'] = 0;
        }
        if ($record['role'] === 'system_admin') {
            $record['role'] = 'store_user';
        }

        $password = trim($_POST['password'] ?? '');
        $record['password_hash'] = password_hash($password !== '' ? $password : 'password', PASSWORD_DEFAULT);
        $record['must_change_password'] = true;

        $this->store->save('users', $record);

        redirect('admin.companies.show', ['id' => (int) ($company['id'] ?? 0)]);
    }

    public function editCompany(): void
    {
        $departments = $this->store->all('departments');
        $company = $this->store->find('departments', (int) ($_GET['id'] ?? 0));
        if ($company === null || $this->normalizeAffiliationType((string) ($company['level'] ?? '')) !== 'company') {
            http_response_code(404);
            exit('Company not found.');
        }

        View::render('admin/departments', [
            'mode' => 'company',
            'editItem' => $company,
            'items' => $this->departmentsByType($departments, 'company', false),
            'companies' => [],
            'companyNames' => $this->departmentNames($departments),
        ]);
    }

    public function updateCompany(): void
    {
        verify_csrf();

        $current = $this->store->find('departments', (int) ($_POST['id'] ?? 0));
        if ($current === null || $this->normalizeAffiliationType((string) ($current['level'] ?? '')) !== 'company') {
            http_response_code(404);
            exit('Company not found.');
        }

        $payload = array_merge($current, $this->affiliationPayload('company'));
        $payload['id'] = (int) ($current['id'] ?? 0);
        if (!empty($_FILES['logo']['name'])) {
            $payload['logo_path'] = $this->saveCompanyLogo($_FILES['logo']);
        } elseif (!empty($current['logo_path'])) {
            $payload['logo_path'] = (string) $current['logo_path'];
        }

        $this->store->save('departments', $payload);

        redirect('admin.companies');
    }

    public function roles(): void
    {
        View::render('admin/roles', [
            'roles' => $this->store->all('roles'),
        ]);
    }

    public function portalSettings(): void
    {
        View::render('admin/portal-settings', [
            'settings' => $this->portalSettingsRecord(),
        ]);
    }

    public function updatePortalSettings(): void
    {
        verify_csrf();

        $current = $this->portalSettingsRecord();
        $deletePassword = trim((string) ($_POST['grid_delete_password'] ?? ''));
        $record = [
            'id' => 1,
            'hero_message' => trim($_POST['hero_message'] ?? ''),
            'new_entry_days' => max(0, (int) ($_POST['new_entry_days'] ?? 5)),
            'completed_todo_delete_days' => max(0, (int) ($_POST['completed_todo_delete_days'] ?? 5)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($deletePassword !== '') {
            $record['grid_delete_password_hash'] = password_hash($deletePassword, PASSWORD_DEFAULT);
        } elseif (($current['grid_delete_password_hash'] ?? '') !== '') {
            $record['grid_delete_password_hash'] = (string) $current['grid_delete_password_hash'];
        }

        $this->store->save('portal_settings', $record);

        $_SESSION['flash'] = 'ポータル設定を更新しました。';
        redirect('admin.portalSettings');
    }

    public function guideSettings(): void
    {
        $settings = $this->portalSettingsRecord();

        View::render('admin/guide', [
            'settings' => $settings,
        ]);
    }

    public function updateGuideSettings(): void
    {
        verify_csrf();

        $current = $this->portalSettingsRecord();
        $record = array_merge($current, [
            'id' => 1,
            'guide_title' => trim((string) ($_POST['guide_title'] ?? '')),
            'guide_body' => trim((string) ($_POST['guide_body'] ?? '')),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($record['guide_title'] === '') {
            $record['guide_title'] = 'HIT Portal 取扱説明';
        }
        if ($record['guide_body'] === '') {
            $record['guide_body'] = $this->defaultGuideBody();
        }

        $this->store->save('portal_settings', $record);

        $_SESSION['flash'] = '取扱説明を更新しました。';
        redirect('admin.guide');
    }

    public function qrCodes(): void
    {
        View::render('admin/qr-codes', [
            'mode' => 'create',
            'qrCode' => null,
            'qrCodes' => $this->qrCodeRows(),
        ]);
    }

    public function storeQrCode(): void
    {
        verify_csrf();

        $this->store->save('qr_codes', $this->qrCodePayload());
        $_SESSION['flash'] = 'QRコードを登録しました。';
        redirect('admin.qrCodes');
    }

    public function editQrCode(): void
    {
        $qrCode = $this->store->find('qr_codes', (int) ($_GET['id'] ?? 0));
        if ($qrCode === null) {
            http_response_code(404);
            exit('QR code not found.');
        }

        View::render('admin/qr-codes', [
            'mode' => 'edit',
            'qrCode' => $this->withQrCodeUrl($qrCode),
            'qrCodes' => $this->qrCodeRows(),
        ]);
    }

    public function updateQrCode(): void
    {
        verify_csrf();

        $current = $this->store->find('qr_codes', (int) ($_POST['id'] ?? 0));
        if ($current === null) {
            http_response_code(404);
            exit('QR code not found.');
        }

        $this->store->save('qr_codes', array_merge($current, $this->qrCodePayload($current)));
        $_SESSION['flash'] = 'QRコードを更新しました。';
        redirect('admin.qrCodes');
    }

    public function grids(): void
    {
        $departments = $this->store->all('departments');
        $stores = $this->departmentsByType($departments, 'store');
        $layoutScope = ($_GET['layout_scope'] ?? '') === 'store' ? 'store' : 'global';
        $storeId = max(0, (int) ($_GET['store_id'] ?? 0));
        if ($this->isStoreAdmin()) {
            $layoutScope = 'store';
            $storeId = $this->currentStoreId();
            $stores = array_values(array_filter(
                $stores,
                fn (array $store): bool => (int) ($store['id'] ?? 0) === $storeId
            ));
        } elseif ($this->isCompanyAdmin()) {
            $layoutScope = 'global';
            $companyId = $this->currentCompanyId();
            $stores = array_values(array_filter(
                $stores,
                fn (array $store): bool => (int) ($store['parent_id'] ?? 0) === $companyId
            ));
        }
        if ($layoutScope === 'store' && $storeId === 0 && $stores !== []) {
            $storeId = (int) ($stores[0]['id'] ?? 0);
        }

        $grids = $this->store->all('grid_sections');
        if ($layoutScope === 'store') {
            $grids = $this->gridsForStoreLayout($grids, $storeId, $departments);
            $grids = $this->applyGridLayouts($grids, 'store', $storeId);
        } elseif ($this->isSystemAdmin()) {
            $grids = $this->systemAdminManageableGrids($grids);
        }
        if ($this->isStoreAdmin()) {
            $grids = $this->storeAdminManageableGrids($grids);
        }
        if ($this->isCompanyAdmin()) {
            $grids = $this->companyAdminManageableGrids($grids);
        }

        usort($grids, fn (array $a, array $b): int => [
            $this->gridLayoutAreaSort($a),
            (int) ($a['column'] ?? 1),
            (int) ($a['sort_order'] ?? 0),
        ] <=> [
            $this->gridLayoutAreaSort($b),
            (int) ($b['column'] ?? 1),
            (int) ($b['sort_order'] ?? 0),
        ]);

        View::render('admin/grids', [
            'grids' => $grids,
            'layoutScope' => $layoutScope,
            'layoutStoreId' => $storeId,
            'layoutApplied' => ($_GET['layout_applied'] ?? '') === '1',
            'stores' => $stores,
            'toneLabels' => $this->gridToneLabels(),
            'registrationTypeLabels' => $this->registrationTypeLabels(),
            'displayTypeLabels' => $this->displayTypeLabels(),
            'expandTypeLabels' => $this->expandTypeLabels(),
            'scopeTypeLabels' => $this->isStoreAdmin() ? ['store' => '店舗専用'] : ($this->isCompanyAdmin() ? ['company' => '会社共通'] : ($layoutScope === 'store' ? $this->scopeTypeLabels() : $this->systemAdminScopeTypeLabels())),
        ]);
    }

    public function createGrid(): void
    {
        $grid = null;
        if ($this->isCompanyAdmin()) {
            $grid = [
                'scope_type' => 'company',
                'scope_target' => $this->currentCompanyName(),
            ];
        } elseif ($this->isStoreAdmin()) {
            $grid = [
                'scope_type' => 'store',
                'scope_target' => $this->currentStoreName(),
            ];
        }

        View::render('admin/grid-form', [
            'mode' => 'create',
            'grid' => $grid,
            'toneLabels' => $this->gridToneLabels(),
            'registrationTypeLabels' => $this->registrationTypeLabels(),
            'displayTypeLabels' => $this->displayTypeLabels(),
            'expandTypeLabels' => $this->expandTypeLabels(),
            'scopeTypeLabels' => $this->limitedScopeTypeLabels(),
            'linkRows' => [['group' => '', 'label' => '', 'url' => '', 'created_at' => '']],
            'fileRows' => [['group' => '', 'label' => '', 'file_id' => '', 'original_name' => '', 'storage_path' => '', 'mime_type' => '', 'file_size' => 0, 'created_at' => '']],
            'todoRows' => [$this->emptyTodoRow()],
            'glossaryRows' => [$this->emptyGlossaryRow()],
            'contentText' => '',
        ]);
    }

    public function storeGrid(): void
    {
        verify_csrf();

        $grids = $this->store->all('grid_sections');
        $payload = $this->gridPayload();
        $payload = $this->forceCompanyAdminGridScope($payload);
        $payload = $this->forceStoreAdminGridScope($payload);
        $payload['column'] = 3;
        $payload['sort_order'] = $this->firstGridSortOrder($grids, 3, $payload);

        $this->store->save('grid_sections', $payload);
        redirect('admin.grids');
    }

    public function editGrid(): void
    {
        $grid = $this->store->find('grid_sections', (int) ($_GET['id'] ?? 0));
        if ($grid === null) {
            http_response_code(404);
            exit('Grid not found.');
        }
        $this->assertCanManageGrid($grid);

        View::render('admin/grid-form', [
            'mode' => 'edit',
            'grid' => $grid,
            'toneLabels' => $this->gridToneLabels(),
            'registrationTypeLabels' => $this->registrationTypeLabels(),
            'displayTypeLabels' => $this->displayTypeLabels(),
            'expandTypeLabels' => $this->expandTypeLabels(),
            'scopeTypeLabels' => $this->limitedScopeTypeLabels($grid),
            'linkRows' => $this->gridLinkRows($grid),
            'fileRows' => $this->gridFileRows($grid),
            'todoRows' => $this->gridTodoRows($grid),
            'glossaryRows' => $this->gridGlossaryRows($grid),
            'contentText' => $this->gridContentText($grid),
        ]);
    }

    public function updateGrid(): void
    {
        verify_csrf();

        $current = $this->store->find('grid_sections', (int) ($_POST['id'] ?? 0));
        if ($current === null) {
            http_response_code(404);
            exit('Grid not found.');
        }
        $this->assertCanManageGrid($current);

        $payload = $this->gridPayload((string) ($current['registration_type'] ?? 'links'));
        $payload = $this->forceCompanyAdminGridScope($payload);
        $payload = $this->forceStoreAdminGridScope($payload);
        $payload['registration_type'] = $current['registration_type'] ?? 'links';
        $payload['column'] = (int) ($current['column'] ?? 1);
        $payload['sort_order'] = (int) ($current['sort_order'] ?? 0);

        $this->store->save('grid_sections', array_merge($current, $payload));
        redirect('admin.grids');
    }

    public function deleteGrid(): void
    {
        verify_csrf();

        $gridId = (int) ($_POST['id'] ?? 0);
        $layoutScope = ($_POST['layout_scope'] ?? '') === 'store' ? 'store' : 'global';
        $storeId = max(0, (int) ($_POST['store_id'] ?? 0));
        $password = trim((string) ($_POST['grid_delete_password'] ?? ''));
        $redirectParams = $layoutScope === 'store' ? ['layout_scope' => 'store', 'store_id' => $storeId] : [];

        $settings = $this->portalSettingsRecord();
        $passwordHash = (string) ($settings['grid_delete_password_hash'] ?? '');
        if ($passwordHash === '' || $password === '' || !password_verify($password, $passwordHash)) {
            $_SESSION['flash'] = 'グリッド削除パスワードが正しくありません。';
            redirect('admin.grids', $redirectParams);
        }

        $grids = $this->store->all('grid_sections');
        $target = null;
        foreach ($grids as $grid) {
            if ((int) ($grid['id'] ?? 0) === $gridId) {
                $target = $grid;
                break;
            }
        }

        if ($target === null) {
            http_response_code(404);
            exit('Grid not found.');
        }
        $this->assertCanManageGrid($target);

        $this->deleteGridFiles($target);
        $grids = array_values(array_filter($grids, static fn (array $grid): bool => (int) ($grid['id'] ?? 0) !== $gridId));
        $this->writeGridSections($grids);

        $layouts = array_values(array_filter(
            $this->store->all('grid_layouts'),
            static fn (array $layout): bool => (int) ($layout['grid_id'] ?? 0) !== $gridId
        ));
        $this->writeDataFile('grid_layouts', $layouts);

        $_SESSION['flash'] = 'グリッドを削除しました。登録済みデータも削除されています。';
        redirect('admin.grids', $redirectParams);
    }

    public function moveGrid(): void
    {
        verify_csrf();

        $gridId = (int) ($_POST['id'] ?? 0);
        $direction = (string) ($_POST['direction'] ?? '');
        $layoutScope = ($_POST['layout_scope'] ?? '') === 'store' ? 'store' : 'global';
        $storeId = max(0, (int) ($_POST['store_id'] ?? 0));
        if ($this->isStoreAdmin()) {
            $layoutScope = 'store';
            $storeId = $this->currentStoreId();
        } elseif ($this->isCompanyAdmin()) {
            $layoutScope = 'global';
            $storeId = 0;
        }
        $grids = $this->store->all('grid_sections');

        if ($layoutScope === 'store') {
            $grids = $this->gridsForStoreLayout($grids, $storeId, $this->store->all('departments'));
            $grids = $this->applyGridLayouts($grids, 'store', $storeId);
        }
        if ($this->isStoreAdmin()) {
            $grids = $this->storeAdminManageableGrids($grids);
        }
        if ($this->isCompanyAdmin()) {
            $grids = $this->companyAdminManageableGrids($grids);
        }

        $currentIndex = $this->gridIndex($grids, $gridId);

        if ($currentIndex === null) {
            http_response_code(404);
            exit('Grid not found.');
        }

        if (in_array($direction, ['left', 'right', 'up', 'down', 'column_left', 'column_right'], true)) {
            $grids = $this->moveGridInLane($grids, $currentIndex, $direction);
            if ($layoutScope === 'store') {
                $this->writeGridLayoutsForStore($grids, $storeId);
            } else {
                $this->writeGridSections($grids);
            }
        }

        redirect('admin.grids', $layoutScope === 'store' ? ['layout_scope' => 'store', 'store_id' => $storeId] : []);
    }

    public function resetStoreLayout(): void
    {
        verify_csrf();

        $storeId = max(0, (int) ($_POST['store_id'] ?? 0));
        if ($this->isStoreAdmin()) {
            $storeId = $this->currentStoreId();
        }
        $layouts = array_values(array_filter(
            $this->store->all('grid_layouts'),
            fn (array $layout): bool => !(($layout['scope_type'] ?? '') === 'store' && (int) ($layout['scope_target_id'] ?? 0) === $storeId)
        ));

        $this->writeDataFile('grid_layouts', $layouts);
        redirect('admin.grids', ['layout_scope' => 'store', 'store_id' => $storeId]);
    }

    private function departmentsByType(array $departments, string $type, bool $activeOnly = true): array
    {
        $items = array_values(array_filter($departments, function (array $department) use ($type, $activeOnly): bool {
            $departmentType = $this->normalizeAffiliationType((string) ($department['level'] ?? 'level1'));
            $isActive = ($department['status'] ?? 'active') === 'active';
            return $departmentType === $type && (!$activeOnly || $isActive);
        }));

        usort($items, fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));
        return $items;
    }

    private function portalSettingsRecord(): array
    {
        return $this->store->find('portal_settings', 1) ?? [
            'id' => 1,
            'hero_message' => '',
            'new_entry_days' => 5,
            'completed_todo_delete_days' => 5,
            'grid_delete_password_hash' => '',
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

    private function isSystemAdmin(): bool
    {
        return $this->auth->hasRole('system_admin');
    }

    private function isStoreAdmin(): bool
    {
        return $this->auth->hasRole('store_admin');
    }

    private function isCompanyAdmin(): bool
    {
        return $this->auth->hasRole('company_admin');
    }

    private function currentCompanyId(): int
    {
        $user = $this->auth->user();
        return max(0, (int) ($user['department1_id'] ?? 0));
    }

    private function currentCompanyName(): string
    {
        $companyId = $this->currentCompanyId();
        if ($companyId === 0) {
            return '';
        }

        return $this->departmentNames($this->store->all('departments'))[$companyId] ?? '';
    }

    private function currentStoreId(): int
    {
        $user = $this->auth->user();
        return max(0, (int) ($user['department2_id'] ?? $user['store_id'] ?? 0));
    }

    private function currentStoreName(): string
    {
        $storeId = $this->currentStoreId();
        if ($storeId === 0) {
            return '';
        }

        return $this->departmentNames($this->store->all('departments'))[$storeId] ?? '';
    }

    private function assertCanManageGrid(array $grid): void
    {
        if ($this->isSystemAdmin()) {
            return;
        }

        if ($this->isCompanyAdmin()) {
            if (($grid['scope_type'] ?? '') !== 'company') {
                http_response_code(403);
                exit('Company admins can manage only company shared grids.');
            }

            $target = trim((string) ($grid['scope_target'] ?? ''));
            if ($target !== '' && $target !== $this->currentCompanyName()) {
                http_response_code(403);
                exit('Company admins can manage only their own company grids.');
            }

            return;
        }

        if (!$this->isStoreAdmin()) {
            http_response_code(403);
            exit('Forbidden.');
        }

        if (($grid['scope_type'] ?? '') !== 'store') {
            http_response_code(403);
            exit('Store admins can manage only store dedicated grids.');
        }

        $target = trim((string) ($grid['scope_target'] ?? ''));
        if ($target !== '' && $target !== $this->currentStoreName()) {
            http_response_code(403);
            exit('Store admins can manage only their own store grids.');
        }
    }

    private function forceStoreAdminGridScope(array $payload): array
    {
        if (!$this->isStoreAdmin()) {
            return $payload;
        }

        $payload['scope_type'] = 'store';
        $payload['scope_target'] = $this->currentStoreName();
        return $payload;
    }

    private function forceCompanyAdminGridScope(array $payload): array
    {
        if (!$this->isCompanyAdmin()) {
            return $payload;
        }

        $payload['scope_type'] = 'company';
        $payload['scope_target'] = $this->currentCompanyName();
        return $payload;
    }

    private function systemAdminManageableGrids(array $grids): array
    {
        return array_values(array_filter($grids, static fn (array $grid): bool => ($grid['scope_type'] ?? 'all') !== 'store'));
    }

    private function companyAdminManageableGrids(array $grids): array
    {
        if (!$this->isCompanyAdmin()) {
            return $grids;
        }

        $companyName = $this->currentCompanyName();
        return array_values(array_filter($grids, static function (array $grid) use ($companyName): bool {
            if (($grid['scope_type'] ?? '') !== 'company') {
                return false;
            }

            $target = trim((string) ($grid['scope_target'] ?? ''));
            return $target === '' || $target === $companyName;
        }));
    }

    private function systemAdminScopeTypeLabels(?array $grid = null): array
    {
        $labels = $this->scopeTypeLabels();
        if (($grid['scope_type'] ?? '') !== 'store') {
            unset($labels['store']);
        }

        return $labels;
    }

    private function limitedScopeTypeLabels(?array $grid = null): array
    {
        if ($this->isStoreAdmin()) {
            return ['store' => '店舗専用'];
        }
        if ($this->isCompanyAdmin()) {
            return ['company' => '会社共通'];
        }

        return $this->systemAdminScopeTypeLabels($grid);
    }

    private function storeAdminManageableGrids(array $grids): array
    {
        if (!$this->isStoreAdmin()) {
            return $grids;
        }

        $storeName = $this->currentStoreName();
        return array_values(array_filter($grids, static function (array $grid) use ($storeName): bool {
            if (($grid['scope_type'] ?? '') !== 'store') {
                return false;
            }

            $target = trim((string) ($grid['scope_target'] ?? ''));
            return $target === '' || $target === $storeName;
        }));
    }

    private function gridIndex(array $grids, int $gridId): ?int
    {
        foreach ($grids as $index => $grid) {
            if ((int) ($grid['id'] ?? 0) === $gridId) {
                return $index;
            }
        }

        return null;
    }

    private function gridsForStoreLayout(array $grids, int $storeId, array $departments): array
    {
        $departmentNames = $this->departmentNames($departments);
        $storeName = $departmentNames[$storeId] ?? '';
        $companyName = $departmentNames[$this->companyIdForStore($storeId, $departments)] ?? '';

        return array_values(array_filter($grids, function (array $grid) use ($storeName, $companyName): bool {
            $scope = (string) ($grid['scope_type'] ?? 'all');
            if ($scope === 'all') {
                return true;
            }
            if ($scope === 'company') {
                $target = trim((string) ($grid['scope_target'] ?? ''));
                return $target === '' || $target === $companyName;
            }
            if ($scope === 'store_shared') {
                return true;
            }

            if ($scope !== 'store') {
                return false;
            }

            $target = trim((string) ($grid['scope_target'] ?? ''));
            return $target === '' || $target === $storeName;
        }));
    }

    private function applyGridLayouts(array $grids, string $scopeType, int $scopeTargetId): array
    {
        $layouts = [];
        foreach ($this->store->all('grid_layouts') as $layout) {
            if (($layout['scope_type'] ?? '') !== $scopeType || (int) ($layout['scope_target_id'] ?? 0) !== $scopeTargetId) {
                continue;
            }

            $layouts[(int) ($layout['grid_id'] ?? 0)] = $layout;
        }

        foreach ($grids as &$grid) {
            $gridId = (int) ($grid['id'] ?? 0);
            if (!isset($layouts[$gridId])) {
                continue;
            }

            $grid['column'] = min(3, max(1, (int) ($layouts[$gridId]['column'] ?? $grid['column'] ?? 1)));
            $grid['sort_order'] = (int) ($layouts[$gridId]['sort_order'] ?? $grid['sort_order'] ?? 0);
        }
        unset($grid);

        return $grids;
    }

    private function writeGridLayoutsForStore(array $grids, int $storeId): void
    {
        $layouts = array_values(array_filter(
            $this->store->all('grid_layouts'),
            fn (array $layout): bool => !(($layout['scope_type'] ?? '') === 'store' && (int) ($layout['scope_target_id'] ?? 0) === $storeId)
        ));

        $nextId = $this->nextRecordId($layouts);
        $now = date('Y-m-d H:i:s');
        foreach ($grids as $grid) {
            $layouts[] = [
                'id' => $nextId++,
                'scope_type' => 'store',
                'scope_target_id' => $storeId,
                'grid_id' => (int) ($grid['id'] ?? 0),
                'column' => min(3, max(1, (int) ($grid['column'] ?? 1))),
                'sort_order' => (int) ($grid['sort_order'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->writeDataFile('grid_layouts', $layouts);
    }

    private function nextRecordId(array $records): int
    {
        $ids = array_map(fn (array $record): int => (int) ($record['id'] ?? 0), $records);
        return $ids === [] ? 1 : max($ids) + 1;
    }

    private function neighborGridIndex(array $grids, array $current, string $direction): ?int
    {
        $currentColumn = min(3, max(1, (int) ($current['column'] ?? 1)));
        $currentSortOrder = (int) ($current['sort_order'] ?? 0);
        $neighbors = [];

        foreach ($grids as $index => $grid) {
            if ((int) ($grid['id'] ?? 0) === (int) ($current['id'] ?? 0)) {
                continue;
            }

            if ((int) ($grid['column'] ?? 1) !== $currentColumn) {
                continue;
            }

            if ($this->gridStatusLane($grid) !== $this->gridStatusLane($current)) {
                continue;
            }

            $sortOrder = (int) ($grid['sort_order'] ?? 0);
            if ($direction === 'up' && $sortOrder < $currentSortOrder) {
                $neighbors[$index] = $sortOrder;
            }

            if ($direction === 'down' && $sortOrder > $currentSortOrder) {
                $neighbors[$index] = $sortOrder;
            }
        }

        if ($neighbors === []) {
            return null;
        }

        $direction === 'up' ? arsort($neighbors) : asort($neighbors);
        return (int) array_key_first($neighbors);
    }

    private function moveGridInLane(array $grids, int $currentIndex, string $direction): array
    {
        $current = $grids[$currentIndex];
        $lane = $this->gridStatusLane($current);
        $area = $this->gridLayoutArea($current);
        $columns = [1 => [], 2 => [], 3 => []];

        foreach ($grids as $index => $grid) {
            if ($this->gridStatusLane($grid) !== $lane) {
                continue;
            }

            if ($this->gridLayoutArea($grid) !== $area) {
                continue;
            }

            $column = min(3, max(1, (int) ($grid['column'] ?? 1)));
            $columns[$column][] = $index;
        }

        foreach ($columns as &$columnIndexes) {
            usort($columnIndexes, function (int $a, int $b) use ($grids): int {
                return [
                    (int) ($grids[$a]['sort_order'] ?? 0),
                    (int) ($grids[$a]['id'] ?? 0),
                ] <=> [
                    (int) ($grids[$b]['sort_order'] ?? 0),
                    (int) ($grids[$b]['id'] ?? 0),
                ];
            });
        }
        unset($columnIndexes);

        $currentColumn = min(3, max(1, (int) ($current['column'] ?? 1)));
        $currentPosition = array_search($currentIndex, $columns[$currentColumn], true);
        if ($currentPosition === false) {
            return $grids;
        }

        if ($direction === 'column_left' || $direction === 'column_right') {
            $targetColumn = $direction === 'column_left' ? $currentColumn - 1 : $currentColumn + 1;
            if ($targetColumn < 1 || $targetColumn > 3) {
                return $grids;
            }

            array_splice($columns[$currentColumn], $currentPosition, 1);
            array_unshift($columns[$targetColumn], $currentIndex);

            return $this->applyGridColumnOrder($grids, $columns, [$currentColumn, $targetColumn]);
        }

        if ($direction === 'up' || $direction === 'down') {
            $targetPosition = $direction === 'up' ? $currentPosition - 1 : $currentPosition + 1;
            if (!isset($columns[$currentColumn][$targetPosition])) {
                return $grids;
            }

            $columns[$currentColumn][$currentPosition] = $columns[$currentColumn][$targetPosition];
            $columns[$currentColumn][$targetPosition] = $currentIndex;
            return $this->applyGridColumnOrder($grids, $columns, [$currentColumn]);
        }

        $targetColumn = $direction === 'left' ? $currentColumn - 1 : $currentColumn + 1;
        if ($targetColumn < 1 || $targetColumn > 3) {
            return $grids;
        }

        if (!isset($columns[$targetColumn][$currentPosition])) {
            return $grids;
        }

        $targetIndex = $columns[$targetColumn][$currentPosition];
        $columns[$currentColumn][$currentPosition] = $targetIndex;
        $columns[$targetColumn][$currentPosition] = $currentIndex;

        return $this->applyGridColumnOrder($grids, $columns, [$currentColumn, $targetColumn]);
    }

    private function applyGridColumnOrder(array $grids, array $columns, array $targetColumns): array
    {
        foreach (array_unique($targetColumns) as $column) {
            foreach (array_values($columns[$column]) as $position => $gridIndex) {
                $grids[$gridIndex]['column'] = $column;
                $grids[$gridIndex]['sort_order'] = ($position + 1) * 10;
            }
        }

        return $grids;
    }

    private function nextGridSortOrder(array $grids, int $column, array $current): int
    {
        $max = 0;
        foreach ($grids as $grid) {
            if ((int) ($grid['column'] ?? 1) === $column) {
                if ($this->gridStatusLane($grid) !== $this->gridStatusLane($current)) {
                    continue;
                }

                if ($this->gridLayoutArea($grid) !== $this->gridLayoutArea($current)) {
                    continue;
                }

                $max = max($max, (int) ($grid['sort_order'] ?? 0));
            }
        }

        return $max + 10;
    }

    private function firstGridSortOrder(array $grids, int $column, array $current): int
    {
        $sortOrders = [];
        foreach ($grids as $grid) {
            if ((int) ($grid['column'] ?? 1) !== $column) {
                continue;
            }

            if ($this->gridStatusLane($grid) !== $this->gridStatusLane($current)) {
                continue;
            }

            if ($this->gridLayoutArea($grid) !== $this->gridLayoutArea($current)) {
                continue;
            }

            $sortOrders[] = (int) ($grid['sort_order'] ?? 0);
        }

        if ($sortOrders === []) {
            return 10;
        }

        return min($sortOrders) - 10;
    }

    private function gridStatusLane(array $grid): string
    {
        return ($grid['status'] ?? '') === 'published' ? 'published' : 'private';
    }

    private function gridLayoutArea(array $grid): string
    {
        $scope = (string) ($grid['scope_type'] ?? 'all');
        if ($scope === 'company') {
            return 'company';
        }
        if ($scope === 'store_shared') {
            return 'store_shared';
        }
        if ($scope === 'store') {
            return 'store';
        }

        return 'common';
    }

    private function gridLayoutAreaSort(array $grid): int
    {
        $area = $this->gridLayoutArea($grid);
        if ($area === 'common') {
            return 1;
        }
        if ($area === 'company') {
            return 2;
        }
        if ($area === 'store_shared') {
            return 3;
        }

        return 4;
    }

    private function writeGridSections(array $grids): void
    {
        $this->writeDataFile('grid_sections', $grids);
    }

    private function writeDataFile(string $name, array $records): void
    {
        $path = BASE_PATH . '/storage/data/' . $name . '.json';
        file_put_contents($path, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function deleteGridFiles(array $grid): void
    {
        $baseDirectory = realpath(BASE_PATH . '/storage/private/grids');
        if ($baseDirectory === false) {
            return;
        }

        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $storagePath = trim((string) ($entry['storage_path'] ?? ''));
                if ($storagePath === '') {
                    continue;
                }

                $path = realpath(BASE_PATH . '/' . ltrim($storagePath, '/\\'));
                if ($path === false || !is_file($path)) {
                    continue;
                }

                if ($path === $baseDirectory || !str_starts_with($path, $baseDirectory . DIRECTORY_SEPARATOR)) {
                    continue;
                }

                unlink($path);
            }
        }
    }

    private function departmentNames(array $departments): array
    {
        $names = [];
        foreach ($departments as $department) {
            $names[(int) $department['id']] = $department['name'];
        }

        return $names;
    }

    private function companyIdForStore(int $storeId, array $departments): int
    {
        foreach ($departments as $department) {
            if ((int) ($department['id'] ?? 0) === $storeId) {
                return max(0, (int) ($department['parent_id'] ?? 0));
            }
        }

        return 0;
    }

    private function userPayload(): array
    {
        $storeId = (int) ($_POST['department2_id'] ?? 0);
        $companyId = (int) ($_POST['department1_id'] ?? 0);
        if ($companyId === 0 && $storeId > 0) {
            $companyId = $this->companyIdForStore($storeId, $this->store->all('departments'));
        }

        return [
            'id' => (int) ($_POST['id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => trim($_POST['role'] ?? 'store_user'),
            'department1_id' => $companyId,
            'department2_id' => $storeId,
            'status' => trim($_POST['status'] ?? 'active'),
        ];
    }

    private function affiliationPayload(string $type): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'level' => $type,
            'parent_id' => $type === 'store' ? (int) ($_POST['parent_id'] ?? 0) : 0,
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'status' => trim($_POST['status'] ?? 'active'),
        ];
    }

    private function qrCodePayload(?array $current = null): array
    {
        $payload = [
            'id' => (int) ($_POST['id'] ?? 0),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'status' => trim((string) ($_POST['status'] ?? 'active')) === 'inactive' ? 'inactive' : 'active',
        ];

        if (!empty($_FILES['qr_image']['name'])) {
            $payload['image_path'] = $this->saveQrCodeImage($_FILES['qr_image']);
        } elseif ($current !== null && !empty($current['image_path'])) {
            $payload['image_path'] = (string) $current['image_path'];
        }

        if (!empty($payload['image_path'])) {
            $payload['url'] = asset_url((string) $payload['image_path']);
        }

        return $payload;
    }

    private function saveQrCodeImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('QRコード画像のアップロードに失敗しました。');
        }

        if ((int) ($file['size'] ?? 0) > 5242880) {
            throw new \RuntimeException('QRコード画像は 5MB までアップロードできます。');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('QRコード画像は jpg, png, gif, webp のみ対応しています。');
        }

        $directory = BASE_PATH . '/public_html/uploads/qr-codes';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $storedName = bin2hex(random_bytes(12)) . '.' . $extension;
        $targetPath = $directory . '/' . $storedName;
        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
            throw new \RuntimeException('QRコード画像を保存できませんでした。');
        }

        return 'uploads/qr-codes/' . $storedName;
    }

    private function qrCodeRows(): array
    {
        return array_map(fn (array $qrCode): array => $this->withQrCodeUrl($qrCode), $this->store->all('qr_codes'));
    }

    private function withQrCodeUrl(array $qrCode): array
    {
        if (!empty($qrCode['image_path'])) {
            $qrCode['generated_url'] = asset_url((string) $qrCode['image_path']);
        } else {
            $qrCode['generated_url'] = (string) ($qrCode['url'] ?? '');
        }

        return $qrCode;
    }

    private function saveCompanyLogo(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('会社ロゴのアップロードに失敗しました。');
        }

        if ((int) ($file['size'] ?? 0) > 5242880) {
            throw new \RuntimeException('会社ロゴは 5MB までアップロードできます。');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('会社ロゴは jpg, png, gif, webp のみ対応しています。');
        }

        $directory = BASE_PATH . '/public_html/uploads/company-logos';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $storedName = bin2hex(random_bytes(12)) . '.' . $extension;
        $targetPath = $directory . '/' . $storedName;
        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
            throw new \RuntimeException('会社ロゴを保存できませんでした。');
        }

        return 'uploads/company-logos/' . $storedName;
    }

    private function gridPayload(?string $lockedRegistrationType = null): array
    {
        $registrationType = $lockedRegistrationType ?? trim($_POST['registration_type'] ?? 'links');
        $scopeType = $this->normalizeGridScopeType(trim($_POST['scope_type'] ?? 'all'));
        $scopeTarget = in_array($scopeType, ['company', 'store'], true) ? trim($_POST['scope_target'] ?? '') : '';
        $displayType = in_array($registrationType, ['manual', 'todo', 'glossary'], true)
            ? 'list'
            : trim($_POST['display_type'] ?? 'list');
        if (!in_array($displayType, ['list', 'grouped'], true)) {
            $displayType = 'list';
        }
        $expandType = trim($_POST['expand_type'] ?? 'open');
        if (!in_array($expandType, ['open', 'collapsed'], true)) {
            $expandType = 'open';
        }
        $postPermission = trim($_POST['post_permission'] ?? 'allowed');
        if (!in_array($postPermission, ['allowed', 'denied'], true)) {
            $postPermission = 'allowed';
        }

        return [
            'id' => (int) ($_POST['id'] ?? 0),
            'title' => trim($_POST['title'] ?? ''),
            'tone' => trim($_POST['tone'] ?? 'green'),
            'column' => min(3, max(1, (int) ($_POST['column'] ?? 1))),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'scope_type' => $scopeType,
            'scope_target' => $scopeTarget,
            'registration_type' => $registrationType,
            'display_type' => $displayType,
            'expand_type' => $expandType,
            'post_permission' => $postPermission,
            'status' => trim($_POST['status'] ?? 'draft'),
            'groups' => $this->parseGridGroups($registrationType),
        ];
    }

    private function normalizeGridScopeType(string $scopeType): string
    {
        return in_array($scopeType, ['all', 'company', 'store_shared', 'store'], true) ? $scopeType : 'all';
    }

    private function parseGridGroups(string $registrationType): array
    {
        if ($registrationType === 'links') {
            return $this->parseLinkRows();
        }

        if ($registrationType === 'files') {
            return $this->parseFileRows();
        }

        if ($registrationType === 'todo') {
            return $this->parseTodoRows();
        }

        if ($registrationType === 'glossary') {
            return $this->parseGlossaryRows();
        }

        return $this->parseGridContent((string) ($_POST['content'] ?? ''));
    }

    private function parseLinkRows(): array
    {
        $groups = [];
        $groupNames = $_POST['link_group'] ?? [];
        $labels = $_POST['link_label'] ?? [];
        $urls = $_POST['link_url'] ?? [];
        $createdAtValues = $_POST['link_created_at'] ?? [];
        $now = date('Y-m-d H:i:s');

        foreach ($labels as $index => $rawLabel) {
            $label = trim((string) $rawLabel);
            $url = trim((string) ($urls[$index] ?? ''));
            $groupLabel = trim((string) ($groupNames[$index] ?? ''));

            if ($label === '' && $url === '') {
                continue;
            }

            if ($label === '') {
                $label = $url;
            }

            if ($url === '') {
                $url = '#';
            }

            $groupKey = $groupLabel !== '' ? $groupLabel : 'default';
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'label' => $groupLabel,
                    'entries' => [],
                ];
            }

            $groups[$groupKey]['entries'][] = [
                'label' => $label,
                'url' => $url,
                'created_at' => trim((string) ($createdAtValues[$index] ?? '')) ?: $now,
            ];
        }

        return array_values($groups);
    }

    private function parseGridContent(string $content): array
    {
        $groups = [];
        $defaultGroup = 'default';

        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));

            if (count($parts) === 1) {
                [$label, $url, $groupLabel] = [$parts[0], '#', ''];
            } elseif (count($parts) === 2) {
                [$label, $url, $groupLabel] = [$parts[0], $parts[1] !== '' ? $parts[1] : '#', ''];
            } else {
                [$groupLabel, $label, $url] = [$parts[0], $parts[1], $parts[2] !== '' ? $parts[2] : '#'];
            }

            $groupKey = $groupLabel !== '' ? $groupLabel : $defaultGroup;
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'label' => $groupLabel,
                    'entries' => [],
                ];
            }

            $groups[$groupKey]['entries'][] = [
                'label' => $label,
                'url' => $url,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        return array_values($groups);
    }

    private function parseFileRows(): array
    {
        $groups = [];
        $groupNames = $_POST['file_group'] ?? [];
        $labels = $_POST['file_label'] ?? [];
        $existingIds = $_POST['existing_file_id'] ?? [];
        $existingOriginalNames = $_POST['existing_original_name'] ?? [];
        $existingStoragePaths = $_POST['existing_storage_path'] ?? [];
        $existingMimeTypes = $_POST['existing_mime_type'] ?? [];
        $existingFileSizes = $_POST['existing_file_size'] ?? [];
        $createdAtValues = $_POST['file_created_at'] ?? [];
        $uploads = $this->normalizeUploadedFiles($_FILES['grid_files'] ?? []);
        $now = date('Y-m-d H:i:s');

        foreach ($labels as $index => $rawLabel) {
            $label = trim((string) $rawLabel);
            $groupLabel = trim((string) ($groupNames[$index] ?? ''));
            $upload = $uploads[$index] ?? null;
            $hasUpload = $upload !== null && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $groupKey = $groupLabel !== '' ? $groupLabel : 'default';

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'label' => $groupLabel,
                    'entries' => [],
                ];
            }

            if ($hasUpload) {
                $saved = $this->saveGridFile($upload);
                $groups[$groupKey]['entries'][] = array_merge([
                    'label' => $label !== '' ? $label : $saved['original_name'],
                    'url' => '#',
                    'created_at' => trim((string) ($createdAtValues[$index] ?? '')) ?: $now,
                ], $saved);
                continue;
            }

            $storagePath = trim((string) ($existingStoragePaths[$index] ?? ''));
            if ($storagePath === '' && $label === '') {
                continue;
            }

            if ($storagePath !== '') {
                $groups[$groupKey]['entries'][] = [
                    'label' => $label !== '' ? $label : (string) ($existingOriginalNames[$index] ?? ''),
                    'url' => '#',
                    'file_id' => (string) ($existingIds[$index] ?? ''),
                    'original_name' => (string) ($existingOriginalNames[$index] ?? ''),
                    'storage_path' => $storagePath,
                    'mime_type' => (string) ($existingMimeTypes[$index] ?? 'application/octet-stream'),
                    'file_size' => (int) ($existingFileSizes[$index] ?? 0),
                    'created_at' => trim((string) ($createdAtValues[$index] ?? '')) ?: $now,
                ];
            }
        }

        return array_values(array_filter($groups, fn (array $group): bool => ($group['entries'] ?? []) !== []));
    }

    private function parseTodoRows(): array
    {
        $entries = [];
        $field1Values = $_POST['todo_field1'] ?? [];
        $field2Values = $_POST['todo_field2'] ?? [];
        $progressValues = $_POST['todo_progress'] ?? [];
        $existingIds = $_POST['existing_todo_file_id'] ?? [];
        $existingOriginalNames = $_POST['existing_todo_original_name'] ?? [];
        $existingStoragePaths = $_POST['existing_todo_storage_path'] ?? [];
        $existingMimeTypes = $_POST['existing_todo_mime_type'] ?? [];
        $existingFileSizes = $_POST['existing_todo_file_size'] ?? [];
        $createdAtValues = $_POST['todo_created_at'] ?? [];
        $completedAtValues = $_POST['todo_completed_at'] ?? [];
        $uploads = $this->normalizeUploadedFiles($_FILES['todo_images'] ?? []);
        $allowedProgress = ['not_started', 'in_progress', 'done'];
        $now = date('Y-m-d H:i:s');

        foreach ($field1Values as $index => $rawField1) {
            $field1 = trim((string) $rawField1);
            $field2 = trim((string) ($field2Values[$index] ?? ''));
            $progress = trim((string) ($progressValues[$index] ?? 'not_started'));
            if (!in_array($progress, $allowedProgress, true)) {
                $progress = 'not_started';
            }

            $entry = [
                'label' => $field1,
                'url' => '#',
                'description' => $field2,
                'progress' => $progress,
                'created_at' => trim((string) ($createdAtValues[$index] ?? '')) ?: $now,
            ];
            if ($progress === 'done') {
                $entry['completed_at'] = trim((string) ($completedAtValues[$index] ?? '')) ?: $now;
            }

            $upload = $uploads[$index] ?? null;
            $hasUpload = $upload !== null && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hasUpload) {
                $entry = array_merge($entry, $this->saveGridImage($upload));
            } else {
                $storagePath = trim((string) ($existingStoragePaths[$index] ?? ''));
                if ($storagePath !== '') {
                    $entry = array_merge($entry, [
                        'file_id' => (string) ($existingIds[$index] ?? ''),
                        'original_name' => (string) ($existingOriginalNames[$index] ?? ''),
                        'storage_path' => $storagePath,
                        'mime_type' => (string) ($existingMimeTypes[$index] ?? 'application/octet-stream'),
                        'file_size' => (int) ($existingFileSizes[$index] ?? 0),
                    ]);
                }
            }

            if ($field1 === '' && $field2 === '' && !isset($entry['file_id'])) {
                continue;
            }

            $entries[] = $entry;
        }

        if ($entries === []) {
            return [];
        }

        return [[
            'label' => '',
            'entries' => $entries,
        ]];
    }

    private function parseGlossaryRows(): array
    {
        $entries = [];
        $termValues = $_POST['glossary_term'] ?? [];
        $descriptionValues = $_POST['glossary_description'] ?? [];
        $existingIds = $_POST['existing_glossary_file_id'] ?? [];
        $existingOriginalNames = $_POST['existing_glossary_original_name'] ?? [];
        $existingStoragePaths = $_POST['existing_glossary_storage_path'] ?? [];
        $existingMimeTypes = $_POST['existing_glossary_mime_type'] ?? [];
        $existingFileSizes = $_POST['existing_glossary_file_size'] ?? [];
        $createdAtValues = $_POST['glossary_created_at'] ?? [];
        $uploads = $this->normalizeUploadedFiles($_FILES['glossary_images'] ?? []);
        $now = date('Y-m-d H:i:s');

        foreach ($termValues as $index => $rawTerm) {
            $term = trim((string) $rawTerm);
            $description = trim((string) ($descriptionValues[$index] ?? ''));

            $entry = [
                'label' => $term,
                'url' => '#',
                'description' => $description,
                'created_at' => trim((string) ($createdAtValues[$index] ?? '')) ?: $now,
            ];

            $upload = $uploads[$index] ?? null;
            $hasUpload = $upload !== null && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($hasUpload) {
                $entry = array_merge($entry, $this->saveGridImage($upload));
            } else {
                $storagePath = trim((string) ($existingStoragePaths[$index] ?? ''));
                if ($storagePath !== '') {
                    $entry = array_merge($entry, [
                        'file_id' => (string) ($existingIds[$index] ?? ''),
                        'original_name' => (string) ($existingOriginalNames[$index] ?? ''),
                        'storage_path' => $storagePath,
                        'mime_type' => (string) ($existingMimeTypes[$index] ?? 'application/octet-stream'),
                        'file_size' => (int) ($existingFileSizes[$index] ?? 0),
                    ]);
                }
            }

            if ($term === '' && $description === '' && !isset($entry['file_id'])) {
                continue;
            }

            $entries[] = $entry;
        }

        if ($entries === []) {
            return [];
        }

        return [[
            'label' => '',
            'entries' => $entries,
        ]];
    }

    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach (($files['name'] ?? []) as $index => $name) {
            $normalized[$index] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
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

        $sourcePath = (string) ($file['tmp_name'] ?? '');
        $sourceData = file_get_contents($sourcePath);
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

    private function gridLinkRows(?array $grid): array
    {
        if ($grid === null) {
            return [['group' => '', 'label' => '', 'url' => '', 'created_at' => '']];
        }

        $rows = [];
        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $rows[] = [
                    'group' => (string) ($group['label'] ?? ''),
                    'label' => (string) ($entry['label'] ?? ''),
                    'url' => (string) ($entry['url'] ?? ''),
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                ];
            }
        }

        return $rows !== [] ? $rows : [['group' => '', 'label' => '', 'url' => '', 'created_at' => '']];
    }

    private function gridFileRows(?array $grid): array
    {
        if ($grid === null) {
            return [['group' => '', 'label' => '', 'file_id' => '', 'original_name' => '', 'storage_path' => '', 'mime_type' => '', 'file_size' => 0, 'created_at' => '']];
        }

        $rows = [];
        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $rows[] = [
                    'group' => (string) ($group['label'] ?? ''),
                    'label' => (string) ($entry['label'] ?? ''),
                    'file_id' => (string) ($entry['file_id'] ?? ''),
                    'original_name' => (string) ($entry['original_name'] ?? ''),
                    'storage_path' => (string) ($entry['storage_path'] ?? ''),
                    'mime_type' => (string) ($entry['mime_type'] ?? ''),
                    'file_size' => (int) ($entry['file_size'] ?? 0),
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                    'completed_at' => (string) ($entry['completed_at'] ?? ''),
                ];
            }
        }

        return $rows !== [] ? $rows : [['group' => '', 'label' => '', 'file_id' => '', 'original_name' => '', 'storage_path' => '', 'mime_type' => '', 'file_size' => 0, 'created_at' => '']];
    }

    private function gridTodoRows(?array $grid): array
    {
        if ($grid === null) {
            return [$this->emptyTodoRow()];
        }

        $rows = [];
        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $rows[] = [
                    'field1' => (string) ($entry['label'] ?? ''),
                    'field2' => (string) ($entry['description'] ?? ''),
                    'progress' => (string) ($entry['progress'] ?? 'not_started'),
                    'file_id' => (string) ($entry['file_id'] ?? ''),
                    'original_name' => (string) ($entry['original_name'] ?? ''),
                    'storage_path' => (string) ($entry['storage_path'] ?? ''),
                    'mime_type' => (string) ($entry['mime_type'] ?? ''),
                    'file_size' => (int) ($entry['file_size'] ?? 0),
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                ];
            }
        }

        return $rows !== [] ? $rows : [$this->emptyTodoRow()];
    }

    private function emptyTodoRow(): array
    {
        return [
            'field1' => '',
            'field2' => '',
            'progress' => 'not_started',
            'file_id' => '',
            'original_name' => '',
            'storage_path' => '',
            'mime_type' => '',
            'file_size' => 0,
            'created_at' => '',
            'completed_at' => '',
        ];
    }

    private function gridGlossaryRows(?array $grid): array
    {
        if ($grid === null) {
            return [$this->emptyGlossaryRow()];
        }

        $rows = [];
        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $rows[] = [
                    'term' => (string) ($entry['label'] ?? ''),
                    'description' => (string) ($entry['description'] ?? ''),
                    'file_id' => (string) ($entry['file_id'] ?? ''),
                    'original_name' => (string) ($entry['original_name'] ?? ''),
                    'storage_path' => (string) ($entry['storage_path'] ?? ''),
                    'mime_type' => (string) ($entry['mime_type'] ?? ''),
                    'file_size' => (int) ($entry['file_size'] ?? 0),
                    'created_at' => (string) ($entry['created_at'] ?? ''),
                ];
            }
        }

        return $rows !== [] ? $rows : [$this->emptyGlossaryRow()];
    }

    private function emptyGlossaryRow(): array
    {
        return [
            'term' => '',
            'description' => '',
            'file_id' => '',
            'original_name' => '',
            'storage_path' => '',
            'mime_type' => '',
            'file_size' => 0,
            'created_at' => '',
        ];
    }

    private function gridContentText(array $grid): string
    {
        $lines = [];
        foreach (($grid['groups'] ?? []) as $group) {
            foreach (($group['entries'] ?? []) as $entry) {
                $groupLabel = (string) ($group['label'] ?? '');
                $label = (string) ($entry['label'] ?? '');
                $url = (string) ($entry['url'] ?? '#');
                $lines[] = $groupLabel !== '' ? "{$groupLabel} | {$label} | {$url}" : "{$label} | {$url}";
            }
        }

        return implode("\n", $lines);
    }

    private function gridToneLabels(): array
    {
        return [
            'yellow' => '黄',
            'green' => '緑',
            'cyan' => '水色',
            'gold' => '金',
            'orange' => '橙',
            'pink' => '桃',
            'purple' => '紫',
            'lime' => '黄緑',
            'gray' => '灰',
            'teal' => '青緑',
            'rose' => 'ローズ',
            'blue' => '青',
            'indigo' => '藍',
            'stone' => '薄灰',
        ];
    }

    private function registrationTypeLabels(): array
    {
        return [
            'links' => 'リンク集',
            'files' => 'ファイル登録',
            'manual' => '手入力',
            'todo' => 'TO DO',
            'glossary' => '業界用語集',
        ];
    }

    private function displayTypeLabels(): array
    {
        return [
            'list' => '一覧表示',
            'grouped' => 'グループ表示',
        ];
    }

    private function expandTypeLabels(): array
    {
        return [
            'open' => '通常表示',
            'collapsed' => '初期折りたたみ',
        ];
    }

    private function scopeTypeLabels(): array
    {
        return [
            'all' => '共通',
            'company' => '会社共通',
            'store_shared' => '店舗共通',
            'store' => '店舗専用',
        ];
    }

    private function normalizeAffiliationType(string $level): string
    {
        if (in_array($level, ['company', 'level1', 'department'], true)) {
            return 'company';
        }

        return in_array($level, ['store', 'level2'], true) ? 'store' : 'company';
    }
}
