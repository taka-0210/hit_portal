<section class="page-header inline-actions">
    <div>
        <p class="eyebrow">Knowledge Module</p>
        <h1>ナレッジ</h1>
        <p class="lead">メーカー、商品、PDF、画像、動画を一か所に集め、探しやすく育てます。</p>
    </div>
    <a class="button primary" href="<?= route_url('knowledge.create') ?>">登録</a>
</section>

<section class="panel">
    <form class="filter-bar" method="get" action="index.php">
        <input type="hidden" name="r" value="knowledge">
        <input name="q" placeholder="キーワード、タグ、本文を検索" value="<?= e($filters['q'] ?? '') ?>">
        <select name="category_id">
            <option value="">すべてのカテゴリ</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="type">
            <option value="">すべての種別</option>
            <?php foreach ($types as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($filters['type'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">検索</button>
    </form>
</section>

<section class="knowledge-list">
    <?php foreach ($articles as $article): ?>
        <article class="knowledge-card">
            <div class="card-main">
                <div class="meta-line">
                    <span class="badge"><?= e($repository->categoryName((int) $article['category_id'])) ?></span>
                    <span class="badge muted"><?= e($types[$article['type']] ?? $article['type']) ?></span>
                    <span><?= e($article['updated_at'] ?? '') ?></span>
                </div>
                <h2><a href="<?= route_url('knowledge.show', ['id' => $article['id']]) ?>"><?= e($article['title']) ?></a></h2>
                <p><?= e($article['summary']) ?></p>
                <div class="tag-row">
                    <?php foreach (($article['tags'] ?? []) as $tag): ?>
                        <span>#<?= e($tag) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <a class="button ghost" href="<?= route_url('knowledge.edit', ['id' => $article['id']]) ?>">編集</a>
        </article>
    <?php endforeach; ?>

    <?php if ($articles === []): ?>
        <div class="empty-state">
            <h2>まだ見つかりません</h2>
            <p>検索条件を変えるか、新しいナレッジとして登録してください。</p>
        </div>
    <?php endif; ?>
</section>
