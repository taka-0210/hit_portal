<?php
$isCompanyMode = ($mode ?? 'store') === 'company';
$isEdit = !empty($editItem);
$title = $isCompanyMode ? '会社マスタ管理' : '店舗マスタ管理';
$label = $isCompanyMode ? '会社' : '店舗';
$action = $isCompanyMode
    ? ($isEdit ? route_url('admin.companies.update') : route_url('admin.companies.store'))
    : ($isEdit ? route_url('admin.stores.update') : route_url('admin.stores.store'));
?>
<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1><?= e($title) ?></h1>
        <p class="lead"><?= $isCompanyMode ? '直営・FC法人など、店舗を束ねる会社マスタを管理します。' : 'ポータルの表示対象になる店舗マスタを管理します。' ?></p>
    </div>
</section>

<section class="admin-grid">
    <form class="panel form-stack" method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) ($editItem['id'] ?? 0) ?>">
        <?php endif; ?>
        <h2><?= e($label) ?><?= $isEdit ? '編集' : '追加' ?></h2>
        <label><span><?= e($label) ?>名</span><input name="name" value="<?= e($editItem['name'] ?? '') ?>" required></label>
        <?php if ($isCompanyMode): ?>
            <label>
                <span>会社ロゴ</span>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
                <?php if ($isEdit && !empty($editItem['logo_path'])): ?>
                    <small class="field-hint">未選択の場合、現在のロゴを維持します。</small>
                <?php endif; ?>
            </label>
        <?php endif; ?>
        <?php if (!$isCompanyMode): ?>
            <label>
                <span>所属会社</span>
                <select name="parent_id">
                    <option value="0">未設定</option>
                    <?php foreach (($companies ?? []) as $company): ?>
                        <option value="<?= (int) ($company['id'] ?? 0) ?>" <?= (int) ($editItem['parent_id'] ?? 0) === (int) ($company['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= e($company['name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label><span>説明</span><textarea name="description" rows="4"><?= e($editItem['description'] ?? '') ?></textarea></label>
        <label><span>表示順</span><input type="number" name="sort_order" value="<?= (int) ($editItem['sort_order'] ?? 0) ?>"></label>
        <label>
            <span>ステータス</span>
            <select name="status">
                <option value="active" <?= ($editItem['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>有効</option>
                <option value="suspended" <?= ($editItem['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>停止</option>
            </select>
        </label>
        <div class="form-actions">
            <button class="button primary" type="submit"><?= $isEdit ? '更新' : '追加' ?></button>
            <?php if ($isEdit): ?>
                <a class="button ghost" href="<?= route_url($isCompanyMode ? 'admin.companies' : 'admin.stores') ?>">追加に戻る</a>
            <?php endif; ?>
        </div>
    </form>

    <section class="panel admin-table-panel">
        <div class="panel-heading">
            <h2><?= e($label) ?>一覧</h2>
            <a href="<?= route_url($isCompanyMode ? 'admin.stores' : 'admin.users') ?>"><?= $isCompanyMode ? '店舗マスタ管理' : 'アカウント管理' ?></a>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th><?= e($label) ?>名</th>
                    <?php if (!$isCompanyMode): ?>
                        <th>所属会社</th>
                    <?php else: ?>
                        <th>ロゴ</th>
                    <?php endif; ?>
                    <th>表示順</th>
                    <th>Status</th>
                    <th>説明</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e($item['name']) ?></strong></td>
                        <?php if (!$isCompanyMode): ?>
                            <td><?= e(($companyNames ?? [])[(int) ($item['parent_id'] ?? 0)] ?? '') ?></td>
                        <?php else: ?>
                            <td>
                                <?php if (!empty($item['logo_path'])): ?>
                                    <img class="company-logo-preview" src="<?= e(asset_url((string) $item['logo_path'])) ?>" alt="<?= e($item['name'] ?? '') ?>">
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td><?= (int) ($item['sort_order'] ?? 0) ?></td>
                        <td><span class="badge"><?= e($item['status'] ?? 'active') ?></span></td>
                        <td><?= e($item['description'] ?? '') ?></td>
                        <td>
                            <?php if ($isCompanyMode): ?>
                                <a class="button ghost" href="<?= route_url('admin.companies.show', ['id' => (int) ($item['id'] ?? 0)]) ?>">詳細</a>
                            <?php endif; ?>
                            <a class="button ghost" href="<?= route_url($isCompanyMode ? 'admin.companies.edit' : 'admin.stores.edit', ['id' => (int) ($item['id'] ?? 0)]) ?>">編集</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
