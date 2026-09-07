<section class="auth-shell">
    <article class="auth-card">
        <span class="eyebrow">Create account</span>
        <div class="auth-title-row">
            <img src="<?= htmlspecialchars(app_root_url('sandworth-icon.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Sandworth Homes icon" class="auth-title-icon">
            <h1>Join Sandworth Homes</h1>
        </div>
        <!-- <p>Search, apply, pay digitally, and manage your tenancy records — all in one place.</p> -->
         <p>Create your account to get started.</p>

        <form action="<?= htmlspecialchars(app_url('register-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="filter-form" style="margin-top:24px">
            <div>
                <label for="register-name">Full name</label>
                <input id="register-name" name="name" type="text" placeholder="John Adeyemi" required>
            </div>

            <div>
                <label for="register-email">Email address</label>
                <input id="register-email" name="email" type="email" placeholder="you@example.com" required>
            </div>

            <div>
                <label for="register-phone">Phone number</label>
                <input id="register-phone" name="phone" type="text" placeholder="+234 800 000 0000" required>
            </div>

            <div>
                <label for="register-password">Password</label>
                <input id="register-password" name="password" type="password" placeholder="Minimum 8 characters" required>
            </div>

            <button type="submit" class="solid-button wide" style="margin-top:8px; padding:14px; font-size:.95rem">
                Create my account
            </button>
        </form>

        <p style="font-size:.75rem; color:var(--muted); text-align:center; margin-top:14px; line-height:1.6">
            By creating an account you agree to our <a class="text-link" href="#">Terms of Use</a> and <a class="text-link" href="#">Privacy Policy</a>.
        </p>

        <p class="muted-text" style="text-align:center; margin-top:14px; font-size:.875rem">
            Already have an account? <a class="text-link" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in</a>
        </p>
    </article>
</section>
