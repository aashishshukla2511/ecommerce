<div style="max-width: 850px; margin: 0 auto;">
        <div style="display:flex; gap: 16px; align-items:center; font-size: 0.9rem;">
            <a href="<?= \App\Helpers\Url::base('my-orders') ?>" class="btn btn-outline" style="padding: 4px 12px; font-size: 0.85rem;">← Back to My Orders</a>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 8px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 700;">Order Details</h1>
                <div style="font-family: monospace; font-size: 1.1rem; color: var(--accent); font-weight: 700; margin-top:4px;">
                    <?= \App\Helpers\Url::escape($order['order_number']) ?>
                </div>
            </div>
            <div>
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
                <span class="status-badge" style="<?= $badgeStyle ?> font-size: 0.95rem; padding: 6px 16px;">
                    <span class="status-dot"></span> Status: <?= strtoupper(\App\Helpers\Url::escape($order['status'])) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Customer Name</div>
                <div style="font-weight: 700; margin-top: 4px;"><?= \App\Helpers\Url::escape($order['customer_name']) ?></div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Customer Email</div>
                <div style="font-family: monospace; font-size: 0.9rem; margin-top: 4px; color: var(--text-muted);"><?= \App\Helpers\Url::escape($order['customer_email']) ?></div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Date Placed</div>
                <div style="font-weight: 600; margin-top: 4px; font-size: 0.9rem;"><?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?></div>
            </div>
        </div>

        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Order Items Breakdown</h3>
        <div style="overflow-x: auto; margin-bottom: 24px;">
            <table style="width:100%; border-collapse: collapse; text-align:left; font-size:0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                        <th style="padding: 12px;">Product Snapshot</th>
                        <th style="padding: 12px;">SKU</th>
                        <th style="padding: 12px;">Unit Price</th>
                        <th style="padding: 12px;">Quantity</th>
                        <th style="padding: 12px; text-align:right;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 14px 12px; font-weight: 600;">
                                <?= \App\Helpers\Url::escape($item['product_name']) ?>
                            </td>
                            <td style="padding: 14px 12px; font-family: monospace; color: var(--accent);">
                                <?= \App\Helpers\Url::escape($item['sku']) ?>
                            </td>
                            <td style="padding: 14px 12px;">
                                $<?= number_format((float)$item['unit_price'], 2) ?>
                            </td>
                            <td style="padding: 14px 12px; font-weight: 600;">
                                <?= $item['quantity'] ?>
                            </td>
                            <td style="padding: 14px 12px; text-align:right; font-weight: 700; color: #34d399;">
                                $<?= number_format((float)$item['line_total'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; padding-top: 16px; border-top: 2px solid var(--border-color); flex-wrap:wrap; gap:16px;">
            <div>
                <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
                    <form method="POST" action="<?= \App\Helpers\Url::base('orders/' . $order['id'] . '/cancel') ?>" onsubmit="return confirm('Are you sure you want to cancel Order #<?= $order['id'] ?>? Item stock will be restored.');">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn-danger" style="padding: 8px 18px; font-size: 0.9rem; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);">
                            ❌ Cancel This Order
                        </button>
                    </form>
                <?php elseif ($status === 'cancelled'): ?>
                    <span style="color: #f87171; font-size: 0.85rem; font-weight: 600;">
                        ⚠️ Order was cancelled on <?= !empty($order['cancelled_at']) ? date('M d, Y - h:i A', strtotime($order['cancelled_at'])) : 'N/A' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <span style="color: var(--text-muted); font-size: 0.95rem; margin-right: 12px;">Grand Total:</span>
                <span style="font-size: 1.6rem; font-weight: 800; color: #34d399;">$<?= number_format((float)$order['total_amount'], 2) ?></span>
            </div>
        </div>
    </div>
</div>
