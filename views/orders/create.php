<div style="max-width: 950px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 1.8rem; font-weight: 700;">🛒 Place a New Order</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Select the products you wish to order by checking the box or entering a quantity</p>
    </div>

    <form action="<?= \App\Helpers\Url::base('orders') ?>" method="POST" id="orderForm">
        <?= \App\Core\Csrf::field() ?>

        <div class="card" style="padding: 20px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Available Products Catalog</h3>

            <?php if (empty($products)): ?>
                <div style="text-align:center; padding: 30px; color: var(--text-muted);">
                    <p>No active products are available for ordering right now.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse: collapse; text-align:left; font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">
                                <th style="padding: 12px; width: 60px; text-align:center;">Select</th>
                                <th style="padding: 12px;">Product Name</th>
                                <th style="padding: 12px;">SKU</th>
                                <th style="padding: 12px;">Unit Price</th>
                                <th style="padding: 12px;">Stock Status</th>
                                <th style="padding: 12px; width: 110px;">Quantity</th>
                                <th style="padding: 12px; text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $index => $p): ?>
                                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;" id="row-<?= $index ?>">
                                    <td style="padding: 14px 12px; text-align:center;">
                                        <input type="checkbox" name="items[<?= $index ?>][selected]" value="1" 
                                               class="product-select" data-index="<?= $index ?>" 
                                               <?= $p['stock_quantity'] == 0 ? 'disabled' : '' ?>
                                               style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td style="padding: 14px 12px; font-weight: 600;">
                                        <?= \App\Helpers\Url::escape($p['name']) ?>
                                    </td>
                                    <td style="padding: 14px 12px; font-family: monospace; color: var(--accent);">
                                        <?= \App\Helpers\Url::escape($p['sku']) ?>
                                    </td>
                                    <td style="padding: 14px 12px; font-weight: 700; color: #34d399;">
                                        $<span class="item-price" data-price="<?= $p['price'] ?>"><?= number_format((float)$p['price'], 2) ?></span>
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <?php if ($p['stock_quantity'] > 0): ?>
                                            <span style="color: #34d399; font-weight:600;"><?= $p['stock_quantity'] ?> available</span>
                                        <?php else: ?>
                                            <span style="color: #f87171; font-weight:600;">Out of stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $p['id'] ?>">
                                        <input type="number" name="items[<?= $index ?>][quantity]" value="0" min="0" max="<?= $p['stock_quantity'] ?>" 
                                               class="qty-input" data-index="<?= $index ?>" data-price="<?= $p['price'] ?>" 
                                               <?= $p['stock_quantity'] == 0 ? 'disabled' : '' ?>
                                               style="width: 80px; padding: 6px 10px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px;">
                                    </td>
                                    <td style="padding: 14px 12px; text-align:right; font-weight: 700; color: var(--text-main);" id="subtotal-<?= $index ?>">
                                        $0.00
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.95rem;">Total Order Amount:</span>
                        <span id="grandTotal" style="font-size: 1.6rem; font-weight: 800; color: #34d399; margin-left: 8px;">$0.00</span>
                    </div>
                    <div style="display:flex; gap: 12px;">
                        <a href="<?= \App\Helpers\Url::base('products') ?>" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-size: 1rem;">Submit Order</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.product-select');
    const qtyInputs = document.querySelectorAll('.qty-input');
    const grandTotalEl = document.getElementById('grandTotal');

    function updateRowAndTotals() {
        let grandTotal = 0;

        qtyInputs.forEach(input => {
            const index = input.dataset.index;
            const checkbox = document.querySelector(`.product-select[data-index="${index}"]`);
            const subtotalEl = document.getElementById(`subtotal-${index}`);
            const row = document.getElementById(`row-${index}`);
            const price = parseFloat(input.dataset.price) || 0;
            let qty = parseInt(input.value) || 0;

            if (checkbox && checkbox.checked && qty === 0) {
                qty = 1;
                input.value = 1;
            } else if (checkbox && !checkbox.checked && qty > 0) {
                // If quantity typed, auto-check checkbox
                checkbox.checked = true;
            }

            if (checkbox && !checkbox.checked) {
                qty = 0;
                input.value = 0;
            }

            const subtotal = qty * price;
            if (subtotalEl) {
                subtotalEl.textContent = '$' + subtotal.toFixed(2);
                subtotalEl.style.color = qty > 0 ? '#34d399' : 'var(--text-muted)';
            }

            if (row) {
                row.style.background = qty > 0 ? 'rgba(99, 102, 241, 0.08)' : 'transparent';
            }

            if (qty > 0) {
                grandTotal += subtotal;
            }
        });

        grandTotalEl.textContent = '$' + grandTotal.toFixed(2);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const index = this.dataset.index;
            const input = document.querySelector(`.qty-input[data-index="${index}"]`);
            if (this.checked && (parseInt(input.value) || 0) === 0) {
                input.value = 1;
            } else if (!this.checked) {
                input.value = 0;
            }
            updateRowAndTotals();
        });
    });

    qtyInputs.forEach(input => {
        input.addEventListener('input', function() {
            const index = this.dataset.index;
            const checkbox = document.querySelector(`.product-select[data-index="${index}"]`);
            const qty = parseInt(this.value) || 0;

            if (qty > 0 && checkbox) {
                checkbox.checked = true;
            } else if (qty <= 0 && checkbox) {
                checkbox.checked = false;
            }
            updateRowAndTotals();
        });
    });
});
</script>
