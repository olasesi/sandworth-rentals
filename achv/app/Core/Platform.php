<?php

namespace App\Core;

final class Platform
{
    private const BOOTSTRAP_VERSION = '2026-08-10-1';

    private $database;
    private $legacyStoragePath;
    private $projectRoot;
    private $settingsCache;
    private $propertyCache;
    private $userCache;
    private $tourSlotCache;

    public function __construct(Database $database, $legacyStoragePath)
    {
        $this->database = $database;
        $this->legacyStoragePath = $legacyStoragePath;
        $this->projectRoot = dirname(dirname(__DIR__));
        $this->settingsCache = array();
        $this->propertyCache = array();
        $this->userCache = array();
        $this->tourSlotCache = array();
    }

    public function initialize()
    {
        $this->database->ensureSchema();

        if (! $this->needsBootstrapRefresh()) {
            return;
        }

        $this->importLegacyDataIfNeeded();
        $this->ensureDefaultSettings();
        $this->ensureDefaultAdminUser();
        $this->ensureDefaultPageContent();
        $this->setSetting('platform_bootstrap_version', self::BOOTSTRAP_VERSION);
    }

    public function currencyCode()
    {
        return $this->setting('currency_code', 'NGN');
    }

    public function currencySymbol()
    {
        return $this->currencyCode() === 'NGN' ? '₦' : '$';
    }

    public function updateCurrencyCode($code)
    {
        $code = strtoupper(trim((string) $code));

        if (! in_array($code, array('USD', 'NGN'), true)) {
            return false;
        }

        $this->setSetting('currency_code', $code);

        return $code;
    }

    public function siteSettings()
    {
        $defaults = $this->siteSettingDefaults();
        $settings = array();

        foreach ($defaults as $key => $defaultValue) {
            $settings[$key] = (string) $this->setting($key, $defaultValue);
        }

        return array(
            'siteName' => $settings['site_name'],
            'siteBaseUrl' => $settings['site_base_url'],
            'defaultMetaTitle' => $settings['default_meta_title'],
            'defaultMetaDescription' => $settings['default_meta_description'],
            'defaultShareImage' => function_exists('app_canonical_public_path')
                ? \app_canonical_public_path($settings['default_share_image'])
                : $settings['default_share_image'],
            'robotsPolicy' => $settings['robots_policy'],
            'contactEmail' => $settings['contact_email'],
            'contactPhone' => $settings['contact_phone'],
            'operationalOffice' => $settings['operational_office'],
            'registeredOffice' => $settings['registered_office'],
            'facebookUrl' => $settings['facebook_url'],
            'instagramUrl' => $settings['instagram_url'],
            'xUrl' => $settings['x_url'],
            'linkedinUrl' => $settings['linkedin_url'],
            'twitterHandle' => $settings['twitter_handle'],
        );
    }

    public function updateSiteSettings(array $payload)
    {
        $defaults = $this->siteSettingDefaults();
        $sanitized = array();

        foreach ($defaults as $key => $defaultValue) {
            $value = isset($payload[$key]) ? trim((string) $payload[$key]) : $defaultValue;

            if ($key === 'site_base_url') {
                $value = rtrim($value, '/');
            }

            if ($key === 'default_share_image' && function_exists('app_canonical_public_path')) {
                $value = \app_canonical_public_path($value);
            }

            if ($key === 'robots_policy' && $value === '') {
                $value = $defaultValue;
            }

            if ($value === '') {
                $value = $defaultValue;
            }

            $sanitized[$key] = $value;
            $this->setSetting($key, $value);
        }

        $this->syncPublicSeoFiles();

        return $this->siteSettings();
    }

    public function rentalStats()
    {
        $properties = $this->allRentalProperties();
        $monthlyVolume = 0;
        $availableCount = 0;

        foreach ($properties as $property) {
            $monthlyVolume += (int) $property['monthlyRent'];

            if ($property['status'] === 'available') {
                $availableCount++;
            }
        }

        return array(
            'activeRentals' => count($properties),
            'availableRentals' => $availableCount,
            'avgDaysToLease' => count($properties) > 0 ? 11 : 0,
            'monthlyVolume' => $monthlyVolume,
        );
    }

    public function managerSummary()
    {
        $occupiedUnits = (int) $this->database->fetchValue("SELECT COUNT(*) FROM tenancies WHERE status = 'active'");
        $totalManaged = (int) $this->database->fetchValue("SELECT COUNT(*) FROM properties WHERE purpose IN ('rent', 'commercial')");
        $monthStart = date('Y-m-01 00:00:00');
        $monthlyCollected = (int) $this->database->fetchValue(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE created_at >= :month_start',
            array('month_start' => $monthStart)
        );

        return array(
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => max($totalManaged - $occupiedUnits, 0),
            'monthlyCollected' => $this->formatMoney($monthlyCollected),
            'openMaintenance' => 0,
        );
    }

    public function adminPortfolio()
    {
        $contentSummary = $this->database->fetchOne(
            'SELECT COUNT(DISTINCT page_key) AS page_count, COUNT(*) AS block_count FROM page_content_blocks'
        );

        return array(
            'activeProperties' => (int) $this->database->fetchValue('SELECT COUNT(*) FROM properties'),
            'submittedApplications' => (int) $this->database->fetchValue('SELECT COUNT(*) FROM applications'),
            'approvedApplications' => (int) $this->database->fetchValue(
                "SELECT COUNT(*) FROM applications WHERE status IN ('approved', 'active')"
            ),
            'contentPages' => $contentSummary ? (int) $contentSummary['page_count'] : 0,
            'contentBlocks' => $contentSummary ? (int) $contentSummary['block_count'] : 0,
        );
    }

    public function pipelineSummary()
    {
        $submitted = (int) $this->database->fetchValue("SELECT COUNT(*) FROM applications WHERE status = 'submitted'");
        $approved = (int) $this->database->fetchValue("SELECT COUNT(*) FROM applications WHERE status = 'approved'");
        $active = (int) $this->database->fetchValue("SELECT COUNT(*) FROM applications WHERE status = 'active'");

        return array(
            array(
                'stage' => 'New applications',
                'count' => $submitted,
                'description' => 'Applicants waiting on admin review, screening, and next-step decisions.',
            ),
            array(
                'stage' => 'Approved to pay',
                'count' => $approved,
                'description' => 'Applicants who can now complete move-in charges online.',
            ),
            array(
                'stage' => 'Active tenancies',
                'count' => $active,
                'description' => 'Approved applicants who have paid and moved into the tenancy ledger.',
            ),
        );
    }

    public function pageContent($pageKey)
    {
        $defaults = $this->defaultPageContent();
        $content = isset($defaults[$pageKey]) ? $defaults[$pageKey] : array();
        $rows = $this->database->fetchAll(
            'SELECT block_key, content_json FROM page_content_blocks WHERE page_key = :page_key ORDER BY id ASC',
            array('page_key' => (string) $pageKey)
        );

        foreach ($rows as $row) {
            $content[$row['block_key']] = $this->decodeJsonValue($row['content_json']);
        }

        return $content;
    }

    public function adminPageContentBlocks()
    {
        $rows = $this->database->fetchAll(
            'SELECT id, page_key, block_key, content_json, updated_at FROM page_content_blocks ORDER BY page_key ASC, id ASC'
        );
        $pages = array();

        foreach ($rows as $row) {
            $pageKey = (string) $row['page_key'];

            if (! isset($pages[$pageKey])) {
                $pages[$pageKey] = array(
                    'pageKey' => $pageKey,
                    'label' => $this->humanizeKey($pageKey),
                    'blocks' => array(),
                );
            }

            $decodedContent = $this->decodeJsonValue($row['content_json']);

            $pages[$pageKey]['blocks'][] = array(
                'id' => (int) $row['id'],
                'pageKey' => $pageKey,
                'blockKey' => (string) $row['block_key'],
                'label' => $this->humanizeKey((string) $row['block_key']),
                'content' => $decodedContent,
                'contentJson' => $this->encodePrettyJson($decodedContent),
                'updatedAt' => (string) $row['updated_at'],
            );
        }

        return array_values($pages);
    }

    public function savePageContentBlock($pageKey, $blockKey, $contentJson)
    {
        $pageKey = trim((string) $pageKey);
        $blockKey = trim((string) $blockKey);
        $contentJson = trim((string) $contentJson);

        if ($pageKey === '' || $blockKey === '') {
            return array(null, 'Page key and block key are required.');
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $pageKey) || ! preg_match('/^[A-Za-z0-9_-]+$/', $blockKey)) {
            return array(null, 'Use only letters, numbers, hyphens, and underscores for page and block keys.');
        }

        if ($contentJson === '') {
            return array(null, 'Content JSON cannot be empty.');
        }

        $decoded = json_decode($contentJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(null, 'Content JSON is invalid: ' . json_last_error_msg() . '.');
        }

        $normalizedJson = json_encode($decoded, JSON_UNESCAPED_SLASHES);

        if ($normalizedJson === false) {
            return array(null, 'The content block could not be encoded for storage.');
        }

        $existing = $this->database->fetchOne(
            'SELECT id FROM page_content_blocks WHERE page_key = :page_key AND block_key = :block_key LIMIT 1',
            array(
                'page_key' => $pageKey,
                'block_key' => $blockKey,
            )
        );
        $now = $this->now();

        if ($existing) {
            $this->database->execute(
                'UPDATE page_content_blocks SET content_json = :content_json, updated_at = :updated_at WHERE id = :id',
                array(
                    'content_json' => $normalizedJson,
                    'updated_at' => $now,
                    'id' => (int) $existing['id'],
                )
            );
        } else {
            $this->database->insert('page_content_blocks', array(
                'page_key' => $pageKey,
                'block_key' => $blockKey,
                'content_json' => $normalizedJson,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        return array(
            array(
                'pageKey' => $pageKey,
                'blockKey' => $blockKey,
                'content' => $decoded,
            ),
            null,
        );
    }

    public function allProperties(array $filters = array())
    {
        $sql = 'SELECT * FROM properties WHERE 1 = 1';
        $params = array();

        if (isset($filters['purpose']) && $filters['purpose'] !== '') {
            $sql .= ' AND purpose = :purpose';
            $params['purpose'] = $filters['purpose'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        return $this->hydrateProperties($this->database->fetchAll($sql, $params));
    }

    public function allRentalProperties(array $filters = array())
    {
        $sql = "SELECT * FROM properties WHERE purpose = 'rent'";
        $params = array();

        if (isset($filters['city']) && $filters['city'] !== '') {
            $sql .= ' AND location LIKE :city';
            $params['city'] = '%' . trim($filters['city']) . '%';
        }

        if (isset($filters['beds']) && $filters['beds'] !== '') {
            $sql .= ' AND beds >= :beds';
            $params['beds'] = (int) $filters['beds'];
        }

        if (isset($filters['petFriendly']) && $filters['petFriendly']) {
            $sql .= ' AND pet_friendly = 1';
        }

        if (isset($filters['market']) && $filters['market'] !== '') {
            $sql .= ' AND location LIKE :market';
            $params['market'] = '%' . trim($filters['market']) . '%';
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        return $this->hydrateProperties($this->database->fetchAll($sql, $params));
    }

    public function allSaleProperties(array $filters = array())
    {
        $sql = "SELECT * FROM properties WHERE purpose = 'sale'";
        $params = array();

        if (isset($filters['location']) && $filters['location'] !== '') {
            $sql .= ' AND location LIKE :location';
            $params['location'] = '%' . trim($filters['location']) . '%';
        }

        if (isset($filters['beds']) && $filters['beds'] !== '') {
            $sql .= ' AND beds >= :beds';
            $params['beds'] = (int) $filters['beds'];
        }

        if (isset($filters['propertyType']) && $filters['propertyType'] !== '') {
            $sql .= ' AND type = :property_type';
            $params['property_type'] = trim($filters['propertyType']);
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        return $this->hydrateProperties($this->database->fetchAll($sql, $params));
    }

    public function allCommercialProperties(array $filters = array())
    {
        $sql = "SELECT * FROM properties WHERE purpose = 'commercial'";
        $params = array();

        if (isset($filters['commercialType']) && $filters['commercialType'] !== '') {
            $sql .= ' AND type = :commercial_type';
            $params['commercial_type'] = trim($filters['commercialType']);
        }

        if (isset($filters['location']) && $filters['location'] !== '') {
            $sql .= ' AND location LIKE :location';
            $params['location'] = '%' . trim($filters['location']) . '%';
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        return $this->hydrateProperties($this->database->fetchAll($sql, $params));
    }

    public function findProperty($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            return null;
        }

        if (array_key_exists($id, $this->propertyCache)) {
            return $this->propertyCache[$id];
        }

        $row = $this->database->fetchOne('SELECT * FROM properties WHERE id = :id LIMIT 1', array('id' => $id));

        if (! $row) {
            $this->propertyCache[$id] = null;

            return null;
        }

        $property = $this->hydrateProperty($row);
        $this->propertyCache[$id] = $property;

        return $property;
    }

    public function availableTourSlotsForProperty($propertyId)
    {
        $rows = $this->database->fetchAll(
            "SELECT ts.*
             FROM tour_slots ts
             LEFT JOIN tour_requests tr
               ON tr.slot_id = ts.id
              AND tr.status IN ('requested', 'confirmed')
             WHERE ts.property_id = :property_id
               AND ts.status = 'open'
               AND ts.starts_at >= :now
               AND tr.id IS NULL
             ORDER BY ts.starts_at ASC, ts.id ASC",
            array(
                'property_id' => (int) $propertyId,
                'now' => $this->now(),
            )
        );

        return $this->hydrateTourSlots($rows);
    }

    public function upcomingTourSlots(array $filters = array())
    {
        $sql = 'SELECT ts.* FROM tour_slots ts';
        $params = array('now' => $this->now());
        $conditions = array('ts.starts_at >= :now');

        if (! empty($filters['available_only'])) {
            $sql .= " LEFT JOIN tour_requests tr
                ON tr.slot_id = ts.id
               AND tr.status IN ('requested', 'confirmed')";
            $conditions[] = 'tr.id IS NULL';
        }

        if (isset($filters['property_id']) && (int) $filters['property_id'] > 0) {
            $conditions[] = 'ts.property_id = :property_id';
            $params['property_id'] = (int) $filters['property_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = 'ts.status = :status';
            $params['status'] = trim((string) $filters['status']);
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);
        $sql .= ' ORDER BY ts.starts_at ASC, ts.id ASC';

        return $this->hydrateTourSlots($this->database->fetchAll($sql, $params));
    }

    public function findTourSlot($slotId)
    {
        $slotId = (int) $slotId;

        if ($slotId <= 0) {
            return null;
        }

        if (array_key_exists($slotId, $this->tourSlotCache)) {
            return $this->tourSlotCache[$slotId];
        }

        $row = $this->database->fetchOne(
            'SELECT * FROM tour_slots WHERE id = :id LIMIT 1',
            array('id' => $slotId)
        );

        if (! $row) {
            $this->tourSlotCache[$slotId] = null;

            return null;
        }

        $slot = $this->hydrateTourSlot($row);
        $this->tourSlotCache[$slotId] = $slot;

        return $slot;
    }

    public function createTourSlot($propertyId, array $payload, $adminUser)
    {
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'Choose a valid property before adding a tour slot.');
        }

        $startsAt = $this->normalizeDateTimeInput(isset($payload['starts_at']) ? $payload['starts_at'] : '');
        $endsAt = $this->normalizeDateTimeInput(isset($payload['ends_at']) ? $payload['ends_at'] : '');
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');

        if ($startsAt === '' || $endsAt === '') {
            return array(false, 'Start and end times are required for every tour slot.');
        }

        if (strtotime($startsAt) === false || strtotime($endsAt) === false) {
            return array(false, 'Enter valid date and time values for the tour slot.');
        }

        if (strtotime($startsAt) < time()) {
            return array(false, 'Tour slots must be scheduled in the future.');
        }

        if (strtotime($endsAt) <= strtotime($startsAt)) {
            return array(false, 'The slot end time must be later than the start time.');
        }

        $overlap = $this->database->fetchOne(
            "SELECT id
             FROM tour_slots
             WHERE property_id = :property_id
               AND status = 'open'
               AND NOT (ends_at <= :starts_at OR starts_at >= :ends_at)
             LIMIT 1",
            array(
                'property_id' => (int) $propertyId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            )
        );

        if ($overlap) {
            return array(false, 'That slot overlaps an existing availability window for this property.');
        }

        $slotId = $this->database->insert('tour_slots', array(
            'property_id' => (int) $propertyId,
            'status' => 'open',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $notes,
            'created_by_name' => isset($adminUser['name']) ? (string) $adminUser['name'] : 'Sandworth Admin',
            'created_at' => $this->now(),
        ));

        unset($this->tourSlotCache[$slotId]);

        return array($this->findTourSlot($slotId), null);
    }

    public function cancelTourSlot($slotId)
    {
        $slot = $this->findTourSlot($slotId);

        if (! $slot) {
            return array(false, 'That tour slot could not be found.');
        }

        if ($slot['status'] !== 'open') {
            return array(false, 'That tour slot is no longer open.');
        }

        $activeRequest = $this->database->fetchOne(
            "SELECT id FROM tour_requests WHERE slot_id = :slot_id AND status IN ('requested', 'confirmed') LIMIT 1",
            array('slot_id' => (int) $slotId)
        );

        if ($activeRequest) {
            return array(false, 'That slot already has an active tour request. Update the request first before removing the slot.');
        }

        $updatedRows = $this->database->execute(
            "UPDATE tour_slots SET status = 'cancelled' WHERE id = :id",
            array('id' => (int) $slotId)
        );

        if ($updatedRows < 1) {
            return array(false, 'The tour slot could not be removed right now.');
        }

        unset($this->tourSlotCache[(int) $slotId]);

        return array($this->findTourSlot($slotId), null);
    }

    public function activeTourRequestForUserAndProperty($userId, $propertyId)
    {
        $row = $this->database->fetchOne(
            "SELECT *
             FROM tour_requests
             WHERE user_id = :user_id
               AND property_id = :property_id
               AND status IN ('requested', 'confirmed')
             ORDER BY id DESC
             LIMIT 1",
            array(
                'user_id' => (int) $userId,
                'property_id' => (int) $propertyId,
            )
        );

        return $row ? $this->hydrateTourRequest($row) : null;
    }

    public function createTourRequest($user, $property, array $payload)
    {
        $slotId = isset($payload['slot_id']) ? (int) $payload['slot_id'] : 0;
        $slot = $this->findTourSlot($slotId);

        if (! $slot || (int) $slot['propertyId'] !== (int) $property['id']) {
            return array(false, 'Choose one of the currently available tour times for this property.');
        }

        if ($slot['status'] !== 'open' || strtotime($slot['startsAt']) < time()) {
            return array(false, 'That tour slot is no longer available. Please choose another time.');
        }

        $existingForUser = $this->activeTourRequestForUserAndProperty($user['id'], $property['id']);

        if ($existingForUser) {
            return array(false, 'You already have an active tour request for this property.');
        }

        $existingForSlot = $this->database->fetchOne(
            "SELECT id FROM tour_requests WHERE slot_id = :slot_id AND status IN ('requested', 'confirmed') LIMIT 1",
            array('slot_id' => $slotId)
        );

        if ($existingForSlot) {
            return array(false, 'That tour slot was just taken. Please choose another time.');
        }

        $now = $this->now();
        $requestId = $this->database->insert('tour_requests', array(
            'property_id' => (int) $property['id'],
            'slot_id' => $slotId,
            'user_id' => (int) $user['id'],
            'status' => 'requested',
            'full_name' => isset($user['name']) ? (string) $user['name'] : '',
            'email' => isset($user['email']) ? (string) $user['email'] : '',
            'phone' => isset($user['phone']) ? (string) $user['phone'] : '',
            'message' => trim(isset($payload['message']) ? $payload['message'] : ''),
            'admin_notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'confirmed_at' => null,
            'confirmed_by_name' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ));

        unset($this->tourSlotCache[$slotId]);

        return array($this->findTourRequest($requestId), null);
    }

    public function createUser(array $payload)
    {
        $name = trim(isset($payload['name']) ? $payload['name'] : '');
        $email = strtolower(trim(isset($payload['email']) ? $payload['email'] : ''));
        $phone = trim(isset($payload['phone']) ? $payload['phone'] : '');
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($name === '' || $email === '' || $phone === '' || $password === '') {
            return array(false, 'Please complete every required account field.');
        }

        if ($this->findUserByEmail($email)) {
            return array(false, 'An account already exists with that email.');
        }

        $userId = $this->database->insert('users', array(
            'role' => 'user',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => $this->now(),
        ));

        return array($this->findUserById($userId), null);
    }

    public function findUserByEmail($email)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            array('email' => strtolower(trim((string) $email)))
        );

        return $row ? $this->hydrateUser($row) : null;
    }

    public function findUserById($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            return null;
        }

        if (array_key_exists($id, $this->userCache)) {
            return $this->userCache[$id];
        }

        $row = $this->database->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', array('id' => $id));

        if (! $row) {
            $this->userCache[$id] = null;

            return null;
        }

        $user = $this->hydrateUser($row);
        $this->userCache[$id] = $user;

        return $user;
    }

    public function createProperty(array $payload, array $files, $adminUser)
    {
        $purpose = trim(isset($payload['purpose']) ? $payload['purpose'] : 'rent');

        if (! in_array($purpose, array('rent', 'sale', 'commercial'), true)) {
            return array(false, 'Choose whether this listing is for rent, sale, or commercial lease.');
        }

        $title = trim(isset($payload['title']) ? $payload['title'] : '');
        $location = trim(isset($payload['location']) ? $payload['location'] : '');
        $type = trim(isset($payload['type']) ? $payload['type'] : '');
        $area = trim(isset($payload['area']) ? $payload['area'] : '');
        $image = $this->canonicalPath(isset($payload['image']) ? $payload['image'] : '');
        $summary = trim(isset($payload['summary']) ? $payload['summary'] : '');
        $availableDate = trim(isset($payload['available_date']) ? $payload['available_date'] : 'Available now');
        $monthlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        $askingPrice = $this->extractAmount(isset($payload['asking_price']) ? $payload['asking_price'] : 0);
        $serviceCharge = $this->extractAmount(isset($payload['service_charge']) ? $payload['service_charge'] : 0);
        $securityDeposit = $this->extractAmount(isset($payload['security_deposit']) ? $payload['security_deposit'] : 0);
        $uploadedPrimaryImage = $this->storeUploadedImage(isset($files['image_file']) ? $files['image_file'] : null, $purpose, $title);
        $uploadedGalleryImages = $this->storeUploadedGallery(isset($files['gallery_files']) ? $files['gallery_files'] : null, $purpose, $title);

        if ($uploadedPrimaryImage === false || $uploadedGalleryImages === false) {
            return array(false, 'The property image could not be uploaded. Please use JPG, PNG, GIF, or WebP files.');
        }

        if ($uploadedPrimaryImage) {
            $image = $uploadedPrimaryImage;
        }

        if ($title === '' || $location === '' || $type === '' || $image === '' || $summary === '') {
            return array(false, 'Title, location, type, main image, and summary are required for every listing.');
        }

        if ($purpose === 'rent' && $monthlyRent <= 0) {
            return array(false, 'Annual rent is required for rental listings.');
        }

        if (($purpose === 'sale' || $purpose === 'commercial') && $askingPrice <= 0 && $monthlyRent <= 0) {
            return array(false, 'Enter an asking price for sale or commercial listings.');
        }

        if ($purpose === 'commercial' && $monthlyRent <= 0) {
            $monthlyRent = $askingPrice;
        }

        $securityDeposit = $this->normalizeCautionDeposit($purpose, $securityDeposit);

        $images = $this->mergePrimaryImage(
            $image,
            array_merge(
                $uploadedGalleryImages,
                $this->canonicalPathList($this->explodeCsv(isset($payload['images']) ? $payload['images'] : ''))
            )
        );
        $now = $this->now();

        $propertyId = $this->database->insert('properties', array(
            'purpose' => $purpose,
            'title' => $title,
            'location' => $location,
            'type' => $type,
            'beds' => $this->normalizeInteger(isset($payload['beds']) ? $payload['beds'] : 0),
            'baths' => $this->normalizeDecimal(isset($payload['baths']) ? $payload['baths'] : 0),
            'area' => $area,
            'pet_friendly' => isset($payload['pet_friendly']) && $payload['pet_friendly'] === '1' ? 1 : 0,
            'available_date' => $availableDate,
            'image' => $image,
            'images_json' => json_encode($images),
            'badges_json' => json_encode($this->explodeCsv(isset($payload['badges']) ? $payload['badges'] : '')),
            'summary' => $summary,
            'features_json' => json_encode($this->explodeCsv(isset($payload['features']) ? $payload['features'] : '')),
            'lat' => $this->normalizeCoordinate(isset($payload['lat']) ? $payload['lat'] : ''),
            'lng' => $this->normalizeCoordinate(isset($payload['lng']) ? $payload['lng'] : ''),
            'monthly_rent' => $monthlyRent,
            'service_charge' => $serviceCharge,
            'security_deposit' => $securityDeposit,
            'asking_price' => $askingPrice,
            'commercial_type' => trim(isset($payload['commercial_type']) ? $payload['commercial_type'] : ''),
            'lease_term' => trim(isset($payload['lease_term']) ? $payload['lease_term'] : ''),
            'units' => $this->normalizeInteger(isset($payload['units']) ? $payload['units'] : 0),
            'floors' => $this->normalizeInteger(isset($payload['floors']) ? $payload['floors'] : 0),
            'status' => 'available',
            'listed_by' => isset($adminUser['name']) ? $adminUser['name'] : 'Sandworth Admin',
            'created_at' => $now,
            'updated_at' => $now,
        ));

        $this->syncPublicSeoFiles();
        unset($this->propertyCache[(int) $propertyId]);

        return array($this->findProperty($propertyId), null);
    }

    public function updateProperty($propertyId, array $payload, array $files, $adminUser)
    {
        $existing = $this->findProperty($propertyId);

        if (! $existing) {
            return array(false, 'That property could not be found for editing.');
        }

        $purpose = trim(isset($payload['purpose']) ? $payload['purpose'] : $existing['purpose']);

        if (! in_array($purpose, array('rent', 'sale', 'commercial'), true)) {
            return array(false, 'Choose whether this listing is for rent, sale, or commercial lease.');
        }

        $title = trim(isset($payload['title']) ? $payload['title'] : $existing['title']);
        $location = trim(isset($payload['location']) ? $payload['location'] : $existing['location']);
        $type = trim(isset($payload['type']) ? $payload['type'] : $existing['type']);
        $area = trim(isset($payload['area']) ? $payload['area'] : $existing['area']);
        $image = isset($existing['image']) ? $this->canonicalPath((string) $existing['image']) : '';
        $summary = trim(isset($payload['summary']) ? $payload['summary'] : $existing['summary']);
        $availableDate = trim(isset($payload['available_date']) ? $payload['available_date'] : $existing['availableDate']);
        $monthlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : $existing['monthlyRent']);
        $askingPrice = $this->extractAmount(isset($payload['asking_price']) ? $payload['asking_price'] : $existing['askingPrice']);
        $serviceCharge = $this->extractAmount(isset($payload['service_charge']) ? $payload['service_charge'] : $existing['serviceCharge']);
        $securityDeposit = $this->extractAmount(isset($payload['security_deposit']) ? $payload['security_deposit'] : $existing['securityDeposit']);
        $uploadedPrimaryImage = $this->storeUploadedImage(isset($files['image_file']) ? $files['image_file'] : null, $purpose, $title);
        $uploadedGalleryImages = $this->storeUploadedGallery(isset($files['gallery_files']) ? $files['gallery_files'] : null, $purpose, $title);

        if ($uploadedPrimaryImage === false || $uploadedGalleryImages === false) {
            return array(false, 'The property image could not be uploaded. Please use JPG, PNG, GIF, or WebP files.');
        }

        if ($uploadedPrimaryImage) {
            $image = $uploadedPrimaryImage;
        }

        if ($title === '' || $location === '' || $type === '' || $image === '' || $summary === '') {
            return array(false, 'Title, location, type, main image, and summary are required for every listing.');
        }

        if ($purpose === 'rent' && $monthlyRent <= 0) {
            return array(false, 'Annual rent is required for rental listings.');
        }

        if (($purpose === 'sale' || $purpose === 'commercial') && $askingPrice <= 0 && $monthlyRent <= 0) {
            return array(false, 'Enter an asking price for sale or commercial listings.');
        }

        if ($purpose === 'commercial' && $monthlyRent <= 0) {
            $monthlyRent = $askingPrice;
        }

        $securityDeposit = $this->normalizeCautionDeposit($purpose, $securityDeposit);

        $existingGallery = $this->canonicalPathList($this->explodeCsv(isset($payload['images']) ? $payload['images'] : ''));

        if ($existingGallery === array()) {
            $existingImages = isset($existing['images']) && is_array($existing['images']) ? $existing['images'] : array();
            $existingGallery = array_values(array_filter($this->canonicalPathList($existingImages), function ($galleryImage) use ($image) {
                return trim((string) $galleryImage) !== '' && trim((string) $galleryImage) !== trim((string) $image);
            }));
        }

        $images = $this->mergePrimaryImage(
            $image,
            array_merge($uploadedGalleryImages, $existingGallery)
        );
        $now = $this->now();

        $this->database->execute(
            'UPDATE properties SET
                purpose = :purpose,
                title = :title,
                location = :location,
                type = :type,
                beds = :beds,
                baths = :baths,
                area = :area,
                pet_friendly = :pet_friendly,
                available_date = :available_date,
                image = :image,
                images_json = :images_json,
                badges_json = :badges_json,
                summary = :summary,
                features_json = :features_json,
                lat = :lat,
                lng = :lng,
                monthly_rent = :monthly_rent,
                service_charge = :service_charge,
                security_deposit = :security_deposit,
                asking_price = :asking_price,
                commercial_type = :commercial_type,
                lease_term = :lease_term,
                units = :units,
                floors = :floors,
                listed_by = :listed_by,
                updated_at = :updated_at
             WHERE id = :id',
            array(
                'purpose' => $purpose,
                'title' => $title,
                'location' => $location,
                'type' => $type,
                'beds' => $this->normalizeInteger(isset($payload['beds']) ? $payload['beds'] : $existing['beds']),
                'baths' => $this->normalizeDecimal(isset($payload['baths']) ? $payload['baths'] : $existing['baths']),
                'area' => $area,
                'pet_friendly' => isset($payload['pet_friendly']) && $payload['pet_friendly'] === '1' ? 1 : 0,
                'available_date' => $availableDate,
                'image' => $image,
                'images_json' => json_encode($images),
                'badges_json' => json_encode($this->explodeCsv(isset($payload['badges']) ? $payload['badges'] : implode(', ', $existing['badges']))),
                'summary' => $summary,
                'features_json' => json_encode($this->explodeCsv(isset($payload['features']) ? $payload['features'] : implode(', ', $existing['features']))),
                'lat' => $this->normalizeCoordinate(isset($payload['lat']) ? $payload['lat'] : $existing['lat']),
                'lng' => $this->normalizeCoordinate(isset($payload['lng']) ? $payload['lng'] : $existing['lng']),
                'monthly_rent' => $monthlyRent,
                'service_charge' => $serviceCharge,
                'security_deposit' => $securityDeposit,
                'asking_price' => $askingPrice,
                'commercial_type' => trim(isset($payload['commercial_type']) ? $payload['commercial_type'] : $existing['commercialType']),
                'lease_term' => trim(isset($payload['lease_term']) ? $payload['lease_term'] : $existing['leaseTerm']),
                'units' => $this->normalizeInteger(isset($payload['units']) ? $payload['units'] : $existing['units']),
                'floors' => $this->normalizeInteger(isset($payload['floors']) ? $payload['floors'] : $existing['floors']),
                'listed_by' => isset($adminUser['name']) ? $adminUser['name'] : $existing['listedBy'],
                'updated_at' => $now,
                'id' => (int) $propertyId,
            )
        );

        $this->syncPublicSeoFiles();
        unset($this->propertyCache[(int) $propertyId]);

        return array($this->findProperty($propertyId), null);
    }

    public function deleteProperty($propertyId)
    {
        $propertyId = (int) $propertyId;
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'That property could not be found for deletion.');
        }

        $applicationCount = (int) $this->database->fetchValue(
            'SELECT COUNT(*) FROM applications WHERE property_id = :property_id',
            array('property_id' => $propertyId)
        );
        $tenancyCount = (int) $this->database->fetchValue(
            'SELECT COUNT(*) FROM tenancies WHERE property_id = :property_id',
            array('property_id' => $propertyId)
        );
        $paymentCount = (int) $this->database->fetchValue(
            'SELECT COUNT(*) FROM payments WHERE property_id = :property_id',
            array('property_id' => $propertyId)
        );

        if ($applicationCount > 0 || $tenancyCount > 0 || $paymentCount > 0) {
            return array(false, 'This property cannot be deleted because it already has linked applications, payments, or tenancy records.');
        }

        $deletedRows = $this->database->execute(
            'DELETE FROM properties WHERE id = :property_id LIMIT 1',
            array('property_id' => $propertyId)
        );

        if ($deletedRows < 1) {
            return array(false, 'The property could not be deleted right now.');
        }

        $this->syncPublicSeoFiles();
        unset($this->propertyCache[$propertyId]);

        return array($property, null);
    }

    public function createApplication($user, $property, array $payload)
    {
        $existing = $this->database->fetchOne(
            "SELECT id FROM applications WHERE user_id = :user_id AND property_id = :property_id AND status IN ('submitted', 'approved', 'active') LIMIT 1",
            array(
                'user_id' => (int) $user['id'],
                'property_id' => (int) $property['id'],
            )
        );

        if ($existing) {
            return array(false, 'You already have an active application for this property.');
        }

        $applicationId = $this->database->insert('applications', array(
            'user_id' => (int) $user['id'],
            'property_id' => (int) $property['id'],
            'status' => 'submitted',
            'full_name' => isset($user['name']) ? $user['name'] : '',
            'email' => isset($user['email']) ? $user['email'] : '',
            'phone' => isset($user['phone']) ? $user['phone'] : '',
            'annual_income' => trim(isset($payload['annual_income']) ? $payload['annual_income'] : ''),
            'employer' => trim(isset($payload['employer']) ? $payload['employer'] : ''),
            'move_in_date' => trim(isset($payload['move_in_date']) ? $payload['move_in_date'] : ''),
            'occupants' => max(1, $this->normalizeInteger(isset($payload['occupants']) ? $payload['occupants'] : 1)),
            'notes' => trim(isset($payload['notes']) ? $payload['notes'] : ''),
            'approved_by_name' => null,
            'approved_at' => null,
            'submitted_at' => $this->now(),
            'activated_at' => null,
        ));

        return array($this->findApplication($applicationId), null);
    }

    public function applicationsForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM applications WHERE user_id = :user_id ORDER BY submitted_at DESC, id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydrateApplications($rows);
    }

    public function allApplications(array $filters = array())
    {
        $sql = 'SELECT * FROM applications ORDER BY submitted_at DESC, id DESC';

        if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        $rows = $this->database->fetchAll($sql);

        return $this->hydrateApplications($rows);
    }

    public function tourRequestsForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT tr.*
             FROM tour_requests tr
             LEFT JOIN tour_slots ts ON ts.id = tr.slot_id
             WHERE tr.user_id = :user_id
             ORDER BY ts.starts_at ASC, tr.created_at DESC, tr.id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydrateTourRequests($rows);
    }

    public function allTourRequests(array $filters = array())
    {
        $sql = 'SELECT tr.* FROM tour_requests tr LEFT JOIN tour_slots ts ON ts.id = tr.slot_id WHERE 1 = 1';
        $params = array();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= ' AND tr.status = :status';
            $params['status'] = trim((string) $filters['status']);
        }

        if (! empty($filters['upcoming_only'])) {
            $sql .= " AND tr.status IN ('requested', 'confirmed') AND ts.ends_at >= :now";
            $params['now'] = $this->now();
        }

        $sql .= ' ORDER BY ts.starts_at ASC, tr.created_at DESC, tr.id DESC';

        if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return $this->hydrateTourRequests($this->database->fetchAll($sql, $params));
    }

    public function findTourRequest($requestId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM tour_requests WHERE id = :id LIMIT 1',
            array('id' => (int) $requestId)
        );

        return $row ? $this->hydrateTourRequest($row) : null;
    }

    public function findApplication($applicationId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM applications WHERE id = :id LIMIT 1',
            array('id' => (int) $applicationId)
        );

        return $row ? $this->hydrateApplication($row) : null;
    }

    public function approveApplication($applicationId, $adminUser)
    {
        $updatedRows = $this->database->execute(
            "UPDATE applications SET status = 'approved', approved_at = :approved_at, approved_by_name = :approved_by_name WHERE id = :id",
            array(
                'approved_at' => $this->now(),
                'approved_by_name' => isset($adminUser['name']) ? $adminUser['name'] : 'Sandworth Admin',
                'id' => (int) $applicationId,
            )
        );

        if ($updatedRows < 1) {
            return false;
        }

        return $this->findApplication($applicationId);
    }

    public function updateTourRequestStatus($requestId, $status, $adminUser, $adminNotes = '')
    {
        $request = $this->findTourRequest($requestId);
        $status = trim((string) $status);
        $allowedStatuses = array('confirmed', 'cancelled', 'completed');

        if (! $request) {
            return array(false, 'That tour request could not be found.');
        }

        if (! in_array($status, $allowedStatuses, true)) {
            return array(false, 'Choose a valid tour request action.');
        }

        if ($status === 'completed' && ! in_array($request['status'], array('confirmed', 'completed'), true)) {
            return array(false, 'Only confirmed tours can be marked as completed.');
        }

        if ($status === 'confirmed' && $request['status'] === 'cancelled') {
            return array(false, 'Cancelled tours cannot be confirmed again. Create a new request instead.');
        }

        $now = $this->now();
        $fields = array(
            'status' => $status,
            'admin_notes' => trim((string) $adminNotes),
            'updated_at' => $now,
            'id' => (int) $requestId,
        );
        $sql = 'UPDATE tour_requests SET status = :status, admin_notes = :admin_notes, updated_at = :updated_at';

        if ($status === 'confirmed') {
            $sql .= ', confirmed_at = :confirmed_at, confirmed_by_name = :confirmed_by_name';
            $fields['confirmed_at'] = $now;
            $fields['confirmed_by_name'] = isset($adminUser['name']) ? (string) $adminUser['name'] : 'Sandworth Admin';
        }

        if ($status === 'cancelled') {
            $sql .= ', cancelled_at = :cancelled_at';
            $fields['cancelled_at'] = $now;
        }

        if ($status === 'completed') {
            $sql .= ', completed_at = :completed_at';
            $fields['completed_at'] = $now;
        }

        $sql .= ' WHERE id = :id';

        $updatedRows = $this->database->execute($sql, $fields);

        if ($updatedRows < 1) {
            return array(false, 'The tour request could not be updated right now.');
        }

        unset($this->tourSlotCache[(int) $request['slotId']]);

        return array($this->findTourRequest($requestId), null);
    }

    public function activateTenancyFromApplication($applicationId, $user, array $payload)
    {
        $application = $this->findApplication($applicationId);

        if (! $application || (int) $application['userId'] !== (int) $user['id']) {
            return array(false, 'We could not find that approved application.');
        }

        if ($application['status'] !== 'approved' && $application['status'] !== 'active') {
            return array(false, 'This application is not ready for payment yet.');
        }

        $existingTenancy = $this->findTenancyByApplicationId($applicationId);

        if ($existingTenancy && $existingTenancy['status'] === 'active') {
            return array($existingTenancy, null);
        }

        $property = $application['property'];
        $now = $this->now();
        $paymentReference = $this->paymentReference($applicationId . $now . mt_rand(1000, 9999));
        $tenancyId = $existingTenancy ? (int) $existingTenancy['id'] : 0;
        $legalFee = $this->legalFeeAmount((int) $property['monthlyRent']);
        $cautionDeposit = $this->normalizeCautionDeposit((string) $property['purpose'], (int) $property['securityDeposit']);

        if (! $existingTenancy) {
            $tenancyId = $this->database->insert('tenancies', array(
                'user_id' => (int) $user['id'],
                'property_id' => (int) $property['id'],
                'application_id' => (int) $applicationId,
                'status' => 'active',
                'start_date' => $application['moveInDate'],
                'monthly_rent' => (int) $property['monthlyRent'],
                'service_charge' => (int) $property['serviceCharge'],
                'security_deposit' => $cautionDeposit,
                'created_at' => $now,
            ));
        } else {
            $this->database->execute(
                "UPDATE tenancies SET status = 'active', start_date = :start_date WHERE id = :id",
                array(
                    'start_date' => $application['moveInDate'],
                    'id' => $tenancyId,
                )
            );
        }

        $totalAmount = (int) $property['monthlyRent'] + (int) $property['serviceCharge'] + $legalFee + $cautionDeposit;
        $this->database->insert('payments', array(
            'user_id' => (int) $user['id'],
            'property_id' => (int) $property['id'],
            'application_id' => (int) $applicationId,
            'tenancy_id' => $tenancyId,
            'amount' => $totalAmount,
            'channel' => trim(isset($payload['channel']) ? $payload['channel'] : 'online'),
            'card_last4' => $this->cardLast4(isset($payload['card_number']) ? $payload['card_number'] : ''),
            'reference' => $paymentReference,
            'description' => 'Move-in payment bundle',
            'created_at' => $now,
        ));

        if (! $existingTenancy) {
            $this->insertLedgerEntry($tenancyId, 'First month rent', 'rent', (int) $property['monthlyRent'], $paymentReference, $now);
            $this->insertLedgerEntry($tenancyId, 'Service charge', 'service_charge', (int) $property['serviceCharge'], $paymentReference, $now);
            $this->insertLedgerEntry($tenancyId, 'Legal fee (10%)', 'legal_fee', $legalFee, $paymentReference, $now);
            $this->insertLedgerEntry($tenancyId, 'Caution deposit', 'deposit', $cautionDeposit, $paymentReference, $now);
        }

        $this->database->execute(
            "UPDATE applications SET status = 'active', activated_at = :activated_at WHERE id = :id",
            array(
                'activated_at' => $now,
                'id' => (int) $applicationId,
            )
        );

        return array($this->findTenancyById($tenancyId), null);
    }

    public function addTenancyPayment($tenancyId, $user, array $payload)
    {
        $tenancy = $this->findTenancyById($tenancyId);

        if (! $tenancy || (int) $tenancy['userId'] !== (int) $user['id']) {
            return array(false, 'We could not post that payment to your tenancy.');
        }

        $amount = $this->extractAmount(isset($payload['amount']) ? $payload['amount'] : 0);

        if ($amount <= 0) {
            return array(false, 'Enter a valid payment amount.');
        }

        $label = trim(isset($payload['label']) ? $payload['label'] : '');
        $chargeType = trim(isset($payload['charge_type']) ? $payload['charge_type'] : 'other');
        $reference = $this->paymentReference($tenancyId . $chargeType . $this->now() . mt_rand(1000, 9999));
        $now = $this->now();

        $this->insertLedgerEntry($tenancyId, $label !== '' ? $label : 'Tenant payment', $chargeType, $amount, $reference, $now);
        $this->database->insert('payments', array(
            'user_id' => (int) $user['id'],
            'property_id' => (int) $tenancy['propertyId'],
            'application_id' => (int) $tenancy['applicationId'],
            'tenancy_id' => (int) $tenancy['id'],
            'amount' => $amount,
            'channel' => trim(isset($payload['channel']) ? $payload['channel'] : 'online'),
            'card_last4' => $this->cardLast4(isset($payload['card_number']) ? $payload['card_number'] : ''),
            'reference' => $reference,
            'description' => $label !== '' ? $label : 'Tenant payment',
            'created_at' => $now,
        ));

        return array($this->findTenancyById($tenancyId), null);
    }

    public function tenancyForUser($userId)
    {
        $row = $this->database->fetchOne(
            "SELECT * FROM tenancies WHERE user_id = :user_id AND status = 'active' ORDER BY id DESC LIMIT 1",
            array('user_id' => (int) $userId)
        );

        if (! $row) {
            $row = $this->database->fetchOne(
                'SELECT * FROM tenancies WHERE user_id = :user_id ORDER BY id DESC LIMIT 1',
                array('user_id' => (int) $userId)
            );
        }

        return $row ? $this->hydrateTenancy($row) : null;
    }

    public function paymentsForTenancy($tenancyId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM payments WHERE tenancy_id = :tenancy_id ORDER BY created_at DESC, id DESC',
            array('tenancy_id' => (int) $tenancyId)
        );
        $payments = array();

        foreach ($rows as $row) {
            $payments[] = array(
                'id' => (int) $row['id'],
                'userId' => (int) $row['user_id'],
                'propertyId' => (int) $row['property_id'],
                'applicationId' => (int) $row['application_id'],
                'tenancyId' => (int) $row['tenancy_id'],
                'amount' => (int) $row['amount'],
                'channel' => (string) $row['channel'],
                'cardLast4' => (string) $row['card_last4'],
                'reference' => (string) $row['reference'],
                'description' => (string) $row['description'],
                'createdAt' => (string) $row['created_at'],
            );
        }

        return $payments;
    }

    private function hydrateProperties(array $rows)
    {
        $properties = array();

        foreach ($rows as $row) {
            $properties[] = $this->hydrateProperty($row);
        }

        return $properties;
    }

    private function hydrateProperty(array $row)
    {
        $purpose = (string) $row['purpose'];
        $monthlyRent = (int) $row['monthly_rent'];
        $askingPrice = (int) $row['asking_price'];
        $securityDeposit = $this->normalizeCautionDeposit($purpose, (int) $row['security_deposit']);

        if ($purpose === 'commercial' && $monthlyRent <= 0 && $askingPrice > 0) {
            $monthlyRent = $askingPrice;
        }

        $property = array(
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'price' => $this->displayPrice($purpose, $monthlyRent, $askingPrice),
            'location' => (string) $row['location'],
            'beds' => (int) $row['beds'],
            'baths' => $this->displayBaths($row['baths']),
            'area' => (string) $row['area'],
            'petFriendly' => (bool) $row['pet_friendly'],
            'availableDate' => (string) $row['available_date'],
            'type' => (string) $row['type'],
            'purpose' => $purpose,
            'image' => $this->displayPath((string) $row['image']),
            'images' => $this->displayPathList($this->decodeJsonList($row['images_json'], array((string) $row['image']))),
            'badges' => $this->decodeJsonList($row['badges_json']),
            'summary' => (string) $row['summary'],
            'features' => $this->decodeJsonList($row['features_json']),
            'lat' => $row['lat'] !== null ? (string) $row['lat'] : '',
            'lng' => $row['lng'] !== null ? (string) $row['lng'] : '',
            'monthlyRent' => $monthlyRent,
            'serviceCharge' => (int) $row['service_charge'],
            'securityDeposit' => $securityDeposit,
            'askingPrice' => $askingPrice,
            'commercialType' => $row['commercial_type'] !== '' ? (string) $row['commercial_type'] : (string) $row['type'],
            'leaseTerm' => (string) $row['lease_term'],
            'units' => (int) $row['units'],
            'floors' => (int) $row['floors'],
            'status' => (string) $row['status'],
            'listedBy' => (string) $row['listed_by'],
            'createdAt' => (string) $row['created_at'],
        );

        $property['sourcePage'] = $purpose === 'sale' ? 'homes' : ($purpose === 'commercial' ? 'commercial' : 'rentals');

        return $property;
    }

    private function hydrateTourSlots(array $rows)
    {
        $slots = array();

        foreach ($rows as $row) {
            $slots[] = $this->hydrateTourSlot($row);
        }

        return $slots;
    }

    private function hydrateTourSlot(array $row)
    {
        $activeRequest = $this->database->fetchOne(
            "SELECT id, status FROM tour_requests WHERE slot_id = :slot_id AND status IN ('requested', 'confirmed') ORDER BY id DESC LIMIT 1",
            array('slot_id' => (int) $row['id'])
        );

        return array(
            'id' => (int) $row['id'],
            'propertyId' => (int) $row['property_id'],
            'status' => (string) $row['status'],
            'startsAt' => (string) $row['starts_at'],
            'endsAt' => (string) $row['ends_at'],
            'notes' => (string) $row['notes'],
            'createdBy' => (string) $row['created_by_name'],
            'createdAt' => (string) $row['created_at'],
            'label' => $this->formatDateTimeRange((string) $row['starts_at'], (string) $row['ends_at']),
            'property' => $this->findProperty($row['property_id']),
            'hasActiveRequest' => $activeRequest ? true : false,
            'activeRequestId' => $activeRequest ? (int) $activeRequest['id'] : 0,
            'activeRequestStatus' => $activeRequest ? (string) $activeRequest['status'] : '',
        );
    }

    private function hydrateUser(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'role' => (string) $row['role'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
            'passwordHash' => (string) $row['password_hash'],
            'createdAt' => (string) $row['created_at'],
        );
    }

    private function hydrateApplications(array $rows)
    {
        $applications = array();

        foreach ($rows as $row) {
            $applications[] = $this->hydrateApplication($row);
        }

        return $applications;
    }

    private function hydrateTourRequests(array $rows)
    {
        $requests = array();

        foreach ($rows as $row) {
            $requests[] = $this->hydrateTourRequest($row);
        }

        return $requests;
    }

    private function hydrateApplication(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'status' => (string) $row['status'],
            'fullName' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
            'annualIncome' => (string) $row['annual_income'],
            'employer' => (string) $row['employer'],
            'moveInDate' => (string) $row['move_in_date'],
            'occupants' => (int) $row['occupants'],
            'notes' => (string) $row['notes'],
            'approvedBy' => $row['approved_by_name'] !== null ? (string) $row['approved_by_name'] : '',
            'approvedAt' => $row['approved_at'] !== null ? (string) $row['approved_at'] : '',
            'submittedAt' => (string) $row['submitted_at'],
            'activatedAt' => $row['activated_at'] !== null ? (string) $row['activated_at'] : '',
            'property' => $this->findProperty($row['property_id']),
            'user' => $this->findUserById($row['user_id']),
        );
    }

    private function hydrateTourRequest(array $row)
    {
        $slot = $this->findTourSlot($row['slot_id']);

        return array(
            'id' => (int) $row['id'],
            'propertyId' => (int) $row['property_id'],
            'slotId' => (int) $row['slot_id'],
            'userId' => (int) $row['user_id'],
            'status' => (string) $row['status'],
            'fullName' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
            'message' => (string) $row['message'],
            'adminNotes' => (string) $row['admin_notes'],
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) $row['updated_at'],
            'confirmedAt' => $row['confirmed_at'] !== null ? (string) $row['confirmed_at'] : '',
            'confirmedBy' => $row['confirmed_by_name'] !== null ? (string) $row['confirmed_by_name'] : '',
            'completedAt' => $row['completed_at'] !== null ? (string) $row['completed_at'] : '',
            'cancelledAt' => $row['cancelled_at'] !== null ? (string) $row['cancelled_at'] : '',
            'property' => $this->findProperty($row['property_id']),
            'slot' => $slot,
            'slotLabel' => $slot ? $slot['label'] : '',
            'user' => $this->findUserById($row['user_id']),
        );
    }

    private function hydrateTenancy(array $row)
    {
        $property = $this->findProperty($row['property_id']);
        $purpose = $property && isset($property['purpose']) ? (string) $property['purpose'] : 'rent';
        $securityDeposit = $this->normalizeCautionDeposit($purpose, (int) $row['security_deposit']);

        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'applicationId' => (int) $row['application_id'],
            'status' => (string) $row['status'],
            'startDate' => (string) $row['start_date'],
            'monthlyRent' => (int) $row['monthly_rent'],
            'serviceCharge' => (int) $row['service_charge'],
            'securityDeposit' => $securityDeposit,
            'createdAt' => (string) $row['created_at'],
            'property' => $property,
            'ledger' => $this->ledgerForTenancy($row['id']),
            'payments' => $this->paymentsForTenancy($row['id']),
        );
    }

    private function ledgerForTenancy($tenancyId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM tenancy_ledger WHERE tenancy_id = :tenancy_id ORDER BY paid_at DESC, id DESC',
            array('tenancy_id' => (int) $tenancyId)
        );
        $ledger = array();

        foreach ($rows as $row) {
            $ledger[] = array(
                'label' => (string) $row['label'],
                'type' => (string) $row['charge_type'],
                'amount' => (int) $row['amount'],
                'status' => (string) $row['status'],
                'paymentReference' => (string) $row['payment_reference'],
                'paidAt' => (string) $row['paid_at'],
            );
        }

        return $ledger;
    }

    private function findTenancyById($tenancyId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM tenancies WHERE id = :id LIMIT 1',
            array('id' => (int) $tenancyId)
        );

        return $row ? $this->hydrateTenancy($row) : null;
    }

    private function findTenancyByApplicationId($applicationId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM tenancies WHERE application_id = :application_id ORDER BY id DESC LIMIT 1',
            array('application_id' => (int) $applicationId)
        );

        return $row ? $this->hydrateTenancy($row) : null;
    }

    private function insertLedgerEntry($tenancyId, $label, $chargeType, $amount, $reference, $paidAt)
    {
        $this->database->insert('tenancy_ledger', array(
            'tenancy_id' => (int) $tenancyId,
            'label' => $label,
            'charge_type' => $chargeType,
            'amount' => max(0, (int) $amount),
            'status' => 'paid',
            'payment_reference' => $reference,
            'paid_at' => $paidAt,
        ));
    }

    private function importLegacyDataIfNeeded()
    {
        if ($this->database->tableCount('users') === 0) {
            $this->importUsers();
        }

        if ($this->database->tableCount('properties') === 0) {
            $this->importProperties();
        }

        if ($this->database->tableCount('applications') === 0) {
            $this->importApplications();
        }

        if ($this->database->tableCount('tenancies') === 0) {
            $this->importTenancies();
        }

        if ($this->database->tableCount('payments') === 0) {
            $this->importPayments();
        }
    }

    private function importUsers()
    {
        $users = $this->readLegacyJson('users.json');

        foreach ($users as $user) {
            $email = strtolower(trim(isset($user['email']) ? $user['email'] : ''));

            if ($email === '') {
                continue;
            }

            $existing = $this->database->fetchOne('SELECT id FROM users WHERE email = :email LIMIT 1', array('email' => $email));

            if ($existing) {
                continue;
            }

            $data = array(
                'role' => isset($user['role']) ? $user['role'] : 'user',
                'name' => isset($user['name']) ? $user['name'] : 'User',
                'email' => $email,
                'phone' => isset($user['phone']) ? $user['phone'] : '',
                'password_hash' => isset($user['passwordHash']) ? $user['passwordHash'] : password_hash('ChangeMe123!', PASSWORD_DEFAULT),
                'created_at' => isset($user['createdAt']) ? $user['createdAt'] : $this->now(),
            );

            if (isset($user['id'])) {
                $data['id'] = (int) $user['id'];
            }

            $this->database->insert('users', $data);
        }
    }

    private function importProperties()
    {
        $groups = array(
            $this->loadLegacyProperties('rental_properties.json', $this->projectRoot . '/app/Data/listings.php'),
            $this->loadLegacyProperties('sale_properties.json', $this->projectRoot . '/app/Data/sale_listings.php'),
            $this->loadLegacyProperties('commercial_properties.json', $this->projectRoot . '/app/Data/commercial_listings.php'),
        );

        foreach ($groups as $properties) {
            foreach ($properties as $property) {
                $purpose = isset($property['purpose']) ? $property['purpose'] : 'rent';
                $amountFromPrice = $this->extractAmount(isset($property['price']) ? $property['price'] : 0);
                $monthlyRent = isset($property['monthlyRent']) ? $this->extractAmount($property['monthlyRent']) : 0;
                $askingPrice = isset($property['askingPrice']) ? $this->extractAmount($property['askingPrice']) : 0;

                if ($purpose === 'rent' && $monthlyRent <= 0) {
                    $monthlyRent = $amountFromPrice;
                }

                if (($purpose === 'sale' || $purpose === 'commercial') && $askingPrice <= 0) {
                    $askingPrice = $amountFromPrice;
                }

                if ($purpose === 'commercial' && $monthlyRent <= 0) {
                    $monthlyRent = $amountFromPrice;
                }

                $data = array(
                    'purpose' => $purpose,
                    'title' => isset($property['title']) ? $property['title'] : 'Property',
                    'location' => isset($property['location']) ? $property['location'] : '',
                    'type' => isset($property['type']) ? $property['type'] : '',
                    'beds' => $this->normalizeInteger(isset($property['beds']) ? $property['beds'] : 0),
                    'baths' => $this->normalizeDecimal(isset($property['baths']) ? $property['baths'] : 0),
                    'area' => isset($property['area']) ? $property['area'] : '',
                    'pet_friendly' => ! empty($property['petFriendly']) ? 1 : 0,
                    'available_date' => isset($property['availableDate']) ? $property['availableDate'] : 'Available now',
                    'image' => $this->canonicalPath(isset($property['image']) ? $property['image'] : ''),
                    'images_json' => json_encode($this->mergePrimaryImage(
                        $this->canonicalPath(isset($property['image']) ? $property['image'] : ''),
                        $this->canonicalPathList(isset($property['images']) && is_array($property['images']) ? $property['images'] : array())
                    )),
                    'badges_json' => json_encode($this->normalizeList(isset($property['badges']) ? $property['badges'] : array())),
                    'summary' => isset($property['summary']) ? $property['summary'] : '',
                    'features_json' => json_encode($this->normalizeList(isset($property['features']) ? $property['features'] : array())),
                    'lat' => $this->normalizeCoordinate(isset($property['lat']) ? $property['lat'] : ''),
                    'lng' => $this->normalizeCoordinate(isset($property['lng']) ? $property['lng'] : ''),
                    'monthly_rent' => $monthlyRent,
                    'service_charge' => isset($property['serviceCharge']) ? $this->extractAmount($property['serviceCharge']) : 0,
                    'security_deposit' => isset($property['securityDeposit']) ? $this->extractAmount($property['securityDeposit']) : 0,
                    'asking_price' => $askingPrice,
                    'commercial_type' => isset($property['commercialType']) ? $property['commercialType'] : '',
                    'lease_term' => isset($property['leaseTerm']) ? $property['leaseTerm'] : '',
                    'units' => $this->normalizeInteger(isset($property['units']) ? $property['units'] : 0),
                    'floors' => $this->normalizeInteger(isset($property['floors']) ? $property['floors'] : 0),
                    'status' => isset($property['status']) ? $property['status'] : 'available',
                    'listed_by' => isset($property['listedBy']) ? $property['listedBy'] : 'Sandworth Admin',
                    'created_at' => isset($property['createdAt']) ? $property['createdAt'] : $this->now(),
                    'updated_at' => isset($property['createdAt']) ? $property['createdAt'] : $this->now(),
                );

                if (isset($property['id'])) {
                    $data['id'] = (int) $property['id'];
                }

                $this->database->insert('properties', $data);
            }
        }
    }

    private function importApplications()
    {
        $applications = $this->readLegacyJson('applications.json');

        foreach ($applications as $application) {
            $data = array(
                'user_id' => isset($application['userId']) ? (int) $application['userId'] : 0,
                'property_id' => isset($application['propertyId']) ? (int) $application['propertyId'] : 0,
                'status' => isset($application['status']) ? $application['status'] : 'submitted',
                'full_name' => isset($application['fullName']) ? $application['fullName'] : '',
                'email' => isset($application['email']) ? $application['email'] : '',
                'phone' => isset($application['phone']) ? $application['phone'] : '',
                'annual_income' => isset($application['annualIncome']) ? $application['annualIncome'] : '',
                'employer' => isset($application['employer']) ? $application['employer'] : '',
                'move_in_date' => isset($application['moveInDate']) ? $application['moveInDate'] : '',
                'occupants' => max(1, $this->normalizeInteger(isset($application['occupants']) ? $application['occupants'] : 1)),
                'notes' => isset($application['notes']) ? $application['notes'] : '',
                'approved_by_name' => isset($application['approvedBy']) ? $application['approvedBy'] : null,
                'approved_at' => isset($application['approvedAt']) ? $application['approvedAt'] : null,
                'submitted_at' => isset($application['submittedAt']) ? $application['submittedAt'] : $this->now(),
                'activated_at' => isset($application['activatedAt']) ? $application['activatedAt'] : null,
            );

            if (isset($application['id'])) {
                $data['id'] = (int) $application['id'];
            }

            $this->database->insert('applications', $data);
        }
    }

    private function importTenancies()
    {
        $tenancies = $this->readLegacyJson('tenancies.json');

        foreach ($tenancies as $tenancy) {
            $data = array(
                'user_id' => isset($tenancy['userId']) ? (int) $tenancy['userId'] : 0,
                'property_id' => isset($tenancy['propertyId']) ? (int) $tenancy['propertyId'] : 0,
                'application_id' => isset($tenancy['applicationId']) ? (int) $tenancy['applicationId'] : 0,
                'status' => isset($tenancy['status']) ? $tenancy['status'] : 'active',
                'start_date' => isset($tenancy['startDate']) ? $tenancy['startDate'] : '',
                'monthly_rent' => isset($tenancy['monthlyRent']) ? $this->extractAmount($tenancy['monthlyRent']) : 0,
                'service_charge' => isset($tenancy['serviceCharge']) ? $this->extractAmount($tenancy['serviceCharge']) : 0,
                'security_deposit' => isset($tenancy['securityDeposit']) ? $this->extractAmount($tenancy['securityDeposit']) : 0,
                'created_at' => isset($tenancy['createdAt']) ? $tenancy['createdAt'] : $this->now(),
            );

            if (isset($tenancy['id'])) {
                $data['id'] = (int) $tenancy['id'];
            }

            $tenancyId = $this->database->insert('tenancies', $data);

            if (isset($tenancy['ledger']) && is_array($tenancy['ledger'])) {
                foreach ($tenancy['ledger'] as $entry) {
                    $this->insertLedgerEntry(
                        $tenancyId,
                        isset($entry['label']) ? $entry['label'] : 'Ledger entry',
                        isset($entry['type']) ? $entry['type'] : 'other',
                        isset($entry['amount']) ? $this->extractAmount($entry['amount']) : 0,
                        isset($entry['paymentReference']) ? $entry['paymentReference'] : '',
                        isset($entry['paidAt']) ? $entry['paidAt'] : $this->now()
                    );
                }
            }
        }
    }

    private function importPayments()
    {
        $payments = $this->readLegacyJson('payments.json');

        foreach ($payments as $payment) {
            $data = array(
                'user_id' => isset($payment['userId']) ? (int) $payment['userId'] : 0,
                'property_id' => isset($payment['propertyId']) ? (int) $payment['propertyId'] : 0,
                'application_id' => isset($payment['applicationId']) ? (int) $payment['applicationId'] : 0,
                'tenancy_id' => isset($payment['tenancyId']) ? (int) $payment['tenancyId'] : 0,
                'amount' => isset($payment['amount']) ? $this->extractAmount($payment['amount']) : 0,
                'channel' => isset($payment['channel']) ? $payment['channel'] : '',
                'card_last4' => isset($payment['cardLast4']) ? $payment['cardLast4'] : '',
                'reference' => isset($payment['reference']) ? $payment['reference'] : $this->paymentReference(mt_rand(1000, 9999)),
                'description' => isset($payment['description']) ? $payment['description'] : '',
                'created_at' => isset($payment['createdAt']) ? $payment['createdAt'] : $this->now(),
            );

            if (isset($payment['id'])) {
                $data['id'] = (int) $payment['id'];
            }

            $this->database->insert('payments', $data);
        }
    }

    private function ensureDefaultAdminUser()
    {
        if ($this->findUserByEmail('admin@sandworthliving.test')) {
            return;
        }

        $this->database->insert('users', array(
            'role' => 'admin',
            'name' => 'Sandworth Admin',
            'email' => 'admin@sandworthliving.test',
            'phone' => '+234 800 000 0000',
            'password_hash' => password_hash('Admin123!', PASSWORD_DEFAULT),
            'created_at' => $this->now(),
        ));
    }

    private function ensureDefaultSettings()
    {
        if ($this->setting('currency_code', null) === null) {
            $this->setSetting('currency_code', 'NGN');
        }

        foreach ($this->siteSettingDefaults() as $key => $defaultValue) {
            if ($this->setting($key, null) === null) {
                $this->setSetting($key, $defaultValue);
            }
        }
    }

    private function ensureDefaultPageContent()
    {
        $now = $this->now();

        foreach ($this->defaultPageContent() as $pageKey => $blocks) {
            foreach ($blocks as $blockKey => $content) {
                $existing = $this->database->fetchOne(
                    'SELECT id FROM page_content_blocks WHERE page_key = :page_key AND block_key = :block_key LIMIT 1',
                    array(
                        'page_key' => $pageKey,
                        'block_key' => $blockKey,
                    )
                );

                if ($existing) {
                    continue;
                }

                $this->database->insert('page_content_blocks', array(
                    'page_key' => $pageKey,
                    'block_key' => $blockKey,
                    'content_json' => json_encode($content),
                    'created_at' => $now,
                    'updated_at' => $now,
                ));
            }
        }
    }

    private function defaultPageContent()
    {
        return array(
            'home' => array(
                'meta' => array(
                    'pageTitle' => 'Sandworth Homes | Rentals, Homes, and Property Management',
                ),
                'hero' => array(
                    'eyebrow' => 'Sandworth Homes',
                    'title' => 'Find homes, rentals & commercial spaces—all in one place.',
                    'description' => 'Sandworth Homes brings together property discovery, renter applications, online payments, and landlord operations in one organization-owned system.',
                ),
                'stats' => array(
                    'preQualifiedRenters' => 612,
                    'activeRentalsLabel' => 'Active rental inventory',
                    'preQualifiedRentersLabel' => 'Pre-qualified renters',
                    'avgDaysToLeaseLabel' => 'Average time to lease',
                ),
                'searchModes' => array('Rent', 'Buy', 'Malls & Shops'),
                'panel' => array(
                    'kicker' => 'Rental Manager',
                    'title' => 'What the platform supports today',
                    'features' => array(
                        'Rental search with filters and detail pages',
                        'Landlord dashboard for leads, listings and operations',
                        'Architecture ready for payments, screening and e-signatures',
                    ),
                ),
                'modules' => array(
                    'eyebrow' => 'Core modules',
                    'title' => 'Three product lanes, one platform',
                    'linkLabel' => 'See planning flow',
                    'cards' => array(
                        array(
                            'title' => 'Planning workspace',
                            'description' => 'Budget visibility, buyer coordination, milestone tracking, and guided planning for serious property decisions.',
                        ),
                        array(
                            'title' => 'Public marketplace',
                            'description' => 'Brand-led homepage, search entry point, category pages, property detail pages, favorites, saved search hooks and SEO-friendly listing URLs.',
                        ),
                        array(
                            'title' => 'Renter experience',
                            'description' => 'Filters, map-led discovery, renter hub, online applications, tour scheduling, messaging, payment setup and identity verification.',
                        ),
                        array(
                            'title' => 'Landlord operations',
                            'description' => 'Inventory control, lead pipeline, application review, screening, lease generation, payment tracking, maintenance and reporting.',
                        ),
                    ),
                ),
                'explore' => array(
                    'eyebrow' => 'Explore with confidence',
                    'title' => 'Choose the path that matches your next move',
                    'cards' => array(
                        array(
                            'title' => 'Buy a home',
                            'description' => 'Search homes, compare neighborhoods, and prepare your next purchase with clearer market context.',
                            'link' => 'homes',
                            'linkLabel' => 'Browse homes',
                        ),
                        array(
                            'title' => 'Rent a home',
                            'description' => 'Find rentals, apply online, and move from approval to payment without leaving the platform.',
                            'link' => 'rentals',
                            'linkLabel' => 'Browse rentals',
                        ),
                        array(
                            'title' => 'Lease a commercial space',
                            'description' => 'Review shopping plazas and retail units with map-based discovery and admin-managed availability.',
                            'link' => 'commercial',
                            'linkLabel' => 'Browse commercial',
                        ),
                    ),
                ),
                'homesShowcase' => array(
                    'eyebrow' => 'Newly listed homes',
                    'title' => 'Fresh homes for sale',
                    'linkLabel' => 'See all homes',
                ),
                'rentalsShowcase' => array(
                    'eyebrow' => 'Newly listed rentals',
                    'title' => 'Fresh rentals for today',
                    'linkLabel' => 'See all rentals',
                ),
                'featured' => array(
                    'eyebrow' => 'Featured inventory',
                    'title' => 'Featured listings',
                    'linkLabel' => 'Browse all rentals',
                ),
            ),
            'plan' => array(
                'meta' => array(
                    'pageTitle' => 'Plan Your Move | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'Plan your move',
                    'title' => 'Give buyers one place to organize budget, people, and next steps.',
                    'description' => 'This planning hub centers on three essentials: understand your finances, align your team, and move through the process with clarity. This version turns those ideas into a company-owned planning workspace.',
                ),
                'budgetSnapshot' => array(
                    'targetBudget' => '$420,000',
                    'monthlyComfort' => '$2,850/mo',
                    'cashToClose' => '$58,000',
                ),
                'processSection' => array(
                    'eyebrow' => 'Core sections',
                    'title' => 'Mirror the planning journey',
                ),
                'processSteps' => array(
                    array(
                        'title' => 'Financial snapshot',
                        'description' => 'Estimate affordability, closing costs, and the payment range that fits your life.',
                    ),
                    array(
                        'title' => 'Build your team',
                        'description' => 'Introduce an agent, lender, and transaction support so buyers stop juggling contacts.',
                    ),
                    array(
                        'title' => 'Understand the process',
                        'description' => 'Turn a complicated purchase into a timeline with next actions, due dates, and checklists.',
                    ),
                ),
                'milestonesSection' => array(
                    'eyebrow' => 'Milestones',
                    'title' => 'Guide users through the buying timeline',
                    'note' => 'Turn this into a checklist, reminders engine, and document request flow in the next phase.',
                ),
                'milestones' => array(
                    'Get pre-approved',
                    'Save homes and compare neighborhoods',
                    'Schedule tours and shortlist favorites',
                    'Prepare offer package',
                    'Track inspection, valuation, and closing',
                ),
                'teamSection' => array(
                    'eyebrow' => 'Build your team',
                    'title' => 'People the buyer should see immediately',
                ),
                'teamMembers' => array(
                    array(
                        'title' => 'Buyer advisor',
                        'name' => 'Ada Nwosu',
                        'note' => 'Guides offer strategy, pricing, and neighborhood fit.',
                    ),
                    array(
                        'title' => 'Mortgage partner',
                        'name' => 'Harbor Lending Desk',
                        'note' => 'Keeps financing, pre-approval, and monthly affordability in one view.',
                    ),
                    array(
                        'title' => 'Closing coordinator',
                        'name' => 'Tomi Adewale',
                        'note' => 'Tracks milestones from accepted offer to keys in hand.',
                    ),
                ),
            ),
            'homes' => array(
                'meta' => array(
                    'pageTitle' => 'Homes For Sale | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'Homes for sale',
                    'title' => 'Browse homes for sale with a live map built for Sandworth clients.',
                    'description' => 'Filter by beds and property type, then browse listings and map pins together so buyers can compare options quickly.',
                ),
                'searchTips' => array(
                    'Search by city, school, or landmark.',
                    'Broaden map radius when results feel too narrow.',
                    'Save promising homes and compare monthly cost before booking tours.',
                ),
            ),
            'rentals' => array(
                'meta' => array(
                    'pageTitle' => 'Find Rentals | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'Renter search center',
                    'title' => 'Discover rentals that fit your life, mapped in real time.',
                    'description' => "Search, filter and compare listings on a live map inside Sandworth Homes' renter experience.",
                ),
            ),
            'commercial' => array(
                'meta' => array(
                    'pageTitle' => 'Malls & Shops For Lease | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'Commercial leasing',
                    'title' => 'Lease a shopping mall unit or shop row on a long-term basis.',
                    'description' => 'Browse malls, retail plazas and shop rows available for long lease, mapped by district so you can match footfall and location to your brand.',
                ),
                'chips' => array(
                    'Long lease',
                    'Anchor & unit leasing',
                    'Foot traffic',
                    'Fit-out ready',
                ),
                'searchTips' => array(
                    'Search by district to find malls and shop rows near your customers.',
                    'Long leases (3-10 years) are typical for anchor and mall space.',
                    'Contact the leasing team for unit-by-unit pricing on multi-unit malls.',
                ),
            ),
            'city-rentals' => array(
                'meta' => array(
                    'pageTitleSuffix' => 'Rentals | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'City rentals',
                    'description' => 'Review one market at a time with local rental stats, nearby areas, and available listings for that geography.',
                ),
                'chips' => array(
                    'For rent',
                    'Price',
                    'Beds & baths',
                    'Property type',
                    'Filters',
                    'Save search',
                ),
                'nearbySection' => array(
                    'eyebrow' => 'Nearby markets',
                    'title' => 'Help renters widen their search fast',
                ),
                'nearbyMarkets' => array(
                    'Victoria Island',
                    'Ikoyi',
                    'Chevron',
                ),
            ),
            'manager' => array(
                'meta' => array(
                    'pageTitle' => 'Rental Manager | Sandworth Homes',
                ),
                'hero' => array(
                    'eyebrow' => 'Rental manager',
                    'title' => 'Operate listings, leads, leases and payments in one place.',
                    'description' => 'Use this operating dashboard to manage inventory, review pipeline activity, and oversee tenancy performance across the Sandworth portfolio.',
                ),
                'pipelineSection' => array(
                    'eyebrow' => 'Leasing pipeline',
                    'title' => 'Track renters from inquiry to signed lease',
                ),
                'roadmap' => array(
                    'eyebrow' => 'Roadmap-ready',
                    'title' => 'Back-office modules to add next',
                    'items' => array(
                        'Tenant screening integrations',
                        'Lease templates and e-signatures',
                        'Rent collection and payout ledger',
                        'Maintenance ticketing and vendor dispatch',
                        'Saved replies, inbox and lead scoring',
                    ),
                ),
                'inventorySection' => array(
                    'eyebrow' => 'Inventory snapshot',
                    'title' => 'Recent listings in the portfolio',
                ),
            ),
        );
    }

    private function siteSettingDefaults()
    {
        return array(
            'site_name' => 'Sandworth Homes',
            'site_base_url' => '',
            'default_meta_title' => 'Sandworth Homes | Buy, Rent, and Lease Property in Nigeria',
            'default_meta_description' => 'Sandworth Homes helps Nigerians discover homes for sale, annual rentals, and commercial spaces with searchable listings, rich property detail pages, and a company-owned real estate platform.',
            'default_share_image' => '/public/assets/img/Arepo-II4-1024x576.jpg',
            'robots_policy' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
            'contact_email' => 'info@sandworthliving.ng',
            'contact_phone' => '+234 803 437 1916',
            'operational_office' => 'The Facility Management Office, The Nigeria Army Shopping Complex (The Arena), Bolade-Oshodi, 101233, Lagos State, Nigeria.',
            'registered_office' => '1, Tafawa Balewa Crescent, off Adeniran Ogunsanya, Surulere, Lagos State, Nigeria.',
            'facebook_url' => '',
            'instagram_url' => '',
            'x_url' => '',
            'linkedin_url' => '',
            'twitter_handle' => '',
        );
    }

    private function loadLegacyProperties($jsonFile, $fallbackPhpFile)
    {
        $records = $this->readLegacyJson($jsonFile);

        if ($records !== array()) {
            return $records;
        }

        if (! file_exists($fallbackPhpFile)) {
            return array();
        }

        $fallback = require $fallbackPhpFile;

        return is_array($fallback) ? $fallback : array();
    }

    private function readLegacyJson($filename)
    {
        $path = $this->legacyPath($filename);

        if (! file_exists($path)) {
            return array();
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return array();
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : array();
    }

    private function legacyPath($filename)
    {
        return rtrim($this->legacyStoragePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    private function decodeJsonList($value, array $fallback = array())
    {
        if (! $value) {
            return $fallback;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded) || $decoded === array()) {
            return $fallback;
        }

        return $decoded;
    }

    private function decodeJsonValue($value)
    {
        $decoded = json_decode($value, true);

        return $decoded === null && $value !== 'null' ? $value : $decoded;
    }

    private function encodePrettyJson($value)
    {
        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? (string) $value : $encoded;
    }

    private function humanizeKey($value)
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $value);
        $value = str_replace(array('-', '_'), ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim((string) $value));

        return $value === '' ? 'Untitled' : ucwords($value);
    }

    private function needsBootstrapRefresh()
    {
        return $this->setting('platform_bootstrap_version', '') !== self::BOOTSTRAP_VERSION;
    }

    private function setting($key, $default = null)
    {
        if (array_key_exists($key, $this->settingsCache)) {
            return $this->settingsCache[$key];
        }

        $row = $this->database->fetchOne(
            'SELECT setting_value FROM app_settings WHERE setting_key = :setting_key LIMIT 1',
            array('setting_key' => (string) $key)
        );

        if (! $row) {
            $this->settingsCache[$key] = $default;

            return $default;
        }

        $this->settingsCache[$key] = (string) $row['setting_value'];

        return $this->settingsCache[$key];
    }

    private function setSetting($key, $value)
    {
        $existing = $this->database->fetchOne(
            'SELECT id FROM app_settings WHERE setting_key = :setting_key LIMIT 1',
            array('setting_key' => (string) $key)
        );
        $now = $this->now();

        if ($existing) {
            $this->database->execute(
                'UPDATE app_settings SET setting_value = :setting_value, updated_at = :updated_at WHERE setting_key = :setting_key',
                array(
                    'setting_value' => (string) $value,
                    'updated_at' => $now,
                    'setting_key' => (string) $key,
                )
            );
        } else {
            $this->database->insert('app_settings', array(
                'setting_key' => (string) $key,
                'setting_value' => (string) $value,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        $this->settingsCache[$key] = (string) $value;
    }

    private function syncPublicSeoFiles()
    {
        $settings = $this->siteSettings();
        $baseUrl = trim((string) $settings['siteBaseUrl']) !== ''
            ? rtrim((string) $settings['siteBaseUrl'], '/')
            : (function_exists('app_base_url') ? rtrim((string) \app_base_url(), '/') : '');
        $properties = $this->allProperties();
        $rentalMarkets = array();
        $sitemapPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'sitemap.xml';
        $robotsPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'robots.txt';
        $urls = array(
            $baseUrl . \app_url('home'),
            $baseUrl . \app_url('homes'),
            $baseUrl . \app_url('rentals'),
            $baseUrl . \app_url('commercial'),
            $baseUrl . \app_url('plan'),
            $baseUrl . \app_url('manager'),
        );

        foreach ($this->allRentalProperties() as $rentalProperty) {
            $market = trim((string) strtok(isset($rentalProperty['location']) ? (string) $rentalProperty['location'] : '', ','));

            if ($market !== '') {
                $rentalMarkets[$market] = true;
            }
        }

        foreach (array_keys($rentalMarkets) as $market) {
            $urls[] = $baseUrl . \app_url('city-rentals', array('market' => $market));
        }

        foreach ($properties as $property) {
            $urls[] = $baseUrl . \app_property_url($property);
        }

        $sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $sitemap .= "  <url>\n";
            $sitemap .= '    <loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</loc>\n";
            $sitemap .= "  </url>\n";
        }

        $sitemap .= "</urlset>\n";

        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin-editorial\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin-properties\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin-applications\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin-inventory\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=admin-seo\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=login\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=register\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=dashboard\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=tenancy\n";
        $robots .= "Disallow: " . \app_public_base_path() . "/?page=payment\n";
        $robots .= "Sitemap: " . $baseUrl . "/sitemap.xml\n";

        $this->writeFileIfChanged($sitemapPath, $sitemap);
        $this->writeFileIfChanged($robotsPath, $robots);
    }

    private function normalizeList($value)
    {
        if (is_array($value)) {
            $results = array();

            foreach ($value as $item) {
                $item = trim((string) $item);

                if ($item !== '') {
                    $results[] = $item;
                }
            }

            return $results;
        }

        return $this->explodeCsv((string) $value);
    }

    private function writeFileIfChanged($path, $contents)
    {
        $existing = file_exists($path) ? file_get_contents($path) : false;

        if ($existing !== false && hash_equals($existing, (string) $contents)) {
            return;
        }

        file_put_contents($path, $contents);
    }

    private function mergePrimaryImage($image, array $images)
    {
        $results = array();
        $image = trim((string) $image);

        if ($image !== '') {
            $results[] = $image;
        }

        foreach ($images as $item) {
            $item = trim((string) $item);

            if ($item !== '' && ! in_array($item, $results, true)) {
                $results[] = $item;
            }
        }

        return $results;
    }

    private function storeUploadedImage($file, $purpose, $title)
    {
        if (! is_array($file) || ! isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        return $this->storeSingleUploadedFile($file, $purpose, $title, 'main');
    }

    private function storeUploadedGallery($fileSet, $purpose, $title)
    {
        if (! is_array($fileSet) || ! isset($fileSet['name']) || ! is_array($fileSet['name'])) {
            return array();
        }

        $stored = array();
        $total = count($fileSet['name']);

        for ($index = 0; $index < $total; $index++) {
            $singleFile = array(
                'name' => isset($fileSet['name'][$index]) ? $fileSet['name'][$index] : '',
                'type' => isset($fileSet['type'][$index]) ? $fileSet['type'][$index] : '',
                'tmp_name' => isset($fileSet['tmp_name'][$index]) ? $fileSet['tmp_name'][$index] : '',
                'error' => isset($fileSet['error'][$index]) ? $fileSet['error'][$index] : UPLOAD_ERR_NO_FILE,
                'size' => isset($fileSet['size'][$index]) ? $fileSet['size'][$index] : 0,
            );

            $storedFile = $this->storeSingleUploadedFile($singleFile, $purpose, $title, 'gallery-' . ($index + 1));

            if ($storedFile === false) {
                return false;
            }

            if ($storedFile !== '') {
                $stored[] = $storedFile;
            }
        }

        return $stored;
    }

    private function storeSingleUploadedFile(array $file, $purpose, $title, $suffix)
    {
        if (! isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK || ! isset($file['tmp_name']) || $file['tmp_name'] === '') {
            return false;
        }

        $extension = strtolower(pathinfo(isset($file['name']) ? $file['name'] : '', PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (! in_array($extension, $allowed, true)) {
            return false;
        }

        $routeFolder = $this->propertyRouteFolder($purpose);
        $directoryPath = $this->projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $routeFolder;

        if (! is_dir($directoryPath) && ! mkdir($directoryPath, 0777, true)) {
            return false;
        }

        $filename = $this->slug($title) . '-' . $suffix . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $extension;
        $targetPath = $directoryPath . DIRECTORY_SEPARATOR . $filename;

        if (! move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        return $this->assetBaseUrl() . '/uploads/' . $routeFolder . '/' . $filename;
    }

    private function propertyRouteFolder($purpose)
    {
        if ($purpose === 'sale') {
            return 'homes';
        }

        if ($purpose === 'commercial') {
            return 'commercial';
        }

        return 'rentals';
    }

    private function assetBaseUrl()
    {
        return '/public/assets';
    }

    private function canonicalPath($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (function_exists('app_canonical_public_path')) {
            return (string) \app_canonical_public_path($value);
        }

        return $value;
    }

    private function canonicalPathList(array $paths)
    {
        $results = array();

        foreach ($paths as $path) {
            $path = $this->canonicalPath($path);

            if ($path !== '' && ! in_array($path, $results, true)) {
                $results[] = $path;
            }
        }

        return $results;
    }

    private function displayPath($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (function_exists('app_normalize_public_path')) {
            return (string) \app_normalize_public_path($value);
        }

        return $value;
    }

    private function displayPathList(array $paths)
    {
        $results = array();

        foreach ($paths as $path) {
            $path = $this->displayPath($path);

            if ($path !== '' && ! in_array($path, $results, true)) {
                $results[] = $path;
            }
        }

        return $results;
    }

    private function slug($value)
    {
        $slug = strtolower(trim((string) $value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'property';
    }

    private function normalizeInteger($value)
    {
        return max(0, (int) preg_replace('/[^\d]/', '', (string) $value));
    }

    private function normalizeDecimal($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        return (float) preg_replace('/[^0-9.]/', '', $value);
    }

    private function normalizeCoordinate($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? $value : null;
    }

    private function displayPrice($purpose, $monthlyRent, $askingPrice)
    {
        if ($purpose === 'sale') {
            return $this->formatMoney($askingPrice);
        }

        if ($purpose === 'commercial') {
            $amount = $askingPrice > 0 ? $askingPrice : $monthlyRent;

            return $this->formatMoney($amount) . '/yr';
        }

        return $this->formatMoney($monthlyRent) . '/yr';
    }

    private function displayBaths($value)
    {
        $number = (float) $value;

        if ((float) ((int) $number) === $number) {
            return (int) $number;
        }

        return $number;
    }

    private function extractAmount($value)
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    private function legalFeeAmount($rentAmount)
    {
        $rentAmount = max(0, (int) $rentAmount);

        return (int) round($rentAmount * 0.10);
    }

    private function normalizeCautionDeposit($purpose, $amount)
    {
        $amount = max(0, (int) $amount);

        if (in_array((string) $purpose, array('rent', 'commercial'), true) && $amount <= 0) {
            if (function_exists('app_caution_deposit_default')) {
                return (int) \app_caution_deposit_default();
            }

            return 500000;
        }

        return $amount;
    }

    private function cardLast4($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return substr($digits, -4);
    }

    private function paymentReference($seed)
    {
        return 'PAY-' . strtoupper(substr(md5((string) $seed), 0, 10));
    }

    private function normalizeDateTimeInput($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = str_replace('T', ' ', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function formatDateTimeRange($startsAt, $endsAt)
    {
        $startTimestamp = strtotime((string) $startsAt);
        $endTimestamp = strtotime((string) $endsAt);

        if ($startTimestamp === false || $endTimestamp === false) {
            return trim((string) $startsAt);
        }

        return date('D, M j, Y g:i A', $startTimestamp) . ' - ' . date('g:i A', $endTimestamp);
    }

    private function now()
    {
        return date('Y-m-d H:i:s');
    }

    private function formatMoney($amount)
    {
        return $this->currencySymbol() . number_format((int) $amount);
    }

    private function explodeCsv($value)
    {
        $parts = preg_split('/[\r\n,]+/', trim((string) $value));
        $results = array();

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part !== '') {
                $results[] = $part;
            }
        }

        return $results;
    }
}
