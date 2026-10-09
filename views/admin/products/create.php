<div style="max-width: 700px; margin: 0 auto;">
    <div style="margin-bottom: 20px;">
        <a href="<?= \App\Helpers\Url::base('admin/products') ?>" style="color: var(--text-muted); font-size: 0.9rem;">← Back to Products List</a>
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-top: 8px;">Add New Product</h1>
    </div>

    <div class="card">
        <form action="<?= \App\Helpers\Url::base('admin/products') ?>" method="POST">
            <?= \App\Core\Csrf::field() ?>

            <div style="margin-bottom: 20px;">
                <label style="display:block; font-weight: 600; margin-bottom: 6px;">Product Name <span style="color:var(--danger)">*</span></label>
                <input type="text" name="name" value="<?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('name')) ?>" required maxlength="180" placeholder="e.g. Wireless Noise-Canceling Headphones"
                       style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
                <?php if (!empty($errors['name'])): ?>
                    <div style="color: var(--danger); font-size: 0.85rem; margin-top: 4px;"><?= \App\Helpers\Url::escape($errors['name'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; font-weight: 600; margin-bottom: 6px;">SKU <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="sku" value="<?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('sku')) ?>" required maxlength="80" placeholder="e.g. PROD-HEAD-001"
                           style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px; font-family: monospace;">
                    <?php if (!empty($errors['sku'])): ?>
                        <div style="color: var(--danger); font-size: 0.85rem; margin-top: 4px;"><?= \App\Helpers\Url::escape($errors['sku'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label style="display:block; font-weight: 600; margin-bottom: 6px;">Price ($) <span style="color:var(--danger)">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" value="<?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('price')) ?>" required placeholder="0.00"
                           style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
                    <?php if (!empty($errors['price'])): ?>
                        <div style="color: var(--danger); font-size: 0.85rem; margin-top: 4px;"><?= \App\Helpers\Url::escape($errors['price'][0]) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; font-weight: 600; margin-bottom: 6px;">Stock Quantity <span style="color:var(--danger)">*</span></label>
                    <input type="number" min="0" name="stock_quantity" value="<?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('stock_quantity', '0')) ?>" required placeholder="0"
                           style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
                    <?php if (!empty($errors['stock_quantity'])): ?>
                        <div style="color: var(--danger); font-size: 0.85rem; margin-top: 4px;"><?= \App\Helpers\Url::escape($errors['stock_quantity'][0]) ?></div>
                    <?php endif; ?>
                </div>

                <div style="display:flex; align-items: center; margin-top: 24px;">
                    <label style="display:flex; align-items:center; gap: 8px; cursor:pointer; font-weight: 600;">
                        <input type="checkbox" name="is_active" value="1" <?= \App\Helpers\Flash::old('is_active', '1') === '1' ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        Active & Available in Catalog
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display:block; font-weight: 600; margin-bottom: 6px;">Description (Optional)</label>
                <textarea name="description" rows="4" placeholder="Detailed product description..."
                          style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px; resize:vertical;"><?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('description')) ?></textarea>
                <?php if (!empty($errors['description'])): ?>
                    <div style="color: var(--danger); font-size: 0.85rem; margin-top: 4px;"><?= \App\Helpers\Url::escape($errors['description'][0]) ?></div>
                <?php endif; ?>
            </div>

            <div style="display:flex; justify-content:flex-end; gap: 12px;">
                <a href="<?= \App\Helpers\Url::base('admin/products') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>
