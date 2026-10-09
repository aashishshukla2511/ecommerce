<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 700;">📦 Product Management</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage inventory, price, SKU, and product status</p>
    </div>
    <div>
        <a href="<?= \App\Helpers\Url::base('admin/products/create') ?>" class="btn btn-primary" style="gap: 6px;">
            + Add New Product
        </a>
    </div>
</div>

<div class="card" style="padding: 20px;">
    <form method="GET" action="<?= \App\Helpers\Url::base('admin/products') ?>" style="display:flex; gap:12px; margin-bottom: 20px;">
        <input type="text" name="q" value="<?= \App\Helpers\Url::escape($search) ?>" placeholder="Search by Product Name or SKU..." 
               style="flex:1; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
        <button type="submit" class="btn btn-outline">Search</button>
        <?php if (!empty($search)): ?>
            <a href="<?= \App\Helpers\Url::base('admin/products') ?>" class="btn btn-outline" style="color: var(--text-muted);">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($products)): ?>
        <div style="text-align:center; padding: 40px 20px; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">📭</div>
            <h3>No products found</h3>
            <p style="font-size:0.9rem; margin-top:4px;">Try refining your search or add a new product.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse: collapse; text-align:left; font-size:0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                        <th style="padding: 12px 16px;">SKU</th>
                        <th style="padding: 12px 16px;">Product Name</th>
                        <th style="padding: 12px 16px;">Price</th>
                        <th style="padding: 12px 16px;">Stock</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;">
                            <td style="padding: 14px 16px; font-family: monospace; color: var(--accent); font-weight:600;">
                                <?= \App\Helpers\Url::escape($p['sku']) ?>
                            </td>
                            <td style="padding: 14px 16px; font-weight: 600;">
                                <a href="<?= \App\Helpers\Url::base('admin/products/' . $p['id']) ?>" style="color: var(--text-main);">
                                    <?= \App\Helpers\Url::escape($p['name']) ?>
                                </a>
                            </td>
                            <td style="padding: 14px 16px; font-weight:600; color: #34d399;">
                                $<?= number_format((float)$p['price'], 2) ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <?php if ($p['stock_quantity'] > 5): ?>
                                    <span style="color: var(--text-main); font-weight:600;"><?= $p['stock_quantity'] ?> units</span>
                                <?php elseif ($p['stock_quantity'] > 0): ?>
                                    <span style="color: var(--warning); font-weight:600;"><?= $p['stock_quantity'] ?> left</span>
                                <?php else: ?>
                                    <span style="color: var(--danger); font-weight:600;">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <?php if ($p['is_active']): ?>
                                    <span class="status-badge status-online">Active</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 16px; text-align:right; white-space:nowrap;">
                                <a href="<?= \App\Helpers\Url::base('admin/products/' . $p['id']) ?>" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; margin-right:4px;">View</a>
                                <a href="<?= \App\Helpers\Url::base('admin/products/' . $p['id'] . '/edit') ?>" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; margin-right:4px; border-color: var(--accent); color: var(--accent);">Edit</a>
                                <?php if ($p['is_active']): ?>
                                    <form action="<?= \App\Helpers\Url::base('admin/products/' . $p['id'] . '/deactivate') ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this product?');">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-outline" style="padding:4px 10px; font-size:0.8rem; border-color: var(--danger); color: var(--danger);">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:16px; border-top:1px solid var(--border-color);">
                <span style="color: var(--text-muted); font-size:0.85rem;">Showing page <?= $page ?> of <?= $totalPages ?> (Total <?= $total ?> products)</span>
                <div style="display:flex; gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= \App\Helpers\Url::base('admin/products?page=' . ($page - 1) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline" style="padding:4px 12px; font-size:0.85rem;">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= \App\Helpers\Url::base('admin/products?page=' . ($page + 1) . (!empty($search) ? '&q=' . urlencode($search) : '')) ?>" class="btn btn-outline" style="padding:4px 12px; font-size:0.85rem;">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
