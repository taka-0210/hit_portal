<?php
$isEdit = $mode === 'edit';
$action = $isEdit ? route_url('admin.users.update') : route_url('admin.users.store');
?>
<section class="page-header">
    <p class="eyebrow">Administration</p>
    <h1><?= $isEdit ? 'アカウント編集' : 'アカウント追加' ?></h1>
    <p class="lead">ログイン情報、店舗、権限を設定します。全体管理者は店舗を未設定にできます。</p>
</section>

<form class="panel staff-form" method="post" action="<?= $action ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
    <?php endif; ?>

    <div class="form-section">
        <h2>ログイン情報</h2>
        <div class="form-grid">
            <label><span>名前</span><input name="name" value="<?= e($user['name'] ?? '') ?>" required></label>
            <label><span>ログインID</span><input type="text" name="email" value="<?= e($user['email'] ?? '') ?>" required></label>
            <input type="hidden" name="phone" value="<?= e($user['phone'] ?? '') ?>">
            <label>
                <span><?= $isEdit ? '新しい仮パスワード' : '仮パスワード' ?></span>
                <input name="password" placeholder="<?= $isEdit ? '変更する場合だけ入力' : '未入力なら password' ?>">
                <small class="field-hint">未入力で追加した場合は password を設定します。</small>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h2>店舗・権限</h2>
        <div class="form-grid">
            <label>
                <span>店舗</span>
                <select name="department2_id">
                    <option value="0">未設定</option>
                    <?php foreach ($departmentLevel2 as $department): ?>
                        <option value="<?= (int) $department['id'] ?>" <?= (int) ($user['department2_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>>
                            <?= e($department['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>権限</span>
                <select name="role">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role['key']) ?>" <?= ($user['role'] ?? 'store_user') === $role['key'] ? 'selected' : '' ?>>
                            <?= e($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>ステータス</span>
                <select name="status">
                    <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>有効</option>
                    <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>停止</option>
                </select>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="button primary" type="submit"><?= $isEdit ? '更新' : '追加' ?></button>
        <a class="button ghost" href="<?= route_url('admin.users') ?>">戻る</a>
    </div>
</form>
