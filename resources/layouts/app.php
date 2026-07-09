<?php
$app = config('app');
$auth = new App\Platform\Auth\AuthService();
$currentUser = $auth->user();
$isSystemAdmin = $auth->hasRole('system_admin');
$isStoreAdmin = $auth->hasRole('store_admin');
$showsSidebar = $isSystemAdmin || $isStoreAdmin;
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="app-shell <?= $showsSidebar ? '' : 'is-staff-shell' ?>" data-app-shell>
    <?php if ($showsSidebar): ?>
        <aside class="sidebar">
            <a class="brand" href="<?= route_url('dashboard') ?>">
                <img class="brand-logo" src="<?= e(asset_url('assets/img/rise-up-logo.png')) ?>" alt="RISE UP">
            </a>
            <nav class="nav">
                <a href="<?= route_url('dashboard') ?>">ポータルTOP</a>
                <a href="<?= route_url('guide') ?>">取扱説明</a>
                <a href="<?= route_url('admin.grids') ?>">グリッド管理</a>
                <?php if ($isSystemAdmin): ?>
                    <a href="<?= route_url('admin.portalSettings') ?>">ポータル設定</a>
                    <a href="<?= route_url('admin.guide') ?>">取扱説明管理</a>
                    <a href="<?= route_url('admin.users') ?>">アカウント管理</a>
                    <a href="<?= route_url('admin.stores') ?>">店舗マスタ管理</a>
                    <a href="<?= route_url('admin.roles') ?>">権限管理</a>
                    <a href="<?= route_url('improvements') ?>">改善ログ</a>
                <?php endif; ?>
            </nav>
            <button class="sidebar-resizer" type="button" aria-label="左メニュー幅を変更" data-sidebar-resizer></button>
        </aside>
    <?php endif; ?>
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <?php if (!$showsSidebar): ?>
                    <a class="topbar-logo" href="<?= route_url('dashboard') ?>" aria-label="ポータルTOPに戻る">
                        <img src="<?= e(asset_url('assets/img/rise-up-logo.png')) ?>" alt="RISE UP">
                    </a>
                <?php endif; ?>
                <div class="top-portal-title">
                    <strong>HIT Portal</strong>
                    <span>店舗ポータル</span>
                </div>
            </div>
            <div class="user-menu">
                <a href="<?= route_url('guide') ?>">取扱説明</a>
                <span><?= e($currentUser['name'] ?? '') ?></span>
                <a href="<?= route_url('logout') ?>">ログアウト</a>
            </div>
        </header>
        <?= $content ?>
    </main>
</div>
<?php if ($showsSidebar): ?>
    <script>
    (() => {
        const shell = document.querySelector('[data-app-shell]');
        const resizer = document.querySelector('[data-sidebar-resizer]');
        const storageKey = 'hitPortalSidebarWidth';
        const minWidth = 180;
        const maxWidth = 420;

        if (!shell || !resizer) {
            return;
        }

        const applyWidth = (width) => {
            const safeWidth = Math.min(maxWidth, Math.max(minWidth, width));
            shell.style.setProperty('--sidebar-width', `${safeWidth}px`);
            return safeWidth;
        };

        const savedWidth = Number(localStorage.getItem(storageKey));
        if (savedWidth > 0) {
            applyWidth(savedWidth);
        }

        resizer.addEventListener('pointerdown', (event) => {
            if (window.matchMedia('(max-width: 900px)').matches) {
                return;
            }

            event.preventDefault();
            resizer.setPointerCapture(event.pointerId);
            document.body.classList.add('is-resizing-sidebar');

            const onMove = (moveEvent) => {
                const width = applyWidth(moveEvent.clientX);
                localStorage.setItem(storageKey, String(width));
            };

            const onUp = () => {
                document.body.classList.remove('is-resizing-sidebar');
                resizer.removeEventListener('pointermove', onMove);
                resizer.removeEventListener('pointerup', onUp);
                resizer.removeEventListener('pointercancel', onUp);
            };

            resizer.addEventListener('pointermove', onMove);
            resizer.addEventListener('pointerup', onUp);
            resizer.addEventListener('pointercancel', onUp);
        });
    })();
    </script>
<?php endif; ?>
</body>
</html>
