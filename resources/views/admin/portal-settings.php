<section class="page-header">
    <p class="eyebrow">Administration</p>
    <h1>ポータル設定</h1>
    <p class="lead">ポータルTOPに表示するメッセージを管理します。</p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
    <div class="flash-message"><?= e($_SESSION['flash']) ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<form class="panel staff-form" method="post" action="<?= route_url('admin.portalSettings.update') ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

    <div class="form-section">
        <h2>TOPメッセージ</h2>
        <label class="form-stack">
            <span>メッセージ</span>
            <textarea name="hero_message" rows="4" placeholder="例: 19期 利益構造改善..."><?= e($settings['hero_message'] ?? '') ?></textarea>
            <small class="field-hint">空欄にすると、TOPにはメッセージを表示しません。</small>
        </label>
        <label class="form-stack">
            <span>NEW表示日数</span>
            <input type="number" name="new_entry_days" min="0" value="<?= (int) ($settings['new_entry_days'] ?? 5) ?>">
            <small class="field-hint">投稿日から何日間NEW表示するかを設定します。0にすると非表示です。</small>
        </label>
        <label class="form-stack">
            <span>完了投稿の自動削除日数</span>
            <input type="number" name="completed_todo_delete_days" min="0" value="<?= (int) ($settings['completed_todo_delete_days'] ?? 5) ?>">
            <small class="field-hint">TO DOを完了にした日から何日後に自動削除するかを設定します。0にすると自動削除しません。</small>
        </label>
        <label class="form-stack">
            <span>グリッド削除パスワード</span>
            <input type="password" name="grid_delete_password" autocomplete="new-password" placeholder="<?= ($settings['grid_delete_password_hash'] ?? '') !== '' ? '設定済み。変更時のみ入力' : '削除時に入力するパスワード' ?>">
            <small class="field-hint">グリッド削除時に必要な確認パスワードです。空欄で更新すると現在のパスワードを維持します。</small>
        </label>
    </div>

    <div class="form-actions">
        <button class="button primary" type="submit">更新</button>
        <a class="button ghost" href="<?= route_url('dashboard') ?>">TOPを見る</a>
    </div>
</form>
