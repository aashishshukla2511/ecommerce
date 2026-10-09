<div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.8rem; font-weight: 700;">🛍️ Product Catalog</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Browse our collection of available products</p>
</div>

<form method="GET" action="<?= \App\Helpers\Url::base('products') ?>" style="display:flex; gap:12px; margin-bottom: 24px;">
    <input type="text" name="q" value="<?= \App\Helpers\Url::escape($search) ?>" placeholder="Search catalog by product name..." 
           style="flex:1; padding: 10px 14px; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if (!empty($search)): ?>
        <a href="<?= \App\Helpers\Url::base('products') ?>" class="btn btn-outline">Reset</a>
    <?php endif; ?>
</form>

<?php if (empty($products)): ?>
    <div class="card" style="text-align:center; padding: 50px 20px;">
        <div style="font-size: 3rem; margin-bottom: 12px;">🛍️</div>
        <h3>No products available at the moment</h3>
        <p style="color: var(--text-muted); margin-top: 6px;">Please check back later or search with a different keyword.</p>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <?php foreach ($products as $p): ?>
            <div class="card" style="display:flex; flex-direction:column; justify-content:space-between; margin-bottom:0; transition: transform 0.2s, border-color 0.2s;">
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 12px;">
                        <span style="font-family:monospace; font-size:0.8rem; color:var(--accent); font-weight:600; background:var(--accent-light); padding: 2px 8px; border-radius: 4px;">
                            <?= \App\Helpers\Url::escape($p['sku']) ?>
                        </span>
                        <?php if ($p['stock_quantity'] > 0): ?>
                            <span style="font-size: 0.8rem; color: #34d399; font-weight:600;">In Stock (<?= $p['stock_quantity'] ?>)</span>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: #f87171; font-weight:600;">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 8px;">
                        <a href="<?= \App\Helpers\Url::base('products/' . $p['id']) ?>" style="color: var(--text-main);">
                            <?= \App\Helpers\Url::escape($p['name']) ?>
                        </a>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= !empty($p['description']) ? \App\Helpers\Url::escape($p['description']) : 'No description available.' ?>
                    </p>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top: 14px; border-top: 1px solid var(--border-color);">
                    <div style="font-size: 1.3rem; font-weight: 700; color: #34d399;">
                        $<?= number_format((float)$p['price'], 2) ?>
                    </div>
                    <a href="<?= \App\Helpers\Url::base('products/' . $p['id']) ?>" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.85rem;">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
            <span style="color: var(--text-muted); font-size:0.85rem;">Page <?= $page ?> of <?= $totalPages ?></span>
            <div style="display:flex; gap:6px;">
                <?php if ($page > 1): ?>
                    <a href="<?= \App\Helpers\Url::base('products?page=' . ($page - 1) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= \App\Helpers\Url::base('products?page=' . ($page + 1) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
