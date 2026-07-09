<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Controllers;

use App\Modules\Knowledge\Repositories\KnowledgeRepository;
use App\Platform\Auth\AuthService;
use App\Platform\View\View;

final class KnowledgeController
{
    private KnowledgeRepository $knowledge;

    public function __construct()
    {
        $this->knowledge = new KnowledgeRepository();
    }

    public function index(): void
    {
        View::render('knowledge/index', [
            'articles' => $this->knowledge->search($_GET),
            'categories' => $this->knowledge->categories(),
            'types' => $this->knowledge->types(),
            'filters' => $_GET,
            'repository' => $this->knowledge,
        ]);
    }

    public function show(): void
    {
        $article = $this->knowledge->find((int) ($_GET['id'] ?? 0));
        if ($article === null) {
            http_response_code(404);
            exit('Knowledge not found.');
        }

        View::render('knowledge/show', [
            'article' => $article,
            'attachments' => $this->knowledge->attachmentsFor((int) $article['id']),
            'repository' => $this->knowledge,
        ]);
    }

    public function create(): void
    {
        View::render('knowledge/form', [
            'article' => null,
            'attachments' => [],
            'categories' => $this->knowledge->categories(),
            'types' => $this->knowledge->types(),
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $userId = (int) (new AuthService())->user()['id'];
        $article = $this->knowledge->save($_POST, $userId);
        $this->knowledge->attachUploadedFiles((int) $article['id'], $_FILES['attachments'] ?? [], $userId);
        redirect('knowledge');
    }

    public function edit(): void
    {
        $article = $this->knowledge->find((int) ($_GET['id'] ?? 0));
        if ($article === null) {
            http_response_code(404);
            exit('Knowledge not found.');
        }

        View::render('knowledge/form', [
            'article' => $article,
            'attachments' => $this->knowledge->attachmentsFor((int) $article['id']),
            'categories' => $this->knowledge->categories(),
            'types' => $this->knowledge->types(),
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        $userId = (int) (new AuthService())->user()['id'];
        $article = $this->knowledge->save($_POST, $userId);
        $this->knowledge->attachUploadedFiles((int) $article['id'], $_FILES['attachments'] ?? [], $userId);
        redirect('knowledge.show', ['id' => (int) $article['id']]);
    }

    public function file(): void
    {
        $attachment = $this->knowledge->attachment((int) ($_GET['id'] ?? 0));
        if ($attachment === null) {
            http_response_code(404);
            exit('File not found.');
        }

        $path = $this->knowledge->fileStorage()->absolutePath($attachment);
        if (!is_file($path)) {
            http_response_code(404);
            exit('File not found.');
        }

        header('Content-Type: ' . ($attachment['mime_type'] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode((string) $attachment['original_name']) . '"');
        readfile($path);
        exit;
    }
}
