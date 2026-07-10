<?php
$roleNames = [];
foreach ($roles as $role) {
    $roleNames[$role['key']] = $role['name'];
}
?>
<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>アカウント管理</h1>
        <p class="lead">全体管理者、FC法人管理者、店舗管理者、店舗アカウントを管理します。</p>
    </div>
    <a class="button primary" href="<?= route_url('admin.users.create') ?>">アカウント追加</a>
</section>

<section class="panel admin-table-panel">
    <div class="panel-heading">
        <h2>登録アカウント</h2>
        <a href="<?= route_url('admin.companies') ?>">会社マスタ管理</a>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>名前</th>
                <th>ログインID</th>
                <th>会社</th>
                <th>店舗</th>
                <th>権限</th>
                <th>Status</th>
                <th>編集</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= e($user['name']) ?></strong></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($departmentNames[(int) ($user['department1_id'] ?? 0)] ?? '') ?></td>
                    <td><?= e($departmentNames[(int) ($user['department2_id'] ?? 0)] ?? '') ?></td>
                    <td><span class="badge muted"><?= e($roleNames[$user['role'] ?? ''] ?? ($user['role'] ?? '')) ?></span></td>
                    <td><span class="badge"><?= e($user['status']) ?></span></td>
                    <td><a class="button ghost" href="<?= route_url('admin.users.edit', ['id' => (int) $user['id']]) ?>">編集</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
