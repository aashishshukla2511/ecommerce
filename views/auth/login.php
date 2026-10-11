<div style="max-width: 440px; margin: 40px auto;">
    <div class="card">
        <h1 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; text-align:center;">Account Login</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; text-align:center; margin-bottom: 24px;">
            Sign in to access admin product management or your customer account
        </p>

        <form action="<?= \App\Helpers\Url::base('login') ?>" method="POST">
            <?= \App\Core\Csrf::field() ?>

            <div style="margin-bottom: 18px;">
                <label style="display:block; font-weight: 600; margin-bottom: 6px;">Email Address</label>
                <input type="email" name="email" value="<?= \App\Helpers\Url::escape(\App\Helpers\Flash::old('email')) ?>" required placeholder="customer@example.com"
                       style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display:block; font-weight: 600; margin-bottom: 6px;">Password</label>
                <input type="password" name="password" required placeholder="••••••••"
                       style="width: 100%; padding: 10px 14px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 8px;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Sign In</button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
            <div style="font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Demo Accounts:</div>
            <div style="margin-bottom: 4px;">👑 <strong>Admin:</strong> admin@example.com / password123</div>
            <div style="margin-bottom: 4px;">🛍️ <strong>Customer 1:</strong> customer@example.com / password123</div>
            <div>🛍️ <strong>Customer 2:</strong> john@example.com / password123</div>
        </div>
    </div>
</div>
