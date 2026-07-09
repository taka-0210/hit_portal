<?php

declare(strict_types=1);

namespace App\Modules\Improvement\Controllers;

use App\Modules\Improvement\Repositories\ImprovementRepository;
use App\Platform\View\View;

final class ImprovementController
{
    public function index(): void
    {
        $repository = new ImprovementRepository();

        View::render('improvement/index', [
            'improvements' => $repository->all(),
            'reasonLabels' => $repository->reasonLabels(),
        ]);
    }
}
