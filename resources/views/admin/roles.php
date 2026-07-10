<section class="page-header">
    <p class="eyebrow">Administration</p>
    <h1>権限管理</h1>
    <p class="lead">HIT Portal の権限は、全体管理者・FC法人管理者・店舗管理者・店舗アカウントの4種類です。</p>
</section>

<section class="panel">
    <div class="panel-heading">
        <h2>ロール一覧</h2>
        <a href="<?= route_url('admin.users') ?>">アカウント管理</a>
    </div>
    <table>
        <thead><tr><th>Name</th><th>Key</th><th>Description</th></tr></thead>
        <tbody>
        <?php foreach ($roles as $role): ?>
            <tr>
                <td><?= e($role['name']) ?></td>
                <td><?= e($role['key']) ?></td>
                <td><?= e($role['description']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
