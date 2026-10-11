<div style="max-width: 900px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= \App\Helpers\Url::base('admin/orders') ?>" style="color: var(--text-muted); font-size: 0.9rem;">← Back to All Customer Orders</a>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 8px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 700;">Admin Order Inspection</h1>
                <div style="font-family: monospace; font-size: 1.1rem; color: var(--accent); font-weight: 700; margin-top:4px;">
                    Order #<?= $order['id'] ?> - <?= \App\Helpers\Url::escape($order['order_number']) ?>
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
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Customer Information</h3>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Customer Name</div>
                <div style="font-weight: 700; margin-top: 4px;"><?= \App\Helpers\Url::escape($order['customer_name']) ?></div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Customer Email</div>
                <div style="font-family: monospace; font-size: 0.9rem; margin-top: 4px; color: var(--accent);"><?= \App\Helpers\Url::escape($order['customer_email']) ?></div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Customer ID</div>
                <div style="font-family: monospace; font-size: 0.9rem; margin-top: 4px;">User #<?= $order['user_id'] ?></div>
            </div>
            <div>
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Date Placed</div>
                <div style="font-weight: 600; margin-top: 4px; font-size: 0.9rem;"><?= date('M d, Y - h:i A', strtotime($order['created_at'])) ?></div>
            </div>
        </div>

        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Purchased Product Line Snapshots</h3>
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

        <div style="display:flex; justify-content:flex-end; padding-top: 16px; border-top: 2px solid var(--border-color);">
            <div style="text-align:right;">
                <span style="color: var(--text-muted); font-size: 0.95rem; margin-right: 12px;">Order Total Amount:</span>
                <span style="font-size: 1.6rem; font-weight: 800; color: #34d399;">$<?= number_format((float)$order['total_amount'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Admin Order Status Update Panel -->
    <div class="card" style="margin-top: 24px; padding: 20px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; display:flex; align-items:center; gap:8px;">
            <span>⚙️ Update Order Status</span>
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">
            Change the order processing state. Setting status to <strong>Cancelled</strong> will automatically restore stock inventory to products.
        </p>

        <?php if ($status === 'cancelled'): ?>
            <div style="padding: 12px 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; border-radius: 8px; font-size: 0.9rem;">
                ⚠️ This order is <strong>Cancelled</strong> (cancelled at <?= !empty($order['cancelled_at']) ? date('M d, Y - h:i A', strtotime($order['cancelled_at'])) : 'N/A' ?>). Stock inventory has been restored. Status cannot be modified further.
            </div>
        <?php else: ?>
            <form method="POST" action="<?= \App\Helpers\Url::base('admin/orders/' . $order['id'] . '/status') ?>" style="display:flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <?= \App\Core\Csrf::field() ?>
                <select name="status" style="padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px; font-weight: 600; min-width: 180px;">
                    <option value="pending" <?= ($status === 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= ($status === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                    <option value="completed" <?= ($status === 'completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled">Cancelled (Restore Stock)</option>
                </select>

                <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Save Status Update</button>
            </form>
        <?php endif; ?>
    </div>
</div>
