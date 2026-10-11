<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 700;">📑 Customer Orders Management</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">View, filter, search, and inspect customer orders</p>
    </div>
</div>

<!-- Filter Controls Form -->
<div class="card" style="padding: 20px; margin-bottom: 24px;">
    <form method="GET" action="<?= \App\Helpers\Url::base('admin/orders') ?>" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items:end;">
        <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Status</label>
            <select name="status" style="width: 100%; padding: 9px 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
                <option value="all" <?= ($filters['status'] === 'all') ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= ($filters['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= ($filters['status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                <option value="completed" <?= ($filters['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= ($filters['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">Search Query</label>
            <input type="text" name="q" value="<?= \App\Helpers\Url::escape($filters['search']) ?>" placeholder="Order #, ID, Name or Email"
                   style="width: 100%; padding: 9px 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">From Date</label>
            <input type="date" name="from" value="<?= \App\Helpers\Url::escape($filters['from']) ?>"
                   style="width: 100%; padding: 9px 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
        </div>

        <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase;">To Date</label>
            <input type="date" name="to" value="<?= \App\Helpers\Url::escape($filters['to']) ?>"
                   style="width: 100%; padding: 9px 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
        </div>

        <div style="display:flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex:1; padding: 10px;">Apply Filter</button>
            <a href="<?= \App\Helpers\Url::base('admin/orders') ?>" class="btn btn-outline" style="padding: 10px 14px; color: var(--text-muted);">Reset</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card" style="padding: 20px;">
    <?php if (empty($orders)): ?>
        <div style="text-align:center; padding: 40px 20px; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 12px;">📭</div>
            <h3>No orders match your filter criteria</h3>
            <p style="font-size: 0.9rem; margin-top: 4px;">Try clearing search keywords or expanding your date range.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse: collapse; text-align:left; font-size:0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                        <th style="padding: 12px 16px;">Order ID / Number</th>
                        <th style="padding: 12px 16px;">Customer Details</th>
                        <th style="padding: 12px 16px;">Date Placed</th>
                        <th style="padding: 12px 16px;">Status</th>
                        <th style="padding: 12px 16px;">Total Amount</th>
                        <th style="padding: 12px 16px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;">
                            <td style="padding: 14px 16px; font-family: monospace; color: var(--accent); font-weight: 700;">
                                <a href="<?= \App\Helpers\Url::base('admin/orders/' . $order['id']) ?>">
                                    #<?= $order['id'] ?> - <?= \App\Helpers\Url::escape($order['order_number']) ?>
                                </a>
                            </td>
                            <td style="padding: 14px 16px;">
                                <div style="font-weight: 600; color: var(--text-main);"><?= \App\Helpers\Url::escape($order['customer_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;"><?= \App\Helpers\Url::escape($order['customer_email']) ?></div>
                            </td>
                            <td style="padding: 14px 16px; color: var(--text-muted);">
                                <?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?>
                            </td>
                            <td style="padding: 14px 16px;">
                                <?php
                                    $status = strtolower($order['status']);
                                    $badgeStyle = match($status) {
                                        'pending' => 'background: rgba(245, 158, 11, 0.2); color: #fbbf24;',
                                        'confirmed' => 'background: rgba(59, 130, 246, 0.2); color: #60a5fa;',
                                        'completed' => 'background: rgba(16, 185, 129, 0.2); color: #34d399;',
                                        'cancelled' => 'background: rgba(239, 68, 68, 0.2); color: #f87171;',
                                        default => 'background: var(--bg-primary); color: var(--text-muted);'
                                    };
                                ?>
                                <span class="status-badge" style="<?= $badgeStyle ?>">
                                    <?= ucfirst(\App\Helpers\Url::escape($order['status'])) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 16px; font-weight: 700; color: #34d399;">
                                $<?= number_format((float)$order['total_amount'], 2) ?>
                            </td>
                            <td style="padding: 14px 16px; text-align:right; white-space:nowrap;">
                                <a href="<?= \App\Helpers\Url::base('admin/orders/' . $order['id']) ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.85rem;">View Order</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
            // Build pagination query string preserving active filters
            $queryParams = array_filter([
                'status' => $filters['status'] !== 'all' ? $filters['status'] : null,
                'q' => !empty($filters['search']) ? $filters['search'] : null,
                'from' => !empty($filters['from']) ? $filters['from'] : null,
                'to' => !empty($filters['to']) ? $filters['to'] : null,
            ]);
            $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
        ?>

        <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:16px; border-top:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <span style="color: var(--text-muted); font-size:0.85rem;">Showing page <?= $page ?> of <?= $totalPages ?> (Total <?= $total ?> orders)</span>
                <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                    <?php
                        $range = 2;
                        $pagesToShow = [];
                        for ($i = 1; $i <= $totalPages; $i++) {
                            if ($i === 1 || $i === $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                                $pagesToShow[] = $i;
                            } elseif (end($pagesToShow) !== '...') {
                                $pagesToShow[] = '...';
                            }
                        }
                    ?>
                    <?php foreach ($pagesToShow as $p): ?>
                        <?php if ($p === '...'): ?>
                            <span style="color: var(--text-muted); padding: 4px 6px; font-size: 0.85rem;">...</span>
                        <?php elseif ($p === $page): ?>
                            <span class="btn btn-primary" style="padding: 6px 12px; min-width: 36px; text-align:center; font-size: 0.85rem; font-weight: 700; cursor: default; background: var(--accent); border-color: var(--accent); color: #fff;">
                                <?= $p ?>
                            </span>
                        <?php else: ?>
                            <a href="<?= \App\Helpers\Url::base('admin/orders?page=' . $p . $queryString) ?>" class="btn btn-outline" style="padding: 6px 12px; min-width: 36px; text-align:center; font-size: 0.85rem;">
                                <?= $p ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
