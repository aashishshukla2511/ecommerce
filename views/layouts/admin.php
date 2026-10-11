<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? \App\Helpers\Url::escape($title) . ' - ' : '' ?>Admin Portal</title>
    <link rel="stylesheet" href="<?= \App\Helpers\Url::base('assets/css/app.css') ?>">
</head>

<body>
    <div class="admin-layout">
        <!-- Left Sidebar Navigation -->
        <aside class="admin-sidebar">
            <div>
                <div class="admin-sidebar-header">
                    <a href="<?= \App\Helpers\Url::base('admin/products') ?>" class="brand">
                        ⚡ Admin <span>Portal</span>
                    </a>
                </div>
                <nav class="admin-sidebar-nav">
                    <div
                        style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 10px; padding-left: 12px;">
                        Management</div>
                    <ul class="admin-nav-list">
                        <li class="admin-nav-item">
                            <a href="<?= \App\Helpers\Url::base('admin/products') ?>">
                                <span>📦</span> Products List
                            </a>
                        </li>
                        <li class="admin-nav-item">
                            <a href="<?= \App\Helpers\Url::base('admin/products/create') ?>">
                                <span>➕</span> Add Product
                            </a>
                        </li>
                        <li class="admin-nav-item">
                            <a href="<?= \App\Helpers\Url::base('admin/orders') ?>">
                                <span>📑</span> Orders List
                            </a>
                        </li>
                    </ul>


                </nav>
            </div>

            <!-- Admin Sidebar Footer / User Profile -->
            <div class="admin-sidebar-footer">
                <div
                    style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px; display:flex; align-items:center; gap: 6px;">
                    <span
                        style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--success);"></span>
                    <?= \App\Helpers\Url::escape(\App\Core\Auth::user()['name'] ?? 'Administrator') ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 12px; font-family: monospace;">
                    <?= \App\Helpers\Url::escape(\App\Core\Auth::user()['email'] ?? 'admin@example.com') ?>
                </div>
                <form action="<?= \App\Helpers\Url::base('logout') ?>" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit" class="btn btn-outline"
                        style="width:100%; padding:6px 12px; font-size:0.8rem; border-color:var(--border-color);">
                        🚪 Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Right Side Main Content -->
        <main class="admin-main-content">
            <?php $flashes = \App\Helpers\Flash::all(); ?>
            <?php if (!empty($flashes)): ?>
                <div class="flash-messages">
                    <?php foreach ($flashes as $type => $messages): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="alert alert-<?= \App\Helpers\Url::escape($type) ?>">
                                <?= \App\Helpers\Url::escape($msg) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</body>

</html>