<?php

namespace App\Core;

final class Platform
{
    private const BOOTSTRAP_VERSION = '2026-08-28-1';

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

        $this->ensureSchemaMigrations();

        if (! $this->needsBootstrapRefresh()) {
            return;
        }

        $this->importLegacyDataIfNeeded();
        $this->ensureDefaultSettings();
        $this->ensureDefaultAdminUser();
        $this->ensureDefaultPageContent();
        $this->setSetting('platform_bootstrap_version', self::BOOTSTRAP_VERSION);
    }

    private function ensureSchemaMigrations()
    {
        $this->ensureUnitTables();
        $this->ensureTenureHistoryTable();
        $this->ensureServiceChargeTables();
        $this->ensureTenancyDocumentsTable();
        $this->ensureKeyCollectionsTable();
        $this->seedServiceChargeHistory();

        $table = 'tenancies';
        $existing = $this->columnMapForTable($table);

        if (! isset($existing['end_date'])) {
            $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `end_date` VARCHAR(120) NOT NULL DEFAULT \'\' AFTER `start_date`');
        }

        if (! isset($existing['term'])) {
            $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `term` VARCHAR(120) NOT NULL DEFAULT \'\' AFTER `end_date`');
        }

        if (! isset($existing['notes'])) {
            $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `notes` TEXT NULL AFTER `security_deposit`');
        }

        if (! isset($existing['updated_at'])) {
            $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `updated_at` DATETIME NOT NULL AFTER `created_at`');
        }

        foreach (array('mall_shops', 'residential_units', 'apartment_flats') as $table) {
            $existing = $this->columnMapForTable($table);
            $legacyColumns = $table === 'mall_shops'
                ? array('owner_name', 'owner_phone', 'owner_email')
                : array('occupant_name', 'occupant_phone', 'occupant_email');

            if (! isset($existing['user_id'])) {
                $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `user_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `property_id`');
            }

            if (! isset($existing['start_date'])) {
                $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `start_date` VARCHAR(120) NOT NULL DEFAULT \'\' AFTER `tenure`');
            }

            if (! isset($existing['end_date'])) {
                $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `end_date` VARCHAR(120) NOT NULL DEFAULT \'\' AFTER `start_date`');
            }

            if (! isset($existing['security_deposit'])) {
                $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `security_deposit` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `service_charge`');
            }

            foreach (array('legal_fee', 'vat_fee', 'subscription_form', 'toilet_fee') as $chargeColumn) {
                if (! isset($existing[$chargeColumn])) {
                    $this->database->execute('ALTER TABLE `' . $table . '` ADD COLUMN `' . $chargeColumn . '` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `security_deposit`');
                }
            }

            foreach ($legacyColumns as $column) {
                if (isset($existing[$column])) {
                    $this->database->execute('ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`');
                }
            }

            $userIndexes = $this->database->fetchAll(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name',
                array('table_name' => $table, 'index_name' => $table . '_user_idx')
            );

            if ($userIndexes === array()) {
                $this->database->execute('ALTER TABLE `' . $table . '` ADD INDEX `' . $table . '_user_idx` (`user_id`)');
            }
        }

        $paymentsColumns = $this->columnMapForTable('payments');
        if (! isset($paymentsColumns['unit_id'])) {
            $this->database->execute('ALTER TABLE `payments` ADD COLUMN `unit_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `property_id`');
        }

        $unitIndexes = $this->database->fetchAll(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name',
            array('table_name' => 'payments', 'index_name' => 'payments_unit_idx')
        );

        if ($unitIndexes === array()) {
            $this->database->execute('ALTER TABLE `payments` ADD INDEX `payments_unit_idx` (`unit_id`)');
        }
    }

    private function ensureTenureHistoryTable()
    {
        $this->database->execute('CREATE TABLE IF NOT EXISTS tenure_history (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id INT UNSIGNED NOT NULL DEFAULT 0,
            unit_table VARCHAR(60) NOT NULL DEFAULT \'\',
            unit_id INT UNSIGNED NOT NULL,
            unit_label VARCHAR(190) NOT NULL DEFAULT \'\',
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            user_name VARCHAR(150) NOT NULL DEFAULT \'\',
            user_email VARCHAR(190) NOT NULL DEFAULT \'\',
            user_phone VARCHAR(80) NOT NULL DEFAULT \'\',
            tenure VARCHAR(120) NOT NULL DEFAULT \'\',
            monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
            service_charge INT UNSIGNED NOT NULL DEFAULT 0,
            security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
            start_date VARCHAR(120) NOT NULL DEFAULT \'\',
            end_date VARCHAR(120) NOT NULL DEFAULT \'\',
            amount_due INT UNSIGNED NOT NULL DEFAULT 0,
            amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
            balance_carried INT NOT NULL DEFAULT 0,
            status VARCHAR(40) NOT NULL DEFAULT \'renewed\',
            notes TEXT NULL,
            closed_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY tenure_history_unit_idx (unit_table, unit_id),
            KEY tenure_history_property_idx (property_id),
            KEY tenure_history_user_idx (user_id),
            KEY tenure_history_status_idx (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function ensureTenancyDocumentsTable()
    {
        $this->database->execute('CREATE TABLE IF NOT EXISTS tenancy_documents (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            unit_table VARCHAR(60) NOT NULL DEFAULT \'\',
            unit_id INT UNSIGNED NOT NULL,
            property_id INT UNSIGNED NOT NULL DEFAULT 0,
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            original_name VARCHAR(190) NOT NULL DEFAULT \'\',
            file_path VARCHAR(255) NOT NULL DEFAULT \'\',
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            mime_type VARCHAR(120) NOT NULL DEFAULT \'\',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY td_unit_idx (unit_table, unit_id),
            KEY td_property_idx (property_id),
            KEY td_user_idx (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Documents are grouped by kind, so the tenancy agreement and the key collection form are
        // kept apart on the same registration.
        $existing = $this->columnMapForTable('tenancy_documents');

        if (! isset($existing['document_type'])) {
            $this->database->execute('ALTER TABLE `tenancy_documents` ADD COLUMN `document_type` VARCHAR(40) NOT NULL DEFAULT \'tenancy_agreement\' AFTER `user_id`');
            $this->database->execute('UPDATE `tenancy_documents` SET `document_type` = \'tenancy_agreement\' WHERE `document_type` = \'\'');
        }
    }

    private function ensureKeyCollectionsTable()
    {
        $this->database->execute('CREATE TABLE IF NOT EXISTS key_collections (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id INT UNSIGNED NOT NULL DEFAULT 0,
            unit_table VARCHAR(60) NOT NULL DEFAULT \'\',
            unit_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            payment_id INT UNSIGNED NOT NULL DEFAULT 0,
            amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
            expected_rent INT UNSIGNED NOT NULL DEFAULT 0,
            balance INT NOT NULL DEFAULT 0,
            collected_at DATETIME NOT NULL,
            collected_by INT UNSIGNED NOT NULL DEFAULT 0,
            collector_name VARCHAR(150) NOT NULL DEFAULT \'\',
            recipient_name VARCHAR(150) NOT NULL DEFAULT \'\',
            keys_count INT UNSIGNED NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY key_collect_unit_idx (unit_table, unit_id),
            KEY key_collect_property_idx (property_id),
            KEY key_collect_user_idx (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function ensureServiceChargeTables()
    {
        $this->database->execute('CREATE TABLE IF NOT EXISTS service_charge_history (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            property_id INT UNSIGNED NOT NULL,
            service_charge INT UNSIGNED NOT NULL DEFAULT 0,
            effective_from DATE NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY sc_history_prop_date (property_id, effective_from),
            KEY sc_history_property_idx (property_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->database->execute('CREATE TABLE IF NOT EXISTS service_charge_allocations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            payment_id INT UNSIGNED NOT NULL,
            property_id INT UNSIGNED NOT NULL,
            unit_table VARCHAR(60) NOT NULL DEFAULT \'\',
            unit_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            service_month CHAR(7) NOT NULL DEFAULT \'\',
            rate_used INT UNSIGNED NOT NULL DEFAULT 0,
            amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY sc_alloc_payment_idx (payment_id),
            KEY sc_alloc_unit_idx (unit_table, unit_id),
            KEY sc_alloc_month_idx (unit_table, unit_id, service_month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function seedServiceChargeHistory()
    {
        $count = (int) $this->database->fetchValue('SELECT COUNT(*) FROM service_charge_history');
        if ($count > 0) {
            return;
        }

        $properties = $this->allProperties();
        $now = $this->now();
        $seedDate = '2001-01-01';

        foreach ($properties as $property) {
            $propertyId = (int) $property['id'];
            $unitTable = $this->unitTableForProperty($property);
            $rows = $this->database->fetchAll(
                'SELECT service_charge, COUNT(*) AS c FROM `' . $unitTable . '` WHERE property_id = :pid AND user_id > 0 AND service_charge > 0 GROUP BY service_charge ORDER BY c DESC LIMIT 1',
                array('pid' => $propertyId)
            );
            $baseline = $rows !== array() ? (int) $rows[0]['service_charge'] : (int) $property['serviceCharge'];

            $this->database->insert('service_charge_history', array(
                'property_id' => $propertyId,
                'service_charge' => $baseline,
                'effective_from' => $seedDate,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }
    }

    private function ensureUnitTables()
    {
        $required = array(
            'mall_shops' => 'CREATE TABLE IF NOT EXISTS mall_shops (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                property_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL DEFAULT 0,
                shop_number VARCHAR(120) NOT NULL DEFAULT \'\',
                shop_name VARCHAR(190) NOT NULL DEFAULT \'\',
                status VARCHAR(40) NOT NULL DEFAULT \'vacant\',
                tenure VARCHAR(120) NOT NULL DEFAULT \'\',
                start_date VARCHAR(120) NOT NULL DEFAULT \'\',
                end_date VARCHAR(120) NOT NULL DEFAULT \'\',
                monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
                service_charge INT UNSIGNED NOT NULL DEFAULT 0,
                security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
                legal_fee INT UNSIGNED NOT NULL DEFAULT 0,
                vat_fee INT UNSIGNED NOT NULL DEFAULT 0,
                subscription_form INT UNSIGNED NOT NULL DEFAULT 0,
                toilet_fee INT UNSIGNED NOT NULL DEFAULT 0,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY mall_shops_property_idx (property_id),
                KEY mall_shops_user_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'residential_units' => 'CREATE TABLE IF NOT EXISTS residential_units (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                property_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL DEFAULT 0,
                block VARCHAR(80) NOT NULL DEFAULT \'\',
                unit_number VARCHAR(120) NOT NULL DEFAULT \'\',
                status VARCHAR(40) NOT NULL DEFAULT \'vacant\',
                tenure VARCHAR(120) NOT NULL DEFAULT \'\',
                start_date VARCHAR(120) NOT NULL DEFAULT \'\',
                end_date VARCHAR(120) NOT NULL DEFAULT \'\',
                monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
                service_charge INT UNSIGNED NOT NULL DEFAULT 0,
                security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
                legal_fee INT UNSIGNED NOT NULL DEFAULT 0,
                vat_fee INT UNSIGNED NOT NULL DEFAULT 0,
                subscription_form INT UNSIGNED NOT NULL DEFAULT 0,
                toilet_fee INT UNSIGNED NOT NULL DEFAULT 0,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY residential_units_property_idx (property_id),
                KEY residential_units_user_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'apartment_flats' => 'CREATE TABLE IF NOT EXISTS apartment_flats (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                property_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL DEFAULT 0,
                block VARCHAR(80) NOT NULL DEFAULT \'\',
                floor VARCHAR(40) NOT NULL DEFAULT \'\',
                flat_number VARCHAR(120) NOT NULL DEFAULT \'\',
                status VARCHAR(40) NOT NULL DEFAULT \'vacant\',
                tenure VARCHAR(120) NOT NULL DEFAULT \'\',
                start_date VARCHAR(120) NOT NULL DEFAULT \'\',
                end_date VARCHAR(120) NOT NULL DEFAULT \'\',
                monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
                service_charge INT UNSIGNED NOT NULL DEFAULT 0,
                security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
                legal_fee INT UNSIGNED NOT NULL DEFAULT 0,
                vat_fee INT UNSIGNED NOT NULL DEFAULT 0,
                subscription_form INT UNSIGNED NOT NULL DEFAULT 0,
                toilet_fee INT UNSIGNED NOT NULL DEFAULT 0,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY apartment_flats_property_idx (property_id),
                KEY apartment_flats_user_idx (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        );

        foreach ($required as $tableName => $createStatement) {
            $result = $this->database->execute($createStatement);
        }
    }

    private function columnMapForTable($table)
    {
        $safeTable = str_replace('`', '', (string) $table);
        $rows = $this->database->fetchAll(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name',
            array('table_name' => $safeTable)
        );
        $map = array();

        foreach ($rows as $row) {
            $map[$row['COLUMN_NAME']] = true;
        }

        return $map;
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
            'openMaintenance' => (int) $this->database->fetchValue("SELECT COUNT(*) FROM maintenance_tickets WHERE status IN ('open', 'in_progress')"),
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
            'activeOffers' => (int) $this->database->fetchValue("SELECT COUNT(*) FROM purchase_offers WHERE status IN ('submitted', 'reviewing', 'countered')"),
            'openMaintenance' => (int) $this->database->fetchValue("SELECT COUNT(*) FROM maintenance_tickets WHERE status IN ('open', 'in_progress')"),
            'unreadMessages' => (int) $this->database->fetchValue('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
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

    public function createTourSlotSeries($propertyId, array $payload, $adminUser)
    {
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'Choose a valid property before generating tour slots.');
        }

        $slotDate = trim(isset($payload['slot_date']) ? $payload['slot_date'] : '');
        $startTime = trim(isset($payload['window_start_time']) ? $payload['window_start_time'] : '');
        $endTime = trim(isset($payload['window_end_time']) ? $payload['window_end_time'] : '');
        $slotMinutes = max(15, $this->normalizeInteger(isset($payload['slot_minutes']) ? $payload['slot_minutes'] : 30));
        $gapMinutes = max(0, $this->normalizeInteger(isset($payload['gap_minutes']) ? $payload['gap_minutes'] : 0));
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');

        if ($slotDate === '' || $startTime === '' || $endTime === '') {
            return array(false, 'Choose a date plus both start and end times before generating slots.');
        }

        $windowStartsAt = $this->normalizeDateTimeInput($slotDate . 'T' . $startTime);
        $windowEndsAt = $this->normalizeDateTimeInput($slotDate . 'T' . $endTime);

        if ($windowStartsAt === '' || $windowEndsAt === '') {
            return array(false, 'Enter a valid date and time window for the slot generator.');
        }

        $windowStartTs = strtotime($windowStartsAt);
        $windowEndTs = strtotime($windowEndsAt);

        if ($windowStartTs === false || $windowEndTs === false || $windowEndTs <= $windowStartTs) {
            return array(false, 'The viewing window must end after it starts.');
        }

        if ($windowStartTs < time()) {
            return array(false, 'Tour slot generation must start in the future.');
        }

        $createdCount = 0;
        $cursor = $windowStartTs;

        while (($cursor + ($slotMinutes * 60)) <= $windowEndTs) {
            $startsAt = date('Y-m-d H:i:s', $cursor);
            $endsAt = date('Y-m-d H:i:s', $cursor + ($slotMinutes * 60));
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

            if (! $overlap) {
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
                $createdCount++;
            }

            $cursor += ($slotMinutes + $gapMinutes) * 60;
        }

        if ($createdCount < 1) {
            return array(false, 'No new slots were generated. The time window may already be covered by existing availability.');
        }

        return array(
            array(
                'count' => $createdCount,
                'property' => $property,
                'slotMinutes' => $slotMinutes,
            ),
            null
        );
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

    public function cancelUserTourRequest($requestId, $user)
    {
        $request = $this->findTourRequest($requestId);

        if (! $request || (int) $request['userId'] !== (int) $user['id']) {
            return array(false, 'We could not find that tour request on your account.');
        }

        if (! in_array($request['status'], array('requested', 'confirmed'), true)) {
            return array(false, 'Only active tour requests can be cancelled.');
        }

        $now = $this->now();
        $updatedRows = $this->database->execute(
            "UPDATE tour_requests
             SET status = 'cancelled', updated_at = :updated_at, cancelled_at = :cancelled_at
             WHERE id = :id",
            array(
                'updated_at' => $now,
                'cancelled_at' => $now,
                'id' => (int) $requestId,
            )
        );

        if ($updatedRows < 1) {
            return array(false, 'That tour request could not be cancelled right now.');
        }

        unset($this->tourSlotCache[(int) $request['slotId']]);

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

        $annualIncome = trim(isset($payload['annual_income']) ? $payload['annual_income'] : '');
        $employer = trim(isset($payload['employer']) ? $payload['employer'] : '');
        $moveInDate = trim(isset($payload['move_in_date']) ? $payload['move_in_date'] : '');
        $occupants = max(1, $this->normalizeInteger(isset($payload['occupants']) ? $payload['occupants'] : 1));
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');
        $autoApproved = $this->shouldAutoApproveApplication($property, $annualIncome, $occupants);
        $now = $this->now();

        $applicationId = $this->database->insert('applications', array(
            'user_id' => (int) $user['id'],
            'property_id' => (int) $property['id'],
            'status' => $autoApproved ? 'approved' : 'submitted',
            'full_name' => isset($user['name']) ? $user['name'] : '',
            'email' => isset($user['email']) ? $user['email'] : '',
            'phone' => isset($user['phone']) ? $user['phone'] : '',
            'annual_income' => $annualIncome,
            'employer' => $employer,
            'move_in_date' => $moveInDate,
            'occupants' => $occupants,
            'notes' => $notes,
            'approved_by_name' => $autoApproved ? 'Auto screening' : null,
            'approved_at' => $autoApproved ? $now : null,
            'submitted_at' => $now,
            'activated_at' => null,
        ));

        return array($this->findApplication($applicationId), null);
    }

    private function shouldAutoApproveApplication($property, $annualIncome, $occupants)
    {
        if (! is_array($property)) {
            return false;
        }

        if (isset($property['purpose']) && $property['purpose'] !== 'rent') {
            return false;
        }

        $incomeAmount = $this->extractAmount($annualIncome);
        $rentAmount = isset($property['monthlyRent']) ? (int) $property['monthlyRent'] : 0;
        $beds = isset($property['beds']) ? max(1, (int) $property['beds']) : 1;

        if ($incomeAmount <= 0 || $rentAmount <= 0) {
            return false;
        }

        if ((int) $occupants > ($beds * 2)) {
            return false;
        }

        return $incomeAmount >= (int) round($rentAmount * 2.5);
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

    public function createPurchaseOffer($user, $property, array $payload)
    {
        if (! is_array($property) || ! isset($property['purpose']) || $property['purpose'] !== 'sale') {
            return array(false, 'Offers can only be submitted for homes listed for sale.');
        }

        $offerAmount = $this->extractAmount(isset($payload['offer_amount']) ? $payload['offer_amount'] : 0);
        $timeline = trim(isset($payload['timeline']) ? $payload['timeline'] : '');
        $terms = trim(isset($payload['terms']) ? $payload['terms'] : '');

        if ($offerAmount <= 0) {
            return array(false, 'Enter a valid offer amount.');
        }

        $existing = $this->database->fetchOne(
            "SELECT id
             FROM purchase_offers
             WHERE user_id = :user_id
               AND property_id = :property_id
               AND status IN ('submitted', 'reviewing', 'countered')
             LIMIT 1",
            array(
                'user_id' => (int) $user['id'],
                'property_id' => (int) $property['id'],
            )
        );

        if ($existing) {
            return array(false, 'You already have an active offer under review for this property.');
        }

        $now = $this->now();
        $offerId = $this->database->insert('purchase_offers', array(
            'user_id' => (int) $user['id'],
            'property_id' => (int) $property['id'],
            'status' => 'submitted',
            'offer_amount' => $offerAmount,
            'terms' => $terms,
            'timeline' => $timeline,
            'full_name' => isset($user['name']) ? (string) $user['name'] : '',
            'email' => isset($user['email']) ? (string) $user['email'] : '',
            'phone' => isset($user['phone']) ? (string) $user['phone'] : '',
            'admin_notes' => null,
            'submitted_at' => $now,
            'updated_at' => $now,
            'reviewed_at' => null,
            'reviewed_by_name' => null,
        ));

        return array($this->findPurchaseOffer($offerId), null);
    }

    public function offersForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM purchase_offers WHERE user_id = :user_id ORDER BY submitted_at DESC, id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydratePurchaseOffers($rows);
    }

    public function allPurchaseOffers(array $filters = array())
    {
        $sql = 'SELECT * FROM purchase_offers WHERE 1 = 1';
        $params = array();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = trim((string) $filters['status']);
        }

        $sql .= ' ORDER BY submitted_at DESC, id DESC';

        if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return $this->hydratePurchaseOffers($this->database->fetchAll($sql, $params));
    }

    public function findPurchaseOffer($offerId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM purchase_offers WHERE id = :id LIMIT 1',
            array('id' => (int) $offerId)
        );

        return $row ? $this->hydratePurchaseOffer($row) : null;
    }

    public function updatePurchaseOfferStatus($offerId, $status, $adminUser, $adminNotes = '')
    {
        $offer = $this->findPurchaseOffer($offerId);
        $status = trim((string) $status);
        $allowedStatuses = array('reviewing', 'countered', 'accepted', 'declined');

        if (! $offer) {
            return array(false, 'That offer could not be found.');
        }

        if (! in_array($status, $allowedStatuses, true)) {
            return array(false, 'Choose a valid offer status update.');
        }

        $now = $this->now();
        $updatedRows = $this->database->execute(
            'UPDATE purchase_offers
             SET status = :status,
                 admin_notes = :admin_notes,
                 reviewed_at = :reviewed_at,
                 reviewed_by_name = :reviewed_by_name,
                 updated_at = :updated_at
             WHERE id = :id',
            array(
                'status' => $status,
                'admin_notes' => trim((string) $adminNotes),
                'reviewed_at' => $now,
                'reviewed_by_name' => isset($adminUser['name']) ? (string) $adminUser['name'] : 'Sandworth Admin',
                'updated_at' => $now,
                'id' => (int) $offerId,
            )
        );

        if ($updatedRows < 1) {
            return array(false, 'The offer could not be updated right now.');
        }

        return array($this->findPurchaseOffer($offerId), null);
    }

    public function createMessage($actor, array $payload, $sender)
    {
        $sender = trim((string) $sender);
        $body = trim(isset($payload['body']) ? $payload['body'] : '');
        $subject = trim(isset($payload['subject']) ? $payload['subject'] : '');
        $propertyId = isset($payload['property_id']) ? (int) $payload['property_id'] : 0;
        $userId = $sender === 'admin'
            ? (isset($payload['user_id']) ? (int) $payload['user_id'] : 0)
            : (isset($actor['id']) ? (int) $actor['id'] : 0);

        if ($body === '') {
            return array(false, 'Write a message before sending it.');
        }

        if ($userId <= 0 || ! $this->findUserById($userId)) {
            return array(false, 'Choose a valid customer before sending a message.');
        }

        if ($propertyId > 0 && ! $this->findProperty($propertyId)) {
            return array(false, 'That property could not be found for this conversation.');
        }

        $messageId = $this->database->insert('messages', array(
            'user_id' => $userId,
            'property_id' => max(0, $propertyId),
            'sender' => $sender === 'admin' ? 'admin' : 'user',
            'subject' => $subject,
            'body' => $body,
            'is_read' => 0,
            'created_at' => $this->now(),
        ));

        return array($this->findMessage($messageId), null);
    }

    public function messagesForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM messages WHERE user_id = :user_id ORDER BY created_at DESC, id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydrateMessages($rows);
    }

    public function allMessages(array $filters = array())
    {
        $sql = 'SELECT * FROM messages WHERE 1 = 1';
        $params = array();

        if (isset($filters['user_id']) && (int) $filters['user_id'] > 0) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (isset($filters['property_id']) && (int) $filters['property_id'] > 0) {
            $sql .= ' AND property_id = :property_id';
            $params['property_id'] = (int) $filters['property_id'];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return $this->hydrateMessages($this->database->fetchAll($sql, $params));
    }

    public function findMessage($messageId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM messages WHERE id = :id LIMIT 1',
            array('id' => (int) $messageId)
        );

        return $row ? $this->hydrateMessage($row) : null;
    }

    public function createMaintenanceTicket($user, array $payload)
    {
        $tenancy = $this->tenancyForUser(isset($user['id']) ? $user['id'] : 0);

        if (! $tenancy) {
            return array(false, 'Only active tenants can submit maintenance tickets.');
        }

        $title = trim(isset($payload['title']) ? $payload['title'] : '');
        $description = trim(isset($payload['description']) ? $payload['description'] : '');
        $priority = trim(isset($payload['priority']) ? $payload['priority'] : 'medium');

        if ($title === '' || $description === '') {
            return array(false, 'Add both a maintenance title and a short description.');
        }

        if (! in_array($priority, array('low', 'medium', 'high', 'urgent'), true)) {
            $priority = 'medium';
        }

        $now = $this->now();
        $ticketId = $this->database->insert('maintenance_tickets', array(
            'tenancy_id' => (int) $tenancy['id'],
            'user_id' => (int) $user['id'],
            'property_id' => (int) $tenancy['propertyId'],
            'status' => 'open',
            'priority' => $priority,
            'title' => $title,
            'description' => $description,
            'admin_notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'resolved_at' => null,
            'resolved_by_name' => null,
        ));

        return array($this->findMaintenanceTicket($ticketId), null);
    }

    public function maintenanceTicketsForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM maintenance_tickets WHERE user_id = :user_id ORDER BY updated_at DESC, id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydrateMaintenanceTickets($rows);
    }

    public function allMaintenanceTickets(array $filters = array())
    {
        $sql = 'SELECT * FROM maintenance_tickets WHERE 1 = 1';
        $params = array();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = trim((string) $filters['status']);
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';

        if (isset($filters['limit']) && (int) $filters['limit'] > 0) {
            $sql .= ' LIMIT ' . (int) $filters['limit'];
        }

        return $this->hydrateMaintenanceTickets($this->database->fetchAll($sql, $params));
    }

    public function findMaintenanceTicket($ticketId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM maintenance_tickets WHERE id = :id LIMIT 1',
            array('id' => (int) $ticketId)
        );

        return $row ? $this->hydrateMaintenanceTicket($row) : null;
    }

    public function updateMaintenanceTicketStatus($ticketId, $status, $adminUser, $adminNotes = '')
    {
        $ticket = $this->findMaintenanceTicket($ticketId);
        $status = trim((string) $status);
        $allowedStatuses = array('open', 'in_progress', 'resolved', 'closed');

        if (! $ticket) {
            return array(false, 'That maintenance ticket could not be found.');
        }

        if (! in_array($status, $allowedStatuses, true)) {
            return array(false, 'Choose a valid maintenance status.');
        }

        $now = $this->now();
        $fields = array(
            'status' => $status,
            'admin_notes' => trim((string) $adminNotes),
            'updated_at' => $now,
            'id' => (int) $ticketId,
        );
        $sql = 'UPDATE maintenance_tickets SET status = :status, admin_notes = :admin_notes, updated_at = :updated_at';

        if (in_array($status, array('resolved', 'closed'), true)) {
            $sql .= ', resolved_at = :resolved_at, resolved_by_name = :resolved_by_name';
            $fields['resolved_at'] = $now;
            $fields['resolved_by_name'] = isset($adminUser['name']) ? (string) $adminUser['name'] : 'Sandworth Admin';
        }

        $sql .= ' WHERE id = :id';

        $updatedRows = $this->database->execute($sql, $fields);

        if ($updatedRows < 1) {
            return array(false, 'The maintenance ticket could not be updated right now.');
        }

        return array($this->findMaintenanceTicket($ticketId), null);
    }

    public function createSavedSearch($user, array $payload)
    {
        $purpose = trim(isset($payload['purpose']) ? $payload['purpose'] : 'rent');
        $location = trim(isset($payload['location']) ? $payload['location'] : '');
        $beds = max(0, $this->normalizeInteger(isset($payload['beds']) ? $payload['beds'] : 0));
        $propertyType = trim(isset($payload['property_type']) ? $payload['property_type'] : '');
        $commercialType = trim(isset($payload['commercial_type']) ? $payload['commercial_type'] : '');
        $petFriendly = isset($payload['pet_friendly']) && (string) $payload['pet_friendly'] === '1' ? 1 : 0;

        if (! in_array($purpose, array('rent', 'sale', 'commercial'), true)) {
            return array(false, 'Choose a valid search category to save.');
        }

        $name = trim(isset($payload['name']) ? $payload['name'] : '');

        if ($name === '') {
            $name = ucfirst($purpose) . ($location !== '' ? ' in ' . $location : ' search');
        }

        $existing = $this->database->fetchOne(
            'SELECT id
             FROM saved_searches
             WHERE user_id = :user_id
               AND purpose = :purpose
               AND location = :location
               AND beds = :beds
               AND property_type = :property_type
               AND commercial_type = :commercial_type
               AND pet_friendly = :pet_friendly
             LIMIT 1',
            array(
                'user_id' => (int) $user['id'],
                'purpose' => $purpose,
                'location' => $location,
                'beds' => $beds,
                'property_type' => $propertyType,
                'commercial_type' => $commercialType,
                'pet_friendly' => $petFriendly,
            )
        );

        if ($existing) {
            return array(false, 'That search is already saved on your dashboard.');
        }

        $now = $this->now();
        $searchId = $this->database->insert('saved_searches', array(
            'user_id' => (int) $user['id'],
            'name' => $name,
            'purpose' => $purpose,
            'location' => $location,
            'beds' => $beds,
            'property_type' => $propertyType,
            'commercial_type' => $commercialType,
            'pet_friendly' => $petFriendly,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        return array($this->findSavedSearch($searchId), null);
    }

    public function savedSearchesForUser($userId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM saved_searches WHERE user_id = :user_id ORDER BY updated_at DESC, id DESC',
            array('user_id' => (int) $userId)
        );

        return $this->hydrateSavedSearches($rows);
    }

    public function deleteSavedSearch($searchId, $user)
    {
        $search = $this->findSavedSearch($searchId);

        if (! $search || (int) $search['userId'] !== (int) $user['id']) {
            return array(false, 'That saved search is not available on your account.');
        }

        $deletedRows = $this->database->execute(
            'DELETE FROM saved_searches WHERE id = :id LIMIT 1',
            array('id' => (int) $searchId)
        );

        if ($deletedRows < 1) {
            return array(false, 'The saved search could not be removed right now.');
        }

        return array($search, null);
    }

    public function findSavedSearch($searchId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM saved_searches WHERE id = :id LIMIT 1',
            array('id' => (int) $searchId)
        );

        return $row ? $this->hydrateSavedSearch($row) : null;
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
                'end_date' => '',
                'term' => '',
                'monthly_rent' => (int) $property['monthlyRent'],
                'service_charge' => (int) $property['serviceCharge'],
                'security_deposit' => $cautionDeposit,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
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

    public function allTenancies(array $filters = array())
    {
        $sql = "SELECT * FROM tenancies WHERE 1 = 1";
        $params = array();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        $sql .= ' ORDER BY id DESC';
        $rows = $this->database->fetchAll($sql, $params);
        $tenancies = array();

        foreach ($rows as $row) {
            $tenancies[] = $this->hydrateTenancy($row);
        }

        return $tenancies;
    }

    public function findTenancyAdmin($tenancyId)
    {
        $row = $this->database->fetchOne(
            'SELECT * FROM tenancies WHERE id = :id LIMIT 1',
            array('id' => (int) $tenancyId)
        );

        return $row ? $this->hydrateTenancy($row) : null;
    }

    public function tenantUserOptions()
    {
        $rows = $this->database->fetchAll(
            "SELECT * FROM users WHERE role <> 'admin' ORDER BY name ASC, id ASC"
        );
        $users = array();

        foreach ($rows as $row) {
            $users[] = $this->hydrateUser($row);
        }

        return $users;
    }

    public function findOrCreateTenantUser(array $payload)
    {
        $name = trim(isset($payload['tenant_name']) ? $payload['tenant_name'] : '');
        $email = strtolower(trim(isset($payload['tenant_email']) ? $payload['tenant_email'] : ''));
        $phone = trim(isset($payload['tenant_phone']) ? $payload['tenant_phone'] : '');

        if ($name === '' || $email === '' || $phone === '') {
            return array(
                'user' => null,
                'error' => 'Choose an existing tenant account, or complete the name, email, and phone fields to register a new tenant.',
            );
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return array('user' => null, 'error' => 'Enter a valid email address for the new tenant.');
        }

        $existing = $this->findUserByEmail($email);

        if ($existing) {
            return array('user' => $existing, 'error' => null);
        }

        $userId = $this->database->insert('users', array(
            'role' => 'user',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'created_at' => $this->now(),
        ));

        $user = $this->findUserById($userId);

        return array('user' => $user, 'error' => $user ? null : 'The new tenant account could not be created.');
    }

    public function saveTenancyByAdmin($tenancyId, array $payload, $adminUser)
    {
        $tenancyId = (int) $tenancyId;
        $userId = (int) $this->normalizeInteger(isset($payload['user_id']) ? $payload['user_id'] : 0);
        $propertyId = (int) $this->normalizeInteger(isset($payload['property_id']) ? $payload['property_id'] : 0);

        if ($userId <= 0) {
            $newTenant = $this->findOrCreateTenantUser($payload);

            if (! $newTenant['user']) {
                return array(false, $newTenant['error']);
            }

            $userId = (int) $newTenant['user']['id'];
        }

        if ($propertyId <= 0) {
            return array(false, 'Choose the property the tenant is renting.');
        }

        $user = $this->findUserById($userId);

        if (! $user) {
            return array(false, 'The selected tenant account could not be found.');
        }

        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'The selected property could not be found.');
        }

        $status = trim(isset($payload['status']) ? $payload['status'] : 'active');

        if (! in_array($status, array('active', 'inactive', 'ended'), true)) {
            $status = 'active';
        }

        $startDate = trim(isset($payload['start_date']) ? $payload['start_date'] : '');
        $endDate = trim(isset($payload['end_date']) ? $payload['end_date'] : '');
        $term = trim(isset($payload['term']) ? $payload['term'] : '');
        $monthlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        $serviceCharge = $this->extractAmount(isset($payload['service_charge']) ? $payload['service_charge'] : 0);
        $securityDeposit = $this->extractAmount(isset($payload['security_deposit']) ? $payload['security_deposit'] : 0);
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');
        $applicationId = (int) $this->normalizeInteger(isset($payload['application_id']) ? $payload['application_id'] : 0);
        $now = $this->now();

        if ($securityDeposit <= 0) {
            $securityDeposit = $this->normalizeCautionDeposit((string) $property['purpose'], $securityDeposit);
        }

        if ($tenancyId <= 0) {
            $tenancyId = $this->database->insert('tenancies', array(
                'user_id' => $userId,
                'property_id' => $propertyId,
                'application_id' => $applicationId,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'term' => $term,
                'monthly_rent' => $monthlyRent,
                'service_charge' => $serviceCharge,
                'security_deposit' => $securityDeposit,
                'notes' => $notes !== '' ? $notes : null,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        } else {
            $this->database->execute(
                'UPDATE tenancies SET
                    user_id = :user_id,
                    property_id = :property_id,
                    application_id = :application_id,
                    status = :status,
                    start_date = :start_date,
                    end_date = :end_date,
                    term = :term,
                    monthly_rent = :monthly_rent,
                    service_charge = :service_charge,
                    security_deposit = :security_deposit,
                    notes = :notes,
                    updated_at = :updated_at
                WHERE id = :id',
                array(
                    'user_id' => $userId,
                    'property_id' => $propertyId,
                    'application_id' => $applicationId,
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'term' => $term,
                    'monthly_rent' => $monthlyRent,
                    'service_charge' => $serviceCharge,
                    'security_deposit' => $securityDeposit,
                    'notes' => $notes !== '' ? $notes : null,
                    'updated_at' => $now,
                    'id' => $tenancyId,
                )
            );
        }

        return array($this->findTenancyAdmin($tenancyId), null);
    }

    public function addTenancyPaymentByAdmin($tenancyId, array $payload, $adminUser)
    {
        $tenancy = $this->findTenancyAdmin((int) $tenancyId);

        if (! $tenancy) {
            return array(false, 'That tenancy record could not be found.');
        }

        $amount = $this->extractAmount(isset($payload['amount']) ? $payload['amount'] : 0);

        if ($amount <= 0) {
            return array(false, 'Enter a valid payment amount.');
        }

        $label = trim(isset($payload['label']) ? $payload['label'] : '');
        $chargeType = trim(isset($payload['charge_type']) ? $payload['charge_type'] : 'rent');
        $channel = trim(isset($payload['channel']) ? $payload['channel'] : 'cash');
        $paidAt = $this->normalizeDateTimeInput(trim(isset($payload['paid_at']) ? $payload['paid_at'] : ''));
        $now = $this->now();

        if ($paidAt === '') {
            $paidAt = $now;
        }

        $reference = trim(isset($payload['reference']) ? $payload['reference'] : '');

        if ($reference === '') {
            $reference = $this->paymentReference($tenancy['id'] . $chargeType . $paidAt . mt_rand(1000, 9999));
        }

        $this->database->insert('payments', array(
            'user_id' => (int) $tenancy['userId'],
            'property_id' => (int) $tenancy['propertyId'],
            'application_id' => (int) $tenancy['applicationId'],
            'tenancy_id' => (int) $tenancy['id'],
            'amount' => $amount,
            'channel' => $channel,
            'card_last4' => '',
            'reference' => $reference,
            'description' => $label !== '' ? $label : 'Tenant payment',
            'created_at' => $paidAt,
        ));

        $this->insertLedgerEntry(
            $tenancy['id'],
            $label !== '' ? $label : 'Tenant payment',
            $chargeType,
            $amount,
            $reference,
            $paidAt
        );

        return array($this->findTenancyAdmin($tenancy['id']), null);
    }

    public function deleteTenancyByAdmin($tenancyId)
    {
        $tenancy = $this->findTenancyAdmin((int) $tenancyId);

        if (! $tenancy) {
            return array(false, 'That tenancy record could not be found.');
        }

        $this->database->execute(
            'DELETE FROM payments WHERE tenancy_id = :tenancy_id',
            array('tenancy_id' => (int) $tenancy['id'])
        );
        $this->database->execute(
            'DELETE FROM tenancy_ledger WHERE tenancy_id = :tenancy_id',
            array('tenancy_id' => (int) $tenancy['id'])
        );
        $this->database->execute('DELETE FROM tenancies WHERE id = :id', array('id' => (int) $tenancy['id']));

        return array($tenancy, null);
    }

    public function deleteTenancyPaymentByAdmin($paymentId, $tenancyId)
    {
        $paymentId = (int) $paymentId;
        $tenancy = $this->findTenancyAdmin((int) $tenancyId);

        if (! $tenancy) {
            return array(false, 'That tenancy record could not be found.');
        }

        $payment = $this->database->fetchOne(
            'SELECT * FROM payments WHERE id = :id AND tenancy_id = :tenancy_id LIMIT 1',
            array('id' => $paymentId, 'tenancy_id' => (int) $tenancy['id'])
        );

        if (! $payment) {
            return array(false, 'That payment could not be found on the tenancy.');
        }

        $this->database->execute(
            'DELETE FROM payments WHERE id = :id AND tenancy_id = :tenancy_id',
            array('id' => $paymentId, 'tenancy_id' => (int) $tenancy['id'])
        );

        if (isset($payment['reference']) && trim($payment['reference']) !== '') {
            $this->database->execute(
                'DELETE FROM tenancy_ledger WHERE tenancy_id = :tenancy_id AND payment_reference = :reference',
                array('tenancy_id' => (int) $tenancy['id'], 'reference' => trim($payment['reference']))
            );
        }

        return array($this->findTenancyAdmin($tenancy['id']), null);
    }

    public function unitTableForProperty($property)
    {
        $type = strtolower(trim((string) $property['type']));

        if (strpos($type, 'mall') !== false) {
            return 'mall_shops';
        }

        if (strpos($type, 'apartment') !== false) {
            return 'apartment_flats';
        }

        return 'residential_units';
    }

    private function registrationTables()
    {
        return array('mall_shops', 'residential_units', 'apartment_flats');
    }

    private function unitRegistrationSelect($unitTable)
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : 'residential_units';

        if ($unitTable === 'mall_shops') {
            $identity = 't.shop_number, t.shop_name, \'\' AS block, \'\' AS floor, \'\' AS flat_number, \'\' AS unit_number';
        } elseif ($unitTable === 'apartment_flats') {
            $identity = '\'\' AS shop_number, \'\' AS shop_name, t.block, t.floor, t.flat_number, \'\' AS unit_number';
        } else {
            $identity = '\'\' AS shop_number, \'\' AS shop_name, t.block, \'\' AS floor, \'\' AS flat_number, t.unit_number';
        }

        return 'SELECT \'' . $unitTable . '\' AS unit_table, t.id AS unit_id, t.property_id, t.user_id, t.status, '
            . 't.tenure, t.start_date, t.end_date, t.monthly_rent, t.service_charge, t.security_deposit, '
            . 't.legal_fee, t.vat_fee, t.subscription_form, t.toilet_fee, '
            . 't.notes, t.created_at, t.updated_at, ' . $identity . ', '
            . 'u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.role AS user_role, '
            . 'p.title AS property_title, p.location AS property_location, p.type AS property_type '
            . 'FROM `' . $unitTable . '` t '
            . 'LEFT JOIN users u ON u.id = t.user_id '
            . 'LEFT JOIN properties p ON p.id = t.property_id';
    }

    public function allTenantRegistrations(array $filters = array())
    {
        $rows = $this->tenantRegistrationRows($filters);
        $totals = $this->unitPaymentTotals();
        $registrations = array();

        foreach ($rows as $row) {
            $registrations[] = $this->hydrateTenantRegistration($row, $totals);
        }

        if (isset($filters['owing']) && $filters['owing']) {
            $registrations = array_values(array_filter($registrations, function ($registration) {
                $balances = $this->registrationBalanceBreakdown($registration);

                return (int) $balances['totalOwed'] > 0;
            }));
        }

        return $registrations;
    }

    private function tenantRegistrationRows(array $filters)
    {
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $propertyId = isset($filters['property_id']) ? (int) $filters['property_id'] : 0;
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $parts = array();
        $params = array();
        $index = 0;

        foreach ($this->registrationTables() as $table) {
            $sql = $this->unitRegistrationSelect($table) . ' WHERE t.user_id > 0';

            if ($status !== '') {
                $key = 'status_' . $index;
                $sql .= ' AND t.status = :' . $key;
                $params[$key] = $status;
            }

            if ($propertyId > 0) {
                $key = 'reg_prop_' . $index;
                $sql .= ' AND t.property_id = :' . $key;
                $params[$key] = $propertyId;
            }

            $parts[] = $sql;
            $index++;
        }

        $searchSql = '';

        if ($q !== '') {
            $searchColumns = array(
                'user_name',
                'user_email',
                'user_phone',
                'property_title',
                'property_location',
                'shop_number',
                'shop_name',
                'block',
                'floor',
                'flat_number',
                'unit_number',
            );
            $clauses = array();

            foreach ($searchColumns as $i => $column) {
                $key = 'q_' . $i;
                $clauses[] = '`' . $column . '` LIKE :' . $key;
                $params[$key] = '%' . $q . '%';
            }

            $searchSql = ' WHERE ' . implode(' OR ', $clauses);
        }

        return $this->database->fetchAll(
            'SELECT * FROM (' . implode(' UNION ALL ', $parts) . ') registrations' . $searchSql . ' ORDER BY registrations.user_name ASC, registrations.unit_id ASC',
            $params
        );
    }

    public function tenantFilterCounts(array $filters = array())
    {
        unset($filters['status'], $filters['owing']);

        $rows = $this->tenantRegistrationRows($filters);
        $totals = $this->unitPaymentTotals();
        $counts = array('all' => 0, 'active' => 0, 'inactive' => 0, 'ended' => 0, 'owing' => 0);

        foreach ($rows as $row) {
            $counts['all']++;
            $status = (string) $row['status'];

            if (isset($counts[$status])) {
                $counts[$status]++;
            }

            $registration = $this->hydrateTenantRegistration($row, $totals);

            if ((int) $this->registrationBalanceBreakdown($registration)['totalOwed'] > 0) {
                $counts['owing']++;
            }
        }

        return $counts;
    }

    public function registrationOwingBalance(array $registration)
    {
        $balances = $this->registrationBalanceBreakdown($registration);

        return max(0, (int) $balances['totalOwed']);
    }

    private function elapsedTenureMonths($startDate)
    {
        $startDate = trim((string) $startDate);
        $startTimestamp = $startDate !== '' ? @strtotime($startDate) : false;

        if ($startTimestamp === false || $startTimestamp <= 0) {
            return 0;
        }

        $nowTs = time();

        if ($nowTs < $startTimestamp) {
            return 0;
        }

        $years = (int) date('Y', $nowTs) - (int) date('Y', $startTimestamp);
        $months = (int) date('n', $nowTs) - (int) date('n', $startTimestamp);

        return max(1, $years * 12 + $months);
    }

    public function registrationAnnualRent(array $registration)
    {
        $property = isset($registration['property']) && is_array($registration['property']) ? $registration['property'] : null;

        if ($property) {
            $charges = $this->propertyCurrentCharges($property);

            if ((int) $charges['annualRent'] > 0) {
                return (int) $charges['annualRent'];
            }
        }

        return isset($registration['monthlyRent']) ? (int) ((int) $registration['monthlyRent'] * 12) : 0;
    }

    public function registrationBalanceBreakdown(array $registration)
    {
        $annualRent = $this->registrationAnnualRent($registration);
        $startDate = trim(isset($registration['startDate']) ? (string) $registration['startDate'] : '');
        $startTimestamp = $startDate !== '' ? @strtotime($startDate) : false;
        $monthsElapsed = 0;

        if ($startTimestamp !== false && $startTimestamp > 0) {
            $nowTs = time();

            if ($nowTs >= $startTimestamp) {
                $monthsElapsed = max(0, ((int) date('Y', $nowTs) - (int) date('Y', $startTimestamp)) * 12 + ((int) date('n', $nowTs) - (int) date('n', $startTimestamp)));
            }
        }

        // A tenant who signs for N years owes the rent for the whole tenure, so the years on the
        // tenancy are what is billed. Each year can still be looked at on its own in the breakdown.
        $tenureMonths = $this->registrationTenureMonths($registration);
        $tenureYears = $tenureMonths > 0 ? max(1, (int) round($tenureMonths / 12)) : 0;
        $rentYearIndex = $annualRent > 0 ? (int) floor($monthsElapsed / 12) : 0;
        $yearsBilled = $tenureYears > 0 ? $tenureYears : ($annualRent > 0 ? $rentYearIndex + 1 : 0);
        $tenureTotal = $annualRent * $yearsBilled;
        $rentYearStart = $startTimestamp === false ? false : @strtotime('+' . ($rentYearIndex * 12) . ' months', $startTimestamp);
        $rentYearEnd = $rentYearStart === false ? false : @strtotime('+12 months', $rentYearStart);

        $payments = isset($registration['payments']) && is_array($registration['payments']) ? $registration['payments'] : array();
        $tenurePaid = 0;

        foreach ($payments as $payment) {
            $paidTimestamp = ! empty($payment['createdAt']) ? @strtotime((string) $payment['createdAt']) : false;

            if ($startTimestamp !== false && $paidTimestamp !== false && $paidTimestamp < $startTimestamp) {
                continue;
            }

            if (stripos((string) $payment['description'], 'rent') === false) {
                continue;
            }

            $tenurePaid += (int) $payment['amount'];
        }

        // Payments settle the earliest rent year of the tenure first.
        $firstYearOwed = 0;
        $rentPaidThisYear = 0;
        $allocated = 0;

        for ($year = 0; $year < $yearsBilled; $year++) {
            $available = max(0, $tenurePaid - $allocated);
            $yearOwed = max(0, $annualRent - $available);

            if ($year === 0) {
                $firstYearOwed = $yearOwed;
                $rentPaidThisYear = min($available, $annualRent);
            }

            $allocated += $annualRent;
        }

        $carried = $this->carriedTenureBalance($registration);

        if ($carried === null) {
            $carried = $this->priorTenureArrears($registration);
        }

        $credit = max(0, $tenurePaid - $tenureTotal);
        $priorArrears = max(0, (int) $carried - $credit);
        $tenureOwed = max(0, ($tenureTotal - min($tenurePaid, $tenureTotal)) - max(0, $credit - max(0, (int) $carried)));

        return array(
            'annualRent' => $annualRent,
            'monthsElapsed' => $monthsElapsed,
            'rentYearIndex' => $rentYearIndex,
            'tenureMonths' => $tenureMonths > 0 ? $tenureMonths : $monthsElapsed,
            'tenureYears' => $tenureYears,
            'yearsBilled' => $yearsBilled,
            'rentYearStart' => $rentYearStart === false ? '' : date('Y-m-d', $rentYearStart),
            'rentYearEnd' => $rentYearEnd === false ? '' : date('Y-m-d', $rentYearEnd),
            'accruedRent' => $tenureTotal,
            'billedToDate' => $tenureTotal,
            'tenureTotal' => $tenureTotal,
            'tenurePaid' => $tenurePaid,
            'rentPaidThisYear' => $rentPaidThisYear,
            'firstYearOwed' => $firstYearOwed,
            'laterYearsOwed' => max(0, $tenureOwed - $firstYearOwed),
            'tenureOwed' => $tenureOwed,
            'priorArrears' => $priorArrears,
            'carriedOver' => (int) $carried,
            'totalOwed' => $tenureOwed + $priorArrears,
        );
    }

    private function carriedTenureBalance(array $registration)
    {
        $unitTable = isset($registration['unitTable']) ? (string) $registration['unitTable'] : '';
        $unitId = isset($registration['unitId']) ? (int) $registration['unitId'] : 0;
        $userId = isset($registration['userId']) ? (int) $registration['userId'] : 0;

        if ($unitTable === '' || $unitId <= 0 || $userId <= 0) {
            return null;
        }

        $row = $this->database->fetchOne(
            'SELECT balance_carried FROM tenure_history WHERE unit_table = :unit_table AND unit_id = :unit_id AND user_id = :user_id ORDER BY id DESC LIMIT 1',
            array('unit_table' => $unitTable, 'unit_id' => $unitId, 'user_id' => $userId)
        );

        if (! $row) {
            return null;
        }

        return (int) $row['balance_carried'];
    }

    public function tenureHistoryForRegistration($unitTable, $unitId)
    {
        if (! in_array($unitTable, $this->registrationTables(), true)) {
            return array();
        }

        $rows = $this->database->fetchAll(
            'SELECT * FROM tenure_history WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY id DESC',
            array('unit_table' => $unitTable, 'unit_id' => (int) $unitId)
        );

        $history = array();

        foreach ($rows as $row) {
            $history[] = array(
                'id' => (int) $row['id'],
                'tenure' => (string) $row['tenure'],
                'startDate' => (string) $row['start_date'],
                'endDate' => (string) $row['end_date'],
                'monthlyRent' => (int) $row['monthly_rent'],
                'serviceCharge' => (int) $row['service_charge'],
                'securityDeposit' => (int) $row['security_deposit'],
                'amountDue' => (int) $row['amount_due'],
                'amountPaid' => (int) $row['amount_paid'],
                'balanceCarried' => (int) $row['balance_carried'],
                'status' => (string) $row['status'],
                'notes' => isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : '',
                'closedAt' => isset($row['closed_at']) ? (string) $row['closed_at'] : '',
                'createdAt' => (string) $row['created_at'],
            );
        }

        return $history;
    }

    private function priorTenureArrears(array $registration)
    {
        $unitTable = isset($registration['unitTable']) ? (string) $registration['unitTable'] : '';
        $unitId = isset($registration['unitId']) ? (int) $registration['unitId'] : 0;
        $userId = isset($registration['userId']) ? (int) $registration['userId'] : 0;

        if ($unitTable === '' || $unitId <= 0 || $userId <= 0) {
            return 0;
        }

        $rows = $this->database->fetchAll(
            'SELECT * FROM unit_history WHERE unit_table = :unit_table AND unit_id = :unit_id AND user_id = :user_id AND occupancy_status <> :active ORDER BY start_date ASC, id ASC',
            array('unit_table' => $unitTable, 'unit_id' => $unitId, 'user_id' => $userId, 'active' => 'active')
        );

        if ($rows === array()) {
            return 0;
        }

        $payments = isset($registration['payments']) && is_array($registration['payments']) ? $registration['payments'] : array();
        $arrears = 0;

        foreach ($rows as $row) {
            $months = $this->tenureMonths(isset($row['tenure']) ? (string) $row['tenure'] : '');
            $rent = (int) $row['monthly_rent'];
            $due = $months > 0 ? $months * $rent : $rent;
            $windowStart = ! empty($row['start_date']) ? @strtotime((string) $row['start_date']) : false;
            $windowEnd = ! empty($row['end_date'])
                ? @strtotime((string) $row['end_date'])
                : (! empty($row['ended_at']) ? @strtotime((string) $row['ended_at']) : false);
            $paid = 0;

            foreach ($payments as $payment) {
                $paidTimestamp = ! empty($payment['createdAt']) ? @strtotime((string) $payment['createdAt']) : false;

                if ($paidTimestamp === false) {
                    continue;
                }

                if (stripos((string) $payment['description'], 'rent') === false) {
                    continue;
                }

                if ($windowStart !== false && $paidTimestamp < $windowStart) {
                    continue;
                }

                if ($windowEnd !== false && $paidTimestamp > $windowEnd) {
                    continue;
                }

                $paid += (int) $payment['amount'];
            }

            $balance = $due - $paid;

            if ($balance > 0) {
                $arrears += $balance;
            }
        }

        return $arrears;
    }

    public function tenantRegistrationCounts()
    {
        $counts = array();
        $index = 0;
        $parts = array();
        $params = array();

        foreach ($this->registrationTables() as $table) {
            $parts[] = $this->unitRegistrationSelect($table) . ' WHERE t.user_id > 0';
            $index++;
        }

        $rows = $this->database->fetchAll(
            'SELECT u.property_id, COUNT(*) AS cnt FROM (' . implode(' UNION ALL ', $parts) . ') u GROUP BY u.property_id',
            $params
        );

        foreach ($rows as $row) {
            $counts[(int) $row['property_id']] = (int) $row['cnt'];
        }

        return $counts;
    }

    public function findTenantRegistration($unitTable, $unitId)
    {
        if (! in_array($unitTable, $this->registrationTables(), true)) {
            return null;
        }

        $row = $this->database->fetchOne(
            $this->unitRegistrationSelect($unitTable) . ' WHERE t.id = :id AND t.user_id > 0 LIMIT 1',
            array('id' => (int) $unitId)
        );

        if (! $row) {
            return null;
        }

        return $this->hydrateTenantRegistration($row, $this->unitPaymentTotals());
    }

    private function unitPaymentTotals()
    {
        $rows = $this->database->fetchAll(
            'SELECT property_id, unit_id, SUM(amount) AS total FROM payments WHERE unit_id > 0 GROUP BY property_id, unit_id'
        );
        $map = array();

        foreach ($rows as $row) {
            $map[(int) $row['property_id'] . ':' . (int) $row['unit_id']] = (int) $row['total'];
        }

        return $map;
    }

    private function hydrateTenantRegistration(array $row, array $totals)
    {
        $propertyId = (int) $row['property_id'];
        $unitId = (int) $row['unit_id'];

        return array(
            'unitTable' => (string) $row['unit_table'],
            'unitId' => $unitId,
            'propertyId' => $propertyId,
            'userId' => (int) $row['user_id'],
            'shopNumber' => (string) $row['shop_number'],
            'shopName' => (string) $row['shop_name'],
            'block' => (string) $row['block'],
            'floor' => (string) $row['floor'],
            'flatNumber' => (string) $row['flat_number'],
            'unitNumber' => (string) $row['unit_number'],
            'tenure' => (string) $row['tenure'],
            'startDate' => (string) $row['start_date'],
            'endDate' => (string) $row['end_date'],
            'monthlyRent' => (int) $row['monthly_rent'],
            'serviceCharge' => (int) $row['service_charge'],
            'securityDeposit' => (int) $row['security_deposit'],
            'legalFee' => isset($row['legal_fee']) ? (int) $row['legal_fee'] : 0,
            'vatFee' => isset($row['vat_fee']) ? (int) $row['vat_fee'] : 0,
            'subscriptionForm' => isset($row['subscription_form']) ? (int) $row['subscription_form'] : 0,
            'toiletFee' => isset($row['toilet_fee']) ? (int) $row['toilet_fee'] : 0,
            'status' => (string) $row['status'],
            'notes' => isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : '',
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => isset($row['updated_at']) ? (string) $row['updated_at'] : (string) $row['created_at'],
            'user' => array(
                'id' => (int) $row['user_id'],
                'role' => (string) $row['user_role'],
                'name' => (string) $row['user_name'],
                'email' => (string) $row['user_email'],
                'phone' => (string) $row['user_phone'],
            ),
            'property' => $this->findProperty($propertyId),
            'payments' => $this->paymentsForUnit($propertyId, $unitId),
            'totalPaid' => isset($totals[$propertyId . ':' . $unitId]) ? $totals[$propertyId . ':' . $unitId] : 0,
        );
    }

    public function paymentsForUnit($propertyId, $unitId)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM payments WHERE property_id = :property_id AND unit_id = :unit_id ORDER BY created_at DESC, id DESC',
            array('property_id' => (int) $propertyId, 'unit_id' => (int) $unitId)
        );
        $payments = array();

        foreach ($rows as $row) {
            $payments[] = array(
                'id' => (int) $row['id'],
                'userId' => (int) $row['user_id'],
                'propertyId' => (int) $row['property_id'],
                'unitId' => (int) $row['unit_id'],
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

    public function saveTenantRegistrationByAdmin($unitTable, $unitId, array $payload, $adminUser, array $files = array())
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';

        if ($unitId > 0 && $unitTable === '') {
            return array(false, 'That tenant registration could not be located.');
        }

        $unitId = (int) $unitId;

        $name = trim(isset($payload['tenant_name']) ? $payload['tenant_name'] : '');
        $email = strtolower(trim(isset($payload['tenant_email']) ? $payload['tenant_email'] : ''));
        $phone = trim(isset($payload['tenant_phone']) ? $payload['tenant_phone'] : '');

        if ($name === '' || $email === '' || $phone === '') {
            return array(false, 'Complete the tenant full name, email, and phone fields.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return array(false, 'Enter a valid email address for the tenant.');
        }

        $phone = $this->normalizeNigerianPhone($phone);

        if ($phone === '') {
            return array(false, 'Enter a valid phone number, e.g. 08023434534.');
        }

        $propertyId = (int) $this->normalizeInteger(isset($payload['property_id']) ? $payload['property_id'] : 0);
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'Choose the property the tenant is renting.');
        }

        $targetTable = $this->unitTableForProperty($property);
        $current = null;

        if ($unitId > 0) {
            $current = $this->findTenantRegistration($unitTable, $unitId);

            if (! $current) {
                return array(false, 'That tenant registration could not be found.');
            }
        }

        $status = trim(isset($payload['status']) ? $payload['status'] : 'active');
        if (! in_array($status, array('active', 'inactive', 'ended'), true)) {
            $status = 'active';
        }

        $tenure = trim(isset($payload['tenure']) ? $payload['tenure'] : '');
        $startDate = trim(isset($payload['start_date']) ? $payload['start_date'] : '');
        $endDate = trim(isset($payload['end_date']) ? $payload['end_date'] : '');
        $charges = $this->propertyCurrentCharges($property);
        $yearlyRent = $charges['annualRent'] > 0
            ? (int) $charges['annualRent']
            : $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        $rentPaidAtRegistration = $this->extractAmount(isset($payload['rent_paid_at_registration']) ? $payload['rent_paid_at_registration'] : 0);

        if ($yearlyRent <= 0) {
            $yearlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        }

        $monthlyRent = $yearlyRent > 0 ? (int) round($yearlyRent / 12) : 0;

        $serviceChargeRaw = isset($payload['service_charge']) ? (string) $payload['service_charge'] : '';
        $serviceCharge = trim($serviceChargeRaw) !== '' ? $this->extractAmount($serviceChargeRaw) : $charges['serviceCharge'];
        $securityDeposit = $this->extractAmount(isset($payload['security_deposit']) ? $payload['security_deposit'] : 0);
        $legalFee = $this->extractAmount(isset($payload['legal_fee']) ? $payload['legal_fee'] : 0);
        $vatFee = $this->extractAmount(isset($payload['vat_fee']) ? $payload['vat_fee'] : 0);
        $subscriptionForm = $this->extractAmount(isset($payload['subscription_form']) ? $payload['subscription_form'] : 0);
        $toiletFee = $this->extractAmount(isset($payload['toilet_fee']) ? $payload['toilet_fee'] : 0);

        // The toilet charge only applies to the mall units, so it is ignored elsewhere.
        if ($targetTable !== 'mall_shops') {
            $toiletFee = 0;
        }

        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');

        if ($tenure === '') {
            return array(false, 'Enter the number of years for this tenant.');
        }

        if ($startDate === '') {
            return array(false, 'Enter the tenancy start date.');
        }

        if ($endDate === '') {
            return array(false, 'Enter the tenancy end date.');
        }

        if (strtotime($endDate) <= strtotime($startDate)) {
            return array(false, 'The tenancy end date must be after the start date.');
        }

        if ($monthlyRent <= 0) {
            return array(false, 'Enter a valid yearly rent amount. It can be auto-filled from the property on the register form.');
        }

        if ($serviceCharge < 0) {
            return array(false, 'Service charge cannot be negative.');
        }

        if ($securityDeposit < 0) {
            return array(false, 'Caution deposit cannot be negative.');
        }

        if ($legalFee < 0 || $vatFee < 0 || $subscriptionForm < 0 || $toiletFee < 0) {
            return array(false, 'Legal fee, VAT, subscription form, and toilet charges cannot be negative.');
        }

        if ($targetTable === 'mall_shops') {
            $shopNumber = trim(isset($payload['shop_number']) ? $payload['shop_number'] : '');
            $shopName = trim(isset($payload['shop_name']) ? $payload['shop_name'] : '');
            if ($shopNumber === '') {
                return array(false, 'Enter the shop number.');
            }
            if ($shopName === '') {
                return array(false, 'Enter the shop name.');
            }
        } elseif ($targetTable === 'apartment_flats') {
            $block = trim(isset($payload['block']) ? $payload['block'] : '');
            $floor = trim(isset($payload['floor']) ? $payload['floor'] : '');
            $flatNumber = trim(isset($payload['flat_number']) ? $payload['flat_number'] : '');
            if ($block === '') {
                return array(false, 'Enter the block name.');
            }
            if ($floor === '') {
                return array(false, 'Enter the floor number.');
            }
            if ($flatNumber === '') {
                return array(false, 'Enter the flat number.');
            }
        } else {
            $block = trim(isset($payload['block']) ? $payload['block'] : '');
            $unitNumber = trim(isset($payload['unit_number']) ? $payload['unit_number'] : '');
            if ($block === '') {
                return array(false, 'Enter the block name.');
            }
            if ($unitNumber === '') {
                return array(false, 'Enter the unit number.');
            }
        }

        $excludeUnitId = $current ? (int) $current['unitId'] : 0;

        if ($targetTable === 'mall_shops') {
            $shopNumber = trim(isset($payload['shop_number']) ? $payload['shop_number'] : '');
            $conflictId = $this->occupiedIdentifierConflict(
                $targetTable,
                $propertyId,
                array('shop_number' => $shopNumber),
                $excludeUnitId
            );

            if ($conflictId > 0) {
                return array(false, 'A tenant already occupies shop number "' . $shopNumber . '" in this property. Choose a different shop number.');
            }
        } elseif ($targetTable === 'apartment_flats') {
            $block = trim(isset($payload['block']) ? $payload['block'] : '');
            $flatNumber = trim(isset($payload['flat_number']) ? $payload['flat_number'] : '');
            $conflictId = $this->occupiedIdentifierConflict(
                $targetTable,
                $propertyId,
                array('block' => $block, 'flat_number' => $flatNumber),
                $excludeUnitId
            );

            if ($conflictId > 0) {
                return array(false, 'A tenant already occupies flat "' . $block . ' ' . $flatNumber . '" in this property. Choose a different flat number.');
            }
        } else {
            $block = trim(isset($payload['block']) ? $payload['block'] : '');
            $unitNumber = trim(isset($payload['unit_number']) ? $payload['unit_number'] : '');
            $conflictId = $this->occupiedIdentifierConflict(
                $targetTable,
                $propertyId,
                array('block' => $block, 'unit_number' => $unitNumber),
                $excludeUnitId
            );

            if ($conflictId > 0) {
                return array(false, 'A tenant already occupies unit "' . $block . ' ' . $unitNumber . '" in this property. Choose a different unit number.');
            }
        }

        $existingUser = $this->findUserByEmail($email);
        $temporaryPassword = '';

        if ($current) {
            $userId = (int) $current['userId'];

            if ($existingUser && (int) $existingUser['id'] !== $userId) {
                return array(false, 'That email address is already used by another tenant account.');
            }
        } elseif ($existingUser) {
            if (strtolower($existingUser['role']) === 'admin') {
                return array(false, 'That email address belongs to an admin account.');
            }

            $userId = (int) $existingUser['id'];
        } else {
            $temporaryPassword = $this->generateTemporaryPassword();
            $userId = $this->database->insert('users', array(
                'role' => 'user',
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
                'created_at' => $this->now(),
            ));
        }

        $this->database->execute(
            'UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id',
            array('name' => $name, 'email' => $email, 'phone' => $phone, 'id' => $userId)
        );

        $now = $this->now();

        if ($securityDeposit <= 0) {
            $securityDeposit = $this->normalizeCautionDeposit((string) $property['purpose'], $securityDeposit);
        }

        $data = array(
            'user_id' => $userId,
            'status' => $status,
            'tenure' => $tenure,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => $monthlyRent,
            'service_charge' => $serviceCharge,
            'security_deposit' => $securityDeposit,
            'legal_fee' => $legalFee,
            'vat_fee' => $vatFee,
            'subscription_form' => $subscriptionForm,
            'toilet_fee' => $toiletFee,
            'notes' => $notes !== '' ? $notes : null,
            'updated_at' => $now,
        );

        if ($targetTable === 'mall_shops') {
            $data['shop_number'] = trim(isset($payload['shop_number']) ? $payload['shop_number'] : '');
            $data['shop_name'] = trim(isset($payload['shop_name']) ? $payload['shop_name'] : '');
        } elseif ($targetTable === 'apartment_flats') {
            $data['block'] = trim(isset($payload['block']) ? $payload['block'] : '');
            $data['floor'] = trim(isset($payload['floor']) ? $payload['floor'] : '');
            $data['flat_number'] = trim(isset($payload['flat_number']) ? $payload['flat_number'] : '');
        } else {
            $data['block'] = trim(isset($payload['block']) ? $payload['block'] : '');
            $data['unit_number'] = trim(isset($payload['unit_number']) ? $payload['unit_number'] : '');
        }

        if ($current) {
            if ((string) $current['unitTable'] === $targetTable) {
                $updateData = $data;
                $updateData['property_id'] = $propertyId;

                $this->database->execute(
                    'UPDATE `' . $targetTable . '` SET ' . $this->buildUnitUpdateSet($updateData) . ' WHERE id = :id AND property_id = :old_property_id',
                    array_merge($updateData, array('id' => (int) $current['unitId'], 'old_property_id' => (int) $current['propertyId']))
                );

                if ($propertyId !== (int) $current['propertyId']) {
                    $this->database->execute(
                        'UPDATE payments SET property_id = :property_id WHERE property_id = :old_property_id AND unit_id = :unit_id',
                        array(
                            'property_id' => $propertyId,
                            'old_property_id' => (int) $current['propertyId'],
                            'unit_id' => (int) $current['unitId'],
                        )
                    );

                    $this->database->execute(
                        'UPDATE service_charge_allocations SET property_id = :property_id WHERE property_id = :old_property_id AND unit_table = :unit_table AND unit_id = :unit_id',
                        array(
                            'property_id' => $propertyId,
                            'old_property_id' => (int) $current['propertyId'],
                            'unit_table' => (string) $current['unitTable'],
                            'unit_id' => (int) $current['unitId'],
                        )
                    );
                }

                $newId = (int) $current['unitId'];
            } else {
                $insertData = $data;
                $insertData['property_id'] = $propertyId;
                $insertData['created_at'] = $now;
                $newId = $this->database->insert($targetTable, $insertData);

                $this->database->execute(
                    'UPDATE payments SET property_id = :property_id, unit_id = :unit_id WHERE property_id = :old_property_id AND unit_id = :old_unit_id',
                    array(
                        'property_id' => $propertyId,
                        'unit_id' => $newId,
                        'old_property_id' => (int) $current['propertyId'],
                        'old_unit_id' => (int) $current['unitId'],
                    )
                );

                $this->database->execute(
                    'UPDATE service_charge_allocations SET property_id = :property_id, unit_table = :unit_table, unit_id = :unit_id WHERE property_id = :old_property_id AND unit_table = :old_unit_table AND unit_id = :old_unit_id',
                    array(
                        'property_id' => $propertyId,
                        'unit_table' => $targetTable,
                        'unit_id' => $newId,
                        'old_property_id' => (int) $current['propertyId'],
                        'old_unit_table' => (string) $current['unitTable'],
                        'old_unit_id' => (int) $current['unitId'],
                    )
                );

                $this->syncUnitHistory((string) $current['unitTable'], array_merge(
                    $this->buildNormalizedUnitFromRegistration($current),
                    array('status' => 'ended')
                ));

                $this->deleteTenancyDocumentsForRegistration((string) $current['unitTable'], (int) $current['unitId']);

                $this->database->execute(
                    'DELETE FROM `' . (string) $current['unitTable'] . '` WHERE id = :id',
                    array('id' => (int) $current['unitId'])
                );
            }
        } else {
            $data['property_id'] = $propertyId;
            $data['created_at'] = $now;
            $newId = $this->database->insert($targetTable, $data);
        }

        $registration = $this->findTenantRegistration($targetTable, $newId);

        if ($registration) {
            $this->syncUnitHistory($targetTable, $this->buildNormalizedUnitFromRegistration($registration));

            if ($rentPaidAtRegistration > 0 && ! $current) {
                $this->database->insert('payments', array(
                    'user_id' => (int) $registration['userId'],
                    'property_id' => (int) $registration['propertyId'],
                    'unit_id' => (int) $registration['unitId'],
                    'application_id' => 0,
                    'tenancy_id' => 0,
                    'amount' => $rentPaidAtRegistration,
                    'channel' => 'cash',
                    'card_last4' => '',
                    'reference' => $this->paymentReference('reg' . $registration['unitId'] . $now . mt_rand(1000, 9999)),
                    'description' => 'Rent payment recorded at registration',
                    'created_at' => $now,
                ));

                $registration = $this->findTenantRegistration($targetTable, $newId);
            }

            // Each document kind replaces only its own previous file, so uploading the key
            // collection form never discards the tenancy agreement and the other way round.
            foreach (array('tenancy_agreement' => 'tenancy_agreement', 'key_collection_form' => 'key_collection_form') as $fileKey => $documentType) {
                if (! isset($files[$fileKey])) {
                    continue;
                }

                $this->deleteTenancyDocumentsForRegistration($registration['unitTable'], $registration['unitId'], $documentType);

                list($documentStored, $documentMessage) = $this->storeTenancyDocument(
                    $files[$fileKey],
                    $registration['unitTable'],
                    $registration['propertyId'],
                    $registration['unitId'],
                    $registration['userId'],
                    $documentType
                );

                // The second slot carries the stored path on success, so only surface it as an
                // error when the upload was actually rejected.
                if (! $documentStored && $documentMessage !== '') {
                    $registration['documentError'] = $documentMessage;
                }
            }

            if (! $current) {
                $registration['temporaryPassword'] = $temporaryPassword;
                $this->queueTenantRegistrationEmail($registration);
            }
        }

        return array($registration, $registration ? null : 'The tenant could not be saved.');
    }

    public function normalizeNigerianPhone($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11 && $digits[0] === '0') {
            return '+234' . substr($digits, 1);
        }

        if (strlen($digits) === 13 && substr($digits, 0, 3) === '234') {
            return '+' . $digits;
        }

        return '';
    }

    public function generateTemporaryPassword()
    {
        $consonants = 'bcdfghjkmnpqrstvwxyz';
        $vowels = 'aeiou';
        $digits = '23456789';
        $password = '';

        for ($i = 0; $i < 4; $i += 1) {
            $password .= $consonants[random_int(0, strlen($consonants) - 1)]
                . $vowels[random_int(0, strlen($vowels) - 1)];
        }

        $password .= $digits[random_int(0, strlen($digits) - 1)] . $digits[random_int(0, strlen($digits) - 1)];

        return $password;
    }

    public function queueTenantRegistrationEmail(array $registration)
    {
        if (! isset($registration['user']) || ! isset($registration['unitTable']) || ! isset($registration['unitId'])) {
            return array(false, 'That tenant registration could not be prepared for email.');
        }

        $toEmail = trim((string) $registration['user']['email']);
        $toName = trim((string) $registration['user']['name']);
        $temporaryPassword = isset($registration['temporaryPassword']) ? (string) $registration['temporaryPassword'] : '';

        if ($toEmail === '' || filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            return array(false, 'The tenant does not have a valid email address on file.');
        }

        $unit = $this->buildNormalizedUnitFromRegistration($registration);
        $unitLabel = $this->unitDisplayLabel($registration['unitTable'], $unit);
        $property = isset($registration['property']) ? $registration['property'] : $this->findProperty((int) $registration['propertyId']);
        $propertyTitle = $property ? (string) $property['title'] : 'the property';
        $subject = 'Your tenancy at ' . $propertyTitle . ' is confirmed';

        $bodyHtml = '<div style="font-family:Arial,Helvetica,sans-serif; line-height:1.6; color:#333;">'
            . '<h2 style="color:#082c61; margin-bottom:8px;">Tenancy registration confirmed</h2>'
            . '<p>Dear ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Your tenancy with Sandworth Homes has been registered. Your tenancy details are below.</p>'
            . '<table style="width:100%; max-width:600px; border-collapse:collapse; margin:16px 0;">'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa; width:180px;">Property</td><td style="padding:8px;">' . htmlspecialchars($propertyTitle, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Unit / shop</td><td style="padding:8px;">' . htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Tenancy period</td><td style="padding:8px;">' . htmlspecialchars($this->tenurePeriodLabel($registration['startDate'], $registration['endDate']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Monthly rent</td><td style="padding:8px;">' . htmlspecialchars(app_currency($registration['monthlyRent']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Yearly rent</td><td style="padding:8px;">' . htmlspecialchars(app_currency($this->registrationAnnualRent($registration)), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Service charge</td><td style="padding:8px;">' . htmlspecialchars(app_currency($registration['serviceCharge']), ENT_QUOTES, 'UTF-8') . '/month</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Caution deposit</td><td style="padding:8px;">' . htmlspecialchars(app_currency($registration['securityDeposit']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>';

        // Everything else charged on this registration, so the tenant sees the full bill and not
        // just the rent and deposit. Zero amounts are left out because nothing was charged for them.
        $extraCharges = array(
            'Legal fee' => (int) $registration['legalFee'],
            'VAT' => (int) $registration['vatFee'],
            'Subscription form' => (int) $registration['subscriptionForm'],
            'Toilet charge' => (int) $registration['toiletFee'],
        );

        $chargeLines = '';

        foreach ($extraCharges as $chargeLabel => $chargeAmount) {
            if ($chargeAmount <= 0) {
                continue;
            }

            $chargeLines .= '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa; width:180px;">'
                . htmlspecialchars($chargeLabel, ENT_QUOTES, 'UTF-8')
                . '</td><td style="padding:8px;">'
                . htmlspecialchars(app_currency($chargeAmount), ENT_QUOTES, 'UTF-8')
                . '</td></tr>';
        }

        if ($chargeLines !== '') {
            $bodyHtml .= '<p style="margin:16px 0 0;">The following charges were also added to this registration:</p>'
                . '<table style="width:100%; max-width:600px; border-collapse:collapse; margin:8px 0 16px;">'
                . $chargeLines
                . '</table>';
        }

        // The rent owed for the whole signed period and what has been paid towards it. Rent paid is
        // summed straight from the payments on this unit because a registration payment made
        // before a future tenancy start date is still money the tenant handed over.
        $balances = $this->registrationBalanceBreakdown($registration);
        $termTotal = (int) $balances['tenureTotal'];
        $termYears = (int) $balances['tenureYears'];
        $rentPaid = 0;

        foreach ((array) $registration['payments'] as $payment) {
            if (stripos((string) $payment['description'], 'rent') !== false) {
                $rentPaid += (int) $payment['amount'];
            }
        }

        $rentOwed = max(0, $termTotal - $rentPaid);
        $rentYearsLabel = $termYears > 0
            ? (string) $termYears . ' year' . ($termYears === 1 ? '' : 's')
            : 'term';

        $bodyHtml .= '<table style="width:100%; max-width:600px; border-collapse:collapse; margin:0 0 16px;">'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa; width:180px;">Rent for the ' . htmlspecialchars($rentYearsLabel, ENT_QUOTES, 'UTF-8') . '</td><td style="padding:8px;">' . htmlspecialchars(app_currency($termTotal), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Rent paid</td><td style="padding:8px;">' . htmlspecialchars(app_currency($rentPaid), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Rent still payable</td><td style="padding:8px;">' . htmlspecialchars(app_currency($rentOwed), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>'
            . ($temporaryPassword !== ''
                ? '<p style="margin-top:12px; padding:12px; border:1px dashed #c4ccdb; border-radius:6px; background:#f8fafc;"><strong style="color:#082c61;">Your sign-in password</strong><br>Email: ' . htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8') . '<br>Temporary password: <strong>' . htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8') . '</strong><br><span style="font-size:.85rem; color:#5b6472;">Use these to sign in to your tenant dashboard, then change your password after your first login.</span></p>'
                : '')
            . '<p>Trouble viewing this? Visit your tenant dashboard for the latest statements and receipts.</p>'
            . '<p style="margin-top:24px;">Sandworth Homes Property Management<br>Mon - Sat, 8 am - 6 pm</p>'
            . '</div>';

        $emailId = $this->database->insert('email_logs', array(
            'recipient_email' => $toEmail,
            'recipient_name' => $toName,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'status' => 'pending',
            'error_message' => '',
            'context' => 'tenant-registration',
            'context_id' => (int) $registration['unitId'],
            'created_at' => $this->now(),
            'sent_at' => null,
        ));

        return array((int) $emailId, null);
    }

    public function flushQueuedTenantRegistrationEmails()
    {
        $emailRows = $this->database->fetchAll(
            "SELECT * FROM email_logs WHERE status = 'pending' AND context = 'tenant-registration' ORDER BY id ASC LIMIT 50"
        );

        $mailer = Mailer::fromConfig();
        $smtpEnabled = $mailer->enabled();
        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($emailRows as $emailRow) {
            if (! $smtpEnabled) {
                $skippedCount++;
                continue;
            }

            $subject = (string) $emailRow['subject'];
            $bodyHtml = (string) $emailRow['body_html'];
            $toEmail = (string) $emailRow['recipient_email'];
            $toName = (string) $emailRow['recipient_name'];

            list($sent, $error) = $mailer->send($toEmail, $toName, $subject, $bodyHtml);

            $this->database->execute(
                'UPDATE email_logs SET status = :status, error_message = :error_message, sent_at = :sent_at WHERE id = :id',
                array(
                    'status' => $sent ? 'sent' : 'failed',
                    'error_message' => $error !== '' ? substr($error, 0, 255) : '',
                    'sent_at' => $sent ? $this->now() : null,
                    'id' => (int) $emailRow['id'],
                )
            );

            if ($sent) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return array(
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'smtpEnabled' => $smtpEnabled,
            'remaining' => $this->pendingTenantRegistrationEmailCount(),
        );
    }

    private function pendingTenantRegistrationEmailCount()
    {
        return (int) $this->database->fetchValue(
            "SELECT COUNT(*) FROM email_logs WHERE status = 'pending' AND context = 'tenant-registration'"
        );
    }

    private function occupiedIdentifierConflict($table, $propertyId, array $criteria, $excludeUnitId)
    {
        $table = in_array($table, $this->registrationTables(), true) ? $table : '';

        if ($table === '' || $criteria === array()) {
            return 0;
        }

        $conditions = array('property_id = :property_id', 'user_id > 0', 'status <> \'ended\'', 'id <> :exclude_id');
        $params = array('property_id' => (int) $propertyId, 'exclude_id' => (int) $excludeUnitId);

        foreach ($criteria as $column => $value) {
            $safeColumn = preg_replace('/[^a-z_]/', '', (string) $column);
            $safeColumn = ltrim($safeColumn, '_');

            if ($safeColumn === '') {
                continue;
            }

            $key = 'c_' . $safeColumn;
            $conditions[] = '`' . $safeColumn . '` = :' . $key;
            $params[$key] = (string) $value;
        }

        return (int) $this->database->fetchValue(
            'SELECT id FROM `' . $table . '` WHERE ' . implode(' AND ', $conditions) . ' LIMIT 1',
            $params
        );
    }

    public function storeTenancyDocument($file, $unitTable, $propertyId, $unitId, $userId, $documentType = 'tenancy_agreement')
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';
        $documentType = (string) $documentType === 'key_collection_form' ? 'key_collection_form' : 'tenancy_agreement';
        $label = $documentType === 'key_collection_form' ? 'The key collection form' : 'The tenancy agreement';

        if (! isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return array(false, '');
        }

        $uploadError = (int) $file['error'];

        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            return array(false, $label . ' exceeds the 5 MB upload limit.');
        }

        if ($uploadError !== UPLOAD_ERR_OK || ! isset($file['tmp_name']) || trim((string) $file['tmp_name']) === '') {
            return array(false, $label . ' could not be read from the upload.');
        }

        if (isset($file['size']) && (int) $file['size'] > 5 * 1024 * 1024) {
            return array(false, $label . ' exceeds the 5 MB upload limit.');
        }

        $originalName = trim((string) $file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');

        if (! in_array($extension, $allowed, true)) {
            return array(false, 'Upload ' . strtolower($label) . ' as a PDF, Word document, or image.');
        }

        $directoryPath = $this->projectRoot
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'tenant-documents';

        if (! is_dir($directoryPath) && ! mkdir($directoryPath, 0777, true)) {
            return array(false, $label . ' folder could not be created.');
        }

        $safeTable = preg_replace('/[^a-z0-9_]/i', '', (string) $unitTable);
        $userRow = $this->database->fetchOne('SELECT name FROM users WHERE id = :id LIMIT 1', array('id' => (int) $userId));
        $nameSlug = $userRow ? $this->slug((string) $userRow['name']) : 'tenant';
        $slug = $documentType === 'key_collection_form' ? 'key-collection' : 'tenancy';
        $filename = $slug . '-' . $nameSlug . '-' . $safeTable . '-' . (int) $unitId . '-' . time() . '-' . mt_rand(1000, 9999) . '.' . $extension;
        $targetPath = $directoryPath . DIRECTORY_SEPARATOR . $filename;

        if (! move_uploaded_file($file['tmp_name'], $targetPath)) {
            return array(false, $label . ' could not be stored.');
        }

        $this->database->insert('tenancy_documents', array(
            'unit_table' => $unitTable,
            'unit_id' => (int) $unitId,
            'property_id' => (int) $propertyId,
            'user_id' => (int) $userId,
            'document_type' => $documentType,
            'original_name' => basename($originalName),
            'file_path' => '/public/assets/uploads/tenant-documents/' . $filename,
            'file_size' => isset($file['size']) ? (int) $file['size'] : 0,
            'mime_type' => isset($file['type']) ? (string) $file['type'] : '',
            'created_at' => $this->now(),
        ));

        return array(true, '/public/assets/uploads/tenant-documents/' . $filename);
    }

    public function findTenancyDocumentsForRegistration($unitTable, $unitId, $documentType = '')
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';
        $unitId = (int) $unitId;

        if ($unitTable === '' || $unitId <= 0) {
            return array();
        }

        $rows = $documentType !== ''
            ? $this->database->fetchAll(
                'SELECT * FROM tenancy_documents WHERE unit_table = :unit_table AND unit_id = :unit_id AND document_type = :document_type ORDER BY created_at DESC, id DESC',
                array('unit_table' => $unitTable, 'unit_id' => $unitId, 'document_type' => (string) $documentType)
            )
            : $this->database->fetchAll(
                'SELECT * FROM tenancy_documents WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY created_at DESC, id DESC',
                array('unit_table' => $unitTable, 'unit_id' => $unitId)
            );

        $documents = array();

        foreach ($rows as $row) {
            $documents[] = array(
                'id' => (int) $row['id'],
                'documentType' => (string) $row['document_type'],
                'originalName' => (string) $row['original_name'],
                'filePath' => (string) $row['file_path'],
                'fileSize' => (int) $row['file_size'],
                'mimeType' => (string) $row['mime_type'],
                'createdAt' => (string) $row['created_at'],
            );
        }

        return $documents;
    }

    public function deleteTenancyDocumentRow(array $document)
    {
        $id = (int) (isset($document['id']) ? $document['id'] : 0);

        if ($id <= 0) {
            return;
        }

        $filePath = trim((string) (isset($document['filePath']) ? $document['filePath'] : ''));

        $this->database->execute('DELETE FROM tenancy_documents WHERE id = :id', array('id' => $id));

        if ($filePath !== '') {
            $absolute = $this->projectRoot
                . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, ltrim($filePath, '/'));

            if (is_file($absolute)) {
                unlink($absolute);
            }
        }
    }

    public function deleteTenancyDocumentsForRegistration($unitTable, $unitId, $documentType = '')
    {
        foreach ($this->findTenancyDocumentsForRegistration($unitTable, $unitId, $documentType) as $document) {
            $this->deleteTenancyDocumentRow($document);
        }
    }

    public function renewTenantTenureByAdmin($unitTable, $unitId, array $payload, $adminUser)
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';
        $unitId = (int) $unitId;

        if ($unitTable === '' || $unitId <= 0) {
            return array(false, 'That tenant registration could not be located.');
        }

        $registration = $this->findTenantRegistration($unitTable, $unitId);

        if (! $registration) {
            return array(false, 'That tenant registration could not be located.');
        }

        $tenure = trim(isset($payload['tenure']) ? $payload['tenure'] : '');
        $startDate = trim(isset($payload['start_date']) ? $payload['start_date'] : '');
        $endDate = trim(isset($payload['end_date']) ? $payload['end_date'] : '');
        $charges = $this->propertyCurrentCharges($registration['property']);
        $yearlyRent = $charges['annualRent'] > 0
            ? (int) $charges['annualRent']
            : $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);

        if ($yearlyRent <= 0) {
            $yearlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        }

        $monthlyRent = $yearlyRent > 0 ? (int) round($yearlyRent / 12) : 0;

        $serviceChargeRaw = isset($payload['service_charge']) ? (string) $payload['service_charge'] : '';
        $serviceCharge = trim($serviceChargeRaw) !== '' ? $this->extractAmount($serviceChargeRaw) : $charges['serviceCharge'];
        $securityDeposit = isset($payload['security_deposit']) && trim((string) $payload['security_deposit']) !== ''
            ? $this->extractAmount($payload['security_deposit'])
            : (int) $registration['securityDeposit'];

        $rentPaidAtRenewal = $this->extractAmount(isset($payload['rent_paid_at_renewal']) ? $payload['rent_paid_at_renewal'] : 0);
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');

        if ($tenure === '') {
            return array(false, 'Enter the number of years for the renewal.');
        }

        if ($startDate === '') {
            return array(false, 'Enter the start date for the new period.');
        }

        if ($endDate === '') {
            return array(false, 'Enter the end date for the new period.');
        }

        if (strtotime($endDate) <= strtotime($startDate)) {
            return array(false, 'The end date must be after the start date.');
        }

        if ($monthlyRent <= 0) {
            return array(false, 'Enter a valid yearly rent for the new period.');
        }

        if ($serviceCharge < 0) {
            return array(false, 'Service charge cannot be negative.');
        }

        if ($securityDeposit < 0) {
            return array(false, 'Caution deposit cannot be negative.');
        }

        $breakdown = $this->registrationBalanceBreakdown($registration);
        $carryOver = (int) $breakdown['totalOwed'];
        $now = $this->now();
        $unit = $this->buildNormalizedUnitFromRegistration($registration);

        $this->database->insert('tenure_history', array(
            'property_id' => (int) $registration['propertyId'],
            'unit_table' => $unitTable,
            'unit_id' => $unitId,
            'unit_label' => $this->unitDisplayLabel($unitTable, $unit),
            'user_id' => (int) $registration['userId'],
            'user_name' => (string) $registration['user']['name'],
            'user_email' => (string) $registration['user']['email'],
            'user_phone' => (string) $registration['user']['phone'],
            'tenure' => (string) $registration['tenure'],
            'monthly_rent' => (int) $registration['monthlyRent'],
            'service_charge' => (int) $registration['serviceCharge'],
            'security_deposit' => (int) $registration['securityDeposit'],
            'start_date' => (string) $registration['startDate'],
            'end_date' => (string) $registration['endDate'],
            'amount_due' => (int) $breakdown['accruedRent'],
            'amount_paid' => (int) $breakdown['tenurePaid'],
            'balance_carried' => $carryOver,
            'status' => 'renewed',
            'notes' => $notes !== '' ? $notes : null,
            'closed_at' => $now,
            'created_at' => $now,
        ));

        $this->syncUnitHistory($unitTable, array_merge($unit, array('status' => 'ended')));

        $data = array(
            'tenure' => $tenure,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_rent' => $monthlyRent,
            'service_charge' => $serviceCharge,
            'security_deposit' => $securityDeposit,
            'status' => 'active',
            'notes' => $notes !== '' ? $notes : (string) $registration['notes'],
            'updated_at' => $now,
        );

        $this->database->execute(
            'UPDATE `' . $unitTable . '` SET ' . $this->buildUnitUpdateSet($data) . ' WHERE id = :id AND property_id = :property_id',
            array_merge($data, array('id' => $unitId, 'property_id' => (int) $registration['propertyId']))
        );

        $renewed = $this->findTenantRegistration($unitTable, $unitId);

        if ($renewed) {
            $this->syncUnitHistory($unitTable, $this->buildNormalizedUnitFromRegistration($renewed));
        }

        if ($rentPaidAtRenewal > 0 && $renewed) {
            $rentReference = 'RENT-' . $unitId . '-RENEW-' . date('Ymd', strtotime($startDate));
            $alreadyPosted = $this->database->fetchOne(
                'SELECT id FROM payments WHERE property_id = :property_id AND unit_id = :unit_id AND reference = :reference',
                array('property_id' => (int) $registration['propertyId'], 'unit_id' => $unitId, 'reference' => $rentReference)
            );

            if (! $alreadyPosted) {
                $this->addUnitPaymentByAdmin(
                    $unitTable,
                    $unitId,
                    array(
                        'amount' => $rentPaidAtRenewal,
                        'charge_type' => 'rent',
                        'label' => 'Rent paid at renewal',
                        'channel' => 'cash',
                        'paid_at' => $startDate,
                        'reference' => $rentReference,
                        'notes' => 'Recorded when the tenancy was renewed.',
                    ),
                    $adminUser
                );
            }
        }

        return array($renewed, $renewed ? null : 'The tenancy could not be renewed.');
    }

    public function addUnitPaymentByAdmin($unitTable, $unitId, array $payload, $adminUser)
    {
        $registration = $this->findTenantRegistration($unitTable, $unitId);

        if (! $registration) {
            return array(false, 'That tenant registration could not be found.');
        }

        $amount = $this->extractAmount(isset($payload['amount']) ? $payload['amount'] : 0);

        if ($amount <= 0) {
            return array(false, 'Enter a valid payment amount.');
        }

        $chargeType = trim(isset($payload['charge_type']) ? $payload['charge_type'] : 'rent');
        $label = trim(isset($payload['label']) ? $payload['label'] : '');
        $channel = trim(isset($payload['channel']) ? $payload['channel'] : 'cash');
        $paidAt = $this->normalizeDateTimeInput(trim(isset($payload['paid_at']) ? $payload['paid_at'] : ''));

        if ($paidAt === '') {
            $paidAt = $this->now();
        }

        if ($chargeType === 'service_charge') {
            return $this->addServiceChargePaymentByAdmin($registration, $amount, $label, $channel, $paidAt, $payload);
        }

        $reference = trim(isset($payload['reference']) ? $payload['reference'] : '');

        if ($reference === '') {
            $reference = $this->paymentReference($registration['unitId'] . $chargeType . $paidAt . mt_rand(1000, 9999));
        }

        $baseDescription = $chargeType === 'deposit' ? 'Deposit payment' : 'Rent payment';
        $periodKey = trim(isset($payload['period_key']) ? (string) $payload['period_key'] : '');

        if ($chargeType === 'rent' && $periodKey !== '') {
            $periodCheck = $this->validateRentPeriodPayment($registration, $periodKey, $amount);

            if (! $periodCheck['ok']) {
                return array(false, $periodCheck['error']);
            }

            $period = $periodCheck['period'];
            $baseDescription = 'Rent payment for ' . $period['shortLabel'];
        }

        $paymentId = $this->database->insert('payments', array(
            'user_id' => (int) $registration['userId'],
            'property_id' => (int) $registration['propertyId'],
            'unit_id' => (int) $registration['unitId'],
            'application_id' => 0,
            'tenancy_id' => 0,
            'amount' => $amount,
            'channel' => $channel,
            'card_last4' => '',
            'reference' => $reference,
            'description' => $label !== '' ? $baseDescription . ' - ' . $label : $baseDescription,
            'created_at' => $paidAt,
        ));

        if ($chargeType === 'rent' && $periodKey !== '' && $paymentId > 0) {
            $period = $periodCheck['period'];
            $this->database->insert('rent_period_allocations', array(
                'payment_id' => (int) $paymentId,
                'property_id' => (int) $registration['propertyId'],
                'unit_table' => (string) $registration['unitTable'],
                'unit_id' => (int) $registration['unitId'],
                'user_id' => (int) $registration['userId'],
                'period_key' => (string) $periodKey,
                'period_start' => $period['startDate'] !== '' ? $period['startDate'] : null,
                'period_end' => $period['endDate'] !== '' ? $period['endDate'] : null,
                'amount_paid' => $amount,
                'created_at' => $paidAt,
            ));
        }

        return array($this->findTenantRegistration($unitTable, $unitId), null);
    }

    /**
     * Checks a rent payment against the period the admin picked: the period has to exist, it cannot
     * already be settled, and the amount cannot go past what is still owing for it.
     */
    private function validateRentPeriodPayment(array $registration, $periodKey, $amount, $alreadyPaid = 0)
    {
        $periods = $this->rentPeriodsForRegistration($registration);
        $found = null;

        foreach ($periods['periods'] as $period) {
            if ((string) $period['key'] === (string) $periodKey) {
                $found = $period;
                break;
            }
        }

        if ($found === null) {
            return array('ok' => false, 'error' => 'Choose which period this rent payment is for.');
        }

        // What this payment already cleared stays available while it is being edited, so saving it
        // without changing the amount is not treated as an over payment.
        $available = (int) $found['remaining'] + max(0, (int) $alreadyPaid);

        if ($available <= 0) {
            return array('ok' => false, 'error' => 'The rent for ' . $found['shortLabel'] . ' is already settled in full, so there is nothing to pay against it.');
        }

        if ($amount > $available) {
            return array(
                'ok' => false,
                'error' => 'That is more than the ' . $this->formatMoney($available) . ' still owing for ' . $found['shortLabel'] . '.',
            );
        }

        return array('ok' => true, 'error' => '', 'period' => $found);
    }

    public function keyCollectionsForRegistration($unitTable, $unitId)
    {
        if (! in_array($unitTable, $this->registrationTables(), true)) {
            return array();
        }

        $rows = $this->database->fetchAll(
            'SELECT * FROM key_collections WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY id DESC',
            array('unit_table' => (string) $unitTable, 'unit_id' => (int) $unitId)
        );

        $collections = array();

        foreach ($rows as $row) {
            $collections[] = array(
                'id' => (int) $row['id'],
                'propertyId' => (int) $row['property_id'],
                'unitTable' => (string) $row['unit_table'],
                'unitId' => (int) $row['unit_id'],
                'userId' => (int) $row['user_id'],
                'paymentId' => (int) $row['payment_id'],
                'amountPaid' => (int) $row['amount_paid'],
                'expectedRent' => (int) $row['expected_rent'],
                'balance' => (int) $row['balance'],
                'collectedAt' => (string) $row['collected_at'],
                'collectorName' => (string) $row['collector_name'],
                'recipientName' => (string) $row['recipient_name'],
                'keysCount' => (int) $row['keys_count'],
                'notes' => isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : '',
                'createdAt' => (string) $row['created_at'],
            );
        }

        return $collections;
    }

    public function recordKeyCollectionByAdmin($unitTable, $unitId, array $payload, $adminUser)
    {
        $registration = $this->findTenantRegistration($unitTable, $unitId);

        if (! $registration) {
            return array(false, 'That tenant registration could not be found.', array());
        }

        $amountPaid = $this->extractAmount(isset($payload['amount_paid']) ? $payload['amount_paid'] : 0);

        if ($amountPaid < 0) {
            return array(false, 'Enter a valid amount paid.', array());
        }

        $keysCount = isset($payload['keys_count']) ? (int) $payload['keys_count'] : 1;
        if ($keysCount < 1) {
            $keysCount = 1;
        }

        $recipientName = trim(isset($payload['recipient_name']) ? (string) $payload['recipient_name'] : '');
        if ($recipientName === '') {
            $recipientName = $registration['user']['name'];
        }

        $collectedAt = $this->normalizeDateTimeInput(trim(isset($payload['collected_at']) ? (string) $payload['collected_at'] : ''));
        if ($collectedAt === '') {
            $collectedAt = $this->now();
        }

        $channel = trim(isset($payload['channel']) ? (string) $payload['channel'] : 'cash');
        $reference = trim(isset($payload['reference']) ? (string) $payload['reference'] : '');
        $notes = trim(isset($payload['notes']) ? (string) $payload['notes'] : '');

        $paymentId = 0;
        $postPayment = isset($payload['post_payment']) && in_array((string) $payload['post_payment'], array('1', 'yes', 'on'), true);
        $balances = $this->registrationBalanceBreakdown($registration);
        $expectedRent = (int) $balances['tenureTotal'];
        $priorPaid = (int) $balances['tenurePaid'];
        $balance = max(0, $expectedRent - $priorPaid - ($postPayment && $amountPaid > 0 ? $amountPaid : 0));

        if ($amountPaid > 0 && $postPayment) {
            if ($reference === '') {
                $reference = $this->paymentReference($registration['unitId'] . 'key' . $collectedAt . mt_rand(1000, 9999));
            }

            $paymentId = $this->database->insert('payments', array(
                'user_id' => (int) $registration['userId'],
                'property_id' => (int) $registration['propertyId'],
                'unit_id' => (int) $registration['unitId'],
                'application_id' => 0,
                'tenancy_id' => 0,
                'amount' => $amountPaid,
                'channel' => $channel,
                'card_last4' => '',
                'reference' => $reference,
                'description' => 'Rent payment recorded at key collection',
                'created_at' => $collectedAt,
            ));
        }

        $keyId = $this->database->insert('key_collections', array(
            'property_id' => (int) $registration['propertyId'],
            'unit_table' => (string) $registration['unitTable'],
            'unit_id' => (int) $registration['unitId'],
            'user_id' => (int) $registration['userId'],
            'payment_id' => $paymentId,
            'amount_paid' => $amountPaid,
            'expected_rent' => $expectedRent,
            'balance' => $balance,
            'collected_at' => $collectedAt,
            'collected_by' => isset($adminUser['id']) ? (int) $adminUser['id'] : 0,
            'collector_name' => isset($adminUser['name']) ? (string) $adminUser['name'] : 'Sandworth Admin',
            'recipient_name' => $recipientName,
            'keys_count' => $keysCount,
            'notes' => $notes !== '' ? $notes : null,
            'created_at' => $this->now(),
        ));

        $info = array(
            'keyId' => $keyId,
            'amountPaid' => $amountPaid,
            'expectedRent' => $expectedRent,
            'balance' => $balance,
            'recipientName' => $recipientName,
            'paymentPosted' => $paymentId > 0,
        );

        return array($this->findTenantRegistration($unitTable, $unitId), null, $info);
    }

    private function addServiceChargePaymentByAdmin(array $registration, $amount, $label, $channel, $paidAt, array $payload = array())
    {
        $summary = $this->serviceChargeSummaryForRegistration($registration);
        $maxPayable = (int) $summary['outstanding'];
        $periodKey = trim(isset($payload['period_key']) ? (string) $payload['period_key'] : '');
        $allocationMonths = isset($summary['months']) && is_array($summary['months']) ? $summary['months'] : array();
        $periodLabel = 'the service charge';

        if ($periodKey !== '') {
            list($period, $periodMonths) = $this->serviceChargePeriodMonthsForRegistration($registration, $periodKey);

            if (! $period) {
                return array(false, 'Choose one of the service charge periods listed.');
            }

            if ($periodMonths === array()) {
                return array(false, 'Choose a service charge period with service charge on it.');
            }

            $periodName = $period['label'] . ' (' . $period['startDate'] . ' to ' . $period['endDate'] . ')';
            $periodLabel = 'the ' . $periodName . ' service charge';

            $periodRemaining = 0;

            foreach ($periodMonths as $month) {
                $periodRemaining += (int) $month['remaining'];
            }

            if ($periodRemaining <= 0) {
                return array(false, 'The service charge for ' . $periodName . ' is already settled in full, so there is nothing to pay against it.');
            }

            if ($amount > $periodRemaining) {
                return array(false, 'That is more than the ' . $this->formatMoney($periodRemaining) . ' still owing for ' . $periodName . '.');
            }

            $allocationMonths = $periodMonths;
        } else {
            if ($maxPayable <= 0) {
                return array(false, 'There is no outstanding service charge to record a payment against.');
            }

            if ($amount > $maxPayable) {
                return array(false, 'That amount is more than the service charge payable of ' . $this->formatMoney($maxPayable) . '. Lower the amount to the unpaid service charge.');
            }
        }

        $reference = trim(isset($payload['reference']) ? $payload['reference'] : '');

        if ($reference === '') {
            $reference = $this->paymentReference($registration['unitId'] . 'service_charge' . $paidAt . mt_rand(1000, 9999));
        }

        $baseDescription = $periodKey !== '' && isset($period)
            ? 'Service charge for ' . $periodName
            : 'Service charge';


        $paymentId = $this->database->insert('payments', array(
            'user_id' => (int) $registration['userId'],
            'property_id' => (int) $registration['propertyId'],
            'unit_id' => (int) $registration['unitId'],
            'application_id' => 0,
            'tenancy_id' => 0,
            'amount' => $amount,
            'channel' => $channel,
            'card_last4' => '',
            'reference' => $reference,
            'description' => $label !== '' ? $baseDescription . ' - ' . $label : $baseDescription,
            'created_at' => $paidAt,
        ));

        $leftOver = $amount;

        foreach ($allocationMonths as $month) {
            if ($leftOver <= 0) {
                break;
            }

            $remaining = (int) $month['remaining'];

            if ($remaining <= 0) {
                continue;
            }

            $monthlyPaid = min($leftOver, $remaining);
            $this->database->insert('service_charge_allocations', array(
                'payment_id' => $paymentId,
                'property_id' => (int) $registration['propertyId'],
                'unit_table' => (string) $registration['unitTable'],
                'unit_id' => (int) $registration['unitId'],
                'user_id' => (int) $registration['userId'],
                'service_month' => (string) $month['serviceMonth'],
                'rate_used' => (int) $month['rate'],
                'amount_paid' => $monthlyPaid,
                'created_at' => $paidAt,
            ));

            $leftOver -= $monthlyPaid;
        }

        return array($this->findTenantRegistration($registration['unitTable'], $registration['unitId']), null);
    }

    /**
     * Works out how an existing payment should be split, so the admin can change the type,
     * amount, or date of a payment that was already posted without losing the history.
     */
    public function paymentClassificationForUnit(array $registration, array $payment)
    {
        $description = (string) $payment['description'];
        $isServiceCharge = $this->database->fetchOne(
            'SELECT id FROM service_charge_allocations WHERE payment_id = :payment_id LIMIT 1',
            array('payment_id' => (int) $payment['id'])
        ) !== null;

        $isDeposit = stripos($description, 'deposit') !== false;
        $isOther = stripos($description, 'rent') === false && stripos($description, 'service charge') === false;

        if ($isServiceCharge) {
            return 'service_charge';
        }

        if ($isDeposit) {
            return 'deposit';
        }

        if ($isOther) {
            return 'other';
        }

        return 'rent';
    }

    /**
     * Tags every payment on a tenant with the type it is treated as, so the payment editor can
     * show what each one settles and which arrears it is being applied to.
     */
    public function paymentClassificationsForRegistration(array $registration)
    {
        $classifications = array();
        $rentYearIndex = 0;
        $rentYears = $this->rentYearsForRegistration($registration);
        $scSummary = $this->serviceChargeSummaryForRegistration($registration);
        $rentStartTs = isset($registration['startDate']) ? @strtotime((string) $registration['startDate']) : false;
        $currentRentYear = null;
        $periodAllocations = $this->rentPeriodAllocationsForUnit($registration['unitTable'], $registration['unitId']);
        $rentPeriodKeys = array();
        $scMonthsByPayment = array();

        foreach ((array) $this->rentPeriodsForRegistration($registration)['periods'] as $rentPeriod) {
            $rentPeriodKeys[] = (string) $rentPeriod['key'];
        }

        foreach ($this->database->fetchAll(
            'SELECT payment_id, service_month FROM service_charge_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY service_month ASC',
            array('unit_table' => (string) $registration['unitTable'], 'unit_id' => (int) $registration['unitId'])
        ) as $allocation) {
            $paymentId = (int) $allocation['payment_id'];
            $monthKey = (string) $allocation['service_month'];

            if ($paymentId > 0 && $monthKey !== '' && ! isset($scMonthsByPayment[$paymentId])) {
                $scMonthsByPayment[$paymentId] = $monthKey;
            }
        }

        foreach ((array) $rentYears['years'] as $year) {
            $yearStartTs = @strtotime((string) $year['yearStart']);
            $yearEndTs = @strtotime((string) $year['yearEnd']);

            if ($yearStartTs !== false && $yearEndTs !== false && (int) $year['remaining'] > 0) {
                $currentRentYear = $year;
            }
        }

        foreach ((array) $registration['payments'] as $payment) {
            $type = $this->paymentClassificationForUnit($registration, $payment);
            $paidAt = (string) $payment['createdAt'];
            $paidTimestamp = $paidAt !== '' ? @strtotime($paidAt) : false;
            $yearNumber = 0;
            $yearIsPast = false;
            $paymentId = (int) $payment['id'];
            $periodKey = '';

            if ($type === 'rent' && isset($periodAllocations[$paymentId])) {
                foreach ($periodAllocations[$paymentId] as $allocation) {
                    $periodKey = (string) $allocation['periodKey'];
                    break;
                }
            }

            if ($type === 'service_charge' && isset($scMonthsByPayment[$paymentId])) {
                $periodKey = $this->serviceChargePeriodKeyForMonth($registration, $scMonthsByPayment[$paymentId]);
            }

            foreach ((array) $rentYears['years'] as $year) {
                $yearStartTs = @strtotime((string) $year['yearStart']);
                $yearEndTs = @strtotime((string) $year['yearEnd']);

                if ($paidTimestamp === false || $yearStartTs === false || $yearEndTs === false) {
                    continue;
                }

                if ($paidTimestamp >= $yearStartTs && $paidTimestamp <= $yearEndTs + 86400) {
                    $yearNumber = (int) $year['yearNumber'];
                    $yearIsPast = (int) $year['remaining'] > 0 && empty($year['isCurrentYear']);
                }
            }

            // Rent recorded before periods were tracked is still grouped under the year it cleared,
            // so the breakdown and the history editor show the same period.
            if ($type === 'rent' && trim((string) $periodKey) === '' && $yearNumber > 0) {
                $candidateKey = 'y' . $yearNumber;

                if (in_array($candidateKey, $rentPeriodKeys, true)) {
                    $periodKey = $candidateKey;
                }
            }

            $classifications[$paymentId] = array(
                'type' => $type,
                'periodKey' => $periodKey,
                'rentYearNumber' => $yearNumber,
                'rentYearLabel' => $yearNumber > 0 ? 'Year ' . $yearNumber : '',
                'settlesArrears' => $type === 'rent'
                    ? ($currentRentYear !== null && $yearNumber > 0 && $yearNumber < (int) $currentRentYear['yearNumber'])
                    : ($type === 'service_charge' && $paidTimestamp !== false && $rentStartTs !== false
                        ? strcmp(date('Y-m', $paidTimestamp), (string) $scSummary['currentYearStart']) < 0
                        : false),
            );

            $rentYearIndex++;
        }

        return $classifications;
    }

    public function updateUnitPaymentByAdmin($paymentId, $unitTable, $unitId, array $payload)
    {
        $paymentId = (int) $paymentId;
        $registration = $this->findTenantRegistration($unitTable, $unitId);

        if (! $registration) {
            return array(false, 'That tenant registration could not be found.');
        }

        $row = $this->database->fetchOne(
            'SELECT * FROM payments WHERE id = :id AND property_id = :property_id AND unit_id = :unit_id',
            array('id' => $paymentId, 'property_id' => (int) $registration['propertyId'], 'unit_id' => (int) $unitId)
        );

        if (! $row) {
            return array(false, 'That payment is no longer on this tenant ledger.');
        }

        $existing = array(
            'id' => (int) $row['id'],
            'amount' => (int) $row['amount'],
            'description' => (string) $row['description'],
        );

        $amount = $this->extractAmount(isset($payload['amount']) ? $payload['amount'] : 0);
        $chargeType = trim(isset($payload['charge_type']) ? (string) $payload['charge_type'] : '');
        $label = trim(isset($payload['label']) ? (string) $payload['label'] : '');
        $channel = trim(isset($payload['channel']) ? (string) $payload['channel'] : 'cash');
        $paidAt = $this->normalizeDateTimeInput(trim(isset($payload['paid_at']) ? (string) $payload['paid_at'] : ''));
        $reference = trim(isset($payload['reference']) ? (string) $payload['reference'] : '');

        if (! in_array($chargeType, array('rent', 'service_charge', 'deposit', 'other'), true)) {
            $chargeType = $this->paymentClassificationForUnit($registration, $existing);
        }

        if ($amount <= 0) {
            return array(false, 'Enter a valid payment amount.');
        }

        if ($paidAt === '') {
            $paidAt = (string) $row['created_at'];
        }

        if ($channel === '') {
            $channel = (string) $row['channel'];
        }

        if ($reference === '') {
            $reference = (string) $row['reference'];
        }

        $baseDescription = $chargeType === 'rent' ? 'Rent payment' : ($chargeType === 'service_charge' ? 'Service charge' : ($chargeType === 'deposit' ? 'Deposit payment' : 'Other payment'));
        $description = $label !== '' ? $baseDescription . ' - ' . $label : $baseDescription;
        $periodKey = trim(isset($payload['period_key']) ? (string) $payload['period_key'] : '');

        // The new service charge split is worked out before anything is written, counting this
        // payment's own months as still available, so a rejected edit leaves the ledger untouched.
        $scMonths = array();

        if ($chargeType === 'service_charge') {
            $ownByMonth = array();

            foreach ($this->database->fetchAll(
                'SELECT service_month, amount_paid FROM service_charge_allocations WHERE payment_id = :payment_id',
                array('payment_id' => $paymentId)
            ) as $own) {
                $monthKey = (string) $own['service_month'];
                $ownByMonth[$monthKey] = (isset($ownByMonth[$monthKey]) ? $ownByMonth[$monthKey] : 0) + (int) $own['amount_paid'];
            }

            $summary = $this->serviceChargeSummaryForRegistration($registration);
            $periodRemaining = 0;
            $periodHasMonths = false;
            $scPeriod = null;

            if ($periodKey !== '') {
                list($scPeriod, $scPeriodMonths) = $this->serviceChargePeriodMonthsForRegistration($registration, $periodKey);

                if (! $scPeriod) {
                    return array($this->findTenantRegistration($unitTable, $unitId), 'Choose one of the service charge periods listed.');
                }

                $windowStart = $scPeriod['windowStart'];
                $windowEnd = $scPeriod['windowEnd'];
            }

            foreach ((array) $summary['months'] as $month) {
                $monthKey = (string) $month['serviceMonth'];
                $available = (int) $month['remaining'] + (isset($ownByMonth[$monthKey]) ? $ownByMonth[$monthKey] : 0);
                $scMonths[] = array(
                    'serviceMonth' => $monthKey,
                    'rate' => (int) $month['rate'],
                    'remaining' => $available,
                );

                if ($periodKey === '') {
                    $periodRemaining += $available;
                } else {
                    $monthShort = substr($monthKey, 0, 7);

                    if ($monthShort >= $windowStart && $monthShort <= $windowEnd) {
                        $periodRemaining += $available;
                        $periodHasMonths = true;
                    }
                }
            }

            if ($periodKey !== '') {
                $periodName = $scPeriod['label'] . ' (' . $scPeriod['startDate'] . ' to ' . $scPeriod['endDate'] . ')';

                if (! $periodHasMonths) {
                    return array($this->findTenantRegistration($unitTable, $unitId), 'Choose a service charge period with service charge on it.');
                }

                if ($periodRemaining <= 0) {
                    return array($this->findTenantRegistration($unitTable, $unitId), 'The service charge for ' . $periodName . ' is already settled in full, so there is nothing to pay against it.');
                }

                if ($amount > $periodRemaining) {
                    return array(
                        $this->findTenantRegistration($unitTable, $unitId),
                        'That is more than the ' . $this->formatMoney($periodRemaining) . ' still owing for ' . $periodName . '.',
                    );
                }

                $scMonths = array_values(array_filter($scMonths, function ($month) use ($windowStart, $windowEnd) {
                    $monthShort = substr($month['serviceMonth'], 0, 7);

                    return $monthShort >= $windowStart && $monthShort <= $windowEnd;
                }));
            } else {
                $maxPayable = (int) $summary['outstanding'] + (int) array_sum($ownByMonth);

                if ($amount > $maxPayable) {
                    return array(
                        $this->findTenantRegistration($unitTable, $unitId),
                        'That amount is more than the service charge payable of ' . $this->formatMoney($maxPayable) . '.',
                    );
                }
            }
        }

        $rentPeriod = null;

        if ($chargeType === 'rent' && $periodKey !== '') {
            $ownRentPaid = 0;

            foreach ((array) $this->database->fetchAll(
                'SELECT period_key, amount_paid FROM rent_period_allocations WHERE payment_id = :payment_id',
                array('payment_id' => $paymentId)
            ) as $ownAllocation) {
                if ((string) $ownAllocation['period_key'] === (string) $periodKey) {
                    $ownRentPaid += (int) $ownAllocation['amount_paid'];
                }
            }

            // Rent recorded before periods were tracked has no allocation row, so the period it
            // already cleared is worked out the same way the breakdown works it out.
            if ($ownRentPaid === 0) {
                $classifications = $this->paymentClassificationsForRegistration($registration);

                if (isset($classifications[$paymentId])
                    && $classifications[$paymentId]['type'] === 'rent'
                    && (string) $classifications[$paymentId]['periodKey'] === (string) $periodKey) {
                    $ownRentPaid = (int) $row['amount'];
                }
            }

            $periodCheck = $this->validateRentPeriodPayment($registration, $periodKey, $amount, $ownRentPaid);

            if (! $periodCheck['ok']) {
                return array($this->findTenantRegistration($unitTable, $unitId), $periodCheck['error']);
            }

            $rentPeriod = $periodCheck['period'];
            $baseDescription = 'Rent payment for ' . $rentPeriod['shortLabel'];
            $description = $label !== '' ? $baseDescription . ' - ' . $label : $baseDescription;
        }

        $this->database->execute(
            'UPDATE payments SET amount = :amount, channel = :channel, reference = :reference, description = :description, created_at = :created_at WHERE id = :id',
            array(
                'amount' => $amount,
                'channel' => $channel,
                'reference' => $reference,
                'description' => $description,
                'created_at' => $paidAt,
                'id' => $paymentId,
            )
        );

        // The service charge months are re-allocated because the amount or type may have changed.
        $this->database->execute(
            'DELETE FROM service_charge_allocations WHERE payment_id = :payment_id',
            array('payment_id' => $paymentId)
        );

        $this->database->execute(
            'DELETE FROM rent_period_allocations WHERE payment_id = :payment_id',
            array('payment_id' => $paymentId)
        );

        if ($chargeType === 'service_charge') {
            $leftOver = $amount;

            foreach ($scMonths as $month) {
                if ($leftOver <= 0) {
                    break;
                }

                $remaining = (int) $month['remaining'];

                if ($remaining <= 0) {
                    continue;
                }

                $monthlyPaid = min($leftOver, $remaining);
                $this->database->insert('service_charge_allocations', array(
                    'payment_id' => $paymentId,
                    'property_id' => (int) $registration['propertyId'],
                    'unit_table' => (string) $unitTable,
                    'unit_id' => (int) $unitId,
                    'user_id' => (int) $registration['userId'],
                    'service_month' => (string) $month['serviceMonth'],
                    'rate_used' => (int) $month['rate'],
                    'amount_paid' => $monthlyPaid,
                    'created_at' => $paidAt,
                ));

                $leftOver -= $monthlyPaid;
            }
        }

        if ($rentPeriod !== null) {
            $this->database->insert('rent_period_allocations', array(
                'payment_id' => $paymentId,
                'property_id' => (int) $registration['propertyId'],
                'unit_table' => (string) $unitTable,
                'unit_id' => (int) $unitId,
                'user_id' => (int) $registration['userId'],
                'period_key' => (string) $periodKey,
                'period_start' => $rentPeriod['startDate'] !== '' ? $rentPeriod['startDate'] : null,
                'period_end' => $rentPeriod['endDate'] !== '' ? $rentPeriod['endDate'] : null,
                'amount_paid' => $amount,
                'created_at' => $paidAt,
            ));
        }

        return array($this->findTenantRegistration($unitTable, $unitId), null);
    }

    public function deleteUnitPaymentByAdmin($paymentId, $unitTable, $unitId)
    {
        $paymentId = (int) $paymentId;
        $registration = $this->findTenantRegistration($unitTable, $unitId);

        if (! $registration) {
            return array(false, 'That tenant registration could not be found.');
        }

        $this->database->execute(
            'DELETE FROM payments WHERE id = :id AND property_id = :property_id AND unit_id = :unit_id',
            array(
                'id' => $paymentId,
                'property_id' => (int) $registration['propertyId'],
                'unit_id' => (int) $registration['unitId'],
            )
        );

        $this->database->execute(
            'DELETE FROM service_charge_allocations WHERE payment_id = :payment_id',
            array('payment_id' => $paymentId)
        );

        $this->database->execute(
            'DELETE FROM rent_period_allocations WHERE payment_id = :payment_id',
            array('payment_id' => $paymentId)
        );

        return array($this->findTenantRegistration($unitTable, $unitId), null);
    }

    public function serviceChargeHistoryForProperty($propertyId)
    {
        $propertyId = (int) $propertyId;

        if ($propertyId <= 0) {
            return array();
        }

        $rows = $this->database->fetchAll(
            'SELECT * FROM service_charge_history WHERE property_id = :property_id ORDER BY effective_from ASC, id ASC',
            array('property_id' => $propertyId)
        );

        $history = array();

        foreach ($rows as $row) {
            $history[] = array(
                'id' => (int) $row['id'],
                'propertyId' => $propertyId,
                'serviceCharge' => (int) $row['service_charge'],
                'effectiveFrom' => (string) $row['effective_from'],
                'effectiveMonth' => substr((string) $row['effective_from'], 0, 7),
                'createdAt' => (string) $row['created_at'],
            );
        }

        return $history;
    }

    public function serviceChargeRateForMonth($propertyId, $serviceMonth)
    {
        $propertyId = (int) $propertyId;
        $serviceMonth = substr(trim((string) $serviceMonth), 0, 7);

        if ($propertyId <= 0 || ! preg_match('/^\d{4}-\d{2}$/', $serviceMonth)) {
            return 0;
        }

        $monthStart = $serviceMonth . '-01';
        $rows = $this->database->fetchAll(
            'SELECT service_charge FROM service_charge_history WHERE property_id = :property_id AND effective_from <= :month_start ORDER BY effective_from DESC, id DESC LIMIT 1',
            array('property_id' => $propertyId, 'month_start' => $monthStart)
        );

        if ($rows !== array()) {
            return (int) $rows[0]['service_charge'];
        }

        $earliest = $this->database->fetchOne(
            'SELECT service_charge FROM service_charge_history WHERE property_id = :property_id ORDER BY effective_from ASC, id ASC LIMIT 1',
            array('property_id' => $propertyId)
        );

        if ($earliest) {
            return (int) $earliest['service_charge'];
        }

        $property = $this->findProperty($propertyId);

        return $property ? (int) $property['serviceCharge'] : 0;
    }

    public function setPropertyServiceCharge($propertyId, $amount, $effectiveFrom)
    {
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'That property could not be found.');
        }

        $amount = $this->extractAmount($amount);
        $effectiveFrom = trim((string) $effectiveFrom);
        $timestamp = $effectiveFrom !== '' ? strtotime($effectiveFrom) : false;

        if ($timestamp === false) {
            return array(false, 'Enter a valid effective-from date for the new service charge.');
        }

        $effectiveDate = date('Y-m-01', $timestamp);
        $now = $this->now();
        $existing = $this->database->fetchOne(
            'SELECT id FROM service_charge_history WHERE property_id = :property_id AND effective_from = :effective_from LIMIT 1',
            array('property_id' => (int) $property['id'], 'effective_from' => $effectiveDate)
        );

        if ($existing) {
            $this->database->execute(
                'UPDATE service_charge_history SET service_charge = :service_charge, updated_at = :updated_at WHERE id = :id',
                array('service_charge' => $amount, 'updated_at' => $now, 'id' => (int) $existing['id'])
            );
        } else {
            $this->database->insert('service_charge_history', array(
                'property_id' => (int) $property['id'],
                'service_charge' => $amount,
                'effective_from' => $effectiveDate,
                'created_at' => $now,
                'updated_at' => $now,
            ));
        }

        $this->database->execute(
            'UPDATE properties SET service_charge = :service_charge, updated_at = :updated_at WHERE id = :id',
            array('service_charge' => $amount, 'updated_at' => $now, 'id' => (int) $property['id'])
        );
        unset($this->propertyCache[(int) $property['id']]);

        return array($this->findProperty($property['id']), null);
    }

    public function propertyCurrentCharges($property)
    {
        if (! is_array($property)) {
            return array('monthlyRent' => 0, 'annualRent' => 0, 'serviceCharge' => 0, 'securityDeposit' => 0);
        }

        $annualRent = (int) $property['monthlyRent'];
        $purpose = isset($property['purpose']) ? (string) $property['purpose'] : 'rent';
        $monthlyRent = $purpose === 'sale' || $annualRent <= 0 ? 0 : (int) round($annualRent / 12);

        return array(
            'monthlyRent' => $monthlyRent,
            'annualRent' => $annualRent,
            'serviceCharge' => $this->serviceChargeRateForMonth((int) $property['id'], date('Y-m')),
            'securityDeposit' => isset($property['securityDeposit']) ? (int) $property['securityDeposit'] : 0,
        );
    }

    public function setPropertyRent($propertyId, $annualRent)
    {
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'That property could not be found.');
        }

        $annualRent = $this->extractAmount($annualRent);

        if ((string) $property['purpose'] !== 'sale' && $annualRent <= 0) {
            return array(false, 'Enter a valid annual rent for this property.');
        }

        $this->database->execute(
            'UPDATE properties SET monthly_rent = :monthly_rent, updated_at = :updated_at WHERE id = :id',
            array('monthly_rent' => $annualRent, 'updated_at' => $this->now(), 'id' => (int) $property['id'])
        );
        unset($this->propertyCache[(int) $property['id']]);

        return array($this->findProperty($property['id']), null);
    }

    private function serviceChargeMonthsBetween($startTs, $endTs)
    {
        $months = array();

        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            return $months;
        }

        $year = (int) date('Y', $startTs);
        $month = (int) date('n', $startTs);

        while (true) {
            $serviceMonth = sprintf('%04d-%02d', $year, $month);
            $monthStart = strtotime($serviceMonth . '-01');
            $monthEnd = strtotime(date('Y-m-t 23:59:59', $monthStart));

            if ($monthStart > $endTs) {
                break;
            }

            $months[] = array(
                'serviceMonth' => $serviceMonth,
                'startTs' => $monthStart,
                'endTs' => min($monthEnd, $endTs),
            );

            $month++;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
        }

        return $months;
    }

    private function serviceChargePaidByMonth($unitTable, $unitId)
    {
        $rows = $this->database->fetchAll(
            'SELECT service_month, SUM(amount_paid) AS paid FROM service_charge_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id GROUP BY service_month',
            array('unit_table' => (string) $unitTable, 'unit_id' => (int) $unitId)
        );
        $map = array();

        foreach ($rows as $row) {
            $map[(string) $row['service_month']] = (int) $row['paid'];
        }

        return $map;
    }

    private function priorServiceChargeTenures(array $registration)
    {
        $unitTable = isset($registration['unitTable']) ? (string) $registration['unitTable'] : '';
        $unitId = isset($registration['unitId']) ? (int) $registration['unitId'] : 0;
        $userId = isset($registration['userId']) ? (int) $registration['userId'] : 0;

        if ($unitTable === '' || $unitId <= 0 || $userId <= 0) {
            return array();
        }

        $tenures = array();
        $historyRows = $this->database->fetchAll(
            'SELECT start_date, end_date FROM tenure_history WHERE unit_table = :unit_table AND unit_id = :unit_id AND user_id = :user_id ORDER BY start_date ASC, id ASC',
            array('unit_table' => $unitTable, 'unit_id' => $unitId, 'user_id' => $userId)
        );
        $unitHistoryRows = $this->database->fetchAll(
            'SELECT start_date, end_date, ended_at FROM unit_history WHERE unit_table = :unit_table AND unit_id = :unit_id AND user_id = :user_id AND occupancy_status <> :active ORDER BY start_date ASC, id ASC',
            array('unit_table' => $unitTable, 'unit_id' => $unitId, 'user_id' => $userId, 'active' => 'active')
        );

        foreach (array_merge($historyRows, $unitHistoryRows) as $row) {
            if (trim((string) $row['start_date']) === '') {
                continue;
            }

            $startTs = @strtotime((string) $row['start_date']);
            $endValue = trim((string) $row['end_date']) !== '' ? (string) $row['end_date'] : (isset($row['ended_at']) && trim((string) $row['ended_at']) !== '' ? (string) $row['ended_at'] : '');
            $endTs = $endValue !== '' ? @strtotime($endValue) : false;

            if ($startTs === false || $startTs <= 0 || $endTs === false || $endTs <= 0 || $endTs < $startTs) {
                continue;
            }

            $tenures[] = array('startTs' => $startTs, 'endTs' => $endTs);
        }

        return $tenures;
    }

    public function serviceChargeSummaryForRegistration(array $registration)
    {
        $propertyId = isset($registration['propertyId']) ? (int) $registration['propertyId'] : 0;
        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $endValue = isset($registration['endDate']) ? trim((string) $registration['endDate']) : '';
        $tenureMonths = $this->tenureMonths(isset($registration['tenure']) ? (string) $registration['tenure'] : '');
        $startTs = $startValue !== '' ? @strtotime($startValue) : false;
        $endTs = $endValue !== '' ? @strtotime($endValue) : false;

        $empty = array(
            'months' => array(),
            'currentYearStart' => '',
            'currentYearEnd' => '',
            'totalRates' => 0,
            'totalPaid' => 0,
            'arrears' => 0,
            'currentPeriod' => 0,
            'outstanding' => 0,
            'maxPayable' => 0,
        );

        if ($startTs === false || $startTs <= 0) {
            return $empty;
        }

        if ($endTs === false || $endTs <= 0) {
            $endTs = strtotime('+11 months', strtotime(date('Y-m-01', $startTs)));
        }

        $monthsElapsed = $this->elapsedTenureMonths($startValue);
        $tenureMonths = $this->registrationTenureMonths($registration);
        $tenureYears = $tenureMonths > 0 ? max(1, (int) round($tenureMonths / 12)) : 0;
        $elapsedYears = $monthsElapsed > 0 ? (int) floor(($monthsElapsed - 1) / 12) : 0;

        // Every month of the signed term is listed so a payment can be split across all the months
        // it covers, not just the months that have already elapsed.
        $yearIndex = max($elapsedYears, $tenureYears - 1);
        $monthEndTs = strtotime('+' . (($yearIndex + 1) * 12) . ' months', strtotime(date('Y-m-01', $startTs))) - 1;

        // A tenancy that has already run out is billed only up to the end of the tenure, otherwise
        // the month after the end date gets counted as well.
        $windowEndTs = min($endTs, $monthEndTs);
        $windowStartTs = strtotime(date('Y-m-01', $startTs));

        $monthList = $this->serviceChargeMonthsBetween($windowStartTs, $windowEndTs);
        $priorTenureMonths = array();

        foreach ($this->priorServiceChargeTenures($registration) as $prior) {
            foreach ($this->serviceChargeMonthsBetween($prior['startTs'], $prior['endTs']) as $mom) {
                $monthList[] = $mom;
                $priorTenureMonths[(string) $mom['serviceMonth']] = true;
            }
        }

        usort($monthList, function ($a, $b) {
            return strcmp($a['serviceMonth'], $b['serviceMonth']);
        });

        $seen = array();
        $months = array();
        $paidByMonth = $this->serviceChargePaidByMonth((string) $registration['unitTable'], (int) $registration['unitId']);

        // The arrears/current boundary follows the billing year in progress, not the final year of
        // the term, so payments made now are not all treated as settling old arrears.
        $currentYearStart = date('Y-m', strtotime('+' . ($elapsedYears * 12) . ' months', $windowStartTs));
        $currentYearEnd = date('Y-m', min($endTs, strtotime('+' . (($elapsedYears + 1) * 12) . ' months', $windowStartTs) - 1));
        $totalRates = 0;
        $totalPaid = 0;
        $outstanding = 0;
        $arrears = 0;
        $currentPeriod = 0;

        foreach ($monthList as $entry) {
            $serviceMonth = (string) $entry['serviceMonth'];

            if (isset($seen[$serviceMonth])) {
                continue;
            }

            $seen[$serviceMonth] = true;
            $rate = $this->serviceChargeRateForMonth($propertyId, $serviceMonth);
            $hasAllocations = isset($paidByMonth[$serviceMonth]) && (int) $paidByMonth[$serviceMonth] > 0;

            // A closed earlier tenancy was settled when it ended, so a month that carries no
            // allocation is treated as paid. Only months with recorded payments stay outstanding.
            if (isset($priorTenureMonths[$serviceMonth]) && ! $hasAllocations) {
                $paid = $rate;
            } else {
                $paid = isset($paidByMonth[$serviceMonth]) ? (int) $paidByMonth[$serviceMonth] : 0;
            }

            $remaining = max(0, $rate - $paid);
            $isArrears = strcmp($serviceMonth, $currentYearStart) < 0;
            $totalRates += $rate;
            $totalPaid += $paid;

            if ($remaining > 0) {
                $outstanding += $remaining;

                if ($isArrears) {
                    $arrears += $remaining;
                } else {
                    $currentPeriod += $remaining;
                }
            }

            $months[] = array(
                'serviceMonth' => $serviceMonth,
                'rate' => $rate,
                'paid' => $paid,
                'remaining' => $remaining,
                'isArrears' => $isArrears,
                'isCurrentYear' => ! $isArrears,
                'startLabel' => date('M Y', strtotime($serviceMonth . '-01')),
            );
        }

        return array(
            'months' => $months,
            'currentYearStart' => $currentYearStart,
            'currentYearEnd' => $currentYearEnd,
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'arrears' => $arrears,
            'currentPeriod' => $currentPeriod,
            'outstanding' => $outstanding,
            'maxPayable' => $outstanding,
        );
    }

    public function serviceChargeMonthlyRatesForProperty($property)
    {
        $history = $this->serviceChargeHistoryForProperty((int) $property['id']);
        $rates = array();

        foreach ($history as $row) {
            $rates[$row['effectiveMonth']] = (int) $row['serviceCharge'];
        }

        return $rates;
    }

    private function serviceChargeLedgerForUnit($unitTable, $unitId)
    {
        $unitTable = (string) $unitTable;
        $unitId = (int) $unitId;

        if ($unitTable === '' || $unitId <= 0) {
            return array();
        }

        $rows = $this->database->fetchAll(
            'SELECT a.service_month, a.amount_paid, p.reference, p.created_at
             FROM service_charge_allocations a
             LEFT JOIN payments p ON p.id = a.payment_id
             WHERE a.unit_table = :unit_table AND a.unit_id = :unit_id
             ORDER BY a.service_month ASC, p.created_at ASC, p.id ASC, a.id ASC',
            array('unit_table' => $unitTable, 'unit_id' => $unitId)
        );

        $ledger = array();

        foreach ($rows as $row) {
            $serviceMonth = (string) $row['service_month'];
            $dateLabel = '';
            $ts = @strtotime((string) $row['created_at']);

            if ($ts !== false && $ts > 0) {
                $dateLabel = date('d M Y', $ts);
            }

            if (! isset($ledger[$serviceMonth])) {
                $ledger[$serviceMonth] = array();
            }

            $ledger[$serviceMonth][] = array(
                'reference' => (string) $row['reference'],
                'date' => $dateLabel,
                'amount' => (int) $row['amount_paid'],
            );
        }

        return $ledger;
    }

    private function serviceChargeMonthsBlock(array $monthEntries, $propertyId, array $allocLedger, $currentYearStart, array $meta = array())
    {
        if ($monthEntries === array()) {
            return null;
        }

        $months = array();
        $totalRates = 0;
        $totalPaid = 0;
        $totalRemaining = 0;
        $totalArrears = 0;
        $todayStartTs = strtotime(date('Y-m-d'));
        $settledAssumed = ! empty($meta['settledAssumed']);

        foreach ($monthEntries as $entry) {
            $serviceMonth = (string) $entry['serviceMonth'];
            $rate = $this->serviceChargeRateForMonth($propertyId, $serviceMonth);
            $allocations = isset($allocLedger[$serviceMonth]) ? $allocLedger[$serviceMonth] : array();
            $paid = 0;

            foreach ($allocations as $a) {
                $paid += (int) $a['amount'];
            }

            if ($settledAssumed && $allocations === array()) {
                $paid = $rate;
            }

            $remaining = max(0, $rate - $paid);
            $monthStartTs = strtotime($serviceMonth . '-01');

            // A signed tenancy owes service charge for every month in its term, so any month left
            // unpaid counts as arrears even when that month has not arrived yet.
            $isArrears = $remaining > 0;

            $totalRates += $rate;
            $totalPaid += $paid;
            $totalRemaining += $remaining;

            if ($isArrears) {
                $totalArrears += $remaining;
            }

            $months[] = array(
                'serviceMonth' => $serviceMonth,
                'startLabel' => date('M Y', $monthStartTs),
                'startDate' => date('Y-m-01', $monthStartTs),
                'endDate' => date('Y-m-t', $monthStartTs),
                'rate' => $rate,
                'allocations' => $allocations,
                'paid' => $paid,
                'remaining' => $remaining,
                'assumed' => $settledAssumed && $allocations === array(),
                'isArrears' => $isArrears,
            );
        }

        $tenureValue = isset($meta['tenure']) ? (string) $meta['tenure'] : '';

        $years = $this->groupServiceChargeMonthsIntoYears($months);

        return array(
            'isOngoing' => ! empty($meta['isOngoing']),
            'tenure' => $tenureValue,
            'tenureLabel' => $this->tenureDisplayLabel($tenureValue),
            'startDate' => isset($meta['startDate']) ? (string) $meta['startDate'] : '',
            'endDate' => isset($meta['endDate']) ? (string) $meta['endDate'] : '',
            'status' => isset($meta['status']) ? (string) $meta['status'] : 'ended',
            'settledAssumed' => $settledAssumed,
            'serviceChargeRate' => isset($meta['serviceChargeRate']) ? (int) $meta['serviceChargeRate'] : 0,
            'months' => $months,
            'years' => $years,
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'totalArrears' => $totalArrears,
            'arrearsFromStart' => $totalArrears,
        );
    }

    private function groupServiceChargeMonthsIntoYears(array $months)
    {
        $groups = array();
        $index = 0;
        $currentYearNumber = 0;
        $currentGroup = array();

        foreach ($months as $m) {
            $yearNumber = (int) floor($index / 12) + 1;

            if ($yearNumber !== $currentYearNumber) {
                if ($currentGroup !== array()) {
                    $groups[] = $currentGroup;
                }

                $currentGroup = array();
                $currentYearNumber = $yearNumber;
            }

            $currentGroup[] = $m;
            $index++;
        }

        if ($currentGroup !== array()) {
            $groups[] = $currentGroup;
        }

        $years = array();
        $todayStartTs = strtotime(date('Y-m-d'));

        foreach ($groups as $groupId => $group) {
            $rate = 0;
            $paid = 0;
            $remaining = 0;

            foreach ($group as $gm) {
                $rate += (int) $gm['rate'];
                $paid += (int) $gm['paid'];
                $remaining += (int) $gm['remaining'];
            }

            $firstMonth = $group[0];
            $lastMonth = $group[count($group) - 1];
            $yearEndTs = strtotime((string) $lastMonth['endDate']);
            $yearIsPast = $yearEndTs !== false && $yearEndTs < $todayStartTs;

            $years[] = array(
                'yearNumber' => $groupId + 1,
                'startLabel' => isset($firstMonth['startLabel']) ? (string) $firstMonth['startLabel'] : '',
                'endLabel' => isset($lastMonth['startLabel']) ? (string) $lastMonth['startLabel'] : '',
                'yearStart' => isset($firstMonth['startDate']) ? (string) $firstMonth['startDate'] : '',
                'yearEnd' => isset($lastMonth['endDate']) ? (string) $lastMonth['endDate'] : '',
                'isArrears' => $remaining > 0 && $yearIsPast,
                'isCurrentYear' => ! $yearIsPast,
                'months' => $group,
                'rate' => $rate,
                'paid' => $paid,
                'remaining' => $remaining,
            );
        }

        return $years;
    }

    public function serviceChargeTenureBlocksForRegistration(array $registration)
    {
        $propertyId = isset($registration['propertyId']) ? (int) $registration['propertyId'] : 0;
        $unitTable = isset($registration['unitTable']) ? (string) $registration['unitTable'] : '';
        $unitId = isset($registration['unitId']) ? (int) $registration['unitId'] : 0;
        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $endValue = isset($registration['endDate']) ? trim((string) $registration['endDate']) : '';
        $startTs = $startValue !== '' ? @strtotime($startValue) : false;
        $endTs = $endValue !== '' ? @strtotime($endValue) : false;

        $empty = array(
            'tenures' => array(),
            'totalRates' => 0,
            'totalPaid' => 0,
            'totalRemaining' => 0,
            'totalArrears' => 0,
        );

        if ($startTs === false || $startTs <= 0) {
            return $empty;
        }

        if ($endTs === false || $endTs <= 0) {
            $endTs = strtotime('+11 months', strtotime(date('Y-m-01', $startTs)));
        }

        $allocLedger = $this->serviceChargeLedgerForUnit($unitTable, $unitId);
        $tenures = array();

        $monthsElapsed = $this->elapsedTenureMonths($startValue);
        $tenureMonths = $this->registrationTenureMonths($registration);
        $tenureYears = $tenureMonths > 0 ? max(1, (int) round($tenureMonths / 12)) : 0;
        $elapsedYears = $monthsElapsed > 0 ? (int) floor(($monthsElapsed - 1) / 12) : 0;

        // The signed tenancy is billed for its whole length, so the window covers every year the
        // tenant committed to rather than stopping at the months that have elapsed. Months that
        // have not arrived yet are still owed.
        $yearIndex = max($elapsedYears, $tenureYears - 1);
        $windowStartTs = strtotime(date('Y-m-01', $startTs));

        // Keep a closed tenancy inside its own dates, otherwise the month after the end date is billed.
        $windowEndTs = min($endTs, strtotime('+' . (($yearIndex + 1) * 12) . ' months', $windowStartTs) - 1);
        $currentYearStart = date('Y-m', strtotime('+' . ($yearIndex * 12) . ' months', $windowStartTs));

        $ongoing = $this->serviceChargeMonthsBlock(
            $this->serviceChargeMonthsBetween($windowStartTs, $windowEndTs),
            $propertyId,
            $allocLedger,
            $currentYearStart,
            array(
                'isOngoing' => true,
                'tenure' => isset($registration['tenure']) ? trim((string) $registration['tenure']) : '',
                'startDate' => $startValue,
                'endDate' => $endValue,
                'status' => 'active',
            )
        );

        if ($ongoing !== null) {
            $tenures[] = $ongoing;
        }

        foreach ($this->tenureHistoryForRegistration($unitTable, $unitId) as $historyRow) {
            $historyStart = isset($historyRow['startDate']) ? trim((string) $historyRow['startDate']) : '';
            $historyEnd = isset($historyRow['endDate']) ? trim((string) $historyRow['endDate']) : '';
            $hsTs = $historyStart !== '' ? @strtotime($historyStart) : false;
            $heTs = $historyEnd !== '' ? @strtotime($historyEnd) : false;

            if ($hsTs === false || $hsTs <= 0 || $heTs === false || $heTs <= 0 || $heTs < $hsTs) {
                continue;
            }

            $prior = $this->serviceChargeMonthsBlock(
                $this->serviceChargeMonthsBetween($hsTs, $heTs + 86400),
                $propertyId,
                $allocLedger,
                '',
                array(
                    'isOngoing' => false,
                    'tenure' => isset($historyRow['tenure']) ? (string) $historyRow['tenure'] : '',
                    'startDate' => $historyStart,
                    'endDate' => $historyEnd,
                    'status' => isset($historyRow['status']) ? (string) $historyRow['status'] : 'ended',
                    'settledAssumed' => true,
                    'serviceChargeRate' => isset($historyRow['serviceCharge']) ? (int) $historyRow['serviceCharge'] : 0,
                )
            );

            if ($prior !== null) {
                $tenures[] = $prior;
            }
        }

        $totalRates = 0;
        $totalPaid = 0;
        $totalRemaining = 0;
        $totalArrears = 0;

        foreach ($tenures as $tb) {
            $totalRates += (int) $tb['totalRates'];
            $totalPaid += (int) $tb['totalPaid'];
            $totalRemaining += (int) $tb['totalRemaining'];
            $totalArrears += (int) $tb['totalArrears'];
        }

        return array(
            'tenures' => $tenures,
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'totalArrears' => $totalArrears,
        );
    }

    private function rentPaymentsForUnit(array $registration)
    {
        $propertyId = (int) $registration['propertyId'];
        $unitTable = (string) $registration['unitTable'];
        $unitId = (int) $registration['unitId'];
        $scRows = $this->database->fetchAll(
            'SELECT DISTINCT payment_id FROM service_charge_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id',
            array('unit_table' => $unitTable, 'unit_id' => $unitId)
        );
        $excludeIds = array();

        foreach ($scRows as $row) {
            if ((int) $row['payment_id'] > 0) {
                $excludeIds[(int) $row['payment_id']] = true;
            }
        }

        if ($excludeIds === array()) {
            $rows = $this->database->fetchAll(
                'SELECT id, amount FROM payments WHERE property_id = :property_id AND unit_id = :unit_id ORDER BY created_at ASC, id ASC',
                array('property_id' => $propertyId, 'unit_id' => $unitId)
            );
        } else {
            $idList = implode(',', array_keys($excludeIds));
            $rows = $this->database->fetchAll(
                'SELECT id, amount FROM payments WHERE property_id = :property_id AND unit_id = :unit_id AND id NOT IN (' . $idList . ') ORDER BY created_at ASC, id ASC',
                array('property_id' => $propertyId, 'unit_id' => $unitId)
            );
        }

        $amounts = array();

        foreach ($rows as $row) {
            $amounts[] = (int) $row['amount'];
        }

        return $amounts;
    }

    private function allocateAcrossMonths(array $amounts, array $months, $capacityPerMonth)
    {
        $map = array();

        foreach ($months as $entry) {
            $map[(string) $entry['serviceMonth']] = 0;
        }

        foreach ($amounts as $amount) {
            $left = (int) $amount;

            foreach ($months as $entry) {
                if ($left <= 0) {
                    break;
                }

                $serviceMonth = (string) $entry['serviceMonth'];
                $alreadyPaid = (int) $map[$serviceMonth];
                $remainingCapacity = max(0, (int) $capacityPerMonth - $alreadyPaid);

                if ($remainingCapacity <= 0) {
                    continue;
                }

                $payment = min($left, $remainingCapacity);
                $map[$serviceMonth] = $alreadyPaid + $payment;
                $left -= $payment;
            }
        }

        return $map;
    }

    public function rentScheduleForRegistration(array $registration)
    {
        $monthlyRent = isset($registration['monthlyRent']) ? (int) $registration['monthlyRent'] : 0;
        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $endValue = isset($registration['endDate']) ? trim((string) $registration['endDate']) : '';
        $startTs = $startValue !== '' ? @strtotime($startValue) : false;
        $endTs = $endValue !== '' ? @strtotime($endValue) : false;

        $empty = array(
            'months' => array(),
            'currentYearStart' => '',
            'currentYearEnd' => '',
            'totalRates' => 0,
            'totalPaid' => 0,
            'arrears' => 0,
            'outstanding' => 0,
            'maxPayable' => 0,
            'monthlyRent' => $monthlyRent,
        );

        if ($startTs === false || $startTs <= 0) {
            return $empty;
        }

        if ($endTs === false || $endTs <= 0) {
            $endTs = strtotime('+11 months', strtotime(date('Y-m-01', $startTs)));
        }

        $monthsElapsed = $this->elapsedTenureMonths($startValue);
        $yearIndex = $monthsElapsed > 0 ? (int) floor(($monthsElapsed - 1) / 12) : 0;
        $monthEndTs = strtotime('+' . ($yearIndex * 12 + 11) . ' months', strtotime(date('Y-m-01', $startTs)));

        // A tenancy that has already run out is billed only up to the end of the tenure, otherwise
        // the month after the end date gets counted as well.
        $windowEndTs = min($endTs, $monthEndTs);
        $windowStartTs = strtotime(date('Y-m-01', $startTs));

        $monthList = $this->serviceChargeMonthsBetween($windowStartTs, $windowEndTs);
        $paidMap = $this->allocateAcrossMonths($this->rentPaymentsForUnit($registration), $monthList, $monthlyRent);
        $currentYearStart = date('Y-m', strtotime('+' . ($yearIndex * 12) . ' months', $windowStartTs));
        $todayStartTs = strtotime(date('Y-m-d'));
        $totalRates = 0;
        $totalPaid = 0;
        $outstanding = 0;
        $arrears = 0;
        $months = array();

        foreach ($monthList as $entry) {
            $serviceMonth = (string) $entry['serviceMonth'];
            $rate = $monthlyRent;
            $paid = isset($paidMap[$serviceMonth]) ? (int) $paidMap[$serviceMonth] : 0;
            $remaining = max(0, $rate - $paid);
            $isArrears = (int) $entry['startTs'] < $todayStartTs && $remaining > 0;
            $totalRates += $rate;
            $totalPaid += $paid;

            if ($remaining > 0) {
                $outstanding += $remaining;

                if ($isArrears) {
                    $arrears += $remaining;
                }
            }

            $months[] = array(
                'serviceMonth' => $serviceMonth,
                'rate' => $rate,
                'paid' => $paid,
                'remaining' => $remaining,
                'isArrears' => $isArrears,
                'isCurrentYear' => ! $isArrears,
                'startLabel' => date('M Y', strtotime($serviceMonth . '-01')),
            );
        }

        return array(
            'months' => $months,
            'yearNumber' => $yearIndex + 1,
            'currentYearStart' => $currentYearStart,
            'currentYearEnd' => date('Y-m', $windowEndTs),
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'arrears' => $arrears,
            'outstanding' => $outstanding,
            'maxPayable' => $outstanding,
            'monthlyRent' => $monthlyRent,
        );
    }

    public function rentYearsForRegistration(array $registration)
    {
        $monthlyRent = isset($registration['monthlyRent']) ? (int) $registration['monthlyRent'] : 0;
        $annualRent = $this->registrationAnnualRent($registration);
        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $endValue = isset($registration['endDate']) ? trim((string) $registration['endDate']) : '';
        $startTs = $startValue !== '' ? @strtotime($startValue) : false;
        $endTs = $endValue !== '' ? @strtotime($endValue) : false;

        $empty = array(
            'years' => array(),
            'monthlyRent' => $monthlyRent,
            'totalRates' => 0,
            'totalPaid' => 0,
            'totalRemaining' => 0,
            'totalArrears' => 0,
        );

        if ($startTs === false || $startTs <= 0) {
            return $empty;
        }

        if ($endTs === false || $endTs <= 0) {
            $endTs = strtotime('+11 months', strtotime(date('Y-m-01', $startTs)));
        }

        $paymentRows = $this->rentPaymentRecordsForUnit($registration);
        $periodAllocations = $this->rentPeriodAllocationsForUnit($registration['unitTable'], $registration['unitId']);

        $monthsElapsed = $this->elapsedTenureMonths($startValue);
        $tenureMonths = $this->registrationTenureMonths($registration);
        $tenureYears = $tenureMonths > 0 ? max(1, (int) round($tenureMonths / 12)) : 0;
        $elapsedYears = $monthsElapsed > 0 ? (int) floor(($monthsElapsed - 1) / 12) : 0;
        $yearIndex = max($elapsedYears, $tenureYears - 1);
        $currentYearStartTs = strtotime(date('Y-m-01', $startTs));
        $windowEndTs = min($endTs + 1, strtotime('+' . (($yearIndex + 1) * 12) . ' months', $currentYearStartTs));

        $years = array();
        $totalRates = 0;
        $totalPaid = 0;
        $totalRemaining = 0;
        $totalArrears = 0;
        $todayStartTs = strtotime(date('Y-m-d'));

        // Every rent year is billed at a rate fixed by the property, so the geometry of the years is
        // settled first. Payments are then spread across those years in a second pass.
        $yearMeta = array();

        for ($i = 0; $i <= $yearIndex; $i++) {
            $yearStartTs = strtotime('+' . ($i * 12) . ' months', $currentYearStartTs);
            $yearEndTs = min($windowEndTs, strtotime('+' . (($i + 1) * 12) . ' months', $currentYearStartTs) - 1);

            if ($yearStartTs > $windowEndTs) {
                break;
            }

            $monthCount = 0;
            $monthCursor = strtotime(date('Y-m-01', $yearStartTs));

            while ($monthCursor !== false && $monthCursor <= $yearEndTs) {
                $monthCount++;
                $monthCursor = @strtotime('+1 month', $monthCursor);
            }

            $monthCount = $monthCount > 0
                ? $monthCount
                : count($this->serviceChargeMonthsBetween($yearStartTs, $yearEndTs));

            $yearMeta[] = array(
                'index' => $i,
                'startTs' => $yearStartTs,
                'endTs' => $yearEndTs,
                'monthCount' => $monthCount,
                'rate' => $annualRent > 0
                    ? (int) round($annualRent * max(0, $monthCount) / 12)
                    : $monthlyRent * max(0, $monthCount),
                'periodKey' => 'y' . ($i + 1),
            );
        }

        if ($yearMeta === array()) {
            return $empty;
        }

        $allocationByYear = array();

        foreach ($yearMeta as $entry) {
            $allocationByYear[$entry['index']] = array();
        }

        foreach ($paymentRows as $row) {
            $paymentId = (int) $row['id'];

            // A payment filed against a named rent year is honoured as recorded, so a payment made
            // today against a closed year still clears that year's arrears.
            if (isset($periodAllocations[$paymentId])) {
                foreach ($periodAllocations[$paymentId] as $allocation) {
                    $amountPaid = (int) $allocation['amountPaid'];

                    if ($amountPaid <= 0) {
                        continue;
                    }

                    foreach ($yearMeta as $entry) {
                        if ($entry['periodKey'] !== (string) $allocation['periodKey']) {
                            continue;
                        }

                        $allocationByYear[$entry['index']][] = array(
                            'id' => $paymentId,
                            'amount' => $amountPaid,
                            'channel' => (string) $row['channel'],
                            'reference' => (string) $row['reference'],
                            'description' => (string) $row['description'],
                            'date' => date('d M Y', @strtotime((string) $row['created_at'])),
                        );

                        break;
                    }
                }

                continue;
            }

            $ts = @strtotime((string) $row['created_at']);

            if ($ts === false || $ts <= 0 || $ts > $windowEndTs) {
                continue;
            }

            $firstYearIndex = null;

            foreach ($yearMeta as $entry) {
                if ($ts >= $entry['startTs'] && $ts <= $entry['endTs']) {
                    $firstYearIndex = $entry['index'];
                    break;
                }
            }

            if ($firstYearIndex === null) {
                continue;
            }

            // A payment covers the year it falls in and then carries into the following years until
            // it is used up. Filing the whole amount against a single year would show that year as
            // overpaid and leave the later years looking unpaid even though the rent is settled.
            $left = (int) $row['amount'];

            foreach ($yearMeta as $entry) {
                if ($entry['index'] < $firstYearIndex || $left <= 0) {
                    continue;
                }

                $yearPaid = 0;

                foreach ($allocationByYear[$entry['index']] as $existing) {
                    $yearPaid += (int) $existing['amount'];
                }

                $applied = min($left, max(0, $entry['rate'] - $yearPaid));

                if ($applied <= 0) {
                    continue;
                }

                $allocationByYear[$entry['index']][] = array(
                    'id' => $paymentId,
                    'amount' => $applied,
                    'channel' => (string) $row['channel'],
                    'reference' => (string) $row['reference'],
                    'description' => (string) $row['description'],
                    'date' => date('d M Y', $ts),
                );

                $left -= $applied;
            }
        }

        foreach ($yearMeta as $entry) {
            $yearPayments = $allocationByYear[$entry['index']];
            $paid = 0;

            foreach ($yearPayments as $allocation) {
                $paid += (int) $allocation['amount'];
            }

            $rate = (int) $entry['rate'];
            $remaining = max(0, $rate - $paid);
            $yearIsPast = $entry['endTs'] < $todayStartTs;
            $isArrears = $remaining > 0 && $yearIsPast;

            if ($isArrears) {
                $totalArrears += $remaining;
            }

            $totalRates += $rate;
            $totalPaid += $paid;
            $totalRemaining += $remaining;

            $years[] = array(
                'yearNumber' => $entry['index'] + 1,
                'yearStart' => date('Y-m-d', $entry['startTs']),
                'yearEnd' => date('Y-m-d', $entry['endTs']),
                'monthCount' => $entry['monthCount'],
                'annualRent' => $rate,
                'paid' => $paid,
                'remaining' => $remaining,
                'isArrears' => $isArrears,
                'isCurrentYear' => ! $yearIsPast,
                'payments' => $yearPayments,
            );
        }

        return array(
            'years' => $years,
            'monthlyRent' => $monthlyRent,
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'totalRemaining' => $totalRemaining,
            'totalArrears' => $totalArrears,
        );
    }

    private function rentPaymentRecordsForUnit(array $registration)
    {
        $propertyId = (int) $registration['propertyId'];
        $unitTable = (string) $registration['unitTable'];
        $unitId = (int) $registration['unitId'];
        $scRows = $this->database->fetchAll(
            'SELECT DISTINCT payment_id FROM service_charge_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id',
            array('unit_table' => $unitTable, 'unit_id' => $unitId)
        );
        $excludeIds = array();

        foreach ($scRows as $row) {
            if ((int) $row['payment_id'] > 0) {
                $excludeIds[(int) $row['payment_id']] = true;
            }
        }

        if ($excludeIds === array()) {
            return $this->database->fetchAll(
                'SELECT id, amount, channel, reference, description, created_at FROM payments WHERE property_id = :property_id AND unit_id = :unit_id ORDER BY created_at ASC, id ASC',
                array('property_id' => $propertyId, 'unit_id' => $unitId)
            );
        }

        $idList = implode(',', array_keys($excludeIds));

        return $this->database->fetchAll(
            'SELECT id, amount, channel, reference, description, created_at FROM payments WHERE property_id = :property_id AND unit_id = :unit_id AND id NOT IN (' . $idList . ') ORDER BY created_at ASC, id ASC',
            array('property_id' => $propertyId, 'unit_id' => $unitId)
        );
    }

    /**
     * Payments recorded against a named rent period, keyed by payment id. A payment recorded today
     * against a year that has already closed is filed under that year, so it clears those arrears
     * instead of landing in whichever year today's date falls in.
     */
    private function rentPeriodAllocationsForUnit($unitTable, $unitId)
    {
        $rows = $this->database->fetchAll(
            'SELECT payment_id, period_key, period_start, period_end, amount_paid FROM rent_period_allocations WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY id ASC',
            array('unit_table' => (string) $unitTable, 'unit_id' => (int) $unitId)
        );

        $byPayment = array();

        foreach ($rows as $row) {
            $paymentId = (int) $row['payment_id'];

            if ($paymentId <= 0) {
                continue;
            }

            if (! isset($byPayment[$paymentId])) {
                $byPayment[$paymentId] = array();
            }

            $byPayment[$paymentId][] = array(
                'periodKey' => (string) $row['period_key'],
                'periodStart' => $row['period_start'] !== null ? (string) $row['period_start'] : '',
                'periodEnd' => $row['period_end'] !== null ? (string) $row['period_end'] : '',
                'amountPaid' => (int) $row['amount_paid'],
            );
        }

        return $byPayment;
    }

    /**
     * The rent periods an admin can post a payment against: arrears carried in from an earlier
     * tenure, then each year of the current tenure with what is billed, paid, and still owing.
     */
    public function rentPeriodsForRegistration(array $registration)
    {
        $yearsData = $this->rentYearsForRegistration($registration);
        $breakdown = $this->registrationBalanceBreakdown($registration);
        $periods = array();
        $priorArrears = (int) $breakdown['priorArrears'];

        if ($priorArrears > 0) {
            $carriedOver = (int) $breakdown['carriedOver'];
            $periods[] = array(
                'key' => 'prior',
                'label' => 'Arrears carried from an earlier period',
                'shortLabel' => 'Earlier period',
                'startDate' => '',
                'endDate' => '',
                'billed' => $carriedOver,
                'paid' => max(0, $carriedOver - $priorArrears),
                'remaining' => $priorArrears,
                'isArrears' => true,
                'isCurrent' => false,
                'isSettled' => false,
                'isPrior' => true,
            );
        }

        foreach ((array) $yearsData['years'] as $year) {
            $remaining = (int) $year['remaining'];
            $periods[] = array(
                'key' => 'y' . (int) $year['yearNumber'],
                'label' => 'Year ' . (int) $year['yearNumber'] . ' (' . $this->shortDate($year['yearStart']) . ' to ' . $this->shortDate($year['yearEnd']) . ')',
                'shortLabel' => 'Year ' . (int) $year['yearNumber'],
                'startDate' => (string) $year['yearStart'],
                'endDate' => (string) $year['yearEnd'],
                'billed' => (int) $year['annualRent'],
                'paid' => (int) $year['paid'],
                'remaining' => $remaining,
                'isArrears' => ! empty($year['isArrears']),
                'isCurrent' => ! empty($year['isCurrentYear']),
                'isSettled' => $remaining <= 0,
                'isPrior' => false,
            );
        }

        return array(
            'periods' => $periods,
            'totalBilled' => (int) $yearsData['totalRates'] + $priorArrears,
            'totalPaid' => (int) $yearsData['totalPaid'],
            'totalRemaining' => (int) $breakdown['totalOwed'],
        );
    }

    /**
     * The service charge billing periods an admin can post a payment against, oldest first.
     */
    public function serviceChargePeriodsForRegistration(array $registration)
    {
        $summary = $this->serviceChargeSummaryForRegistration($registration);
        $groups = array();
        $order = array();
        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $startTs = $startValue !== '' ? strtotime($startValue) : false;
        $windowStartTs = $startTs !== false && $startTs > 0 ? strtotime(date('Y-m-01', $startTs)) : 0;
        $priorTenures = $this->priorServiceChargeTenures($registration);
        $priorWindowStartTs = 0;
        $priorWindowEndTs = 0;

        foreach ($priorTenures as $priorTenure) {
            if ($priorWindowStartTs === 0 || $priorTenure['startTs'] < $priorWindowStartTs) {
                $priorWindowStartTs = $priorTenure['startTs'];
            }

            if ($priorTenure['endTs'] > $priorWindowEndTs) {
                $priorWindowEndTs = $priorTenure['endTs'];
            }
        }

        foreach ((array) $summary['months'] as $month) {
            $monthValue = (string) $month['serviceMonth'];
            $monthTs = $monthValue !== '' ? strtotime($monthValue . '-01') : false;

            if ($monthValue === '' || $monthTs === false) {
                continue;
            }

            $fromEarlierTenure = false;

            foreach ($priorTenures as $priorTenure) {
                if ($monthTs >= $priorTenure['startTs'] && $monthTs <= $priorTenure['endTs']) {
                    $fromEarlierTenure = true;
                    break;
                }
            }

            if ($fromEarlierTenure || $windowStartTs <= 0 || $monthTs < $windowStartTs) {
                $periodKey = 'prior';
                $periodIndex = 0;
            } else {
                $monthGap = (((int) date('Y', $monthTs)) - (int) date('Y', $windowStartTs)) * 12
                    + ((int) date('n', $monthTs)) - (int) date('n', $windowStartTs);
                $periodIndex = (int) floor($monthGap / 12);
                $periodKey = 'y' . ($periodIndex + 1);
            }

            if (! isset($groups[$periodKey])) {
                if ($periodKey === 'prior' && $priorWindowStartTs > 0) {
                    $windowStart = strtotime(date('Y-m-01', $priorWindowStartTs));
                    $windowEnd = strtotime(date('Y-m-t', $priorWindowEndTs));
                } else {
                    $windowStart = strtotime('+' . ($periodIndex * 12) . ' months', $windowStartTs);
                    $windowEnd = strtotime('+' . 11 . ' months', $windowStart);
                }
                $groups[$periodKey] = array(
                    'key' => $periodKey,
                    'label' => $periodKey === 'prior' ? 'Earlier period' : 'Year ' . ($periodIndex + 1),
                    'shortLabel' => $periodKey === 'prior' ? 'Arrears' : 'Year ' . ($periodIndex + 1),
                    'startDate' => date('Y-m-d', $windowStart),
                    'endDate' => date('Y-m-t', $windowEnd),
                    'windowStart' => date('Y-m', $windowStart),
                    'windowEnd' => date('Y-m', $windowEnd),
                    'billed' => 0,
                    'paid' => 0,
                    'remaining' => 0,
                    'isArrears' => false,
                    'isCurrent' => false,
                    'isSettled' => true,
                    'isPrior' => $periodKey === 'prior',
                );
                $order[] = $periodKey;
            }

            $groups[$periodKey]['billed'] += (int) $month['rate'];
            $groups[$periodKey]['paid'] += (int) $month['paid'];
            $groups[$periodKey]['remaining'] += (int) $month['remaining'];
            $groups[$periodKey]['isArrears'] = $groups[$periodKey]['isArrears'] || ! empty($month['isArrears']);
        }

        $periods = array();

        foreach ($order as $periodKey) {
            $group = $groups[$periodKey];
            $group['isCurrent'] = $group['key'] !== 'prior' && $group['windowStart'] === (string) $summary['currentYearStart'];
            $group['isSettled'] = $group['remaining'] <= 0;
            $group['startDate'] = $this->shortDate($group['startDate']);
            $group['endDate'] = $this->shortDate($group['endDate']);
            $periods[] = $group;
        }

        return array(
            'periods' => $periods,
            'totalBilled' => (int) $summary['totalRates'],
            'totalPaid' => (int) $summary['totalPaid'],
            'totalRemaining' => (int) $summary['outstanding'],
        );
    }

    /**
     * The tenure year a service charge month falls in, so a payment can be shown against the same
     * period it was allocated to.
     */
    private function serviceChargePeriodKeyForMonth(array $registration, $monthValue)
    {
        $monthValue = trim((string) $monthValue);

        if ($monthValue === '') {
            return '';
        }

        $monthTs = strtotime($monthValue . '-01');

        if ($monthTs === false) {
            return '';
        }

        $startValue = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $startTs = $startValue !== '' ? strtotime($startValue) : false;

        foreach ($this->priorServiceChargeTenures($registration) as $priorTenure) {
            if ($monthTs >= $priorTenure['startTs'] && $monthTs <= $priorTenure['endTs']) {
                return 'prior';
            }
        }

if ($startTs === false || $startTs <= 0) {
            return 'prior';
        }

        $windowStartTs = strtotime(date('Y-m-01', $startTs));

        if ($monthTs < $windowStartTs) {
            return 'prior';
        }

        $monthGap = (((int) date('Y', $monthTs)) - (int) date('Y', $windowStartTs)) * 12
            + ((int) date('n', $monthTs)) - (int) date('n', $windowStartTs);

        return 'y' . ((int) floor($monthGap / 12) + 1);
    }

    /**
     * Month by month service charge periods. Each month can be paid, adjusted or cleared on its own
     * from the breakdown editor.
     */
    public function serviceChargeMonthPeriodsForRegistration(array $registration)
    {
        $summary = $this->serviceChargeSummaryForRegistration($registration);
        $periods = array();

        foreach ((array) $summary['months'] as $month) {
            $key = (string) $month['serviceMonth'];

            if ($key === '') {
                continue;
            }

            $monthTs = strtotime($key . '-01');
            $periods[] = array(
                'key' => $key,
                'label' => date('M Y', $monthTs),
                'shortLabel' => date('M Y', $monthTs),
                'startDate' => date('Y-m-d', $monthTs),
                'endDate' => date('Y-m-t', $monthTs),
                'windowStart' => $key,
                'windowEnd' => $key,
                'billed' => (int) $month['rate'],
                'paid' => (int) $month['paid'],
                'remaining' => (int) $month['remaining'],
                'isArrears' => ! empty($month['isArrears']),
                'isCurrent' => ! empty($month['isCurrentYear']),
                'isSettled' => (int) $month['remaining'] <= 0,
                'isPrior' => false,
            );
        }

        return array(
            'periods' => $periods,
            'totalBilled' => (int) $summary['totalRates'],
            'totalPaid' => (int) $summary['totalPaid'],
            'totalRemaining' => (int) $summary['outstanding'],
        );
    }

    /**
     * The rent payments already allocated to each rent period, so the breakdown editor can list,
     * adjust or remove them without hunting through the whole history.
     */
    public function rentPeriodPaymentsForRegistration(array $registration)
    {
        $groups = array();
        $allocated = array();

        foreach ((array) $this->database->fetchAll(
            'SELECT r.payment_id, r.period_key, p.amount, p.channel, p.description, p.reference, p.created_at
             FROM rent_period_allocations r
             INNER JOIN payments p ON p.id = r.payment_id
             WHERE r.unit_table = :unit_table AND r.unit_id = :unit_id
             ORDER BY p.created_at DESC, r.payment_id DESC',
            array('unit_table' => (string) $registration['unitTable'], 'unit_id' => (int) $registration['unitId'])
        ) as $row) {
            $groupKey = trim((string) $row['period_key']) !== '' ? (string) $row['period_key'] : 'unallocated';

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = array();
            }

            $groups[$groupKey][] = array(
                'paymentId' => (int) $row['payment_id'],
                'amount' => (int) $row['amount'],
                'channel' => (string) $row['channel'],
                'description' => (string) $row['description'],
                'reference' => (string) $row['reference'],
                'date' => substr((string) $row['created_at'], 0, 10),
            );

            $allocated[(int) $row['payment_id']] = true;
        }

        // Payments taken before periods were tracked still belong on the breakdown, so they are
        // grouped under the year they cleared instead of disappearing from the admin's view.
        $classifications = $this->paymentClassificationsForRegistration($registration);

        foreach ((array) $registration['payments'] as $payment) {
            $paymentId = (int) $payment['id'];

            if ($paymentId === 0 || isset($allocated[$paymentId])) {
                continue;
            }

            if (! isset($classifications[$paymentId]) || $classifications[$paymentId]['type'] !== 'rent') {
                continue;
            }

            $periodKey = trim((string) $classifications[$paymentId]['periodKey']);
            $groupKey = $periodKey !== '' ? $periodKey : 'unallocated';

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = array();
            }

            $groups[$groupKey][] = array(
                'paymentId' => $paymentId,
                'amount' => (int) $payment['amount'],
                'channel' => (string) $payment['channel'],
                'description' => (string) $payment['description'],
                'reference' => (string) $payment['reference'],
                'date' => substr((string) $payment['createdAt'], 0, 10),
            );
        }

        return $groups;
    }

    /**
     * The service charge payments allocated to each month, with the part of the payment that covers
     * that month.
     */
    public function serviceChargeMonthPaymentsForRegistration(array $registration)
    {
        $groups = array();

        foreach ((array) $this->database->fetchAll(
            'SELECT a.service_month, a.amount_paid, p.id AS payment_id, p.channel, p.description, p.reference, p.created_at
             FROM service_charge_allocations a
             INNER JOIN payments p ON p.id = a.payment_id
             WHERE a.unit_table = :unit_table AND a.unit_id = :unit_id
             ORDER BY a.service_month ASC, p.created_at ASC',
            array('unit_table' => (string) $registration['unitTable'], 'unit_id' => (int) $registration['unitId'])
        ) as $row) {
            $monthKey = (string) $row['service_month'];

            if ($monthKey === '') {
                continue;
            }

            if (! isset($groups[$monthKey])) {
                $groups[$monthKey] = array();
            }

            $groups[$monthKey][] = array(
                'paymentId' => (int) $row['payment_id'],
                'amount' => (int) $row['amount_paid'],
                'channel' => (string) $row['channel'],
                'description' => (string) $row['description'],
                'reference' => (string) $row['reference'],
                'date' => substr((string) $row['created_at'], 0, 10),
            );
        }

        return $groups;
    }

    /**
     * Returns the chosen service charge period together with the months inside its window.
     */
    private function serviceChargePeriodMonthsForRegistration(array $registration, $periodKey)
    {
        $period = null;
        $months = array();

        foreach ((array) $this->serviceChargePeriodsForRegistration($registration)['periods'] as $candidate) {
            if ((string) $candidate['key'] === (string) $periodKey) {
                $period = $candidate;
                break;
            }
        }

        // A single month can be paid on its own from the breakdown, so month keys are looked up too.
        if (! $period && preg_match('/^\d{4}-\d{2}$/', (string) $periodKey)) {
            foreach ((array) $this->serviceChargeMonthPeriodsForRegistration($registration)['periods'] as $candidate) {
                if ((string) $candidate['key'] === (string) $periodKey) {
                    $period = $candidate;
                    break;
                }
            }
        }

        if (! $period) {
            return array(null, array());
        }

        foreach ((array) $this->serviceChargeSummaryForRegistration($registration)['months'] as $month) {
            $monthKey = substr((string) $month['serviceMonth'], 0, 7);

            if ($monthKey >= $period['windowStart'] && $monthKey <= $period['windowEnd']) {
                $months[] = $month;
            }
        }

        return array($period, $months);
    }

    private function shortDate($value)
    {
        $value = trim((string) $value);
        $ts = $value !== '' ? @strtotime($value) : false;

        return $ts !== false && $ts > 0 ? date('d M Y', $ts) : $value;
    }

    public function priorTenureBreakdown(array $historyRow)
    {
        $monthlyRent = isset($historyRow['monthlyRent']) ? (int) $historyRow['monthlyRent'] : 0;
        $startValue = isset($historyRow['startDate']) ? trim((string) $historyRow['startDate']) : '';
        $endValue = isset($historyRow['endDate']) ? trim((string) $historyRow['endDate']) : '';
        $startTs = $startValue !== '' ? @strtotime($startValue) : false;
        $endTs = $endValue !== '' ? @strtotime($endValue) : false;

        $empty = array(
            'months' => array(),
            'totalRates' => 0,
            'totalPaid' => 0,
            'outstanding' => 0,
            'arrears' => 0,
            'billedAmount' => isset($historyRow['amountDue']) ? (int) $historyRow['amountDue'] : 0,
            'paidAmount' => isset($historyRow['amountPaid']) ? (int) $historyRow['amountPaid'] : 0,
            'balanceCarried' => isset($historyRow['balanceCarried']) ? (int) $historyRow['balanceCarried'] : 0,
            'serviceChargeRate' => isset($historyRow['serviceCharge']) ? (int) $historyRow['serviceCharge'] : 0,
            'monthlyRent' => $monthlyRent,
            'tenure' => isset($historyRow['tenure']) ? (string) $historyRow['tenure'] : '',
            'startDate' => $startValue,
            'endDate' => $endValue,
            'status' => isset($historyRow['status']) ? (string) $historyRow['status'] : 'ended',
        );

        if ($monthlyRent <= 0 || $startTs === false || $startTs <= 0 || $endTs === false || $endTs <= 0 || $endTs < $startTs) {
            return $empty;
        }

        $monthList = $this->serviceChargeMonthsBetween($startTs, $endTs + 86400);
        $allocated = (int) $empty['paidAmount'];
        $months = array();
        $totalRates = 0;
        $totalPaid = 0;
        $outstanding = 0;

        foreach ($monthList as $entry) {
            $serviceMonth = (string) $entry['serviceMonth'];
            $rate = $monthlyRent;
            $paid = min($rate, max(0, $allocated));
            $remaining = max(0, $rate - $paid);
            $allocated -= $paid;
            $totalRates += $rate;
            $totalPaid += $paid;
            $outstanding += $remaining;
            $months[] = array(
                'serviceMonth' => $serviceMonth,
                'rate' => $rate,
                'paid' => $paid,
                'remaining' => $remaining,
                'isArrears' => false,
                'isCurrentYear' => false,
                'startLabel' => date('M Y', strtotime($serviceMonth . '-01')),
            );
        }

        return array(
            'months' => $months,
            'totalRates' => $totalRates,
            'totalPaid' => $totalPaid,
            'outstanding' => $outstanding,
            'arrears' => $outstanding,
            'billedAmount' => (int) $empty['billedAmount'],
            'paidAmount' => (int) $empty['paidAmount'],
            'balanceCarried' => (int) $empty['balanceCarried'],
            'serviceChargeRate' => (int) $empty['serviceChargeRate'],
            'monthlyRent' => $monthlyRent,
            'tenure' => (string) $empty['tenure'],
            'startDate' => $startValue,
            'endDate' => $endValue,
            'status' => (string) $empty['status'],
        );
    }

    public function tenureBlocksForRegistration(array $registration)
    {
        $ongoing = $this->currentTenureYearsBlock($registration);

        if ($ongoing === null) {
            return array('tenures' => array(), 'totalRates' => 0, 'totalPaid' => 0, 'totalRemaining' => 0, 'totalArrears' => 0);
        }

        $tenures = array($ongoing);

        foreach ($this->tenureHistoryForRegistration($registration['unitTable'], $registration['unitId']) as $historyRow) {
            $tenures[] = $this->previousTenureYearsBlock($historyRow);
        }

        return array(
            'tenures' => $tenures,
            'totalRates' => (int) $ongoing['totalRates'],
            'totalPaid' => (int) $ongoing['totalPaid'],
            'totalRemaining' => (int) $ongoing['totalRemaining'],
            'totalArrears' => (int) $ongoing['totalArrears'],
        );
    }

    public function currentTenureYearsBlock(array $registration)
    {
        $yearsData = $this->rentYearsForRegistration($registration);

        if ($yearsData['years'] === array() || $yearsData['totalRates'] <= 0) {
            return null;
        }

        $tenureValue = isset($registration['tenure']) ? trim((string) $registration['tenure']) : '';
        $startDate = isset($registration['startDate']) ? trim((string) $registration['startDate']) : '';
        $endDate = isset($registration['endDate']) ? trim((string) $registration['endDate']) : '';

        return array(
            'isOngoing' => true,
            'label' => 'Current tenancy',
            'tenure' => $tenureValue,
            'tenureLabel' => $this->tenureDisplayLabel($tenureValue),
            'periodLabel' => $this->tenurePeriodLabel($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => 'active',
            'years' => $yearsData['years'],
            'totalRates' => $yearsData['totalRates'],
            'totalPaid' => $yearsData['totalPaid'],
            'totalRemaining' => $yearsData['totalRemaining'],
            'totalArrears' => $yearsData['totalArrears'],
            'arrearsFromStart' => $yearsData['totalArrears'],
        );
    }

    public function previousTenureYearsBlock(array $historyRow)
    {
        $bd = $this->priorTenureBreakdown($historyRow);
        $tenureValue = isset($bd['tenure']) ? trim((string) $bd['tenure']) : '';

        return array(
            'isOngoing' => false,
            'label' => 'Previous tenancy',
            'tenure' => $tenureValue,
            'tenureLabel' => $this->tenureDisplayLabel($tenureValue),
            'periodLabel' => $this->tenurePeriodLabel($bd['startDate'], $bd['endDate']),
            'startDate' => $bd['startDate'],
            'endDate' => $bd['endDate'],
            'status' => $bd['status'] !== '' ? $bd['status'] : 'ended',
            'years' => $this->groupMonthsIntoYears($bd['months']),
            'totalRates' => $bd['totalRates'],
            'totalPaid' => $bd['totalPaid'],
            'totalRemaining' => $bd['outstanding'],
            'totalArrears' => $bd['arrears'],
            'arrearsFromStart' => $bd['arrears'],
            'balanceCarried' => $bd['balanceCarried'],
            'serviceChargeRate' => $bd['serviceChargeRate'],
        );
    }

    private function groupMonthsIntoYears(array $months)
    {
        $years = array();

        if ($months === array()) {
            return $years;
        }

        $startTs = strtotime(date('Y-m-01', strtotime($months[0]['serviceMonth'] . '-01')));
        $lastTs = strtotime(date('Y-m-01', strtotime($months[count($months) - 1]['serviceMonth'] . '-01')));
        $yearIndex = 0;

        while ($startTs <= $lastTs && $yearIndex < 120) {
            $yearStartTs = strtotime('+' . ($yearIndex * 12) . ' months', $startTs);
            $yearEndTs = min($lastTs, strtotime('+' . (($yearIndex + 1) * 12) . ' months', $startTs) - 1);
            $yearMonths = array();
            $rate = 0;
            $paid = 0;
            $remaining = 0;

            foreach ($months as $m) {
                $mTs = strtotime($m['serviceMonth'] . '-01');

                if ($mTs >= $yearStartTs && $mTs <= $yearEndTs) {
                    $yearMonths[] = $m;
                    $rate += (int) $m['rate'];
                    $paid += (int) $m['paid'];
                    $remaining += (int) $m['remaining'];
                }
            }

            if ($yearMonths !== array()) {
                $years[] = array(
                    'yearNumber' => $yearIndex + 1,
                    'yearStart' => date('Y-m-d', $yearStartTs),
                    'yearEnd' => date('Y-m-d', $yearEndTs),
                    'annualRent' => $rate,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'isArrears' => false,
                    'isCurrentYear' => false,
                    'months' => $yearMonths,
                );
            }

            $yearIndex++;
        }

        $todayStartTs = strtotime(date('Y-m-d'));

        foreach ($years as $k => $year) {
            $yearEndTs = strtotime($year['yearEnd'] . ' 23:59:59');
            $isPast = $yearEndTs < $todayStartTs;
            $years[$k]['isArrears'] = $isPast && $year['remaining'] > 0;
            $years[$k]['isCurrentYear'] = ! $isPast;
        }

        return $years;
    }

    public function tenurePeriodLabel($startDate, $endDate)
    {
        $startValue = trim((string) $startDate);
        $endValue = trim((string) $endDate);

        if ($startValue === '' && $endValue === '') {
            return '';
        }

        // The tenancy period is the real length of the term, so show the dates rather
        // than a running "Tenure 1, Tenure 2" count that leaves gaps between renewals.
        $range = $startValue !== '' && $endValue !== ''
            ? $startValue . ' → ' . $endValue
            : ($startValue !== '' ? 'From ' . $startValue : 'Until ' . $endValue);

        $months = 0;

        if ($startValue !== '' && $endValue !== '') {
            $startTs = @strtotime($startValue);
            $endTs = @strtotime($endValue);

            if ($startTs !== false && $endTs !== false && $endTs > $startTs) {
                $months = (int) round(($endTs - $startTs) / 2629800);
            }
        }

        if ($months > 0 && $months % 12 === 0) {
            $years = (int) ($months / 12);

            return $range . ' (' . $years . ' year' . ($years === 1 ? '' : 's') . ')';
        }

        if ($months > 0) {
            return $range . ' (' . $months . ' months)';
        }

        return $range;
    }

    private function tenureDisplayLabel($tenureValue)
    {
        $tenureValue = trim((string) $tenureValue);

        if ($tenureValue === '') {
            return '';
        }

        $matches = array();

        if (preg_match('/^(\d+)\s*months?$/i', $tenureValue, $matches) && ((int) $matches[1]) % 12 === 0 && (int) $matches[1] > 0) {
            $years = (int) $matches[1] / 12;

            return $years . ($years === 1 ? ' year' : ' years');
        }

        return $tenureValue;
    }

    private function unitSelectWithUser($unitTable)
    {
        return 'SELECT `' . $unitTable . '`.*, `u`.`name` AS `unit_user_name`, `u`.`email` AS `unit_user_email`, `u`.`phone` AS `unit_user_phone`
                FROM `' . $unitTable . '`
                LEFT JOIN `users` `u` ON `u`.`id` = `' . $unitTable . '`.`user_id`';
    }

    public function propertyUnits($propertyId, $page = 1, $perPage = 25)
    {
        $property = $this->findProperty((int) $propertyId);

        if (! $property) {
            return array('property' => null, 'units' => array(), 'unitTable' => '', 'totalUnits' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 1);
        }

        $unitTable = $this->unitTableForProperty($property);
        $totalUnits = (int) $this->database->fetchValue(
            'SELECT COUNT(*) FROM `' . $unitTable . '` WHERE `property_id` = :property_id',
            array('property_id' => (int) $property['id'])
        );

        $perPage = max(1, (int) $perPage);
        $totalPages = max(1, (int) ceil($totalUnits / $perPage));
        $page = max(1, min((int) $page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $rows = $this->database->fetchAll(
            $this->unitSelectWithUser($unitTable) . ' WHERE `' . $unitTable . '`.`property_id` = :property_id ORDER BY `' . $unitTable . '`.`id` ASC LIMIT :limit OFFSET :offset',
            array('property_id' => (int) $property['id'], 'limit' => $perPage, 'offset' => $offset)
        );

        return array(
            'property' => $property,
            'units' => $this->hydrateUnits($unitTable, $rows),
            'unitTable' => $unitTable,
            'totalUnits' => $totalUnits,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
        );
    }

    public function findPropertyUnit($propertyId, $unitId)
    {
        $propertyId = (int) $propertyId;
        $unitId = (int) $unitId;

        if ($propertyId <= 0 || $unitId <= 0) {
            return null;
        }

        $property = $this->findProperty($propertyId);

        if (! $property) {
            return null;
        }

        $unitTable = $this->unitTableForProperty($property);
        $row = $this->database->fetchOne(
            $this->unitSelectWithUser($unitTable) . ' WHERE `' . $unitTable . '`.`id` = :id AND `' . $unitTable . '`.`property_id` = :property_id LIMIT 1',
            array('id' => $unitId, 'property_id' => $propertyId)
        );

        if (! $row) {
            return null;
        }

        return $this->hydrateUnit($unitTable, $row);
    }

    public function savePropertyUnitByAdmin($propertyId, $unitId, array $payload, $adminUser)
    {
        $propertyId = (int) $propertyId;
        $unitId = (int) $unitId;
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'That property could not be found.');
        }

        $unitTable = $this->unitTableForProperty($property);

        $userId = (int) $this->normalizeInteger(isset($payload['user_id']) ? $payload['user_id'] : 0);

        if ($userId <= 0) {
            return array(false, 'Choose the tenant occupying this ' . ($unitTable === 'mall_shops' ? 'shop' : ($unitTable === 'apartment_flats' ? 'flat' : 'unit')) . '.');
        }

        $unitUser = $this->findUserById($userId);

        if (! $unitUser) {
            return array(false, 'The selected tenant account could not be found.');
        }

        $shopNumber = trim(isset($payload['shop_number']) ? $payload['shop_number'] : '');
        $shopName = trim(isset($payload['shop_name']) ? $payload['shop_name'] : '');
        $block = trim(isset($payload['block']) ? $payload['block'] : '');
        $floor = trim(isset($payload['floor']) ? $payload['floor'] : '');
        $unitNumber = trim(isset($payload['unit_number']) ? $payload['unit_number'] : '');
        $flatNumber = trim(isset($payload['flat_number']) ? $payload['flat_number'] : '');
        $status = trim(isset($payload['status']) ? $payload['status'] : 'active');
        $tenure = trim(isset($payload['tenure']) ? $payload['tenure'] : '');
        $monthlyRent = $this->extractAmount(isset($payload['monthly_rent']) ? $payload['monthly_rent'] : 0);
        $serviceCharge = $this->extractAmount(isset($payload['service_charge']) ? $payload['service_charge'] : 0);
        $notes = trim(isset($payload['notes']) ? $payload['notes'] : '');
        $now = $this->now();

        if (! in_array($status, array('active', 'inactive', 'vacant', 'ended'), true)) {
            $status = 'active';
        }

        $data = array(
            'user_id' => $userId,
            'status' => $status,
            'tenure' => $tenure,
            'monthly_rent' => $monthlyRent,
            'service_charge' => $serviceCharge,
            'notes' => $notes !== '' ? $notes : null,
            'updated_at' => $now,
        );

        if ($unitTable === 'mall_shops') {
            $data['shop_number'] = $shopNumber;
            $data['shop_name'] = $shopName;
        } elseif ($unitTable === 'apartment_flats') {
            $data['block'] = $block;
            $data['floor'] = $floor;
            $data['flat_number'] = $flatNumber;
        } else {
            $data['block'] = $block;
            $data['unit_number'] = $unitNumber;
        }

        if ($unitId <= 0) {
            $data['property_id'] = $propertyId;
            $data['created_at'] = $now;
            $unitId = $this->database->insert($unitTable, $data);
        } else {
            $this->database->execute(
                'UPDATE `' . $unitTable . '` SET ' . $this->buildUnitUpdateSet($data) . ' WHERE id = :id AND property_id = :property_id',
                array_merge($data, array('id' => $unitId, 'property_id' => $propertyId))
            );
        }

        $unit = $this->findPropertyUnit($propertyId, $unitId);

        if ($unit) {
            $this->syncUnitHistory($unitTable, $unit);
        }

        return array($unit, $unit ? null : 'The unit could not be saved.');
    }

    public function deletePropertyUnitByAdmin($propertyId, $unitId)
    {
        $propertyId = (int) $propertyId;
        $unitId = (int) $unitId;
        $property = $this->findProperty($propertyId);

        if (! $property) {
            return array(false, 'That property could not be found.');
        }

        $unitTable = $this->unitTableForProperty($property);
        $unit = $this->findPropertyUnit($propertyId, $unitId);

        if (! $unit) {
            return array(false, 'That unit could not be found.');
        }

        $this->syncUnitHistory($unitTable, array_merge($unit, array('status' => 'ended')));

        $this->database->execute(
            'DELETE FROM `' . $unitTable . '` WHERE id = :id AND property_id = :property_id',
            array('id' => $unitId, 'property_id' => $propertyId)
        );

        return array($unit, null);
    }

    private function buildUnitUpdateSet(array $data)
    {
        $set = array();

        foreach (array_keys($data) as $column) {
            $set[] = '`' . str_replace('`', '', $column) . '` = :' . $column;
        }

        return implode(', ', $set);
    }

    private function hydrateUnits($unitTable, array $rows)
    {
        $units = array();

        foreach ($rows as $row) {
            $units[] = $this->hydrateUnit($unitTable, $row);
        }

        return $units;
    }

    private function hydrateUnit($unitTable, array $row)
    {
        $userName = array_key_exists('unit_user_name', $row) ? trim((string) $row['unit_user_name']) : '';
        $userEmail = array_key_exists('unit_user_email', $row) ? trim((string) $row['unit_user_email']) : '';
        $userPhone = array_key_exists('unit_user_phone', $row) ? trim((string) $row['unit_user_phone']) : '';
        $unit = array(
            'id' => (int) $row['id'],
            'propertyId' => (int) $row['property_id'],
            'userId' => (int) $row['user_id'],
            'ownerName' => $userName,
            'ownerPhone' => $userPhone,
            'ownerEmail' => $userEmail,
            'status' => (string) $row['status'],
            'tenure' => (string) $row['tenure'],
            'startDate' => isset($row['start_date']) ? (string) $row['start_date'] : '',
            'endDate' => isset($row['end_date']) ? (string) $row['end_date'] : '',
            'monthlyRent' => (int) $row['monthly_rent'],
            'serviceCharge' => (int) $row['service_charge'],
            'securityDeposit' => isset($row['security_deposit']) ? (int) $row['security_deposit'] : 0,
            'notes' => isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : '',
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) $row['updated_at'],
        );

        if ($unitTable === 'mall_shops') {
            $unit['shopNumber'] = (string) $row['shop_number'];
            $unit['shopName'] = (string) $row['shop_name'];
        } elseif ($unitTable === 'apartment_flats') {
            $unit['block'] = (string) $row['block'];
            $unit['floor'] = (string) $row['floor'];
            $unit['flatNumber'] = (string) $row['flat_number'];
        } else {
            $unit['block'] = (string) $row['block'];
            $unit['unitNumber'] = (string) $row['unit_number'];
        }

        return $unit;
    }

    public function unitDisplayLabel($unitTable, array $unit)
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : 'residential_units';

        if ($unitTable === 'mall_shops') {
            $name = trim((string) (isset($unit['shopName']) ? $unit['shopName'] : ''));
            $number = trim((string) (isset($unit['shopNumber']) ? $unit['shopNumber'] : ''));

            if ($name === '' && $number !== '') {
                $name = 'Shop ' . $number;
            } elseif ($name !== '' && $number !== '') {
                $name = $name . ' (' . $number . ')';
            }

            return $name !== '' ? $name : 'Shop #' . $unit['id'];
        }

        if ($unitTable === 'apartment_flats') {
            $label = trim((string) (isset($unit['flatNumber']) ? $unit['flatNumber'] : ''));
            $block = trim((string) (isset($unit['block']) ? $unit['block'] : ''));

            if ($label === '') {
                $label = 'Flat #' . $unit['id'];
            }

            return $block !== '' ? $label . ' · ' . $block : $label;
        }

        $label = trim((string) (isset($unit['unitNumber']) ? $unit['unitNumber'] : ''));
        $block = trim((string) (isset($unit['block']) ? $unit['block'] : ''));

        if ($label === '') {
            $label = 'Unit #' . $unit['id'];
        }

        return $block !== '' ? $label . ' · ' . $block : $label;
    }

    private function registrationTenureMonths(array $registration)
    {
        // The tenancy dates say how long the term really runs, which matters because the tenure
        // field was stored in years by the registration form but in months by older records.
        $startDate = trim(isset($registration['startDate']) ? (string) $registration['startDate'] : '');
        $endDate = trim(isset($registration['endDate']) ? (string) $registration['endDate'] : '');

        if ($startDate !== '' && $endDate !== '') {
            $startTimestamp = @strtotime($startDate);
            $endTimestamp = @strtotime($endDate);

            if ($startTimestamp !== false && $endTimestamp !== false && $endTimestamp > $startTimestamp) {
                $months = (int) round(($endTimestamp - $startTimestamp) / 2629800);

                if ($months > 0) {
                    return $months;
                }
            }
        }

        return $this->tenureMonths(isset($registration['tenure']) ? (string) $registration['tenure'] : '');
    }

    private function tenureMonths($tenure)
    {        $tenure = strtolower(trim((string) $tenure));

        if ($tenure === '') {
            return 0;
        }

        if (preg_match('/^\d{1,3}$/', $tenure)) {
            return min(120, max(1, (int) $tenure)) * 12;
        }

        if (preg_match('/(\d+)\s*(?:-|\s)?\s*months?/i', $tenure, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/(\d+)\s*(?:-|\s)?\s*years?/i', $tenure, $m)) {
            return max(1, (int) $m[1]) * 12;
        }

        $wordMonths = array(
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
            'eleven' => 11,
            'twelve' => 12,
        );

        if (preg_match('/(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\s*years?/i', $tenure, $m)) {
            $key = strtolower($m[1]);

            return isset($wordMonths[$key]) ? $wordMonths[$key] * 12 : 0;
        }

        if (preg_match('/(one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve)\s*months?/i', $tenure, $m)) {
            $key = strtolower($m[1]);

            return isset($wordMonths[$key]) ? $wordMonths[$key] : 0;
        }

        return 0;
    }

    private function unitPaymentsTotal($propertyId, $unitId)
    {
        return (int) $this->database->fetchValue(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE property_id = :property_id AND unit_id = :unit_id',
            array('property_id' => (int) $propertyId, 'unit_id' => (int) $unitId)
        );
    }

    public function unitBillingSummary($unitTable, array $unit)
    {
        $monthlyRent = (int) $unit['monthlyRent'];
        $startDate = isset($unit['startDate']) ? trim((string) $unit['startDate']) : '';
        $tenure = isset($unit['tenure']) ? trim((string) $unit['tenure']) : '';
        $tenantMonths = $this->tenureMonths($tenure);
        $startTimestamp = $startDate !== '' ? @strtotime($startDate) : false;
        $monthsElapsed = 0;

        if ($startTimestamp !== false && $startTimestamp > 0) {
            $nowTs = time();

            if ($nowTs >= $startTimestamp) {
                $years = (int) date('Y', $nowTs) - (int) date('Y', $startTimestamp);
                $months = (int) date('n', $nowTs) - (int) date('n', $startTimestamp);
                $monthsElapsed = max(1, $years * 12 + $months);
            }
        }

        $totalPaid = $this->unitPaymentsTotal($unit['propertyId'], $unit['id']);
        $monthsCovered = $tenantMonths > 0 ? $tenantMonths : max(1, $monthsElapsed);
        $chargedMonths = min($monthsElapsed, $monthsCovered);
        $expected = $monthlyRent * $chargedMonths;
        $balance = $expected - $totalPaid;

        return array(
            'monthlyRent' => $monthlyRent,
            'serviceCharge' => (int) $unit['serviceCharge'],
            'securityDeposit' => isset($unit['securityDeposit']) ? (int) $unit['securityDeposit'] : 0,
            'tenure' => $tenure,
            'tenureMonths' => $tenantMonths,
            'startDate' => $startDate,
            'endDate' => isset($unit['endDate']) ? (string) $unit['endDate'] : '',
            'status' => (string) $unit['status'],
            'monthsElapsed' => $monthsElapsed,
            'monthsCovered' => $monthsCovered,
            'chargedMonths' => $chargedMonths,
            'totalPaid' => $totalPaid,
            'expectedTotal' => $expected,
            'balanceDue' => $balance,
            'isDue' => $balance > 0,
        );
    }

    public function unitHistoryForUnit($unitTable, $unitId)
    {
        if (! in_array($unitTable, $this->registrationTables(), true)) {
            return array();
        }

        $rows = $this->database->fetchAll(
            'SELECT * FROM unit_history WHERE unit_table = :unit_table AND unit_id = :unit_id ORDER BY created_at ASC, id ASC',
            array('unit_table' => $unitTable, 'unit_id' => (int) $unitId)
        );

        $history = array();

        foreach ($rows as $row) {
            $history[] = array(
                'id' => (int) $row['id'],
                'propertyId' => (int) $row['property_id'],
                'unitTable' => (string) $row['unit_table'],
                'unitId' => (int) $row['unit_id'],
                'unitLabel' => (string) $row['unit_label'],
                'userId' => (int) $row['user_id'],
                'userName' => (string) $row['user_name'],
                'userEmail' => (string) $row['user_email'],
                'userPhone' => (string) $row['user_phone'],
                'occupancyStatus' => (string) $row['occupancy_status'],
                'tenure' => (string) $row['tenure'],
                'monthlyRent' => (int) $row['monthly_rent'],
                'serviceCharge' => (int) $row['service_charge'],
                'securityDeposit' => (int) $row['security_deposit'],
                'startDate' => (string) $row['start_date'],
                'endDate' => (string) $row['end_date'],
                'notes' => isset($row['notes']) && $row['notes'] !== null ? (string) $row['notes'] : '',
                'endedAt' => $row['ended_at'] !== null ? (string) $row['ended_at'] : '',
                'createdAt' => (string) $row['created_at'],
                'updatedAt' => (string) $row['updated_at'],
            );
        }

        return $history;
    }

    private function findRawUnit($unitTable, $unitId)
    {
        if (! in_array($unitTable, $this->registrationTables(), true)) {
            return null;
        }

        $row = $this->database->fetchOne(
            $this->unitSelectWithUser($unitTable) . ' WHERE `' . $unitTable . '`.`id` = :id LIMIT 1',
            array('id' => (int) $unitId)
        );

        if (! $row) {
            return null;
        }

        return $this->hydrateUnit($unitTable, $row);
    }

    private function syncUnitHistory($unitTable, array $unit)
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';

        if ($unitTable === '') {
            return;
        }

        $unitId = (int) $unit['id'];
        $propertyId = (int) $unit['propertyId'];
        $unitLabel = $this->unitDisplayLabel($unitTable, $unit);
        $now = $this->now();

        $openRows = $this->database->fetchAll(
            'SELECT * FROM unit_history WHERE unit_table = :unit_table AND unit_id = :unit_id AND occupancy_status = :status ORDER BY id ASC',
            array('unit_table' => $unitTable, 'unit_id' => $unitId, 'status' => 'active')
        );

        $userId = (int) $unit['userId'];
        $status = isset($unit['status']) ? (string) $unit['status'] : 'active';
        $isOccupied = $userId > 0 && ! in_array($status, array('vacant', 'ended'), true);

        if (! $isOccupied) {
            foreach ($openRows as $openRow) {
                $this->database->execute(
                    'UPDATE unit_history SET occupancy_status = :new_status, ended_at = :ended_at, updated_at = :updated_at WHERE id = :id',
                    array('new_status' => 'ended', 'ended_at' => $now, 'updated_at' => $now, 'id' => (int) $openRow['id'])
                );
            }

            return;
        }

        $userName = isset($unit['ownerName']) ? trim((string) $unit['ownerName']) : '';
        $userEmail = isset($unit['ownerEmail']) ? trim((string) $unit['ownerEmail']) : '';
        $userPhone = isset($unit['ownerPhone']) ? trim((string) $unit['ownerPhone']) : '';
        $tenure = isset($unit['tenure']) ? (string) $unit['tenure'] : '';
        $monthlyRent = isset($unit['monthlyRent']) ? (int) $unit['monthlyRent'] : 0;
        $serviceCharge = isset($unit['serviceCharge']) ? (int) $unit['serviceCharge'] : 0;
        $securityDeposit = isset($unit['securityDeposit']) ? (int) $unit['securityDeposit'] : 0;
        $startDate = isset($unit['startDate']) ? (string) $unit['startDate'] : '';
        $endDate = isset($unit['endDate']) ? (string) $unit['endDate'] : '';
        $notes = isset($unit['notes']) && $unit['notes'] !== '' ? $unit['notes'] : null;

        if ($openRows !== array()) {
            $lastOpen = $openRows[count($openRows) - 1];

            if ((int) $lastOpen['user_id'] === $userId) {
                $this->database->execute(
                    'UPDATE unit_history SET unit_label = :unit_label, user_name = :user_name, user_email = :user_email, user_phone = :user_phone, tenure = :tenure, monthly_rent = :monthly_rent, service_charge = :service_charge, security_deposit = :security_deposit, start_date = :start_date, end_date = :end_date, notes = :notes, updated_at = :updated_at WHERE id = :id',
                    array(
                        'unit_label' => $unitLabel,
                        'user_name' => $userName,
                        'user_email' => $userEmail,
                        'user_phone' => $userPhone,
                        'tenure' => $tenure,
                        'monthly_rent' => $monthlyRent,
                        'service_charge' => $serviceCharge,
                        'security_deposit' => $securityDeposit,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'notes' => $notes,
                        'updated_at' => $now,
                        'id' => (int) $lastOpen['id'],
                    )
                );

                return;
            }

            foreach ($openRows as $openRow) {
                $this->database->execute(
                    'UPDATE unit_history SET occupancy_status = :new_status, ended_at = :ended_at, updated_at = :updated_at WHERE id = :id',
                    array('new_status' => 'ended', 'ended_at' => $now, 'updated_at' => $now, 'id' => (int) $openRow['id'])
                );
            }
        }

        $this->database->insert('unit_history', array(
            'property_id' => $propertyId,
            'unit_table' => $unitTable,
            'unit_id' => $unitId,
            'unit_label' => $unitLabel,
            'user_id' => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_phone' => $userPhone,
            'occupancy_status' => 'active',
            'tenure' => $tenure,
            'monthly_rent' => $monthlyRent,
            'service_charge' => $serviceCharge,
            'security_deposit' => $securityDeposit,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes,
            'created_at' => $now,
            'updated_at' => $now,
        ));
    }

    private function buildNormalizedUnitFromRegistration(array $registration)
    {
        return array(
            'id' => (int) $registration['unitId'],
            'propertyId' => (int) $registration['propertyId'],
            'userId' => (int) $registration['userId'],
            'ownerName' => $registration['user']['name'],
            'ownerEmail' => $registration['user']['email'],
            'ownerPhone' => $registration['user']['phone'],
            'status' => (string) $registration['status'],
            'tenure' => (string) $registration['tenure'],
            'startDate' => (string) $registration['startDate'],
            'endDate' => (string) $registration['endDate'],
            'monthlyRent' => (int) $registration['monthlyRent'],
            'serviceCharge' => (int) $registration['serviceCharge'],
            'securityDeposit' => (int) $registration['securityDeposit'],
            'notes' => (string) $registration['notes'],
            'shopNumber' => (string) $registration['shopNumber'],
            'shopName' => (string) $registration['shopName'],
            'block' => (string) $registration['block'],
            'floor' => (string) $registration['floor'],
            'flatNumber' => (string) $registration['flatNumber'],
            'unitNumber' => (string) $registration['unitNumber'],
        );
    }

    public function dueRentUnits()
    {
        $due = array();
        $properties = $this->allProperties();

        foreach ($properties as $property) {
            $bundle = $this->propertyUnits($property['id'], 1, 9999);

            foreach ($bundle['units'] as $unit) {
                $userId = (int) $unit['userId'];
                $unitStatus = (string) $unit['status'];

                if ($userId <= 0 || in_array($unitStatus, array('vacant', 'ended'), true)) {
                    continue;
                }

                $summary = $this->unitBillingSummary($bundle['unitTable'], $unit);

                if (! $summary['isDue']) {
                    continue;
                }

                $due[] = array(
                    'unitTable' => $bundle['unitTable'],
                    'unit' => $unit,
                    'unitLabel' => $this->unitDisplayLabel($bundle['unitTable'], $unit),
                    'property' => $property,
                    'billing' => $summary,
                    'occupant' => array(
                        'name' => $unit['ownerName'],
                        'email' => $unit['ownerEmail'],
                        'phone' => $unit['ownerPhone'],
                    ),
                    'balanceDue' => $summary['balanceDue'],
                );
            }
        }

        usort($due, function ($a, $b) {
            return $b['balanceDue'] - $a['balanceDue'];
        });

        return $due;
    }

    public function dueRentCount()
    {
        return count($this->dueRentUnits());
    }

    public function sendRentReminder($unitTable, $unitId)
    {
        $unitTable = in_array($unitTable, $this->registrationTables(), true) ? $unitTable : '';

        if ($unitTable === '') {
            return array(false, 'That unit could not be found.');
        }

        $unitId = (int) $unitId;
        $unit = $this->findRawUnit($unitTable, $unitId);

        if (! $unit) {
            return array(false, 'That unit could not be found.');
        }

        if ((int) $unit['userId'] <= 0) {
            return array(false, 'That unit has no occupant to remind.');
        }

        $property = $this->findProperty((int) $unit['propertyId']);
        $unitLabel = $this->unitDisplayLabel($unitTable, $unit);
        $propertyTitle = $property ? (string) $property['title'] : 'the property';
        $summary = $this->unitBillingSummary($unitTable, $unit);

        if (! $summary['isDue']) {
            return array(false, 'There is no outstanding balance for this unit.');
        }

        $toEmail = $unit['ownerEmail'];
        $toName = $unit['ownerName'];

        if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return array(false, 'The occupant does not have a valid email address on file.');
        }

        $now = $this->now();
        $currency = app_currency_symbol();
        $subject = 'Rent reminder for ' . $unitLabel . ' - ' . $propertyTitle;
        $bodyHtml = '<div style="font-family:Arial,Helvetica,sans-serif; line-height:1.6; color:#333;">'
            . '<h2 style="color:#082c61; margin-bottom:8px;">Rent payment reminder</h2>'
            . '<p>Dear ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>This is a reminder that your rent payment is due for your occupancy at the property below.</p>'
            . '<table style="width:100%; max-width:600px; border-collapse:collapse; margin:16px 0;">'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa; width:180px;">Property</td><td style="padding:8px;">' . htmlspecialchars($propertyTitle, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Unit / shop</td><td style="padding:8px;">' . htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Monthly rent</td><td style="padding:8px;">' . htmlspecialchars(app_currency($summary['monthlyRent']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Months elapsed</td><td style="padding:8px;">' . (int) $summary['monthsElapsed'] . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Expected to date</td><td style="padding:8px;">' . htmlspecialchars(app_currency($summary['expectedTotal']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; font-weight:600; background:#f4f6fa;">Paid to date</td><td style="padding:8px;">' . htmlspecialchars(app_currency($summary['totalPaid']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '<tr style="background:#fff8f0;"><td style="padding:8px; font-weight:700;">Balance due</td><td style="padding:8px; font-weight:700; color:#b45309;">' . htmlspecialchars(app_currency($summary['balanceDue']), ENT_QUOTES, 'UTF-8') . '</td></tr>'
            . '</table>'
            . '<p>Please ensure your payment is completed promptly to avoid any interruption in your occupancy.</p>'
            . '<p style="margin-top:24px;">Sandworth Homes Property Management<br>Mon - Sat, 8 am - 6 pm</p>'
            . '</div>';

        $mailer = Mailer::fromConfig();

        if ($mailer->enabled()) {
            list($sent, $error) = $mailer->send($toEmail, $toName, $subject, $bodyHtml);
            $status = $sent ? 'sent' : 'failed';
            $sentAt = $sent ? $now : null;
        } else {
            $status = 'pending';
            $error = 'SMTP is not configured. The reminder was queued.';
            $sentAt = null;
        }

        $reminderId = $this->database->insert('rent_reminders', array(
            'unit_table' => $unitTable,
            'unit_id' => $unitId,
            'property_id' => (int) $unit['propertyId'],
            'user_id' => (int) $unit['userId'],
            'occupant_name' => $toName,
            'to_email' => $toEmail,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => strip_tags($bodyHtml),
            'amount_expected' => $summary['expectedTotal'],
            'amount_paid' => $summary['totalPaid'],
            'balance_due' => $summary['balanceDue'],
            'status' => $status,
            'sent_at' => $sentAt,
            'error_message' => $error !== '' ? substr($error, 0, 255) : '',
            'created_at' => $now,
        ));

        $this->database->insert('email_logs', array(
            'recipient_email' => $toEmail,
            'recipient_name' => $toName,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'status' => $status,
            'error_message' => $error !== '' ? substr($error, 0, 255) : '',
            'context' => 'rent-reminder',
            'context_id' => $unitId,
            'created_at' => $now,
            'sent_at' => $sentAt,
        ));

        return array(array(
            'id' => $reminderId,
            'status' => $status,
            'sentAt' => $sentAt,
        ), $error !== '' ? $error : null);
    }

    public function sendRentRemindersFor(array $items)
    {
        $sentCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            $unitTable = isset($item['unitTable']) ? $item['unitTable'] : '';
            $unitId = isset($item['unitId']) ? $item['unitId'] : 0;

            if ($unitTable === '' || (int) $unitId <= 0) {
                $skippedCount++;
                continue;
            }

            list($result, $error) = $this->sendRentReminder($unitTable, (int) $unitId);

            if ($result) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        return array(
            'sent' => $sentCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
        );
    }

    public function recentEmailLogs($limit = 20)
    {
        $rows = $this->database->fetchAll(
            'SELECT * FROM email_logs ORDER BY created_at DESC, id DESC LIMIT :limit',
            array('limit' => max(1, (int) $limit))
        );

        $logs = array();

        foreach ($rows as $row) {
            $logs[] = array(
                'id' => (int) $row['id'],
                'recipientEmail' => (string) $row['recipient_email'],
                'recipientName' => (string) $row['recipient_name'],
                'subject' => (string) $row['subject'],
                'status' => (string) $row['status'],
                'errorMessage' => (string) $row['error_message'],
                'context' => (string) $row['context'],
                'contextId' => (int) $row['context_id'],
                'createdAt' => (string) $row['created_at'],
                'sentAt' => $row['sent_at'] !== null ? (string) $row['sent_at'] : '',
            );
        }

        return $logs;
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

    private function hydratePurchaseOffers(array $rows)
    {
        $offers = array();

        foreach ($rows as $row) {
            $offers[] = $this->hydratePurchaseOffer($row);
        }

        return $offers;
    }

    private function hydrateMessages(array $rows)
    {
        $messages = array();

        foreach ($rows as $row) {
            $messages[] = $this->hydrateMessage($row);
        }

        return $messages;
    }

    private function hydrateMaintenanceTickets(array $rows)
    {
        $tickets = array();

        foreach ($rows as $row) {
            $tickets[] = $this->hydrateMaintenanceTicket($row);
        }

        return $tickets;
    }

    private function hydrateSavedSearches(array $rows)
    {
        $searches = array();

        foreach ($rows as $row) {
            $searches[] = $this->hydrateSavedSearch($row);
        }

        return $searches;
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

    private function hydratePurchaseOffer(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'status' => (string) $row['status'],
            'offerAmount' => (int) $row['offer_amount'],
            'terms' => (string) $row['terms'],
            'timeline' => (string) $row['timeline'],
            'fullName' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'phone' => (string) $row['phone'],
            'adminNotes' => (string) $row['admin_notes'],
            'submittedAt' => (string) $row['submitted_at'],
            'updatedAt' => (string) $row['updated_at'],
            'reviewedAt' => $row['reviewed_at'] !== null ? (string) $row['reviewed_at'] : '',
            'reviewedBy' => $row['reviewed_by_name'] !== null ? (string) $row['reviewed_by_name'] : '',
            'property' => $this->findProperty($row['property_id']),
            'user' => $this->findUserById($row['user_id']),
        );
    }

    private function hydrateMessage(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'sender' => (string) $row['sender'],
            'subject' => (string) $row['subject'],
            'body' => (string) $row['body'],
            'isRead' => (bool) $row['is_read'],
            'createdAt' => (string) $row['created_at'],
            'property' => (int) $row['property_id'] > 0 ? $this->findProperty($row['property_id']) : null,
            'user' => $this->findUserById($row['user_id']),
        );
    }

    private function hydrateMaintenanceTicket(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'tenancyId' => (int) $row['tenancy_id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'status' => (string) $row['status'],
            'priority' => (string) $row['priority'],
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
            'adminNotes' => (string) $row['admin_notes'],
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) $row['updated_at'],
            'resolvedAt' => $row['resolved_at'] !== null ? (string) $row['resolved_at'] : '',
            'resolvedBy' => $row['resolved_by_name'] !== null ? (string) $row['resolved_by_name'] : '',
            'property' => $this->findProperty($row['property_id']),
            'user' => $this->findUserById($row['user_id']),
        );
    }

    private function hydrateSavedSearch(array $row)
    {
        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'name' => (string) $row['name'],
            'purpose' => (string) $row['purpose'],
            'location' => (string) $row['location'],
            'beds' => (int) $row['beds'],
            'propertyType' => (string) $row['property_type'],
            'commercialType' => (string) $row['commercial_type'],
            'petFriendly' => (bool) $row['pet_friendly'],
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => (string) $row['updated_at'],
            'matchCount' => $this->savedSearchMatchCount($row),
            'summary' => $this->savedSearchSummary($row),
        );
    }

    private function hydrateTenancy(array $row)
    {
        $property = $this->findProperty($row['property_id']);
        $purpose = $property && isset($property['purpose']) ? (string) $property['purpose'] : 'rent';
        $securityDeposit = $this->normalizeCautionDeposit($purpose, (int) $row['security_deposit']);
        $payments = $this->paymentsForTenancy($row['id']);
        $totalPaid = 0;

        foreach ($payments as $payment) {
            $totalPaid += (int) $payment['amount'];
        }

        return array(
            'id' => (int) $row['id'],
            'userId' => (int) $row['user_id'],
            'propertyId' => (int) $row['property_id'],
            'applicationId' => (int) $row['application_id'],
            'status' => (string) $row['status'],
            'startDate' => (string) $row['start_date'],
            'endDate' => (string) $row['end_date'],
            'term' => (string) $row['term'],
            'monthlyRent' => (int) $row['monthly_rent'],
            'serviceCharge' => (int) $row['service_charge'],
            'securityDeposit' => $securityDeposit,
            'notes' => $row['notes'] !== null ? (string) $row['notes'] : '',
            'createdAt' => (string) $row['created_at'],
            'updatedAt' => isset($row['updated_at']) ? (string) $row['updated_at'] : (string) $row['created_at'],
            'user' => $this->findUserById((int) $row['user_id']),
            'property' => $property,
            'ledger' => $this->ledgerForTenancy($row['id']),
            'payments' => $payments,
            'totalPaid' => $totalPaid,
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

    private function savedSearchMatchCount(array $row)
    {
        $sql = 'SELECT COUNT(*) FROM properties WHERE status = :status AND purpose = :purpose';
        $params = array(
            'status' => 'available',
            'purpose' => (string) $row['purpose'],
        );

        if (trim((string) $row['location']) !== '') {
            $sql .= ' AND location LIKE :location';
            $params['location'] = '%' . trim((string) $row['location']) . '%';
        }

        if ((int) $row['beds'] > 0 && (string) $row['purpose'] !== 'commercial') {
            $sql .= ' AND beds >= :beds';
            $params['beds'] = (int) $row['beds'];
        }

        if (trim((string) $row['property_type']) !== '') {
            $sql .= ' AND type = :property_type';
            $params['property_type'] = trim((string) $row['property_type']);
        }

        if (trim((string) $row['commercial_type']) !== '') {
            $sql .= ' AND type = :commercial_type';
            $params['commercial_type'] = trim((string) $row['commercial_type']);
        }

        if ((int) $row['pet_friendly'] === 1) {
            $sql .= ' AND pet_friendly = 1';
        }

        return (int) $this->database->fetchValue($sql, $params);
    }

    private function savedSearchSummary(array $row)
    {
        $parts = array(ucfirst((string) $row['purpose']));

        if (trim((string) $row['location']) !== '') {
            $parts[] = 'in ' . trim((string) $row['location']);
        }

        if ((int) $row['beds'] > 0 && (string) $row['purpose'] !== 'commercial') {
            $parts[] = 'from ' . (int) $row['beds'] . ' bed';
        }

        if (trim((string) $row['property_type']) !== '') {
            $parts[] = trim((string) $row['property_type']);
        }

        if (trim((string) $row['commercial_type']) !== '') {
            $parts[] = trim((string) $row['commercial_type']);
        }

        if ((int) $row['pet_friendly'] === 1) {
            $parts[] = 'pet-friendly';
        }

        return implode(' | ', $parts);
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
                'end_date' => isset($tenancy['endDate']) ? $tenancy['endDate'] : '',
                'term' => isset($tenancy['term']) ? $tenancy['term'] : '',
                'monthly_rent' => isset($tenancy['monthlyRent']) ? $this->extractAmount($tenancy['monthlyRent']) : 0,
                'service_charge' => isset($tenancy['serviceCharge']) ? $this->extractAmount($tenancy['serviceCharge']) : 0,
                'security_deposit' => isset($tenancy['securityDeposit']) ? $this->extractAmount($tenancy['securityDeposit']) : 0,
                'notes' => isset($tenancy['notes']) ? $tenancy['notes'] : null,
                'created_at' => isset($tenancy['createdAt']) ? $tenancy['createdAt'] : $this->now(),
                'updated_at' => isset($tenancy['updatedAt']) ? $tenancy['updatedAt'] : (isset($tenancy['createdAt']) ? $tenancy['createdAt'] : $this->now()),
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
