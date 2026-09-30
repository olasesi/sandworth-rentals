<?php

/**
 * Seeds example tenants that cover the tenancy situations the admin screens have to handle:
 * a new tenant who has paid everything, a new tenant part way through the year, an old tenant
 * who renewed with arrears carried in, a mall tenant with the Toilet charge, and an old tenant
 * sitting on heavy arrears on both rent and service charge.
 *
 * Run from the project root:  php database/seed-tenant-scenarios.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script is for the command line only.\n");
    exit(1);
}

$root = dirname(__DIR__);

require_once $root . '/app/Core/Database.php';
require_once $root . '/app/Core/Platform.php';
require_once $root . '/app/Support/helpers.php';

$dbConfig = require $root . '/config/database.php';
$database = new App\Core\Database($dbConfig, $root . '/database/schema.sql');
$platform = new App\Core\Platform($database, $root . '/storage');
$platform->initialize();

$admin = $database->fetchOne("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");

if (! $admin) {
    fwrite(STDERR, "No admin user was found to attribute the seed data to.\n");
    exit(1);
}

$failures = 0;

$say = function ($message) {
    echo $message . PHP_EOL;
};

$fail = function ($message) use (&$failures) {
    $failures++;
    echo '  ! ' . $message . PHP_EOL;
};

/**
 * Removes a unit from the seeded scenarios so the script can be run again without piling up
 * duplicate tenants. The identifiers below belong to the seed alone, so the row the app created
 * for the tenant is deleted along with the tenant's own records.
 */
$resetUnit = function ($unitTable, $unitId, $db, $platform) {
    $registration = $platform->findTenantRegistration($unitTable, $unitId);

    if ($registration) {
        $userId = (int) $registration['userId'];
        $propertyId = (int) $registration['propertyId'];

        $db->execute('DELETE FROM rent_period_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id', array('unit_table' => $unitTable, 'unit_id' => $unitId));
        $db->execute('DELETE FROM service_charge_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id', array('unit_table' => $unitTable, 'unit_id' => $unitId));
        $db->execute('DELETE FROM key_collections WHERE unit_table = :unit_table AND unit_id = :unit_id', array('unit_table' => $unitTable, 'unit_id' => $unitId));
        $db->execute('DELETE FROM rent_reminders WHERE property_id = :property_id AND unit_id = :unit_id', array('property_id' => $propertyId, 'unit_id' => $unitId));
        $db->execute('DELETE FROM tenure_history WHERE unit_table = :unit_table AND unit_id = :unit_id', array('unit_table' => $unitTable, 'unit_id' => $unitId));
        $db->execute('DELETE FROM unit_history WHERE unit_table = :unit_table AND unit_id = :unit_id', array('unit_table' => $unitTable, 'unit_id' => $unitId));
        $db->execute('DELETE FROM payments WHERE property_id = :property_id AND unit_id = :unit_id', array('property_id' => $propertyId, 'unit_id' => $unitId));
        $db->execute('DELETE FROM users WHERE id = :id', array('id' => $userId));
    }

    $db->execute('DELETE FROM `' . $unitTable . '` WHERE id = :id', array('id' => $unitId));
};

$scenarios = array(
    array(
        'title' => 'New tenant, tenure paid in full and nothing owed',
        'unitTable' => 'residential_units',
        'payload' => array(
            'tenant_name' => 'Ada Nwachukwu',
            'tenant_email' => 'ada.nwachukwu@sandworth.test',
            'tenant_phone' => '+2348031002001',
            'property_id' => 707,
            'block' => 'Block B',
            'unit_number' => 'House 16',
            'tenure' => '2',
            'start_date' => '2026-04-01',
            'end_date' => '2028-03-31',
            'service_charge' => '25000',
            'security_deposit' => '1000000',
            'legal_fee' => '303000',
            'vat_fee' => '454500',
            'subscription_form' => '10000',
            'rent_paid_at_registration' => '12000000',
            'notes' => 'Paid the whole two year term up front, so there is nothing outstanding.',
        ),
        'payments' => array(
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 300000, 'paid_at' => '2026-04-01', 'channel' => 'transfer', 'label' => 'First year, April 2026 to March 2027'),
        ),
    ),
    array(
        'title' => 'New tenant paying monthly, only the rest of the current year owed',
        'unitTable' => 'residential_units',
        'payload' => array(
            'tenant_name' => 'Bode Adeyemi',
            'tenant_email' => 'bode.adeyemi@sandworth.test',
            'tenant_phone' => '+2348031002002',
            'property_id' => 707,
            'block' => 'Block C',
            'unit_number' => 'House 17',
            'tenure' => '2',
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'service_charge' => '25000',
            'security_deposit' => '1000000',
            'legal_fee' => '345000',
            'vat_fee' => '517500',
            'subscription_form' => '10000',
            'rent_paid_at_registration' => '4500000',
            'notes' => 'Pays rent monthly, so the balance is only the months left in the running year.',
        ),
        'payments' => array(
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 225000, 'paid_at' => '2026-09-27', 'channel' => 'transfer', 'label' => 'January to September 2026'),
        ),
    ),
    array(
        'title' => 'Old tenant who renewed and is still carrying arrears from the first tenure',
        'unitTable' => 'residential_units',
        'payload' => array(
            'tenant_name' => 'Chioma Nwankwo',
            'tenant_email' => 'chioma.nwankwo@sandworth.test',
            'tenant_phone' => '+2348031002003',
            'property_id' => 707,
            'block' => 'Block A',
            'unit_number' => 'House 16',
            'tenure' => '2',
            'start_date' => '2022-07-01',
            'end_date' => '2024-06-30',
            'service_charge' => '25000',
            'security_deposit' => '1000000',
            'legal_fee' => '387000',
            'vat_fee' => '580500',
            'subscription_form' => '10000',
            'rent_paid_at_registration' => '0',
            'notes' => 'Paid most of the first two years, then renewed with the rest still owing.',
        ),
        'firstTenurePayments' => array(
            array('type' => 'rent', 'period' => '', 'amount' => 8400000, 'paid_at' => '2022-07-01', 'channel' => 'transfer', 'label' => 'First two years, part payment'),
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 240000, 'paid_at' => '2022-07-01', 'channel' => 'transfer', 'label' => 'First year, July 2022 to June 2023'),
        ),
        'renewal' => array(
            'tenure' => '5',
            'start_date' => '2024-07-01',
            'end_date' => '2029-06-30',
            'service_charge' => '25000',
            'security_deposit' => '1000000',
            'rent_paid_at_renewal' => 7740000,
            'notes' => 'Renewed for five years. The balance from the first tenure was carried across.',
        ),
        'payments' => array(
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 150000, 'paid_at' => '2024-12-20', 'channel' => 'transfer', 'label' => 'July to December 2024'),
            array('type' => 'service_charge', 'period' => 'y2', 'amount' => 300000, 'paid_at' => '2026-01-18', 'channel' => 'transfer', 'label' => 'July 2025 to June 2026, full year'),
            array('type' => 'rent', 'period' => 'y2', 'amount' => 2000000, 'paid_at' => '2026-08-14', 'channel' => 'transfer', 'label' => 'Part payment towards year 2'),
        ),
    ),
    array(
        'title' => 'Mall tenant with the Toilet charge and rent plus service charge owing',
        'unitTable' => 'mall_shops',
        'payload' => array(
            'tenant_name' => 'Emeka Uche',
            'tenant_email' => 'emeka.uche@sandworth.test',
            'tenant_phone' => '+2348031002004',
            'property_id' => 703,
            'shop_number' => 'SHOP-21',
            'shop_name' => 'Ngozi Ogunleye Cafe',
            'tenure' => '3',
            'start_date' => '2025-01-01',
            'end_date' => '2027-12-31',
            'service_charge' => '30000',
            'security_deposit' => '2000000',
            'legal_fee' => '480000',
            'vat_fee' => '720000',
            'subscription_form' => '15000',
            'toilet_fee' => '250000',
            'rent_paid_at_registration' => '0',
            'notes' => 'Shop with the Toilet charge. The first year of rent is paid, the rest is still owing.',
        ),
        'payments' => array(
            array('type' => 'rent', 'period' => 'y1', 'amount' => 6000000, 'paid_at' => '2025-01-01', 'channel' => 'transfer', 'label' => 'First year, full year'),
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 360000, 'paid_at' => '2025-01-01', 'channel' => 'transfer', 'label' => 'First year, full year'),
            array('type' => 'service_charge', 'period' => 'y2', 'amount' => 180000, 'paid_at' => '2026-06-20', 'channel' => 'cash', 'label' => 'January to June 2026'),
            array('type' => 'rent', 'period' => 'y2', 'amount' => 2400000, 'paid_at' => '2026-07-30', 'channel' => 'transfer', 'label' => 'Part payment towards year 2'),
        ),
    ),
    array(
        'title' => 'Old tenant on heavy arrears for both rent and service charge',
        'unitTable' => 'residential_units',
        'payload' => array(
            'tenant_name' => 'Fatima Bello',
            'tenant_email' => 'fatima.bello@sandworth.test',
            'tenant_phone' => '+2348031002005',
            'property_id' => 701,
            'block' => 'Block C',
            'unit_number' => 'House 16',
            'tenure' => '3',
            'start_date' => '2023-01-01',
            'end_date' => '2025-12-31',
            'service_charge' => '25000',
            'security_deposit' => '500000',
            'legal_fee' => '180000',
            'vat_fee' => '270000',
            'subscription_form' => '10000',
            'rent_paid_at_registration' => '3600000',
            'status' => 'inactive',
            'notes' => 'Moved out without clearing the balance. The unit is held as inactive with the arrears still on the ledger.',
        ),
        'payments' => array(
            array('type' => 'rent', 'period' => 'y2', 'amount' => 3600000, 'paid_at' => '2024-03-15', 'channel' => 'cash', 'label' => 'Second year paid late'),
            array('type' => 'service_charge', 'period' => 'y1', 'amount' => 150000, 'paid_at' => '2023-07-20', 'channel' => 'cash', 'label' => 'First half of 2023'),
        ),
    ),
);

foreach ($scenarios as $scenario) {
    $say('');
    $say('== ' . $scenario['title']);
    $unitTable = $scenario['unitTable'];
    $unitId = 0;

    if ($unitTable === 'mall_shops') {
        $existingRows = $database->fetchAll('SELECT id FROM mall_shops WHERE property_id = :property_id AND shop_number = :shop_number', array('property_id' => (int) $scenario['payload']['property_id'], 'shop_number' => $scenario['payload']['shop_number']));
    } else {
        $existingRows = $database->fetchAll('SELECT id FROM residential_units WHERE property_id = :property_id AND block = :block AND unit_number = :unit_number', array('property_id' => (int) $scenario['payload']['property_id'], 'block' => $scenario['payload']['block'], 'unit_number' => $scenario['payload']['unit_number']));
    }

    foreach ($existingRows as $existing) {
        $resetUnit($unitTable, (int) $existing['id'], $database, $platform);
    }

    $payload = $scenario['payload'];
    $payload['unit_table'] = $unitTable;
    $payload['status'] = isset($payload['status']) ? $payload['status'] : 'active';

    list($tenant, $error) = $platform->saveTenantRegistrationByAdmin($unitTable, 0, $payload, $admin);

    if (! $tenant) {
        $fail('registration: ' . $error);
        continue;
    }

    $unitId = (int) $tenant['unitId'];
    $say('  registered ' . $tenant['user']['name'] . ' on ' . $unitTable . ' #' . $unitId);

    $postPayments = function (array $payments) use ($platform, $admin, $unitTable, $unitId, $say, $fail) {
        foreach ($payments as $payment) {
            $paymentPayload = array(
                'unit_table' => $unitTable,
                'unit_id' => $unitId,
                'charge_type' => $payment['type'],
                'period_key' => $payment['period'],
                'amount' => $payment['amount'],
                'label' => $payment['label'],
                'channel' => $payment['channel'],
                'paid_at' => $payment['paid_at'] . ' 12:00:00',
            );

            list($tenant, $paymentError) = $platform->addUnitPaymentByAdmin($unitTable, $unitId, $paymentPayload, $admin);

            if (! $tenant) {
                $fail('payment of ' . number_format($payment['amount']) . ' for ' . ($payment['period'] !== '' ? $payment['period'] : 'the tenure') . ': ' . $paymentError);
                continue;
            }

            $say('  posted ' . number_format($payment['amount']) . ' against ' . ($payment['period'] !== '' ? $payment['period'] : 'the tenure'));
        }
    };

    // Payments made during the first tenancy have to be in place before the renewal so the
    // arrears carried into the new tenure are worked out correctly.
    if (isset($scenario['firstTenurePayments'])) {
        $postPayments($scenario['firstTenurePayments']);
    }

    if (isset($scenario['renewal'])) {
        $renewal = $scenario['renewal'];
        $renewal['unit_table'] = $unitTable;
        $renewal['unit_id'] = $unitId;
        list($tenant, $renewError) = $platform->renewTenantTenureByAdmin($unitTable, $unitId, $renewal, $admin);

        if (! $tenant) {
            $fail('renewal: ' . $renewError);
            continue;
        }

        $say('  renewed to ' . $tenant['tenure'] . ' from ' . $tenant['startDate']);
    }

    $postPayments((array) (isset($scenario['payments']) ? $scenario['payments'] : array()));

    $registration = $platform->findTenantRegistration($unitTable, $unitId);
    $balances = $platform->registrationBalanceBreakdown($registration);
    $scSummary = $platform->serviceChargeSummaryForRegistration($registration);
    $say('  rent owed ' . number_format($balances['totalOwed']) . ' | service charge owed ' . number_format($scSummary['outstanding']));
}

$say('');
$say($failures === 0 ? 'All scenarios were seeded.' : $failures . ' problem(s) while seeding.');

exit($failures === 0 ? 0 : 1);
