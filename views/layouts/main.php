<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? \App\Helpers\Url::escape($title) . ' - ' : '' ?>Ecommerce MVC</title>
    <link rel="stylesheet" href="<?= \App\Helpers\Url::base('assets/css/app.css') ?>">
</head>
<body>
    <header>
        <div class="container header-nav">
            <a href="<?= \App\Helpers\Url::base('/') ?>" class="brand">
                ⚡ ECommerce <span>MVC</span>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="<?= \App\Helpers\Url::base('/') ?>">Home</a></li>
                    <?php if (\App\Core\Auth::check()): ?>
                        <?php if (\App\Core\Auth::isAdmin()): ?>
                            <li><a href="<?= \App\Helpers\Url::base('admin/products') ?>" class="btn btn-outline" style="padding:4px 10px; font-size:0.85rem; border-color:var(--accent); color:var(--accent);">Admin Portal</a></li>
                        <?php else: ?>
                            <li><a href="<?= \App\Helpers\Url::base('my-orders') ?>">My Orders</a></li>
                            <li><a href="<?= \App\Helpers\Url::base('orders/create') ?>" class="btn btn-primary" style="padding:5px 14px; font-size:0.85rem;">+ Create Order</a></li>
                        <?php endif; ?>
                        <li>
                            <form action="<?= \App\Helpers\Url::base('logout') ?>" method="POST" style="display:inline;">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem;">Logout (<?= \App\Helpers\Url::escape(\App\Core\Auth::user()['name']) ?>)</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= \App\Helpers\Url::base('login') ?>" class="btn btn-primary">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
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

    <footer>
        <div class="container">
            PHP MVC E-Commerce Demo &copy; <?= date('Y') ?>. Built with custom lightweight MVC framework.
        </div>
    </footer>
</body>
</html>
