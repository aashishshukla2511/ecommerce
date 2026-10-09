<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= \App\Helpers\Url::base('admin/products') ?>" style="color: var(--text-muted); font-size: 0.9rem;">← Back to Products List</a>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
            <h1 style="font-size: 1.8rem; font-weight: 700;"><?= \App\Helpers\Url::escape($product['name']) ?></h1>
            <div style="display:flex; gap:8px;">
                <a href="<?= \App\Helpers\Url::base('admin/products/' . $product['id'] . '/edit') ?>" class="btn btn-primary">Edit Product</a>
                <?php if ($product['is_active']): ?>
                    <form action="<?= \App\Helpers\Url::base('admin/products/' . $product['id'] . '/deactivate') ?>" method="POST" onsubmit="return confirm('Deactivate this product?');">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn-outline" style="border-color:var(--danger); color:var(--danger);">Deactivate</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">SKU</div>
                <div style="font-size:1.2rem; font-weight:700; font-family:monospace; color:var(--accent);"><?= \App\Helpers\Url::escape($product['sku']) ?></div>
            </div>
            <div>
                <div style="color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">Price</div>
                <div style="font-size:1.4rem; font-weight:700; color:#34d399;">$<?= number_format((float)$product['price'], 2) ?></div>
            </div>
            <div>
                <div style="color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">Stock Quantity</div>
                <div style="font-size:1.2rem; font-weight:700;"><?= $product['stock_quantity'] ?> units</div>
            </div>
            <div>
                <div style="color:var(--text-muted); font-size:0.85rem; text-transform:uppercase;">Status</div>
                <div style="margin-top:4px;">
                    <?php if ($product['is_active']): ?>
                        <span class="status-badge status-online">Active</span>
                    <?php else: ?>
                        <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <h3 style="font-size: 1rem; font-weight:700; margin-bottom: 8px;">Description</h3>
            <p style="color: var(--text-muted); line-height:1.7;">
                <?= !empty($product['description']) ? nl2br(\App\Helpers\Url::escape($product['description'])) : '<em>No description provided.</em>' ?>
            </p>
        </div>

        <div style="font-size: 0.8rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 24px; display:flex; gap: 24px;">
            <div>Created At: <?= \App\Helpers\Url::escape($product['created_at']) ?></div>
            <div>Updated At: <?= \App\Helpers\Url::escape($product['updated_at']) ?></div>
        </div>
    </div>
</div>
