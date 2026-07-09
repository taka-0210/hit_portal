<?php

declare(strict_types=1);

namespace App\Modules\Improvement\Repositories;

use App\Platform\Storage\JsonStore;

final class ImprovementRepository
{
    private JsonStore $store;

    public function __construct(?JsonStore $store = null)
    {
        $this->store = $store ?? new JsonStore();
    }

    public function all(): array
    {
        $items = $this->store->all('improvements');
        usort($items, fn (array $a, array $b): int => [
            strtotime((string) ($b['updated_at'] ?? '')) ?: 0,
            (int) ($b['id'] ?? 0),
        ] <=> [
            strtotime((string) ($a['updated_at'] ?? '')) ?: 0,
            (int) ($a['id'] ?? 0),
        ]);

        return $items;
    }

    public function count(): int
    {
        return count($this->store->all('improvements'));
    }

    public function reasonLabels(): array
    {
        return [
            'needs_update' => '情報が古い',
            'missing_info' => '情報が足りない',
            'hard_to_find' => '探しにくい',
            'hard_to_understand' => '分かりにくい',
            'other' => 'その他',
        ];
    }
}
