<div style="max-width: 650px; margin: 0 auto;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 1.8rem; font-weight: 700;">👤 Account Profile</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Manage your customer details and account information</p>
    </div>

    <div class="card">
        <div style="display:flex; align-items:center; gap:16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-color);">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--accent-light); border: 2px solid var(--accent); display:flex; align-items:center; justify-content:center; font-size: 1.5rem;">
                👤
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 700;"><?= \App\Helpers\Url::escape($user['name']) ?></h2>
                <div style="font-family: monospace; color: var(--text-muted); font-size: 0.9rem;"><?= \App\Helpers\Url::escape($user['email']) ?></div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: var(--bg-primary); padding: 14px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Account Role</div>
                <div style="font-weight: 700; margin-top: 4px; color: var(--accent); text-transform: capitalize;"><?= \App\Helpers\Url::escape($user['role']) ?></div>
            </div>
            <div style="background: var(--bg-primary); padding: 14px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Account Status</div>
                <div style="margin-top: 4px;">
                    <?php if ($user['is_active']): ?>
                        <span class="status-badge status-online">Active</span>
                    <?php else: ?>
                        <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="background: var(--bg-primary); padding: 14px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Member Since</div>
                <div style="font-weight: 600; margin-top: 4px; font-size: 0.9rem;"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <a href="<?= \App\Helpers\Url::base('products') ?>" class="btn btn-primary">Browse Product Catalog</a>
            <form action="<?= \App\Helpers\Url::base('logout') ?>" method="POST">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">Logout</button>
            </form>
        </div>
    </div>
</div>
