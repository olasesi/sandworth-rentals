<?php

/** @var string $viewPath */
/** @var string $pageTitle */
/** @var string $activePage */
/** @var array|null $currentUser */
/** @var array $flashMessages */

$pageTitle = isset($pageTitle) ? $pageTitle : 'Sandworth Homes';
$activePage = isset($activePage) ? $activePage : 'home';
$currentUser = isset($currentUser) ? $currentUser : null;
$flashMessages = isset($flashMessages) ? $flashMessages : array();
$manageHref = $currentUser && isset($currentUser['role']) && $currentUser['role'] === 'admin'
    ? app_url('admin')
    : app_url('manager');
$brandLogo = 'sandworth.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="public/assets/css/app.css">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">
            <img
                class="brand-logo"
                src="<?= htmlspecialchars($brandLogo, ENT_QUOTES, 'UTF-8') ?>"
                alt="Sandworth Homes"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            >
            <span style="display:none; color:#fff; font-weight:800; font-size:1.15rem; letter-spacing:-.02em; font-family:'Fraunces',Georgia,serif;">Sandworth Homes</span>
        </a>

        <nav class="topnav" aria-label="Main navigation">
            <a class="<?= $activePage === 'home' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">Home</a>
            <a class="<?= $activePage === 'plan' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Plan</a>
            <a class="<?= $activePage === 'homes' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Buy</a>
            <a class="<?= $activePage === 'rentals' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Rent</a>
            <a class="<?= $activePage === 'commercial' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Malls &amp; Shops</a>
            <a class="<?= $activePage === 'manager' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($manageHref, ENT_QUOTES, 'UTF-8') ?>">Manage</a>
        </nav>

        <div class="topbar-actions">
            <?php if ($currentUser): ?>
                <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Admin console</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-tours'), ENT_QUOTES, 'UTF-8') ?>">Tours</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin-messages'), ENT_QUOTES, 'UTF-8') ?>">Messages<?php if (! empty($adminUnread)): ?> (<?= (int) $adminUnread ?>)<?php endif; ?></a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('owner'), ENT_QUOTES, 'UTF-8') ?>">Report</a>
                <?php else: ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('saved'), ENT_QUOTES, 'UTF-8') ?>">Saved</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('tours'), ENT_QUOTES, 'UTF-8') ?>">Tours</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('offers'), ENT_QUOTES, 'UTF-8') ?>">Offers</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('messages'), ENT_QUOTES, 'UTF-8') ?>">Messages<?php if (! empty($renterUnread)): ?> (<?= (int) $renterUnread ?>)<?php endif; ?></a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('history'), ENT_QUOTES, 'UTF-8') ?>">History</a>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Tenancy</a>
                <?php endif; ?>
                <form action="<?= htmlspecialchars(app_url('logout-submit'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                    <button type="submit" class="solid-button">Sign out</button>
                </form>
            <?php else: ?>
                <a class="ghost-button" href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in</a>
                <a class="solid-button" href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Get started</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($flashMessages !== array()): ?>
        <section class="flash-stack">
            <?php foreach ($flashMessages as $message): ?>
                <article class="flash flash-<?= htmlspecialchars((string) $message['type'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $message['message'], ENT_QUOTES, 'UTF-8') ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <div class="site-shell">
        <main class="page-content">
            <?php require $viewPath; ?>
        </main>
    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-brand">
                    <img
                        src="<?= htmlspecialchars($brandLogo, ENT_QUOTES, 'UTF-8') ?>"
                        alt="Sandworth Homes"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                    >
                    <span class="footer-brand-name">Sand<em>worth</em> Homes</span>
                    <p>Helping Nigerians find the right home, rental, and commercial space with a trusted property platform.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook">Fb</a>
                        <a href="#" aria-label="Instagram">Ig</a>
                        <a href="#" aria-label="Twitter / X">X</a>
                        <a href="#" aria-label="LinkedIn">Li</a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Homes for Sale</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Rental Properties</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Commercial Spaces</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Buying Guide</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('city-rentals', array('market' => 'Lekki')), ENT_QUOTES, 'UTF-8') ?>">Lekki Rentals</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('city-rentals', array('market' => 'Ikeja')), ENT_QUOTES, 'UTF-8') ?>">Ikeja Rentals</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="<?= htmlspecialchars(app_url('register'), ENT_QUOTES, 'UTF-8') ?>">Create account</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('login'), ENT_QUOTES, 'UTF-8') ?>">Sign in</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">My dashboard</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Tenancy records</a></li>
                        <li><a href="<?= htmlspecialchars(app_url('manager'), ENT_QUOTES, 'UTF-8') ?>">Landlord portal</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="mailto:hello@sandworthliving.ng">hello@sandworthliving.ng</a></li>
                        <li><a href="tel:+2348000000000">+234 800 000 0000</a></li>
                        <li><a href="#">27 Admiralty Way, Lekki Phase 1, Lagos</a></li>
                        <li><a href="#" style="color:rgba(255,255,255,.38); font-size:.8rem; cursor:default">Mon - Sat 8 am - 6 pm</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Sandworth Homes. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Use</a>
                    <a href="#">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="public/assets/js/app.js"></script>
    <script src="public/assets/js/map.js"></script>
</body>
</html>
