<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= \App\Helpers\Url::base('products') ?>" style="color: var(--text-muted); font-size: 0.9rem;">← Back to Product Catalog</a>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 16px; flex-wrap:wrap; gap:12px;">
            <div>
                <span style="font-family:monospace; font-size:0.85rem; color:var(--accent); font-weight:600; background:var(--accent-light); padding: 4px 10px; border-radius: 4px;">
                    <?= \App\Helpers\Url::escape($product['sku']) ?>
                </span>
                <h1 style="font-size: 1.8rem; font-weight: 700; margin-top: 10px;"><?= \App\Helpers\Url::escape($product['name']) ?></h1>
            </div>
            <div style="text-align:right;">
                <div style="font-size: 1.8rem; font-weight: 800; color: #34d399;">$<?= number_format((float)$product['price'], 2) ?></div>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span style="color: #34d399; font-weight:600; font-size: 0.9rem;">Available in Stock (<?= $product['stock_quantity'] ?> left)</span>
                <?php else: ?>
                    <span style="color: #f87171; font-weight:600; font-size: 0.9rem;">Currently Out of Stock</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 20px;">
            <h3 style="font-size: 1rem; font-weight:700; margin-bottom: 8px;">Product Description</h3>
            <p style="color: var(--text-muted); line-height: 1.7;">
                <?= !empty($product['description']) ? nl2br(\App\Helpers\Url::escape($product['description'])) : 'No detailed description available for this product.' ?>
            </p>
        </div>
    </div>
</div>
