<?php

declare(strict_types=1);

namespace App\Platform\Storage;

final class JsonStore
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = BASE_PATH . '/storage/data';
        $this->ensureSeedData();
    }

    public function all(string $name): array
    {
        $path = $this->path($name);
        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        return json_decode($json ?: '[]', true) ?: [];
    }

    public function find(string $name, int $id): ?array
    {
        foreach ($this->all($name) as $record) {
            if ((int) ($record['id'] ?? 0) === $id) {
                return $record;
            }
        }

        return null;
    }

    public function save(string $name, array $record): array
    {
        $records = $this->all($name);
        $now = date('Y-m-d H:i:s');

        if (empty($record['id'])) {
            $record['id'] = $this->nextId($records);
            $record['created_at'] = $now;
        }

        $record['updated_at'] = $now;
        $found = false;

        foreach ($records as $index => $current) {
            if ((int) ($current['id'] ?? 0) === (int) $record['id']) {
                $records[$index] = array_merge($current, $record);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $records[] = $record;
        }

        $this->write($name, $records);
        return $record;
    }

    private function write(string $name, array $records): void
    {
        file_put_contents(
            $this->path($name),
            json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function path(string $name): string
    {
        return $this->basePath . '/' . $name . '.json';
    }

    private function nextId(array $records): int
    {
        $ids = array_map(fn (array $record): int => (int) ($record['id'] ?? 0), $records);
        return $ids === [] ? 1 : max($ids) + 1;
    }

    private function ensureSeedData(): void
    {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0775, true);
        }

        $seedFiles = [
            'roles' => [
                ['id' => 1, 'name' => '全体管理者', 'key' => 'system_admin', 'description' => '共通グリッド、会社マスタ、店舗マスタ、アカウント、ポータル設定を管理します。'],
                ['id' => 2, 'name' => 'FC法人管理者', 'key' => 'company_admin', 'description' => '自社に紐づく会社共通グリッドと店舗運用を管理します。'],
                ['id' => 3, 'name' => '店舗管理者', 'key' => 'store_admin', 'description' => '自店舗の店舗専用グリッドと店舗別レイアウトを管理します。'],
                ['id' => 4, 'name' => '店舗アカウント', 'key' => 'store_user', 'description' => 'ポータル閲覧、投稿、TO DOステータス変更を行います。'],
            ],
            'departments' => [
                ['id' => 100, 'name' => '直営', 'level' => 'company', 'parent_id' => 0, 'description' => 'RISE UP直営店舗', 'logo_path' => 'assets/img/rise-up-logo.png', 'sort_order' => 1, 'status' => 'active'],
                ['id' => 1, 'name' => '商品センター', 'level' => 'store', 'parent_id' => 100, 'description' => '', 'sort_order' => 1, 'status' => 'active'],
                ['id' => 2, 'name' => '播磨店', 'level' => 'store', 'parent_id' => 100, 'description' => '', 'sort_order' => 2, 'status' => 'active'],
                ['id' => 3, 'name' => '姫路店', 'level' => 'store', 'parent_id' => 100, 'description' => '', 'sort_order' => 3, 'status' => 'active'],
                ['id' => 4, 'name' => '高松店', 'level' => 'store', 'parent_id' => 100, 'description' => '', 'sort_order' => 4, 'status' => 'active'],
            ],
            'users' => [
                [
                    'id' => 1,
                    'name' => '全体管理者',
                    'email' => 'system-admin',
                    'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                    'role' => 'system_admin',
                    'department1_id' => 0,
                    'department2_id' => 0,
                    'phone' => '',
                    'status' => 'active',
                    'must_change_password' => true,
                ],
            ],
            'knowledge_categories' => [],
            'knowledge_articles' => [],
            'improvements' => [],
            'notifications' => [],
        ];

        foreach ($seedFiles as $name => $records) {
            if (!is_file($this->path($name))) {
                $this->write($name, $records);
            }
        }
    }
}
