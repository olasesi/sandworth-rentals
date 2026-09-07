<section class="auth-shell">
    <article class="auth-card">
        <span class="eyebrow">Welcome back</span>
        <h1>Sign in to your account</h1>
        <p>Access your renter dashboard, applications, and tenancy records.</p>

        <form action="<?= htmlspecialchars(app_url('login-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form" style="margin-top:24px">
            <div>
                <label for="login-email">Email address</label>
                <input id="login-email" name="email" type="email" placeholder="you@example.com" required>
            </div>

            <div>
                <label for="login-password">Password</label>
                <input id="login-password" name="password" type="password" placeholder="Your password" required>
            </div>

            <button type="submit" class="solid-button wide" style="margin-top:8px; padding:14px; font-size:.95rem">
                Sign in to Sandworth
            </button>
        </form>

        <div class="detail-card compact-card" style="margin-top:20px; background:var(--bg); border:1px dashed var(--line)">
            <strong style="font-size:.8rem; color:var(--muted); text-transform:uppercase; letter-spacing:.07em">Demo access</strong>
            <p style="margin:6px 0 2px; font-size:.82rem">Email: <code>admin@sandworthliving.test</code></p>
            <p style="margin:0; font-size:.82rem">Password: <code>Admin123!</code></p>
        </div>

        <p class="muted-text" style="text-align:center; margin-top:18px; font-size:.875rem">
            Don't have an account? <a class="text-link" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create one free</a>
        </p>
    </article>
</section>
