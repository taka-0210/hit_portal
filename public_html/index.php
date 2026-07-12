<?php

declare(strict_types=1);

require __DIR__ . '/../app/Platform/Bootstrap.php';

use App\Modules\Administration\Controllers\AdminController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Improvement\Controllers\ImprovementController;
use App\Platform\Auth\AuthController;
use App\Platform\Auth\AuthService;
use App\Platform\Http\Router;

$auth = new AuthService();
$router = new Router($auth);

$router->get('login', [AuthController::class, 'showLogin']);
$router->post('login', [AuthController::class, 'login']);
$router->get('logout', [AuthController::class, 'logout']);

$router->get('dashboard', [DashboardController::class, 'index'], true);
$router->get('grid.file', [DashboardController::class, 'file'], true);
$router->get('grid.excelViewer', [DashboardController::class, 'excelViewer'], true);
$router->post('grid.entryStore', [DashboardController::class, 'entryStore'], true);
$router->post('grid.todoProgress', [DashboardController::class, 'todoProgress'], true);
$router->post('grid.glossaryUpdate', [DashboardController::class, 'glossaryUpdate'], true);
$router->get('guide', [DashboardController::class, 'guide'], true);

$router->get('improvements', [ImprovementController::class, 'index'], true, 'system_admin');

$router->get('admin.users', [AdminController::class, 'users'], true, 'system_admin');
$router->get('admin.users.create', [AdminController::class, 'createUser'], true, 'system_admin');
$router->post('admin.users.store', [AdminController::class, 'storeUser'], true, 'system_admin');
$router->get('admin.users.edit', [AdminController::class, 'editUser'], true, 'system_admin');
$router->post('admin.users.update', [AdminController::class, 'updateUser'], true, 'system_admin');
$router->get('admin.stores', [AdminController::class, 'stores'], true, 'system_admin');
$router->post('admin.stores.store', [AdminController::class, 'storeStore'], true, 'system_admin');
$router->get('admin.stores.edit', [AdminController::class, 'editStore'], true, 'system_admin');
$router->post('admin.stores.update', [AdminController::class, 'updateStore'], true, 'system_admin');
$router->post('admin.stores.move', [AdminController::class, 'moveStore'], true, 'system_admin');
$router->get('admin.companies', [AdminController::class, 'companies'], true, 'system_admin');
$router->post('admin.companies.store', [AdminController::class, 'storeCompany'], true, 'system_admin');
$router->get('admin.companies.show', [AdminController::class, 'showCompany'], true, 'system_admin');
$router->get('admin.companies.edit', [AdminController::class, 'editCompany'], true, 'system_admin');
$router->post('admin.companies.update', [AdminController::class, 'updateCompany'], true, 'system_admin');
$router->post('admin.companies.move', [AdminController::class, 'moveCompany'], true, 'system_admin');
$router->post('admin.companies.stores.store', [AdminController::class, 'storeCompanyStore'], true, 'system_admin');
$router->post('admin.companies.users.store', [AdminController::class, 'storeCompanyUser'], true, 'system_admin');
$router->get('admin.roles', [AdminController::class, 'roles'], true, 'system_admin');
$router->get('admin.portalSettings', [AdminController::class, 'portalSettings'], true, 'system_admin');
$router->post('admin.portalSettings.update', [AdminController::class, 'updatePortalSettings'], true, 'system_admin');
$router->get('admin.qrCodes', [AdminController::class, 'qrCodes'], true, 'system_admin');
$router->post('admin.qrCodes.store', [AdminController::class, 'storeQrCode'], true, 'system_admin');
$router->get('admin.qrCodes.edit', [AdminController::class, 'editQrCode'], true, 'system_admin');
$router->post('admin.qrCodes.update', [AdminController::class, 'updateQrCode'], true, 'system_admin');
$router->get('admin.guide', [AdminController::class, 'guideSettings'], true, 'system_admin');
$router->post('admin.guide.update', [AdminController::class, 'updateGuideSettings'], true, 'system_admin');
$router->get('admin.grids', [AdminController::class, 'grids'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->post('admin.grids.move', [AdminController::class, 'moveGrid'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->post('admin.grids.resetStoreLayout', [AdminController::class, 'resetStoreLayout'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->get('admin.grids.create', [AdminController::class, 'createGrid'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->post('admin.grids.store', [AdminController::class, 'storeGrid'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->get('admin.grids.edit', [AdminController::class, 'editGrid'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->post('admin.grids.update', [AdminController::class, 'updateGrid'], true, ['system_admin', 'company_admin', 'store_admin']);
$router->post('admin.grids.delete', [AdminController::class, 'deleteGrid'], true, ['system_admin', 'company_admin', 'store_admin']);

$router->dispatch($_GET['r'] ?? 'dashboard');
