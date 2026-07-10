<?php
$roleNames = [];
foreach ($roles as $role) {
    $roleNames[$role['key']] = $role['name'];
}
$companyId = (int) ($company['id'] ?? 0);
?>
<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1><?= e($company['name'] ?? '') ?></h1>
        <p class="lead">会社に紐づく店舗とアカウントをまとめて管理します。</p>
    </div>
    <a class="button ghost" href="<?= route_url('admin.companies') ?>">会社一覧</a>
</section>

<section class="admin-grid">
    <section class="panel">
        <div class="panel-heading">
            <h2>基本情報</h2>
            <a class="button ghost" href="<?= route_url('admin.companies.edit', ['id' => $companyId]) ?>">編集</a>
        </div>
        <?php if (!empty($company['logo_path'])): ?>
            <img class="company-detail-logo" src="<?= e(asset_url((string) $company['logo_path'])) ?>" alt="<?= e($company['name'] ?? '') ?>">
        <?php endif; ?>
        <table>
            <tbody>
            <tr><th>会社名</th><td><?= e($company['name'] ?? '') ?></td></tr>
            <tr><th>ステータス</th><td><span class="badge"><?= e($company['status'] ?? 'active') ?></span></td></tr>
            <tr><th>表示順</th><td><?= (int) ($company['sort_order'] ?? 0) ?></td></tr>
            <tr><th>説明</th><td><?= e($company['description'] ?? '') ?></td></tr>
            </tbody>
        </table>
    </section>

    <form class="panel form-stack" method="post" action="<?= route_url('admin.companies.stores.store') ?>">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="company_id" value="<?= $companyId ?>">
        <h2>店舗追加</h2>
        <label><span>店舗名</span><input name="name" required></label>
        <label><span>説明</span><textarea name="description" rows="4"></textarea></label>
        <label><span>表示順</span><input type="number" name="sort_order" value="0"></label>
        <label>
            <span>ステータス</span>
            <select name="status">
                <option value="active">有効</option>
                <option value="suspended">停止</option>
            </select>
        </label>
        <button class="button primary" type="submit">店舗を追加</button>
    </form>
</section>

<section class="panel admin-table-panel">
    <div class="panel-heading">
        <h2>店舗一覧</h2>
        <span><?= count($stores) ?>件</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>店舗名</th>
                <th>表示順</th>
                <th>Status</th>
                <th>説明</th>
                <th>編集</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($stores as $store): ?>
                <tr>
                    <td><strong><?= e($store['name'] ?? '') ?></strong></td>
                    <td><?= (int) ($store['sort_order'] ?? 0) ?></td>
                    <td><span class="badge"><?= e($store['status'] ?? 'active') ?></span></td>
                    <td><?= e($store['description'] ?? '') ?></td>
                    <td><a class="button ghost" href="<?= route_url('admin.stores.edit', ['id' => (int) ($store['id'] ?? 0)]) ?>">編集</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-grid">
    <form class="panel form-stack" method="post" action="<?= route_url('admin.companies.users.store') ?>">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="company_id" value="<?= $companyId ?>">
        <input type="hidden" name="phone" value="">
        <h2>アカウント追加</h2>
        <label><span>名前</span><input name="name" required></label>
        <label><span>ログインID</span><input name="email" required></label>
        <label>
            <span>仮パスワード</span>
            <input name="password" placeholder="未入力なら password">
            <small class="field-hint">追加後に必要に応じて変更してください。</small>
        </label>
        <label>
            <span>権限</span>
            <select name="role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role['key']) ?>"><?= e($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>店舗</span>
            <select name="department2_id">
                <option value="0">会社管理者または未設定</option>
                <?php foreach ($stores as $store): ?>
                    <option value="<?= (int) ($store['id'] ?? 0) ?>"><?= e($store['name'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>ステータス</span>
            <select name="status">
                <option value="active">有効</option>
                <option value="suspended">停止</option>
            </select>
        </label>
        <button class="button primary" type="submit">アカウントを追加</button>
    </form>

    <section class="panel admin-table-panel">
        <div class="panel-heading">
            <h2>アカウント一覧</h2>
            <span><?= count($accounts) ?>件</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>名前</th>
                    <th>ログインID</th>
                    <th>店舗</th>
                    <th>権限</th>
                    <th>Status</th>
                    <th>編集</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><strong><?= e($account['name'] ?? '') ?></strong></td>
                        <td><?= e($account['email'] ?? '') ?></td>
                        <td><?= e($departmentNames[(int) ($account['department2_id'] ?? 0)] ?? '') ?></td>
                        <td><span class="badge muted"><?= e($roleNames[$account['role'] ?? ''] ?? ($account['role'] ?? '')) ?></span></td>
                        <td><span class="badge"><?= e($account['status'] ?? 'active') ?></span></td>
                        <td><a class="button ghost" href="<?= route_url('admin.users.edit', ['id' => (int) ($account['id'] ?? 0)]) ?>">編集</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
