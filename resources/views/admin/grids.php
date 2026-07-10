<?php
$isStoreLayout = ($layoutScope ?? 'global') === 'store';
$layoutStoreId = (int) ($layoutStoreId ?? 0);
$columnLabels = ['左列', '中央列', '右列'];
$emptyColumns = [1 => [], 2 => [], 3 => []];

$splitColumns = static function (array $items) use ($emptyColumns): array {
    $areas = [
        'common' => $emptyColumns,
        'company' => $emptyColumns,
        'store_shared' => $emptyColumns,
        'store' => $emptyColumns,
    ];

    foreach ($items as $grid) {
        $scope = (string) ($grid['scope_type'] ?? 'all');
        if ($scope === 'company') {
            $area = 'company';
        } elseif ($scope === 'store_shared') {
            $area = 'store_shared';
        } elseif ($scope === 'store') {
            $area = 'store';
        } else {
            $area = 'common';
        }
        $column = min(3, max(1, (int) ($grid['column'] ?? 1)));
        $areas[$area][$column][] = $grid;
    }

    return $areas;
};

$publishedGrids = array_values(array_filter($grids, static fn (array $grid): bool => ($grid['status'] ?? '') === 'published'));
$privateGrids = array_values(array_filter($grids, static fn (array $grid): bool => ($grid['status'] ?? '') !== 'published'));
$publishedAreas = $splitColumns($publishedGrids);
$privateAreas = $splitColumns($privateGrids);

$areaTitles = [
    'common' => '共通グリッド',
    'company' => '会社共通グリッド',
    'store_shared' => '店舗共通グリッド',
    'store' => '店舗専用グリッド',
];

$areaDescriptions = [
    'common' => 'すべての店舗に表示されるグリッドです。このエリア内だけで上下左右に移動できます。',
    'company' => '指定した会社またはFC法人に所属する店舗だけに表示されるグリッドです。',
    'store_shared' => 'すべての店舗に枠が表示され、投稿データは店舗ごとに分かれるグリッドです。',
    'store' => '特定店舗だけに表示されるグリッドです。店舗共通グリッドとは別のエリアで配置します。',
];

$areaCounts = static function (array $columns): int {
    return array_sum(array_map('count', $columns));
};

$renderMoveButton = static function (array $grid, string $direction, string $label, string $title, bool $disabled) use ($layoutScope, $layoutStoreId): void {
    ?>
    <form method="post" action="<?= route_url('admin.grids.move') ?>">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= (int) $grid['id'] ?>">
        <input type="hidden" name="direction" value="<?= e($direction) ?>">
        <input type="hidden" name="layout_scope" value="<?= e($layoutScope ?? 'global') ?>">
        <input type="hidden" name="store_id" value="<?= (int) $layoutStoreId ?>">
        <button type="submit" title="<?= e($title) ?>" <?= $disabled ? 'disabled' : '' ?>><?= e($label) ?></button>
    </form>
    <?php
};

$renderGridCard = static function (array $grid, int $columnNumber, int $gridIndex, int $columnCount, bool $hasLeftNeighbor, bool $hasRightNeighbor) use ($toneLabels, $scopeTypeLabels, $registrationTypeLabels, $displayTypeLabels, $expandTypeLabels, $renderMoveButton, $layoutScope, $layoutStoreId): void {
    $tone = $grid['tone'] ?? 'green';
    $deleteDialogId = 'grid-delete-dialog-' . (int) ($grid['id'] ?? 0);
    $entryCount = 0;
    foreach (($grid['groups'] ?? []) as $group) {
        $entryCount += count($group['entries'] ?? []);
    }
    ?>
    <article class="grid-admin-card <?= ($grid['status'] ?? '') === 'published' ? '' : 'is-private-card' ?>">
        <div class="grid-card-band tone-<?= e($tone) ?>"></div>
        <div class="grid-card-body">
            <div class="grid-card-title">
                <div>
                    <h2><?= e($grid['title'] ?? '') ?></h2>
                </div>
                <span class="badge <?= ($grid['status'] ?? '') === 'published' ? '' : 'muted' ?>">
                    <?= e(($grid['status'] ?? '') === 'published' ? '公開' : '非公開') ?>
                </span>
            </div>

            <div class="grid-card-meta">
                <span><?= e($toneLabels[$tone] ?? $tone) ?></span>
                <span><?= e($scopeTypeLabels[$grid['scope_type'] ?? 'all'] ?? ($grid['scope_type'] ?? '')) ?></span>
                <span><?= e($registrationTypeLabels[$grid['registration_type'] ?? 'links'] ?? '') ?></span>
                <span><?= e($displayTypeLabels[$grid['display_type'] ?? 'list'] ?? '') ?></span>
                <?php if (($grid['scope_type'] ?? 'all') === 'all'): ?>
                    <span><?= ($grid['post_permission'] ?? 'allowed') === 'denied' ? '投稿不可' : '投稿可' ?></span>
                <?php endif; ?>
                <span><?= e($expandTypeLabels[$grid['expand_type'] ?? 'open'] ?? '通常表示') ?></span>
            </div>

            <div class="grid-card-footer">
                <span><?= $entryCount ?>件</span>
                <div class="grid-card-actions">
                    <div class="grid-move-controls">
                        <div class="grid-swap-controls">
                            <?php $renderMoveButton($grid, 'left', '◀', '左隣と入替え', !$hasLeftNeighbor); ?>
                            <?php $renderMoveButton($grid, 'up', '▲', '上と入替え', $gridIndex === 0); ?>
                            <?php $renderMoveButton($grid, 'down', '▼', '下と入替え', $gridIndex === $columnCount - 1); ?>
                            <?php $renderMoveButton($grid, 'right', '▶', '右隣と入替え', !$hasRightNeighbor); ?>
                        </div>
                        <div class="grid-column-move-controls">
                            <?php $renderMoveButton($grid, 'column_left', '列◀', '左列の一番上へ移動', $columnNumber === 1); ?>
                            <?php $renderMoveButton($grid, 'column_right', '▶列', '右列の一番上へ移動', $columnNumber === 3); ?>
                        </div>
                    </div>
                    <a class="button ghost" href="<?= route_url('admin.grids.edit', ['id' => (int) $grid['id']]) ?>">編集</a>
                    <button class="button danger" type="button" data-open-dialog="<?= e($deleteDialogId) ?>">削除</button>
                </div>
            </div>
        </div>
        <dialog class="portal-modal grid-delete-dialog" id="<?= e($deleteDialogId) ?>">
            <div class="portal-modal-panel">
                <div class="portal-modal-heading section-rose">
                    <h3>グリッド削除</h3>
                    <button type="button" aria-label="閉じる" data-close-dialog>×</button>
                </div>
                <form class="portal-entry-form" method="post" action="<?= route_url('admin.grids.delete') ?>">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= (int) ($grid['id'] ?? 0) ?>">
                    <input type="hidden" name="layout_scope" value="<?= e($layoutScope ?? 'global') ?>">
                    <input type="hidden" name="store_id" value="<?= (int) $layoutStoreId ?>">

                    <p class="delete-warning">
                        「<?= e($grid['title'] ?? '') ?>」を削除します。登録済みデータもすべて削除されます。
                    </p>
                    <label>
                        <span>グリッド削除パスワード</span>
                        <input type="password" name="grid_delete_password" required autocomplete="current-password">
                    </label>

                    <div class="form-actions">
                        <button class="button danger" type="submit">削除する</button>
                        <button class="button ghost" type="button" data-close-dialog>閉じる</button>
                    </div>
                </form>
            </div>
        </dialog>
    </article>
    <?php
};

$renderArea = static function (string $areaKey, array $columns, string $stateClass = '') use ($areaTitles, $areaDescriptions, $areaCounts, $columnLabels, $renderGridCard): void {
    if ($areaCounts($columns) === 0) {
        return;
    }

    ?>
    <section class="grid-layout-area <?= e($stateClass) ?>">
        <div class="grid-layout-area-heading">
            <div>
                <h2><?= e($areaTitles[$areaKey] ?? '') ?></h2>
                <p><?= e($areaDescriptions[$areaKey] ?? '') ?></p>
            </div>
            <span><?= $areaCounts($columns) ?>件</span>
        </div>

        <div class="grid-card-board <?= e($stateClass) ?>">
            <?php foreach ($columns as $columnNumber => $columnGrids): ?>
                <div class="grid-admin-column">
                    <div class="grid-admin-column-heading">
                        <h3><?= e($columnLabels[$columnNumber - 1]) ?></h3>
                        <span><?= count($columnGrids) ?>件</span>
                    </div>
                    <?php foreach ($columnGrids as $gridIndex => $grid): ?>
                        <?php $renderGridCard(
                            $grid,
                            $columnNumber,
                            $gridIndex,
                            count($columnGrids),
                            isset($columns[$columnNumber - 1][$gridIndex]),
                            isset($columns[$columnNumber + 1][$gridIndex])
                        ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};
?>

<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>グリッド管理</h1>
        <p class="lead">ポータルTOPに表示するグリッドを、共通・会社共通・店舗共通・店舗専用に分けて管理します。</p>
    </div>
    <a class="button primary" href="<?= route_url('admin.grids.create') ?>">グリッド新規作成</a>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php if (!empty($layoutApplied)): ?>
    <div class="flash-message">切替えました</div>
<?php endif; ?>

<section class="grid-admin-toolbar">
    <h2>登録グリッド</h2>
    <a href="<?= route_url('dashboard') ?>">ポータルTOP</a>
</section>

<section class="grid-layout-switcher">
    <form class="grid-layout-form" method="get" action="index.php">
        <input type="hidden" name="r" value="admin.grids">
        <input type="hidden" name="layout_applied" value="1">
        <label>
            <span>配置対象</span>
            <select name="layout_scope">
                <option value="global" <?= !$isStoreLayout ? 'selected' : '' ?>>共通配置</option>
                <option value="store" <?= $isStoreLayout ? 'selected' : '' ?>>店舗別配置</option>
            </select>
        </label>
        <?php if ($isStoreLayout): ?>
            <label>
                <span>店舗</span>
                <select name="store_id">
                    <?php foreach (($stores ?? []) as $store): ?>
                        <option value="<?= (int) ($store['id'] ?? 0) ?>" <?= $layoutStoreId === (int) ($store['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= e($store['name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <button class="button ghost" type="submit">表示</button>
    </form>

    <?php if ($isStoreLayout): ?>
        <form method="post" action="<?= route_url('admin.grids.resetStoreLayout') ?>">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="store_id" value="<?= (int) $layoutStoreId ?>">
            <button class="button ghost" type="submit">店舗別配置を共通配置に戻す</button>
        </form>
    <?php endif; ?>
</section>

<?php $renderArea('common', $publishedAreas['common']); ?>
<?php $renderArea('company', $publishedAreas['company']); ?>
<?php $renderArea('store_shared', $publishedAreas['store_shared']); ?>
<?php $renderArea('store', $publishedAreas['store']); ?>

<section class="grid-private-area">
    <div class="grid-private-heading">
        <div>
            <p class="eyebrow">Draft Area</p>
            <h2>非公開グリッド</h2>
        </div>
        <span><?= count($privateGrids) ?>件</span>
    </div>

    <?php $renderArea('common', $privateAreas['common'], 'is-private'); ?>
    <?php $renderArea('company', $privateAreas['company'], 'is-private'); ?>
    <?php $renderArea('store_shared', $privateAreas['store_shared'], 'is-private'); ?>
    <?php $renderArea('store', $privateAreas['store'], 'is-private'); ?>
</section>

<script>
(() => {
    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-open-dialog]');
        if (openButton) {
            document.getElementById(openButton.dataset.openDialog)?.showModal();
            return;
        }

        const closeButton = event.target.closest('[data-close-dialog]');
        if (closeButton) {
            closeButton.closest('dialog')?.close();
            return;
        }

        if (event.target instanceof HTMLDialogElement) {
            event.target.close();
        }
    });
})();
</script>
