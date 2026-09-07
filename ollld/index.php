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
app_set_currency($platform->currencyCode());

$router = new App\Core\Router();
$currentUser = App\Core\Auth::user($platform);
$routeName = isset($_GET['page']) ? (string) $_GET['page'] : 'home';

App\Core\View::share('currentUser', $currentUser);
App\Core\View::share('flashMessages', App\Core\Flash::consume());
App\Core\View::share('routeName', $routeName);
App\Core\View::share('currentCurrencyCode', $platform->currencyCode());

$renterUnread = ($currentUser && ! App\Core\Auth::isAdmin($currentUser)) ? $platform->unreadMessageCount($currentUser['id']) : 0;
App\Core\View::share('renterUnread', $renterUnread);
$adminUnread = ($currentUser && App\Core\Auth::isAdmin($currentUser)) ? $platform->unreadUserMessages() : 0;
App\Core\View::share('adminUnread', $adminUnread);

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
        'marketName' => $market,
        'listings' => $listings,
        'marketStats' => array(
            'averageRent' => count($listings) > 0 ? app_currency($rentTotal / count($listings)) . '/mo' : app_currency(0) . '/mo',
            'newListings' => count($listings),
            'tourReady' => min(count($listings), 9),
        ),
        'nearbyMarkets' => isset($content['nearbyMarkets']) ? $content['nearbyMarkets'] : array(),
    ));
});

$router->get('property', function () use ($platform) {
    $requestedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $selectedListing = $platform->findProperty($requestedId);
    $viewer = App\Core\Auth::user($platform);
    $activePage = 'rentals';
    $isFavorite = false;

    if ($selectedListing && $viewer && ! App\Core\Auth::isAdmin($viewer)) {
        $isFavorite = $platform->isFavorite($viewer['id'], $selectedListing['id']);
    }

    if ($selectedListing && isset($selectedListing['sourcePage'])) {
        if ($selectedListing['sourcePage'] === 'homes') {
            $activePage = 'homes';
        } elseif ($selectedListing['sourcePage'] === 'commercial') {
            $activePage = 'commercial';
        }
    }

    if ($selectedListing !== null) {
        $similarSource = $selectedListing['sourcePage'] === 'homes'
            ? $platform->allSaleProperties()
            : ($selectedListing['sourcePage'] === 'commercial' ? $platform->allCommercialProperties() : $platform->allRentalProperties());

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
        'similarListings' => $similarListings,
        'isFavorite' => $isFavorite,
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

$router->post('favorite-toggle', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    $property = $platform->findProperty(isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0);

    if (! $property) {
        App\Core\Flash::add('error', 'That property could not be found.');
        app_redirect('homes');
    }

    list($saved, $message) = $platform->toggleFavorite($viewer['id'], $property['id']);
    App\Core\Flash::add($saved ? 'success' : 'info', $message);
    header('Location: ' . app_url('property', array('id' => $property['id'])));
    exit;
});

$router->get('saved', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    App\Core\View::render('pages/saved', array(
        'pageTitle' => 'Saved Properties | Sandworth Homes',
        'activePage' => 'homes',
        'favorites' => $platform->favoritesForUser($viewer['id']),
    ));
});

$router->post('tour-request', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin-tours');
    }

    $property = $platform->findProperty(isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0);

    if (! $property) {
        App\Core\Flash::add('error', 'That property could not be found.');
        app_redirect('rentals');
    }

    list($tour, $error) = $platform->createTourRequest($viewer, $property, $_POST);

    if (! $tour) {
        App\Core\Flash::add('error', $error);
        header('Location: ' . app_url('property', array('id' => $property['id'])));
        exit;
    }

    App\Core\Flash::add('success', 'Tour request submitted. The leasing desk will confirm your date and time here.');
    header('Location: ' . app_url('tours'));
    exit;
});

$router->get('tours', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin-tours');
    }

    App\Core\View::render('pages/tours', array(
        'pageTitle' => 'My Tours | Sandworth Homes',
        'activePage' => 'rentals',
        'tours' => $platform->tourRequestsForUser($viewer['id']),
    ));
});

$router->get('history', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin');
    }

    $tenancy = $platform->tenancyForUser($viewer['id']);

    App\Core\View::render('pages/history', array(
        'pageTitle' => 'My Activity History | Sandworth Homes',
        'activePage' => 'rentals',
        'activity' => $platform->activityForUser($viewer['id']),
        'applications' => $platform->applicationsForUser($viewer['id']),
        'tenancy' => $tenancy,
        'payments' => $tenancy ? $tenancy['payments'] : array(),
        'favorites' => $platform->favoritesForUser($viewer['id']),
        'offers' => $platform->offersForUser($viewer['id']),
        'tours' => $platform->tourRequestsForUser($viewer['id']),
    ));
});

$router->post('message-send', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin-messages');
    }

    list($message, $error) = $platform->createMessage($viewer, $_POST);

    if (! $message) {
        App\Core\Flash::add('error', $error);
        header('Location: ' . app_url('messages'));
        exit;
    }

    App\Core\Flash::add('success', 'Your message was sent to the leasing desk. We will reply here.');
    header('Location: ' . app_url('messages'));
    exit;
});

$router->get('messages', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin-messages');
    }

    $platform->markUserMessagesRead($viewer['id']);

    App\Core\View::render('pages/messages', array(
        'pageTitle' => 'Messages | Sandworth Homes',
        'activePage' => 'rentals',
        'conversation' => $platform->messagesForUser($viewer['id']),
        'properties' => $platform->allProperties(),
    ));
});

$router->post('offer-submit', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    $property = $platform->findProperty(isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0);

    if (! $property || $property['purpose'] !== 'sale') {
        App\Core\Flash::add('error', 'Offers are only available on homes for sale.');
        app_redirect('homes');
    }

    list($offer, $error) = $platform->createOffer($viewer, $property, $_POST);

    if (! $offer) {
        App\Core\Flash::add('error', $error);
        header('Location: ' . app_url('property', array('id' => $property['id'])));
        exit;
    }

    App\Core\Flash::add('success', 'Your offer was submitted. The sales desk will review it and respond here.');
    app_redirect('offers');
});

$router->get('offers', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_login($viewer);

    if (App\Core\Auth::isAdmin($viewer)) {
        app_redirect('admin-offers');
    }

    App\Core\View::render('pages/offers', array(
        'pageTitle' => 'My Offers | Sandworth Homes',
        'activePage' => 'homes',
        'offers' => $platform->offersForUser($viewer['id']),
    ));
});

$router->get('admin-offers', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    App\Core\View::render('pages/admin-offers', array(
        'pageTitle' => 'Purchase Offers | Sandworth Homes',
        'activePage' => 'manager',
        'offers' => $platform->allOffers(),
    ));
});

$router->post('admin-offer-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($offer, $error) = $platform->updateOfferStatus(
        isset($_POST['offer_id']) ? (int) $_POST['offer_id'] : 0,
        isset($_POST['status']) ? $_POST['status'] : '',
        isset($_POST['admin_notes']) ? $_POST['admin_notes'] : ''
    );

    if (! $offer) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-offers');
    }

    App\Core\Flash::add('success', 'Offer updated to ' . ucfirst($offer['status']) . '.');
    app_redirect('admin-offers');
});

$router->get('admin-messages', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $platform->markUserMessagesReviewed();

    App\Core\View::render('pages/admin-messages', array(
        'pageTitle' => 'Messages | Sandworth Homes',
        'activePage' => 'manager',
        'conversation' => $platform->allMessages(),
    ));
});

$router->post('admin-message-reply', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($message, $error) = $platform->adminReplyToMessage(
        isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0,
        $viewer,
        isset($_POST['reply']) ? $_POST['reply'] : ''
    );

    if (! $message) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-messages');
    }

    App\Core\Flash::add('success', 'Your reply was sent to the renter.');
    app_redirect('admin-messages');
});

$router->get('admin-tours', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    App\Core\View::render('pages/admin-tours', array(
        'pageTitle' => 'Tour Management | Sandworth Homes',
        'activePage' => 'manager',
        'tours' => $platform->allTourRequests(),
        'statusCounts' => $platform->tourStatusCounts(),
    ));
});

$router->post('admin-tour-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $tourId = isset($_POST['tour_id']) ? (int) $_POST['tour_id'] : 0;
    $status = isset($_POST['status']) ? (string) $_POST['status'] : '';
    $notes = isset($_POST['scheduled_date']) ? (string) $_POST['scheduled_date'] : '';

    list($tour, $error) = $platform->updateTourStatus($tourId, $status, $viewer, $notes);

    if (! $tour) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin-tours');
    }

    App\Core\Flash::add('success', 'Tour request marked as ' . str_replace('_', ' ', $status) . '.');
    app_redirect('admin-tours');
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
        'tenancy' => $platform->tenancyForUser($viewer['id']),
        'favorites' => $platform->favoritesForUser($viewer['id']),
        'offers' => $platform->offersForUser($viewer['id']),
        'tours' => $platform->tourRequestsForUser($viewer['id']),
        'conversation' => $platform->messagesForUser($viewer['id']),
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

    App\Core\View::render('pages/tenancy', array(
        'pageTitle' => 'Tenancy App | Sandworth Homes',
        'activePage' => 'rentals',
        'tenancy' => $platform->tenancyForUser($viewer['id']),
    ));
});

$router->get('owner', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    App\Core\View::render('pages/owner', array(
        'pageTitle' => 'Owner Impact Report | Sandworth Homes',
        'activePage' => 'manager',
        'report' => $platform->ownerReport(),
        'pipeline' => $platform->pipelineSummary(),
    ));
});

$router->get('manager', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    $content = $platform->pageContent('manager');

    App\Core\View::render('pages/manager', array(
        'pageTitle' => isset($content['meta']['pageTitle']) ? $content['meta']['pageTitle'] : 'Manager | Sandworth Homes',
        'activePage' => 'manager',
        'content' => $content,
        'viewerIsAdmin' => App\Core\Auth::isAdmin($viewer),
        'portfolio' => $platform->managerSummary(),
        'pipeline' => $platform->pipelineSummary(),
        'recentListings' => array_slice($platform->allProperties(), 0, 4),
    ));
});

$router->get('admin', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $properties = $platform->allProperties();
    $applications = $platform->allApplications();
    $editProperty = null;

    if (isset($_GET['edit_property']) && $_GET['edit_property'] !== '') {
        $editProperty = $platform->findProperty((int) $_GET['edit_property']);

        if (! $editProperty) {
            App\Core\Flash::add('error', 'That property could not be opened for editing.');
            app_redirect('admin');
        }
    }

    App\Core\View::render('pages/admin', array(
        'pageTitle' => 'Admin Console | Sandworth Homes',
        'activePage' => 'manager',
        'properties' => $properties,
        'applications' => $applications,
        'editProperty' => $editProperty,
        'currentCurrencyCode' => $platform->currencyCode(),
        'portfolio' => array(
            'activeProperties' => count($properties),
            'submittedApplications' => count($applications),
            'approvedApplications' => count(array_filter($applications, function ($application) {
                return $application['status'] === 'approved' || $application['status'] === 'active';
            })),
        ),
    ));
});

$router->post('admin-property-create', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    list($property, $error) = $platform->createProperty($_POST, $_FILES, $viewer);

    if (! $property) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin');
    }

    App\Core\Flash::add('success', $property['title'] . ' was added to the live property inventory.');
    app_redirect('admin');
});

$router->post('admin-property-update', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
    list($property, $error) = $platform->updateProperty($propertyId, $_POST, $_FILES, $viewer);

    if (! $property) {
        App\Core\Flash::add('error', $error);
        app_redirect('admin', array('edit_property' => $propertyId));
    }

    App\Core\Flash::add('success', $property['title'] . ' was updated successfully.');
    app_redirect('admin', array('edit_property' => $property['id']));
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

$router->post('application-approve', function () use ($platform) {
    $viewer = App\Core\Auth::user($platform);
    app_require_admin($viewer);

    $application = $platform->approveApplication(isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0, $viewer);

    if (! $application) {
        App\Core\Flash::add('error', 'We could not approve that application.');
        app_redirect('admin');
    }

    App\Core\Flash::add('success', 'Application approved. The tenant can now pay online and move into the tenancy app.');
    app_redirect('admin');
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $routeName);
