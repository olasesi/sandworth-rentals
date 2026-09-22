CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY app_settings_setting_key_unique (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS properties (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    purpose VARCHAR(20) NOT NULL,
    title VARCHAR(190) NOT NULL,
    location VARCHAR(190) NOT NULL,
    type VARCHAR(120) NOT NULL,
    beds INT UNSIGNED NOT NULL DEFAULT 0,
    baths DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    area VARCHAR(120) NOT NULL DEFAULT '',
    pet_friendly TINYINT(1) NOT NULL DEFAULT 0,
    available_date VARCHAR(120) NOT NULL DEFAULT '',
    image TEXT NOT NULL,
    images_json LONGTEXT NULL,
    badges_json LONGTEXT NULL,
    summary TEXT NOT NULL,
    features_json LONGTEXT NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    asking_price INT UNSIGNED NOT NULL DEFAULT 0,
    commercial_type VARCHAR(120) NOT NULL DEFAULT '',
    lease_term VARCHAR(120) NOT NULL DEFAULT '',
    units INT UNSIGNED NOT NULL DEFAULT 0,
    floors INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'available',
    listed_by VARCHAR(150) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY properties_purpose_idx (purpose),
    KEY properties_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_content_blocks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    page_key VARCHAR(80) NOT NULL,
    block_key VARCHAR(80) NOT NULL,
    content_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY page_content_blocks_unique (page_key, block_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tour_slots (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'open',
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    notes VARCHAR(255) NOT NULL DEFAULT '',
    created_by_name VARCHAR(150) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY tour_slots_property_idx (property_id),
    KEY tour_slots_status_idx (status),
    KEY tour_slots_starts_at_idx (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tour_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    slot_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'requested',
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    message TEXT NULL,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    confirmed_at DATETIME NULL,
    confirmed_by_name VARCHAR(150) NULL,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY tour_requests_property_idx (property_id),
    KEY tour_requests_slot_idx (slot_id),
    KEY tour_requests_user_idx (user_id),
    KEY tour_requests_status_idx (status),
    KEY tour_requests_created_at_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'submitted',
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    annual_income VARCHAR(120) NOT NULL DEFAULT '',
    employer VARCHAR(190) NOT NULL DEFAULT '',
    move_in_date VARCHAR(120) NOT NULL DEFAULT '',
    occupants INT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT NULL,
    approved_by_name VARCHAR(150) NULL,
    approved_at DATETIME NULL,
    submitted_at DATETIME NOT NULL,
    activated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY applications_user_idx (user_id),
    KEY applications_property_idx (property_id),
    KEY applications_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenancies (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    application_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    term VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY tenancies_user_idx (user_id),
    KEY tenancies_application_idx (application_id),
    KEY tenancies_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenancy_ledger (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenancy_id INT UNSIGNED NOT NULL,
    label VARCHAR(190) NOT NULL,
    charge_type VARCHAR(80) NOT NULL,
    amount INT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'paid',
    payment_reference VARCHAR(80) NOT NULL DEFAULT '',
    paid_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY tenancy_ledger_tenancy_idx (tenancy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    unit_id INT UNSIGNED NOT NULL DEFAULT 0,
    application_id INT UNSIGNED NOT NULL DEFAULT 0,
    tenancy_id INT UNSIGNED NOT NULL DEFAULT 0,
    amount INT UNSIGNED NOT NULL DEFAULT 0,
    channel VARCHAR(80) NOT NULL DEFAULT '',
    card_last4 VARCHAR(4) NOT NULL DEFAULT '',
    reference VARCHAR(80) NOT NULL,
    description VARCHAR(190) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY payments_unit_idx (unit_id),
    KEY payments_tenancy_idx (tenancy_id),
    KEY payments_application_idx (application_id),
    KEY payments_user_idx (user_id),
    KEY payments_property_idx (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_offers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'submitted',
    offer_amount INT UNSIGNED NOT NULL DEFAULT 0,
    terms TEXT NULL,
    timeline VARCHAR(190) NOT NULL DEFAULT '',
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL,
    admin_notes TEXT NULL,
    submitted_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    reviewed_by_name VARCHAR(150) NULL,
    PRIMARY KEY (id),
    KEY purchase_offers_user_idx (user_id),
    KEY purchase_offers_property_idx (property_id),
    KEY purchase_offers_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL DEFAULT 0,
    sender VARCHAR(20) NOT NULL DEFAULT 'user',
    subject VARCHAR(190) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY messages_user_idx (user_id),
    KEY messages_property_idx (property_id),
    KEY messages_sender_idx (sender),
    KEY messages_read_idx (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenancy_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'open',
    priority VARCHAR(40) NOT NULL DEFAULT 'medium',
    title VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    resolved_at DATETIME NULL,
    resolved_by_name VARCHAR(150) NULL,
    PRIMARY KEY (id),
    KEY maintenance_tickets_tenancy_idx (tenancy_id),
    KEY maintenance_tickets_user_idx (user_id),
    KEY maintenance_tickets_property_idx (property_id),
    KEY maintenance_tickets_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mall_shops (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    shop_number VARCHAR(120) NOT NULL DEFAULT '',
    shop_name VARCHAR(190) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL DEFAULT 'vacant',
    tenure VARCHAR(120) NOT NULL DEFAULT '',
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY mall_shops_property_idx (property_id),
    KEY mall_shops_user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS residential_units (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    block VARCHAR(80) NOT NULL DEFAULT '',
    unit_number VARCHAR(120) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL DEFAULT 'vacant',
    tenure VARCHAR(120) NOT NULL DEFAULT '',
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY residential_units_property_idx (property_id),
    KEY residential_units_user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS apartment_flats (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    block VARCHAR(80) NOT NULL DEFAULT '',
    floor VARCHAR(40) NOT NULL DEFAULT '',
    flat_number VARCHAR(120) NOT NULL DEFAULT '',
    status VARCHAR(40) NOT NULL DEFAULT 'vacant',
    tenure VARCHAR(120) NOT NULL DEFAULT '',
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY apartment_flats_property_idx (property_id),
    KEY apartment_flats_user_idx (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_searches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL DEFAULT '',
    purpose VARCHAR(20) NOT NULL DEFAULT 'rent',
    location VARCHAR(190) NOT NULL DEFAULT '',
    beds INT UNSIGNED NOT NULL DEFAULT 0,
    property_type VARCHAR(120) NOT NULL DEFAULT '',
    commercial_type VARCHAR(120) NOT NULL DEFAULT '',
    pet_friendly TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY saved_searches_user_idx (user_id),
    KEY saved_searches_purpose_idx (purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS unit_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    unit_table VARCHAR(60) NOT NULL DEFAULT '',
    unit_id INT UNSIGNED NOT NULL,
    unit_label VARCHAR(190) NOT NULL DEFAULT '',
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    user_name VARCHAR(150) NOT NULL DEFAULT '',
    user_email VARCHAR(190) NOT NULL DEFAULT '',
    user_phone VARCHAR(80) NOT NULL DEFAULT '',
    occupancy_status VARCHAR(40) NOT NULL DEFAULT 'active',
    tenure VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    notes TEXT NULL,
    ended_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY unit_history_unit_idx (unit_table, unit_id),
    KEY unit_history_property_idx (property_id),
    KEY unit_history_user_idx (user_id),
    KEY unit_history_status_idx (occupancy_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenure_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL DEFAULT 0,
    unit_table VARCHAR(60) NOT NULL DEFAULT '',
    unit_id INT UNSIGNED NOT NULL,
    unit_label VARCHAR(190) NOT NULL DEFAULT '',
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    user_name VARCHAR(150) NOT NULL DEFAULT '',
    user_email VARCHAR(190) NOT NULL DEFAULT '',
    user_phone VARCHAR(80) NOT NULL DEFAULT '',
    tenure VARCHAR(120) NOT NULL DEFAULT '',
    monthly_rent INT UNSIGNED NOT NULL DEFAULT 0,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    security_deposit INT UNSIGNED NOT NULL DEFAULT 0,
    start_date VARCHAR(120) NOT NULL DEFAULT '',
    end_date VARCHAR(120) NOT NULL DEFAULT '',
    amount_due INT UNSIGNED NOT NULL DEFAULT 0,
    amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
    balance_carried INT NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'renewed',
    notes TEXT NULL,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY tenure_history_unit_idx (unit_table, unit_id),
    KEY tenure_history_property_idx (property_id),
    KEY tenure_history_user_idx (user_id),
    KEY tenure_history_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_charge_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    property_id INT UNSIGNED NOT NULL,
    service_charge INT UNSIGNED NOT NULL DEFAULT 0,
    effective_from DATE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY sc_history_prop_date (property_id, effective_from),
    KEY sc_history_property_idx (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_charge_allocations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    payment_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL,
    unit_table VARCHAR(60) NOT NULL DEFAULT '',
    unit_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    service_month CHAR(7) NOT NULL DEFAULT '',
    rate_used INT UNSIGNED NOT NULL DEFAULT 0,
    amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY sc_alloc_payment_idx (payment_id),
    KEY sc_alloc_unit_idx (unit_table, unit_id),
    KEY sc_alloc_month_idx (unit_table, unit_id, service_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rent_reminders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    unit_table VARCHAR(60) NOT NULL DEFAULT '',
    unit_id INT UNSIGNED NOT NULL,
    property_id INT UNSIGNED NOT NULL DEFAULT 0,
    user_id INT UNSIGNED NOT NULL DEFAULT 0,
    occupant_name VARCHAR(150) NOT NULL DEFAULT '',
    to_email VARCHAR(190) NOT NULL DEFAULT '',
    subject VARCHAR(190) NOT NULL DEFAULT '',
    body_html TEXT NULL,
    body_text TEXT NULL,
    amount_expected INT UNSIGNED NOT NULL DEFAULT 0,
    amount_paid INT UNSIGNED NOT NULL DEFAULT 0,
    balance_due INT NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    error_message VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY rent_reminders_unit_idx (unit_table, unit_id),
    KEY rent_reminders_user_idx (user_id),
    KEY rent_reminders_status_idx (status),
    KEY rent_reminders_property_idx (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_email VARCHAR(190) NOT NULL,
    recipient_name VARCHAR(150) NOT NULL DEFAULT '',
    subject VARCHAR(190) NOT NULL DEFAULT '',
    body_html TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_message VARCHAR(255) NOT NULL DEFAULT '',
    context VARCHAR(60) NOT NULL DEFAULT '',
    context_id INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY email_logs_recipient_idx (recipient_email),
    KEY email_logs_status_idx (status),
    KEY email_logs_context_idx (context, context_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
