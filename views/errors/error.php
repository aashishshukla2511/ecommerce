<div class="card" style="text-align: center; padding: 40px;">
    <h1 style="font-size: 3rem; color: var(--danger); margin-bottom: 12px;"><?= \App\Helpers\Url::escape((string)$code) ?></h1>
    <h2 style="font-size: 1.4rem; margin-bottom: 16px;"><?= \App\Helpers\Url::escape($message) ?></h2>

    <?php if (!empty($debug) && !empty($exception)): ?>
        <div style="text-align: left; background: var(--bg-primary); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color); overflow-x: auto; margin: 20px 0;">
            <pre style="color: #f87171; font-size: 0.85rem;"><?= \App\Helpers\Url::escape((string)$exception) ?></pre>
        </div>
    <?php endif; ?>

    <div style="margin-top: 24px;">
        <a href="<?= \App\Helpers\Url::base('/') ?>" class="btn btn-primary">Return to Home</a>
    </div>
</div>
