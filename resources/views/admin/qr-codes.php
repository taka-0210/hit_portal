<?php
$isEdit = ($mode ?? 'create') === 'edit';
$action = $isEdit ? route_url('admin.qrCodes.update') : route_url('admin.qrCodes.store');
?>
<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>QRコード管理</h1>
        <p class="lead">リンク投稿で選択できるQRコード画像を管理します。</p>
    </div>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<section class="admin-grid">
    <form class="panel form-stack" method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) ($qrCode['id'] ?? 0) ?>">
        <?php endif; ?>

        <h2><?= $isEdit ? 'QRコード編集' : 'QRコード登録' ?></h2>
        <label>
            <span>表示名</span>
            <input name="title" value="<?= e($qrCode['title'] ?? '') ?>" placeholder="例: LINE公式 QR" required>
        </label>
        <label>
            <span>QRコード画像</span>
            <input name="qr_image" type="file" accept="image/png,image/jpeg,image/gif,image/webp" <?= $isEdit ? '' : 'required' ?>>
            <?php if ($isEdit && !empty($qrCode['image_path'])): ?>
                <small class="field-hint">未選択の場合は現在の画像を維持します。</small>
            <?php endif; ?>
        </label>
        <?php if (!empty($qrCode['generated_url'])): ?>
            <label>
                <span>生成URL</span>
                <input value="<?= e($qrCode['generated_url']) ?>" readonly>
            </label>
            <div class="qr-code-preview">
                <img src="<?= e($qrCode['generated_url']) ?>" alt="<?= e($qrCode['title'] ?? '') ?>">
            </div>
        <?php else: ?>
            <label>
                <span>生成URL</span>
                <input value="画像登録後に自動生成されます" readonly>
            </label>
        <?php endif; ?>
        <label>
            <span>説明</span>
            <textarea name="description" rows="4" placeholder="用途や設置場所など"><?= e($qrCode['description'] ?? '') ?></textarea>
        </label>
        <label>
            <span>ステータス</span>
            <select name="status">
                <option value="active" <?= ($qrCode['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>有効</option>
                <option value="inactive" <?= ($qrCode['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>停止</option>
            </select>
        </label>
        <div class="form-actions">
            <button class="button primary" type="submit"><?= $isEdit ? '更新' : '登録' ?></button>
            <?php if ($isEdit): ?>
                <a class="button ghost" href="<?= route_url('admin.qrCodes') ?>">新規登録に戻る</a>
            <?php endif; ?>
        </div>
    </form>

    <section class="panel admin-table-panel">
        <div class="panel-heading">
            <h2>登録QRコード</h2>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>表示名</th>
                    <th>QR画像</th>
                    <th>生成URL</th>
                    <th>Status</th>
                    <th>説明</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($qrCodes ?? []) as $item): ?>
                    <tr>
                        <td><strong><?= e($item['title'] ?? '') ?></strong></td>
                        <td>
                            <?php if (!empty($item['generated_url'])): ?>
                                <img class="qr-code-thumb" src="<?= e($item['generated_url']) ?>" alt="<?= e($item['title'] ?? '') ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['generated_url'])): ?>
                                <a href="<?= e($item['generated_url']) ?>" target="_blank" rel="noopener"><?= e($item['generated_url']) ?></a>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge"><?= e($item['status'] ?? 'active') ?></span></td>
                        <td><?= e($item['description'] ?? '') ?></td>
                        <td>
                            <a class="button ghost" href="<?= route_url('admin.qrCodes.edit', ['id' => (int) ($item['id'] ?? 0)]) ?>">編集</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($qrCodes)): ?>
                    <tr>
                        <td colspan="6">まだQRコードは登録されていません。</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
