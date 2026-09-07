<?php

$GLOBALS['app_currency_code'] = isset($GLOBALS['app_currency_code']) ? $GLOBALS['app_currency_code'] : 'USD';

function app_url($page, array $params = array())
{
    $query = array_merge(array('page' => $page), $params);

    return '?' . http_build_query($query);
}

function app_redirect($page, array $params = array())
{
    header('Location: ' . app_url($page, $params));
    exit;
}

function app_set_currency($code)
{
    $code = strtoupper(trim((string) $code));

    if (! in_array($code, array('USD', 'NGN'), true)) {
        $code = 'USD';
    }

    $GLOBALS['app_currency_code'] = $code;
}

function app_currency_code()
{
    return isset($GLOBALS['app_currency_code']) ? $GLOBALS['app_currency_code'] : 'USD';
}

function app_currency_symbol()
{
    return app_currency_code() === 'NGN' ? '₦' : '$';
}

function app_currency($amount)
{
    return app_currency_symbol() . number_format((int) $amount);
}

function app_currency_text($value)
{
    if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
        return app_currency((int) $value);
    }

    $text = trim((string) $value);

    if ($text === '') {
        return '';
    }

    $amount = (int) preg_replace('/[^\d]/', '', $text);

    if ($amount === 0 && strpos($text, '0') === false) {
        return $text;
    }

    $lastDigitOffset = -1;
    $length = strlen($text);

    for ($index = $length - 1; $index >= 0; $index--) {
        if (ctype_digit($text[$index])) {
            $lastDigitOffset = $index;
            break;
        }
    }

    $suffix = '';

    if ($lastDigitOffset >= 0 && $lastDigitOffset < $length - 1) {
        $suffix = trim(substr($text, $lastDigitOffset + 1));
    }

    return app_currency($amount) . ($suffix !== '' ? $suffix : '');
}

function app_require_login($user)
{
    if (! $user) {
        app_redirect('login');
    }
}

function app_require_admin($user)
{
    if (! $user || ! isset($user['role']) || $user['role'] !== 'admin') {
        app_redirect('login');
    }
}

/**
 * Build a JSON-safe payload of listing pins for the Leaflet map layer.
 *
 * @param array $listings
 * @return string
 */
function app_map_payload(array $listings)
{
    $pins = array();

    foreach ($listings as $listing) {
        if (! isset($listing['lat'], $listing['lng']) || $listing['lat'] === '' || $listing['lng'] === '') {
            continue;
        }

        $pins[] = array(
            'id' => (int) $listing['id'],
            'lat' => (float) $listing['lat'],
            'lng' => (float) $listing['lng'],
            'title' => (string) $listing['title'],
            'price' => (string) $listing['price'],
            'location' => (string) $listing['location'],
            'image' => (string) $listing['image'],
            'url' => app_url('property', array('id' => $listing['id'])),
        );
    }

    return htmlspecialchars(json_encode($pins), ENT_QUOTES, 'UTF-8');
}
