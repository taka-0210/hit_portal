<?php

declare(strict_types=1);

namespace App\Platform\Storage;

final class FileStorageService
{
    private const MAX_BYTES = 52428800;

    private array $allowedExtensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'mp4',
        'webm',
        'mov',
    ];

    public function saveKnowledgeFile(array $file, int $knowledgeId, int $userId): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('ファイルのアップロードに失敗しました。');
        }

        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('アップロードできるファイルサイズは 50MB までです。');
        }

        $originalName = (string) $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new \RuntimeException('このファイル形式はまだ対応していません。');
        }

        $directory = BASE_PATH . '/storage/private/knowledge/' . $knowledgeId;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $directory . '/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('ファイルを保存できませんでした。');
        }

        $mimeType = $this->detectMimeType($targetPath);

        return [
            'attachable_type' => 'knowledge_article',
            'attachable_id' => $knowledgeId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mimeType,
            'file_size' => (int) $file['size'],
            'storage_path' => 'storage/private/knowledge/' . $knowledgeId . '/' . $storedName,
            'created_by' => $userId,
        ];
    }

    public function absolutePath(array $attachment): string
    {
        return BASE_PATH . '/' . ltrim((string) $attachment['storage_path'], '/\\');
    }

    public function isPreviewableImage(array $attachment): bool
    {
        return str_starts_with((string) ($attachment['mime_type'] ?? ''), 'image/');
    }

    public function isPreviewableVideo(array $attachment): bool
    {
        return str_starts_with((string) ($attachment['mime_type'] ?? ''), 'video/');
    }

    public function isPdf(array $attachment): bool
    {
        return ($attachment['mime_type'] ?? '') === 'application/pdf';
    }

    private function detectMimeType(string $path): string
    {
        $mime = mime_content_type($path);
        return $mime !== false ? $mime : 'application/octet-stream';
    }
}
