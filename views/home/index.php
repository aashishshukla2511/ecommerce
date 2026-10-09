<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1 class="card-title">System Health & MVC Status</h1>
        <span class="status-badge status-online">
            <span class="status-dot"></span> System Operational
        </span>
    </div>

    <p style="color: var(--text-muted); margin-bottom: 24px;">
        Welcome to the PHP 8 custom MVC product, inventory, and order management application.
    </p>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: var(--bg-primary); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">PHP Version</div>
            <div style="font-size: 1.2rem; font-weight: 700; margin-top: 4px; color: var(--accent);"><?= \App\Helpers\Url::escape(PHP_VERSION) ?></div>
        </div>
        <div style="background: var(--bg-primary); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Database</div>
            <div style="font-size: 1.2rem; font-weight: 700; margin-top: 4px; color: var(--success);"><?= \App\Helpers\Url::escape($dbStatus) ?></div>
        </div>
        <div style="background: var(--bg-primary); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color);">
            <div style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Environment</div>
            <div style="font-size: 1.2rem; font-weight: 700; margin-top: 4px; color: var(--warning);"><?= \App\Helpers\Url::escape($_ENV['APP_ENV'] ?? 'unknown') ?></div>
        </div>
    </div>

    <div style="background: var(--accent-light); border: 1px solid var(--accent); border-radius: var(--radius); padding: 16px;">
        <h3 style="color: #fff; margin-bottom: 8px;">Phase 0 Infrastructure Active</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted);">
            Core MVC, Router, CSRF protection, Output escaping, Session management, and PDO Database connection initialized cleanly.
        </p>
    </div>
</div>
