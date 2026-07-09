<?php
$title = '店舗マスタ管理';
$label = '店舗';
$action = route_url('admin.stores.store');
?>
<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1><?= e($title) ?></h1>
        <p class="lead">ポータルの表示対象になる店舗マスタを管理します。</p>
    </div>
</section>

<section class="admin-grid">
    <form class="panel form-stack" method="post" action="<?= $action ?>">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <h2><?= e($label) ?>追加</h2>
        <label><span><?= e($label) ?>名</span><input name="name" required></label>
        <label><span>説明</span><textarea name="description" rows="4"></textarea></label>
        <label><span>表示順</span><input type="number" name="sort_order" value="0"></label>
        <label>
            <span>ステータス</span>
            <select name="status">
                <option value="active">有効</option>
                <option value="suspended">停止</option>
            </select>
        </label>
        <button class="button primary" type="submit">追加</button>
    </form>

    <section class="panel admin-table-panel">
        <div class="panel-heading">
            <h2><?= e($label) ?>一覧</h2>
            <a href="<?= route_url('admin.users') ?>">アカウント管理</a>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th><?= e($label) ?>名</th>
                    <th>表示順</th>
                    <th>Status</th>
                    <th>説明</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= e($item['name']) ?></strong></td>
                        <td><?= (int) ($item['sort_order'] ?? 0) ?></td>
                        <td><span class="badge"><?= e($item['status'] ?? 'active') ?></span></td>
                        <td><?= e($item['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
