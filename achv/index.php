<?php

if (! is_dir(__DIR__ . '/storage/sessions')) {
    mkdir(__DIR__ . '/storage/sessions', 0777, true);
}

if (is_dir(__DIR__ . '/storage/sessions') && is_writable(__DIR__ . '/storage/sessions')) {
    session_save_path(__DIR__ . '/storage/sessions');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Core/View.php';
require_once __DIR__ . '/app/Core/Flash.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Platform.php';
require_once __DIR__ . '/app/Core/Auth.php';
require_once __DIR__ . '/app/Support/helpers.php';

$dbConfig = require __DIR__ . '/config/database.php';
$database = new App\Core\Database($dbConfig, __DIR__ . '/database/schema.sql');
$platform = new App\Core\Platform($database, __DIR__ . '/storage');
$platform->initialize();
$siteSettings = $platform->siteSettings();
app_set_site_base_url($siteSettings['siteBaseUrl']);
app_set_currency($platform->currencyCode());

$router = new App\Core\Router();
$currentUser = App\Core\Auth::user($platform);
$currentTenancy = ($currentUser && ! App\Core\Auth::isAdmin($currentUser))
    ? $platform->tenancyForUser($currentUser['id'])
    : null;
$routeName = isset($_GET['page']) ? (string) $_GET['page'] : 'home';

App\Core\View::share('currentUser', $currentUser);
App\Core\View::share('currentTenancy', $currentTenancy);
App\Core\View::share('flashMessages', App\Core\Flash::consume());
App\Core\View::share('routeName', $routeName);
App\Core\View::share('currentCurrencyCode', $platform->currencyCode());
App\Core\View::share('siteSettings', $siteSettings);

$router->get('sitemap', function () use ($platform) {
    $properties = $platform->allProperties();
    $rentalMarkets = array();
    $urls = array(
        array(
            'loc' => app_absolute_url(app_url('home')),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ),
        array(
            'loc' => app_absolute_url(app_url('homes')),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ),
        array(
            'loc' => app_absolute_url(app_url('rentals')),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ),
        array(
            'loc' => app_absolute_url(app_url('commercial')),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ),
        array(
            'loc' => app_absolute_url(app_url('plan')),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ),
    );

    foreach ($platform->allRentalProperties() as $rentalListing) {
        $market = trim((string) strtok(isset($rentalListing['location']) ? (string) $rentalListing['location'] : '', ','));

        if ($market !== '') {
            $rentalMarkets[$market] = true;
        }
    }

    foreach (array_keys($rentalMarkets) as $market) {
        $urls[] = array(
            'loc' => app_absolute_url(app_url('city-rentals', array('market' => $market))),
            'changefreq' => 'daily',
            'priority' => '0.8',
        );
    }

    foreach ($properties as $property) {
        $urls[] = array(
            'loc' => app_absolute_url(app_property_url($property)),
            'lastmod' => isset($property['createdAt']) ? substr((string) $property['createdAt'], 0, 10) : date('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        );
    }

    header('Content-Type: application/xml; charset=UTF-8');

    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($urls as $url) {
        echo "  <url>\n";
        echo '    <loc>' . htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8') . "</loc>\n";

        if (isset($url['lastmod']) && $url['lastmod'] !== '') {
            echo '    <lastmod>' . htmlspecialchars((string) $url['lastmod'], ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
        }

        echo '    <changefreq>' . htmlspecialchars((string) $url['changefreq'], ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
        echo '    <priority>' . htmlspecialchars((string) $url['priority'], ENT_QUOTES, 'UTF-8') . "</priority>\n";
        echo "  </url>\n";
    }

    echo "</urlset>";
});

$router->get('home', function () use ($platform) {
    $content = $platform->pageContent('home');
    $stats = $platform->rentalStats();
    $allProperties = $platform->allProperties();
    $featuredRentals = array_slice($platform->allRentalProperties(), 0, 4);
    $featuredHomes = array_slice($platform->allSaleProperties(), 0, 4);
    $featuredCommercial = array_slice($platform->allCommercialProperties(), 0, 2);
    $freshListings = array_slice($allProperties, 0, 6);
    $marketSpotlights = array();

    foreach ($allProperties as $property) {
        $location = isset($property['location']) ? trim((string) $property['location']) : '';
        $marketName = trim((string) strtok($location, ','));

        if ($marketName === '') {
            $marketName = $location;
        }

        if ($marketName === '') {
            continue;
        }

        if (! isset($marketSpotlights[$marketName])) {
            $marketSpotlights[$marketName] = array(
                'name' => $marketName,
                'image' => isset($property['image']) ? $property['image'] : '',
                'rentCount' => 0,
                'saleCount' => 0,
                'commercialCount' => 0,
                'startingPrice' => isset($property['price']) ? $property['price'] : '',
            );
        }

        if ($property['purpose'] === 'rent') {
            $marketSpotlights[$marketName]['rentCount']++;
        } elseif ($property['purpose'] === 'sale') {
            $marketSpotlights[$marketName]['saleCount']++;
        } elseif ($property['purpose'] === 'commercial') {
            $marketSpotlights[$marketName]['commercialCount']++;
        }
    }

    $marketSpotlights = array_values($marketSpotlights);

    usort($marketSpotlights, function ($left, $right) {
        $leftTotal = $left['rentCount'] + $left['saleCount'] + $left['commercialCount'];
        $rightTotal = $right['rentCount'] + $right['saleCount'] + $right['commercialCount'];

        if ($leftTotal === $rightTotal) {
            return strcmp($left['name'], $right['name']);
        }

        return $rightTotal - $leftTotal;
    });

    $marketSpotlights = array_slice($marketSpotlights, 0, 6);

    App\Core\View::render('pages/home', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Sandworth Homes',
        'activePage' => 'home',
        'content' => $content,
        'seo' => array(
            'title' => 'Sandworth Homes | Buy, Rent, and Lease Property in Nigeria',
            'description' => 'Browse homes for sale, annual rentals, and commercial spaces across key Nigerian markets with Sandworth Homes.',
            'type' => 'website',
        ),
        'featuredRentals' => $featuredRentals,
        'featuredHomes' => $featuredHomes,
        'featuredCommercial' => $featuredCommercial,
        'freshListings' => $freshListings,
        'marketSpotlights' => $marketSpotlights,
        'stats' => array(
            'activeRentals' => $stats['activeRentals'],
            'preQualifiedRenters' => isset($content['stats']['preQualifiedRenters']) ? (int) $content['stats']['preQualifiedRenters'] : 0,
            'avgDaysToLease' => $stats['avgDaysToLease'],
        ),
    ));
});

$router->get('plan', function () use ($platform) {
    $content = $platform->pageContent('plan');

    App\Core\View::render('pages/plan', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Plan | Sandworth Homes',
        'activePage' => 'plan',
        'content' => $content,
        'seo' => array(
            'title' => 'Plan Your Move | Sandworth Homes',
            'description' => 'Plan your next property move with budgeting guidance, milestones, and buyer readiness tools from Sandworth Homes.',
            'type' => 'website',
        ),
        'budgetSnapshot' => isset($content['budgetSnapshot']) ? $content['budgetSnapshot'] : array(),
        'teamMembers' => isset($content['teamMembers']) ? $content['teamMembers'] : array(),
        'processSteps' => isset($content['processSteps']) ? $content['processSteps'] : array(),
        'milestones' => isset($content['milestones']) ? $content['milestones'] : array(),
    ));
});

$router->get('homes', function () use ($platform) {
    $bedrooms = isset($_GET['beds']) ? trim((string) $_GET['beds']) : '';
    $propertyType = isset($_GET['property_type']) ? trim((string) $_GET['property_type']) : '';
    $location = isset($_GET['location']) ? trim((string) $_GET['location']) : '';
    $content = $platform->pageContent('homes');

    App\Core\View::render('pages/homes', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Homes | Sandworth Homes',
        'activePage' => 'homes',
        'content' => $content,
        'seo' => array(
            'title' => 'Homes for Sale in Nigeria | Sandworth Homes',
            'description' => 'Search homes for sale by area, property type, and bedrooms with detailed property pages from Sandworth Homes.',
            'type' => 'website',
        ),
        'listings' => $platform->allSaleProperties(array(
            'beds' => $bedrooms,
            'propertyType' => $propertyType,
            'location' => $location,
        )),
        'filters' => array(
            'beds' => $bedrooms,
            'propertyType' => $propertyType,
            'location' => $location,
        ),
        'searchTips' => isset($content['searchTips']) ? $content['searchTips'] : array(),
    ));
});

$router->get('commercial', function () use ($platform) {
    $commercialType = isset($_GET['commercial_type']) ? trim((string) $_GET['commercial_type']) : '';
    $location = isset($_GET['location']) ? trim((string) $_GET['location']) : '';
    $content = $platform->pageContent('commercial');

    App\Core\View::render('pages/commercial', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Commercial | Sandworth Homes',
        'activePage' => 'commercial',
        'content' => $content,
        'seo' => array(
            'title' => 'Commercial Property and Mall Spaces | Sandworth Homes',
            'description' => 'Explore commercial leases, mall units, and retail spaces with searchable listings and share-ready detail pages.',
            'type' => 'website',
        ),
        'listings' => $platform->allCommercialProperties(array(
            'commercialType' => $commercialType,
            'location' => $location,
        )),
        'filters' => array(
            'commercialType' => $commercialType,
            'location' => $location,
        ),
        'searchTips' => isset($content['searchTips']) ? $content['searchTips'] : array(),
    ));
});

$router->get('rentals', function () use ($platform) {
    $city = isset($_GET['city']) ? trim((string) $_GET['city']) : '';
    $beds = isset($_GET['beds']) ? trim((string) $_GET['beds']) : '';
    $petFriendly = isset($_GET['pet_friendly']) && $_GET['pet_friendly'] === '1';
    $content = $platform->pageContent('rentals');

    App\Core\View::render('pages/rentals', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Rentals | Sandworth Homes',
        'activePage' => 'rentals',
        'content' => $content,
        'seo' => array(
            'title' => 'Annual Rental Listings | Sandworth Homes',
            'description' => 'Find annual rental homes with pricing, filters, gallery images, and move-in details on Sandworth Homes.',
            'type' => 'website',
        ),
        'listings' => $platform->allRentalProperties(array(
            'city' => $city,
            'beds' => $beds,
            'petFriendly' => $petFriendly,
        )),
        'filters' => array(
            'city' => $city,
            'beds' => $beds,
            'petFriendly' => $petFriendly,
        ),
    ));
});

$router->get('city-rentals', function () use ($platform) {
    $market = isset($_GET['market']) ? trim((string) $_GET['market']) : 'Lekki';
    $listings = $platform->allRentalProperties(array('market' => $market));
    $rentTotal = 0;
    $content = $platform->pageContent('city-rentals');

    foreach ($listings as $listing) {
        $rentTotal += (int) $listing['monthlyRent'];
    }

    App\Core\View::render('pages/city-rentals', array(
        'pageTitle' => $market . ' ' . (isset($content['meta']['pageTitleSuffix']) ? $content['meta']['pageTitleSuffix'] : 'Rentals | Sandworth Homes'),
        'activePage' => 'rentals',
        'content' => $content,
        'seo' => array(
            'title' => $market . ' Rentals | Sandworth Homes',
            'description' => 'Browse annual rental listings in ' . $market . ' with market stats, nearby areas, and detailed listing pages.',
            'type' => 'website',
        ),
        'marketName' => $market,
        'listings' => $listings,
        'marketStats' => array(
            'averageRent' => count($listings) > 0 ? app_currency($rentTotal / count($listings)) . '/yr' : app_currency(0) . '/yr',
            'newListings' => count($listings),
            'tourReady' => min(count($listings), 9),
        ),
        'nearbyMarkets' => isset($content['nearbyMarkets']) ? $content['nearbyMarkets'] : array(),
    ));
});

$router->get('property', function () use ($platform) {
    $requestedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $selectedListing = $platform->findProperty($requestedId);
    $activePage = 'rentals';
    $viewer = App\Core\Auth::user($platform);
    $availableTourSlots = array();
    $activeTourRequest = null;

    if ($selectedListing && isset($selectedListing['sourcePage'])) {
        if ($selectedListing['sourcePage'] === 'homes') {
            $activePage = 'homes';
        } elseif ($selectedListing['sourcePage'] === 'commercial') {
            $activePage = 'commercial';
        }
    }

    if ($selectedListing !== null) {
        $expectedSlug = app_slug($selectedListing['title'] . ' ' . $selectedListing['location']);
        $requestedSlug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';

        if ($requestedSlug !== $expectedSlug) {
            header('Location: ' . app_property_url($selectedListing), true, 301);
            exit;
        }
    }

    if ($selectedListing !== null) {
        $similarSource = $selectedListing['sourcePage'] === 'homes'
            ? $platform->allSaleProperties()
            : ($selectedListing['sourcePage'] === 'commercial' ? $platform->allCommercialProperties() : $platform->allRentalProperties());
        $availableTourSlots = $platform->availableTourSlotsForProperty($selectedListing['id']);

        if ($viewer && ! App\Core\Auth::isAdmin($viewer)) {
            $activeTourRequest = $platform->activeTourRequestForUserAndProperty($viewer['id'], $selectedListing['id']);
        }

        $similarListings = array_values(array_filter($similarSource, function ($item) use ($selectedListing) {
            return (int) $item['id'] !== (int) $selectedListing['id'];
        }));
        $similarListings = array_slice($similarListings, 0, 3);
    }

    if ($selectedListing === null) {
        http_response_code(404);

        App\Core\View::render('pages/property', array(
            'pageTitle' => 'Listing not found | Sandworth Homes',
            'activePage' => $activePage,
            'listing' => null,
        ));

        return;
    }

    App\Core\View::render('pages/property', array(
        'pageTitle' => $selectedListing['title'] . ' | Sandworth Homes',
        'activePage' => $activePage,
        'listing' => $selectedListing,
        'seo' => array(
            'title' => $selectedListing['title'] . ' | Sandworth Homes',
            'description' => app_meta_description($selectedListing['summary'] . ' Located in ' . $selectedListing['location'] . '. View pricing, features, gallery images, and move-in costs on Sandworth Homes.', 165),
            'image' => $selectedListing['image'],
            'type' => 'article',
        ),
        'similarListings' => $similarListings,
        'availableTourSlots' => $availableTourSlots,
        'activeTourRequest' => $activeTourRequest,
    ));
});

$router->get('apply', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    $property = $platform->findProperty(isset($_GET['id']) ? (int) $_GET['id'] : 0);

    if (! $property || ! in_array($property['purpose'], array('rent', 'commercial'), true)) {
        App\Core\Flash::add('error', 'That listing is not available for application.');
        app_redirect('rentals');
    }

    $isCommercial = $property['purpose'] === 'commercial';

    App\Core\View::render('pages/apply', array(
        'pageTitle' => ($isCommercial ? 'Request Lease | Sandworth Homes' : 'Apply For Rent | Sandworth Homes'),
        'activePage' => $isCommercial ? 'commercial' : 'rentals',
        'listing' => $property,
        'isCommercial' => $isCommercial,
    ));
});

$router->post('application-submit', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    $property = $platform->findProperty(isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0);

    if (! $property) {
        App\Core\Flash::add('error', 'That property could not be found.');
        app_redirect('rentals');
    }

    list($application, $error) = $platform->createApplication($viewer, $property, $_POST);

    if (! $application) {
        App\Core\Flash::add('error', $error);
        app_redirect('apply', array('id' => $property['id']));
    }

    App\Core\Flash::add('success', 'Rental application submitted. We will review it from the admin desk.');
    app_redirect('dashboard');
});

$router->post('tour-request-submit', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        App\Core\Flash::add('error', 'Admin accounts cannot submit renter tour requests.');
        app_redirect('admin-tours');
    }

    $property = $platform->findProperty(isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0);

    if (! $property) {
        App\Core\Flash::add('error', 'That property could not be found.');
        app_redirect('rentals');
    }

    list($request, $error) = $platform->createTourRequest($viewer, $property, $_POST);

    if (! $request) {
        App\Core\Flash::add('error', $error);
        header('Location: ' . app_property_url($property) . '#tour-booking');
        exit;
    }

    App\Core\Flash::add('success', 'Tour request submitted. We will confirm the appointment shortly.');
    header('Location: ' . app_property_url($property) . '#tour-booking');
    exit;
});

$router->get('login', function () {
    App\Core\View::render('pages/login', array(
        'pageTitle' => 'Sign In | Sandworth Homes',
        'activePage' => 'rentals',
    ));
});

$router->post('login-submit', function () use ($platform) {
    $user = App\Core\Auth::attempt(
        $platform,
        isset($_POST['email']) ? $_POST['email'] : '',
        isset($_POST['password']) ? $_POST['password'] : ''
    );

    if (! $user) {
        App\Core\Flash::add('error', 'Invalid email or password.');
        app_redirect('login');
    }

    App\Core\Flash::add('success', 'Welcome back, ' . $user['name'] . '.');

    if (App\Core\Auth::isAdmin($user)) {
        app_redirect('admin');
    }

    app_redirect('dashboard');
});

$router->get('register', function () {
    App\Core\View::render('pages/register', array(
        'pageTitle' => 'Create Account | Sandworth Homes',
        'activePage' => 'rentals',
    ));
});

$router->post('register-submit', function () use ($platform) {
    if (! isset($_POST['name'], $_POST['email'], $_POST['phone'], $_POST['password'])) {
        App\Core\Flash::add('error', 'Please complete the sign-up form.');
        app_redirect('register');
    }

    list($user, $error) = $platform->createUser($_POST);

    if (! $user) {
        App\Core\Flash::add('error', $error);
        app_redirect('register');
    }

    App\Core\Auth::login($user);
    App\Core\Flash::add('success', 'Your account is ready. You can now search, apply, and pay online.');
    app_redirect('dashboard');
});

$router->post('logout-submit', function () {
    App\Core\Auth::logout();
    App\Core\Flash::add('success', 'You have been signed out.');
    app_redirect('home');
});

$router->get('dashboard', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    App\Core\View::render('pages/dashboard', array(
        'pageTitle' => 'My Dashboard | Sandworth Homes',
        'activePage' => 'rentals',
        'applications' => $platform->applicationsForUser($viewer['id']),
        'tourRequests' => $platform->tourRequestsForUser($viewer['id']),
        'tenancy' => $platform->tenancyForUser($viewer['id']),
    ));
});

$router->get('payment', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    $application = null;
    $tenancy = null;

    if (isset($_GET['application_id'])) {
        $application = $platform->findApplication((int) $_GET['application_id']);
    }

    if (isset($_GET['tenancy_id'])) {
        $tenancy = $platform->tenancyForUser($viewer['id']);
    }

    App\Core\View::render('pages/payment', array(
        'pageTitle' => 'Online Payment | Sandworth Homes',
        'activePage' => 'rentals',
        'application' => $application,
        'tenancy' => $tenancy,
    ));
});

$router->post('payment-submit', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (isset($_POST['application_id']) && $_POST['application_id'] !== '') {
        list($tenancy, $error) = $platform->activateTenancyFromApplication((int) $_POST['application_id'], $viewer, $_POST);

        if (! $tenancy) {
            App\Core\Flash::add('error', $error);
            app_redirect('dashboard');
        }

        App\Core\Flash::add('success', 'Payment received. Your tenancy account is now active.');
        app_redirect('tenancy');
    }

    if (isset($_POST['tenancy_id']) && $_POST['tenancy_id'] !== '') {
        list($tenancy, $error) = $platform->addTenancyPayment((int) $_POST['tenancy_id'], $viewer, $_POST);

        if (! $tenancy) {
            App\Core\Flash::add('error', $error);
            app_redirect('tenancy');
        }

        App\Core\Flash::add('success', 'Your payment was posted to the tenancy ledger.');
        app_redirect('tenancy');
    }

    App\Core\Flash::add('error', 'No payment target was selected.');
    app_redirect('dashboard');
});

$router->get('tenancy', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    App\Core\View::render('pages/tenancy', array(
        'pageTitle' => 'Tenancy App | Sandworth Homes',
        'activePage' => 'manager',
        'tenancy' => $platform->tenancyForUser($viewer['id']),
    ));
});

$router->get('manager', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);

    if (! $viewer) {
        App\Core\Flash::add('error', 'Sign in to manage your tenancy or account.');
        app_redirect('login');
    }

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    $tenancy = $platform->tenancyForUser($viewer['id']);

    if (! $tenancy) {
        App\Core\Flash::add('error', 'Management access is available only for customers with an active rented apartment or commercial space.');
        app_redirect('dashboard');
    }

    app_redirect('tenancy');
});

$router->get('admin', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $applications = $platform->allApplications(array('limit' => 3));
    $tourRequests = $platform->allTourRequests(array(
        'upcoming_only' => true,
        'limit' => 3,
    ));

    App\Core\View::render('pages/admin', array(
        'pageTitle' => 'Admin Console | Sandworth Homes',
        'activePage' => 'manager',
        'currentCurrencyCode' => $platform->currencyCode(),
        'portfolio' => $platform->adminPortfolio(),
        'applications' => $applications,
        'tourRequests' => $tourRequests,
    ));
});

$router->get('admin-editorial', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $pageContentBlocks = $platform->adminPageContentBlocks();

    App\Core\View::render('pages/admin-editorial', array(
        'pageTitle' => 'Editorial Pages | Sandworth Homes',
        'activePage' => 'manager',
        'pageContentBlocks' => $pageContentBlocks,
        'portfolio' => $platform->adminPortfolio(),
    ));
});

$router->get('admin-properties', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $editProperty = null;

    if (isset($_GET['edit_property']) && $_GET['edit_property'] !== '') {
        $editProperty = $platform->findProperty((int) $_GET['edit_property']);

        if (! $editProperty) {
            App\Core\Flash::add('error', 'That property could not be opened for editing.');
            app_redirect('admin-properties');
        }
    }

    App\Core\View::render('pages/admin-properties', array(
        'pageTitle' => ($editProperty ? 'Edit Property' : 'Load Property') . ' | Sandworth Homes',
        'activePage' => 'manager',
        'editProperty' => $editProperty,
        'portfolio' => $platform->adminPortfolio(),
    ));
});

$router->get('admin-applications', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $applications = $platform->allApplications();

    App\Core\View::render('pages/admin-applications', array(
        'pageTitle' => 'Applications | Sandworth Homes',
        'activePage' => 'manager',
        'applications' => $applications,
        'portfolio' => $platform->adminPortfolio(),
    ));
});

$router->get('admin-tours', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $properties = $platform->allProperties();
    $tourRequests = $platform->allTourRequests(array('upcoming_only' => true));
    $tourSlots = $platform->upcomingTourSlots(array(
        'status' => 'open',
        'available_only' => true,
    ));

    App\Core\View::render('pages/admin-tours', array(
        'pageTitle' => 'Tours | Sandworth Homes',
        'activePage' => 'manager',
        'portfolio' => $platform->adminPortfolio(),
        'properties' => $properties,
        'tourRequests' => $tourRequests,
        'tourSlots' => $tourSlots,
    ));
});

$router->get('admin-inventory', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $properties = $platform->allProperties();

    App\Core\View::render('pages/admin-inventory', array(
        'pageTitle' => 'Loaded Properties | Sandworth Homes',
        'activePage' => 'manager',
        'properties' => $properties,
        'portfolio' => $platform->adminPortfolio(),
    ));
});

$router->get('admin-seo', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    App\Core\View::render('pages/admin-seo', array(
        'pageTitle' => 'Marketing & SEO | Sandworth Homes',
        'activePage' => 'manager',
        'portfolio' => $platform->adminPortfolio(),
        'siteSettings' => $platform->siteSettings(),
    ));
});

$router->post('admin-property-create', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($property, $error) = $platform->createProperty($_POST, $_FILES, $viewer);

    if (! $property) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-properties');
    }

    App\Core\Flash::add('success', $property['title'] . ' was added to the live property inventory.');
    app_redirect('admin-inventory');
});

$router->post('admin-property-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
    list($property, $error) = $platform->updateProperty($propertyId, $_POST, $_FILES, $viewer);

    if (! $property) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-properties', array('edit_property' => $propertyId));
    }

    App\Core\Flash::add('success', $property['title'] . ' was updated successfully.');
    app_redirect('admin-properties', array('edit_property' => $property['id']));
});

$router->post('admin-property-delete', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
    list($property, $error) = $platform->deleteProperty($propertyId);

    if (! $property) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-inventory');
    }

    App\Core\Flash::add('success', $property['title'] . ' was deleted from the property inventory.');
    app_redirect('admin-inventory');
});

$router->post('admin-currency-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $updatedCode = $platform->updateCurrencyCode(isset($_POST['currency_code']) ? $_POST['currency_code'] : '');

    if (! $updatedCode) {
        App\Core\Flash::add('error', 'Choose either Naira or Dollar as the display currency.');
        app_redirect('admin');
    }

    app_set_currency($updatedCode);
    App\Core\Flash::add('success', 'Display currency updated to ' . ($updatedCode === 'NGN' ? 'Naira (₦).' : 'Dollar ($).'));
    app_redirect('admin');
});

$router->post('admin-page-content-save', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($block, $error) = $platform->savePageContentBlock(
        isset($_POST['page_key']) ? $_POST['page_key'] : '',
        isset($_POST['block_key']) ? $_POST['block_key'] : '',
        isset($_POST['content_json']) ? $_POST['content_json'] : ''
    );

    if (! $block) {
        App\Core\Flash::add('error', $error);
        header('Location: ' . app_url('admin-editorial'));
        exit;
    }

    App\Core\Flash::add('success', $block['pageKey'] . ' / ' . $block['blockKey'] . ' was saved successfully.');
    header('Location: ' . app_url('admin-editorial'));
    exit;
});

$router->post('admin-seo-save', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $platform->updateSiteSettings($_POST);
    App\Core\Flash::add('success', 'Marketing and SEO settings were updated successfully.');
    app_redirect('admin-seo');
});

$router->post('application-approve', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $application = $platform->approveApplication(isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0, $viewer);

    if (! $application) {
        App\Core\Flash::add('error', 'We could not approve that application.');
        app_redirect('admin-applications');
    }

    App\Core\Flash::add('success', 'Application approved. The tenant can now pay online and move into the tenancy app.');
    app_redirect('admin-applications');
});

$router->post('admin-tour-slot-create', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
    list($slot, $error) = $platform->createTourSlot($propertyId, $_POST, $viewer);

    if (! $slot) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-tours');
    }

    App\Core\Flash::add('success', 'Tour availability added for ' . $slot['property']['title'] . '.');
    app_redirect('admin-tours');
});

$router->post('admin-tour-slot-delete', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($slot, $error) = $platform->cancelTourSlot(isset($_POST['slot_id']) ? (int) $_POST['slot_id'] : 0);

    if (! $slot) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-tours');
    }

    App\Core\Flash::add('success', 'Tour slot removed from the live schedule.');
    app_redirect('admin-tours');
});

$router->post('admin-tour-request-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($request, $error) = $platform->updateTourRequestStatus(
        isset($_POST['tour_request_id']) ? (int) $_POST['tour_request_id'] : 0,
        isset($_POST['status']) ? $_POST['status'] : '',
        $viewer,
        isset($_POST['admin_notes']) ? $_POST['admin_notes'] : ''
    );

    if (! $request) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-tours');
    }

    App\Core\Flash::add('success', 'Tour request updated to ' . ucfirst($request['status']) . '.');
    app_redirect('admin-tours');
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $routeName);
