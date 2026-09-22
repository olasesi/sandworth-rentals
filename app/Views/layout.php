<?php

/** @var string $viewPath */
/** @var string $pageTitle */
/** @var string $activePage */
/** @var array|null $currentUser */
/** @var array|null $currentTenancy */
/** @var array $flashMessages */
/** @var array $siteSettings */

$pageTitle = isset($pageTitle) ? $pageTitle : 'Sandworth Homes';
$activePage = isset($activePage) ? $activePage : 'home';
$routeName = isset($routeName) ? (string) $routeName : 'home';
$currentUser = isset($currentUser) ? $currentUser : null;
$currentTenancy = isset($currentTenancy) ? $currentTenancy : null;
$flashMessages = isset($flashMessages) ? $flashMessages : array();
$siteSettings = isset($siteSettings) && is_array($siteSettings) ? $siteSettings : array();
$viewerIsAdmin = $currentUser && isset($currentUser['role']) && $currentUser['role'] === 'admin';
$viewerHasTenancy = is_array($currentTenancy) && isset($currentTenancy['id']) && (int) $currentTenancy['id'] > 0;
$manageHref = $viewerIsAdmin ? app_url('admin') : app_url('tenancy');
$brandLogo = app_root_url('sandworth.png');
$faviconPath = app_root_url('sandworth-icon.png');
$defaultShareImagePath = app_asset_url('img/Arepo-II4-1024x576.jpg');
$content = isset($content) && is_array($content) ? $content : array();
$listing = isset($listing) && is_array($listing) ? $listing : null;
$listings = isset($listings) && is_array($listings) ? $listings : array();
$marketName = isset($marketName) ? (string) $marketName : '';
$seo = isset($seo) && is_array($seo) ? $seo : array();
$siteName = isset($siteSettings['siteName']) && trim((string) $siteSettings['siteName']) !== '' ? (string) $siteSettings['siteName'] : 'Sandworth Homes';
$defaultMetaTitle = isset($siteSettings['defaultMetaTitle']) ? (string) $siteSettings['defaultMetaTitle'] : $pageTitle;
$defaultMetaDescription = isset($siteSettings['defaultMetaDescription']) ? (string) $siteSettings['defaultMetaDescription'] : '';
$defaultShareImage = isset($siteSettings['defaultShareImage']) && trim((string) $siteSettings['defaultShareImage']) !== ''
    ? (string) $siteSettings['defaultShareImage']
    : $defaultShareImagePath;
$contactEmail = isset($siteSettings['contactEmail']) && trim((string) $siteSettings['contactEmail']) !== ''
    ? (string) $siteSettings['contactEmail']
    : 'info@sandworthliving.ng';
$contactPhone = isset($siteSettings['contactPhone']) && trim((string) $siteSettings['contactPhone']) !== ''
    ? (string) $siteSettings['contactPhone']
    : '+234 803 437 1916';
$operationalOffice = isset($siteSettings['operationalOffice']) ? (string) $siteSettings['operationalOffice'] : 'The Facility Management Office, The Nigeria Army Shopping Complex (The Arena), Bolade-Oshodi, 101233, Lagos State, Nigeria.';
$registeredOffice = isset($siteSettings['registeredOffice']) ? (string) $siteSettings['registeredOffice'] : '1, Tafawa Balewa Crescent, off Adeniran Ogunsanya, Surulere, Lagos State, Nigeria.';
$facebookUrl = isset($siteSettings['facebookUrl']) ? (string) $siteSettings['facebookUrl'] : '';
$instagramUrl = isset($siteSettings['instagramUrl']) ? (string) $siteSettings['instagramUrl'] : '';
$xUrl = isset($siteSettings['xUrl']) ? (string) $siteSettings['xUrl'] : '';
$linkedinUrl = isset($siteSettings['linkedinUrl']) ? (string) $siteSettings['linkedinUrl'] : '';
$twitterHandle = isset($siteSettings['twitterHandle']) ? (string) $siteSettings['twitterHandle'] : '';
$publicRoutes = array('home', 'plan', 'homes', 'commercial', 'rentals', 'city-rentals', 'property');
$isPublicPage = in_array($routeName, $publicRoutes, true);
$seoTitle = isset($seo['title']) ? (string) $seo['title'] : ($defaultMetaTitle !== '' ? $defaultMetaTitle : $pageTitle);
$seoDescription = isset($seo['description']) ? trim((string) $seo['description']) : '';

if ($seoDescription === '') {
    if (isset($content['meta']['metaDescription']) && trim((string) $content['meta']['metaDescription']) !== '') {
        $seoDescription = app_meta_description((string) $content['meta']['metaDescription'], 165);
    } elseif ($listing && isset($listing['summary'])) {
        $seoDescription = app_meta_description($listing['summary'], 165);
    } elseif (isset($content['hero']['description'])) {
        $seoDescription = app_meta_description($content['hero']['description'], 165);
    } elseif ($routeName === 'city-rentals' && $marketName !== '') {
        $seoDescription = app_meta_description('Browse annual rental listings in ' . $marketName . ' with Sandworth Homes. View pricing, location highlights, and available homes before you schedule a tour.', 165);
    } elseif ($routeName === 'property' && $listing) {
        $seoDescription = app_meta_description($listing['title'] . ' in ' . $listing['location'] . ' with annual rent, gallery images, features, and move-in costs on Sandworth Homes.', 165);
    } else {
        $seoDescription = $defaultMetaDescription !== ''
            ? app_meta_description($defaultMetaDescription, 165)
            : 'Sandworth Homes helps Nigerians discover homes for sale, annual rentals, and commercial spaces with a shareable, organization-owned property platform.';
    }
}

$seoImageSource = isset($seo['image']) && trim((string) $seo['image']) !== ''
    ? (string) $seo['image']
    : $defaultShareImage;
$faviconSource = $faviconPath;
$seoImageWidth = 1200;
$seoImageHeight = 630;

if ($listing && isset($listing['image']) && trim((string) $listing['image']) !== '') {
    $seoImageSource = (string) $listing['image'];
    $faviconSource = (string) $listing['image'];
}

$seoImage = app_absolute_url(app_social_share_image_path($seoImageSource));
$faviconHref = app_absolute_url(app_social_icon_image_path($faviconSource));

$seoCanonical = isset($seo['canonical']) && trim((string) $seo['canonical']) !== ''
    ? app_absolute_url((string) $seo['canonical'])
    : app_absolute_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : app_url($routeName));

if ($routeName === 'property' && $listing) {
    $seoCanonical = app_absolute_url(app_property_url($listing));
}

$seoType = isset($seo['type']) ? (string) $seo['type'] : ($routeName === 'property' && $listing ? 'article' : 'website');
$seoRobots = isset($seo['robots']) ? (string) $seo['robots'] : ($isPublicPage && ! ($routeName === 'property' && ! $listing)
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,nofollow,noarchive');
$seoRobots = isset($siteSettings['robotsPolicy']) && trim((string) $siteSettings['robotsPolicy']) !== '' && $isPublicPage
    ? (string) $siteSettings['robotsPolicy']
    : $seoRobots;
$seoStructuredData = array();
$organizationSchema = array(
    '@context' => 'https://schema.org',
    '@type' => 'RealEstateAgent',
    'name' => $siteName,
    'url' => app_absolute_url(app_url('home')),
    'logo' => app_absolute_url($brandLogo),
    'image' => app_absolute_url($brandLogo),
    'email' => $contactEmail,
    'telephone' => $contactPhone,
    'areaServed' => 'Nigeria',
    'address' => array(
        '@type' => 'PostalAddress',
        'streetAddress' => $registeredOffice,
        'addressLocality' => 'Surulere',
        'addressRegion' => 'Lagos State',
        'addressCountry' => 'NG',
    ),
    'location' => array(
        '@type' => 'Place',
        'name' => 'Operational Office',
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => $operationalOffice,
            'addressLocality' => 'Bolade-Oshodi',
            'postalCode' => '101233',
            'addressRegion' => 'Lagos State',
            'addressCountry' => 'NG',
        ),
    ),
    'contactPoint' => array(
        '@type' => 'ContactPoint',
        'contactType' => 'customer support',
        'telephone' => $contactPhone,
        'email' => $contactEmail,
        'areaServed' => 'NG',
        'availableLanguage' => array('English'),
    ),
);

if ($isPublicPage) {
    $seoStructuredData[] = $organizationSchema;
    $seoStructuredData[] = array(
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => app_absolute_url(app_url('home')),
        'inLanguage' => 'en-NG',
        'publisher' => array(
            '@type' => 'Organization',
            'name' => $siteName,
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => app_absolute_url($brandLogo),
            ),
        ),
        'potentialAction' => array(
            '@type' => 'SearchAction',
            'target' => app_absolute_url(app_url('rentals')) . '&city={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ),
    );
}

if ($routeName === 'property' && $listing) {
    $propertyImages = array();

    if (isset($listing['images']) && is_array($listing['images'])) {
        foreach ($listing['images'] as $propertyImage) {
            if (trim((string) $propertyImage) !== '') {
                $propertyImages[] = app_absolute_url((string) $propertyImage);
            }
        }
    }

    if ($propertyImages === array()) {
        $propertyImages[] = $seoImage;
    }

    $offerPrice = isset($listing['askingPrice']) && (int) $listing['askingPrice'] > 0
        ? (int) $listing['askingPrice']
        : (isset($listing['monthlyRent']) ? (int) $listing['monthlyRent'] : 0);

    $seoStructuredData[] = array(
        '@context' => 'https://schema.org',
        '@type' => 'Residence',
        'name' => isset($listing['title']) ? (string) $listing['title'] : $seoTitle,
        'description' => $seoDescription,
        'url' => $seoCanonical,
        'image' => $propertyImages,
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => isset($listing['location']) ? (string) $listing['location'] : '',
            'addressCountry' => 'NG',
        ),
        'numberOfRooms' => isset($listing['beds']) ? (int) $listing['beds'] : 0,
        'offers' => array(
            '@type' => 'Offer',
            'url' => $seoCanonical,
            'priceCurrency' => app_currency_code(),
            'price' => $offerPrice,
            'availability' => 'https://schema.org/InStock',
        ),
    );
}

if ($isPublicPage && $routeName !== 'property' && $listings !== array()) {
    $itemListElements = array();
    $position = 1;

    foreach (array_slice($listings, 0, 12) as $listItem) {
        $itemListElements[] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'url' => app_absolute_url(app_property_url($listItem)),
            'name' => isset($listItem['title']) ? (string) $listItem['title'] : 'Property listing',
        );
    }

    if ($itemListElements !== array()) {
        $seoStructuredData[] = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $seoTitle,
            'description' => $seoDescription,
            'url' => $seoCanonical,
            'mainEntity' => array(
                '@type' => 'ItemList',
                'numberOfItems' => count($listings),
                'itemListElement' => $itemListElements,
            ),
        );
    }
}

if (isset($structuredData)) {
    if (isset($structuredData[0]) && is_array($structuredData[0])) {
        $seoStructuredData = array_merge($seoStructuredData, $structuredData);
    } elseif (is_array($structuredData)) {
        $seoStructuredData[] = $structuredData;
    }
}
?>
<!DOCTYPE html>
<html lang="en-NG">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($seoRobots, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#0b3d91">
    <meta property="og:locale" content="en_NG">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($seoType, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="<?= $seoImageWidth ?>">
    <meta property="og:image:height" content="<?= $seoImageHeight ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php if ($twitterHandle !== ''): ?>
    <meta name="twitter:site" content="<?= htmlspecialchars($twitterHandle, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <meta name="twitter:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($seoCanonical, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="image_src" href="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="alternate" type="application/xml" title="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> Sitemap" href="<?= htmlspecialchars(app_absolute_url(app_public_base_path() . '/sitemap.xml'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php
    $appCssPath = function_exists('app_public_file_path') ? app_public_file_path('/public/assets/css/app.css') : null;
    $appCssVersion = ($appCssPath && is_file($appCssPath)) ? filemtime($appCssPath) : '1';
    ?>
    <link rel="stylesheet" href="<?= htmlspecialchars(app_asset_url('css/app.css') . '?v=' . $appCssVersion, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($seoStructuredData as $schema): ?>
        <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>
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
            <span style="display:none; color:#082c61; font-weight:800; font-size:1.15rem; letter-spacing:-.02em; font-family:'Fraunces',Georgia,serif;">Sandworth Homes</span>
        </a>

        <nav class="topnav" aria-label="Main navigation">
            <a class="<?= $activePage === 'home' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('home'), ENT_QUOTES, 'UTF-8') ?>">Home</a>
            <!-- <a class="<?= $activePage === 'plan' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('plan'), ENT_QUOTES, 'UTF-8') ?>">Plan</a> -->
            <a class="<?= $activePage === 'homes' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('homes'), ENT_QUOTES, 'UTF-8') ?>">Buy</a>
            <a class="<?= $activePage === 'rentals' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('rentals'), ENT_QUOTES, 'UTF-8') ?>">Rent</a>
            <a class="<?= $activePage === 'commercial' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(app_url('commercial'), ENT_QUOTES, 'UTF-8') ?>">Malls &amp; Shops</a>
            <?php if ($viewerIsAdmin || $viewerHasTenancy): ?>
                <a class="<?= $activePage === 'manager' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($manageHref, ENT_QUOTES, 'UTF-8') ?>">Manage</a>
            <?php endif; ?>
        </nav>

        <div class="topbar-actions">
            <?php if ($currentUser): ?>
                <?php if ($viewerIsAdmin): ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Admin console</a>
                <?php else: ?>
                    <a class="ghost-button" href="<?= htmlspecialchars(app_url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
                    <?php if ($viewerHasTenancy): ?>
                        <a class="ghost-button" href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Tenancy</a>
                    <?php endif; ?>
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
                        <?php if ($facebookUrl !== ''): ?><a href="<?= htmlspecialchars($facebookUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Facebook">Fb</a><?php endif; ?>
                        <?php if ($instagramUrl !== ''): ?><a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Instagram">Ig</a><?php endif; ?>
                        <?php if ($xUrl !== ''): ?><a href="<?= htmlspecialchars($xUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Twitter / X">X</a><?php endif; ?>
                        <?php if ($linkedinUrl !== ''): ?><a href="<?= htmlspecialchars($linkedinUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="LinkedIn">Li</a><?php endif; ?>
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
                        <?php if ($viewerIsAdmin): ?>
                            <li><a href="<?= htmlspecialchars(app_url('admin'), ENT_QUOTES, 'UTF-8') ?>">Admin console</a></li>
                        <?php elseif ($viewerHasTenancy): ?>
                            <li><a href="<?= htmlspecialchars(app_url('tenancy'), ENT_QUOTES, 'UTF-8') ?>">Manage my tenancy</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="tel:<?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li class="footer-contact-item">
                            <span class="footer-contact-label">Operational Office</span>
                            <span class="footer-contact-text"><?= htmlspecialchars($operationalOffice, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                        <li class="footer-contact-item">
                            <span class="footer-contact-label">Registered Office</span>
                            <span class="footer-contact-text"><?= htmlspecialchars($registeredOffice, ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                        <li><span class="footer-contact-note">Mon - Sat 8 am - 6 pm</span></li>
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
    <script src="<?= htmlspecialchars(app_asset_url('js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(app_asset_url('js/map.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
