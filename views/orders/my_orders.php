<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 700;">📦 My Order History</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">View your past orders, status, and item breakdowns</p>
    </div>
    <div>
        <a href="<?= \App\Helpers\Url::base('orders/create') ?>" class="btn btn-primary">
            + Place New Order
        </a>
    </div>
</div>

<div class="card" style="padding: 20px;">
    <?php if (empty($orders)): ?>
        <div style="text-align:center; padding: 50px 20px; color: var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 12px;">📭</div>
            <h3>You haven't placed any orders yet</h3>
            <p style="font-size: 0.9rem; margin-top: 6px; margin-bottom: 20px;">Place your first order to get started.</p>
            <a href="<?= \App\Helpers\Url::base('orders/create') ?>" class="btn btn-primary">+ Place Order</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width:100%; border-collapse: collapse; text-align:left; font-size:0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                        <th style="padding: 12px 16px;">Order Number</th>
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
                                <a href="<?= \App\Helpers\Url::base('orders/' . $order['id']) ?>">
                                    <?= \App\Helpers\Url::escape($order['order_number']) ?>
                                </a>
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
                            <td style="padding: 14px 16px; text-align:right; white-space:nowrap; display:flex; gap: 6px; justify-content: flex-end;">
                                <a href="<?= \App\Helpers\Url::base('orders/' . $order['id']) ?>" class="btn btn-outline" style="padding: 5px 12px; font-size: 0.85rem;">View Details</a>
                                <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
                                    <form method="POST" action="<?= \App\Helpers\Url::base('orders/' . $order['id'] . '/cancel') ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel Order #<?= $order['id'] ?>? Stock will be restored.');">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 12px; font-size: 0.85rem; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);">Cancel</button>
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
                <span style="color: var(--text-muted); font-size:0.85rem;">Showing page <?= $page ?> of <?= $totalPages ?> (Total <?= $total ?> orders)</span>
                <div style="display:flex; gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= \App\Helpers\Url::base('my-orders?page=' . ($page - 1)) ?>" class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= \App\Helpers\Url::base('my-orders?page=' . ($page + 1)) ?>" class="btn btn-outline">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
