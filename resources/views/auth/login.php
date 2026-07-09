<section class="login-panel">
    <div>
        <p class="eyebrow">HIT Portal Version 0.1</p>
        <h1>店舗ログイン</h1>
        <p class="lead">店舗または管理者のログインIDでポータルに入ります。</p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= route_url('login') ?>" class="form-stack">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <label>
            <span>ログインID</span>
            <input type="text" name="email" value="system-admin" required autocomplete="username">
        </label>
        <label>
            <span>パスワード</span>
            <input type="password" name="password" value="password" required autocomplete="current-password">
        </label>
        <button class="button primary" type="submit">ログイン</button>
    </form>
    <p class="hint">初期全体管理者: system-admin / password</p>
</section>
