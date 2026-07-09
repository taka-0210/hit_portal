<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Repositories;

use App\Platform\Storage\FileStorageService;
use App\Platform\Storage\JsonStore;

final class KnowledgeRepository
{
    private JsonStore $store;
    private FileStorageService $files;

    public function __construct(?JsonStore $store = null, ?FileStorageService $files = null)
    {
        $this->store = $store ?? new JsonStore();
        $this->files = $files ?? new FileStorageService();
    }

    public function search(array $filters): array
    {
        $query = mb_strtolower(trim($filters['q'] ?? ''));
        $categoryId = (int) ($filters['category_id'] ?? 0);
        $type = trim($filters['type'] ?? '');

        $articles = array_filter($this->store->all('knowledge_articles'), function (array $article) use ($query, $categoryId, $type): bool {
            if (($article['status'] ?? '') !== 'published') {
                return false;
            }

            if ($categoryId > 0 && (int) ($article['category_id'] ?? 0) !== $categoryId) {
                return false;
            }

            if ($type !== '' && ($article['type'] ?? '') !== $type) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', [
                $article['title'] ?? '',
                $article['summary'] ?? '',
                $article['body'] ?? '',
                implode(' ', $article['tags'] ?? []),
            ]));

            return str_contains($haystack, $query);
        });

        usort($articles, fn (array $a, array $b): int => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''));
        return array_values($articles);
    }

    public function recent(int $limit = 5): array
    {
        return array_slice($this->search([]), 0, $limit);
    }

    public function find(int $id): ?array
    {
        return $this->store->find('knowledge_articles', $id);
    }

    public function save(array $data, int $userId): array
    {
        $record = [
            'id' => (int) ($data['id'] ?? 0),
            'title' => trim($data['title'] ?? ''),
            'summary' => trim($data['summary'] ?? ''),
            'body' => trim($data['body'] ?? ''),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'type' => trim($data['type'] ?? 'document'),
            'source_name' => trim($data['source_name'] ?? ''),
            'source_url' => $this->normalizeSource((string) ($data['source_url'] ?? '')),
            'tags' => $this->normalizeTags($data['tags'] ?? ''),
            'status' => 'published',
            'updated_by' => $userId,
        ];

        if ($record['id'] === 0) {
            $record['created_by'] = $userId;
        }

        return $this->store->save('knowledge_articles', $record);
    }

    public function attachUploadedFiles(int $knowledgeId, array $files, int $userId): void
    {
        foreach ($this->normalizeUploadedFiles($files) as $file) {
            $attachment = $this->files->saveKnowledgeFile($file, $knowledgeId, $userId);
            if ($attachment !== null) {
                $this->store->save('attachments', $attachment);
            }
        }
    }

    public function attachmentsFor(int $knowledgeId): array
    {
        $attachments = array_filter($this->store->all('attachments'), function (array $attachment) use ($knowledgeId): bool {
            return ($attachment['attachable_type'] ?? '') === 'knowledge_article'
                && (int) ($attachment['attachable_id'] ?? 0) === $knowledgeId;
        });

        usort($attachments, fn (array $a, array $b): int => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_values($attachments);
    }

    public function attachment(int $id): ?array
    {
        return $this->store->find('attachments', $id);
    }

    public function categories(): array
    {
        return $this->store->all('knowledge_categories');
    }

    public function categoryName(?int $id): string
    {
        foreach ($this->categories() as $category) {
            if ((int) $category['id'] === (int) $id) {
                return $category['name'];
            }
        }

        return '未分類';
    }

    public function types(): array
    {
        return [
            'maker' => 'メーカー',
            'product' => '商品',
            'pdf' => 'PDF',
            'image' => '画像',
            'video' => '動画',
            'document' => 'その他ナレッジ',
        ];
    }

    public function isExternalSource(?string $source): bool
    {
        $source = $this->displaySource($source);
        return str_starts_with($source, 'http://') || str_starts_with($source, 'https://');
    }

    public function displaySource(?string $source): string
    {
        return $this->normalizeSource((string) $source);
    }

    public function fileStorage(): FileStorageService
    {
        return $this->files;
    }

    private function normalizeTags(string $tags): array
    {
        $items = preg_split('/[,、\s]+/u', $tags) ?: [];
        $items = array_map('trim', $items);
        return array_values(array_filter(array_unique($items)));
    }

    private function normalizeSource(string $source): string
    {
        $source = trim($source);

        // Users often paste a quoted Windows path. Keep the path, but remove wrapping quotes.
        $source = trim($source, " \t\n\r\0\x0B\"'");

        // If Markdown style text is pasted, keep the actual URL/path inside parentheses.
        if (preg_match('/^\[[^\]]+\]\((.+)\)$/u', $source, $matches) === 1) {
            $source = trim($matches[1], " \t\n\r\0\x0B\"'");
        }

        return $source;
    }

    private function normalizeUploadedFiles(array $files): array
    {
        if (($files['name'] ?? '') === '') {
            return [];
        }

        if (!is_array($files['name'])) {
            return [$files];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }
}
