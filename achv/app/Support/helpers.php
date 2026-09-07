<?php

$GLOBALS['app_currency_code'] = isset($GLOBALS['app_currency_code']) ? $GLOBALS['app_currency_code'] : 'NGN';
$GLOBALS['app_site_base_url'] = isset($GLOBALS['app_site_base_url']) ? $GLOBALS['app_site_base_url'] : null;

function app_env_base_url()
{
    foreach (array('APP_URL', 'BASE_URL') as $key) {
        $value = getenv($key);

        if (is_string($value) && trim($value) !== '') {
            return rtrim(trim($value), '/');
        }
    }

    return '';
}

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
        $code = 'NGN';
    }

    $GLOBALS['app_currency_code'] = $code;
}

function app_currency_code()
{
    return isset($GLOBALS['app_currency_code']) ? $GLOBALS['app_currency_code'] : 'NGN';
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

function app_caution_deposit_default()
{
    return 500000;
}

function app_caution_deposit_amount($amount)
{
    $normalized = (int) preg_replace('/[^\d]/', '', (string) $amount);

    return $normalized > 0 ? $normalized : app_caution_deposit_default();
}

function app_legal_fee_amount($rentAmount)
{
    $rentAmount = (int) preg_replace('/[^\d]/', '', (string) $rentAmount);

    if ($rentAmount <= 0) {
        return 0;
    }

    return (int) round($rentAmount * 0.10);
}

function app_move_in_total($rentAmount, $serviceCharge, $cautionDeposit)
{
    return (int) $rentAmount
        + (int) $serviceCharge
        + app_legal_fee_amount($rentAmount)
        + app_caution_deposit_amount($cautionDeposit);
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

function app_public_base_path()
{
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = rtrim($basePath, '/.');

    return $basePath === '' ? '' : $basePath;
}

function app_path_with_base($path)
{
    $path = ltrim(str_replace('\\', '/', (string) $path), '/');
    $basePath = app_public_base_path();

    if ($path === '') {
        return $basePath;
    }

    return ($basePath === '' ? '' : $basePath) . '/' . $path;
}

function app_normalize_public_path($path)
{
    $path = trim((string) $path);

    if ($path === '' || preg_match('#^https?://#i', $path) || strpos($path, 'data:') === 0) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);
    $currentBase = app_public_base_path();

    if ($currentBase !== '' && ($path === $currentBase || strpos($path, $currentBase . '/') === 0)) {
        return $path;
    }

    if (preg_match('#^/[^/]+/(public/.*)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    if (preg_match('#^/[^/]+/((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    if (preg_match('#^/(public/.*)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    if (preg_match('#^/((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    if (preg_match('#^(public/.*)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    if (preg_match('#^((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return app_path_with_base($matches[1]);
    }

    return $path;
}

function app_canonical_public_path($path)
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('#^https?://#i', $path)) {
        $parts = parse_url($path);

        if (! is_array($parts) || ! isset($parts['path'])) {
            return $path;
        }

        $path = (string) $parts['path'];
    }

    $currentBase = app_public_base_path();

    if ($currentBase !== '' && strpos($path, $currentBase . '/') === 0) {
        $path = substr($path, strlen($currentBase));
    }

    if (preg_match('#^/[^/]+/(public/.*)$#i', $path, $matches)) {
        return '/' . ltrim($matches[1], '/');
    }

    if (preg_match('#^/[^/]+/((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return '/' . $matches[1];
    }

    if (preg_match('#^/(public/.*)$#i', $path, $matches)) {
        return '/' . ltrim($matches[1], '/');
    }

    if (preg_match('#^/((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return '/' . $matches[1];
    }

    if (preg_match('#^(public/.*)$#i', $path, $matches)) {
        return '/' . ltrim($matches[1], '/');
    }

    if (preg_match('#^((?:sandworth(?:-icon)?\.png)|robots\.txt|sitemap\.xml)$#i', $path, $matches)) {
        return '/' . $matches[1];
    }

    return $path;
}

function app_root_url($path = '')
{
    $path = trim((string) $path);

    if ($path === '') {
        return app_public_base_path();
    }

    return app_normalize_public_path('/' . ltrim($path, '/'));
}

function app_project_root()
{
    return dirname(dirname(__DIR__));
}

function app_asset_url($path = '')
{
    $assetPath = '/public/assets';

    if (trim((string) $path) !== '') {
        $assetPath .= '/' . ltrim((string) $path, '/');
    }

    return app_normalize_public_path($assetPath);
}

function app_base_url()
{
    $envBaseUrl = app_env_base_url();

    if ($envBaseUrl !== '') {
        return $envBaseUrl;
    }

    if (isset($GLOBALS['app_site_base_url']) && is_string($GLOBALS['app_site_base_url']) && trim($GLOBALS['app_site_base_url']) !== '') {
        return rtrim(trim($GLOBALS['app_site_base_url']), '/');
    }

    $isHttps = (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
    );
    $scheme = $isHttps ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
        ? (string) $_SERVER['HTTP_HOST']
        : 'localhost';

    return $scheme . '://' . $host . app_public_base_path();
}

function app_set_site_base_url($url)
{
    $url = trim((string) $url);

    if ($url === '') {
        $GLOBALS['app_site_base_url'] = null;

        return;
    }

    $GLOBALS['app_site_base_url'] = rtrim($url, '/');
}

function app_absolute_url($path = '')
{
    $path = (string) $path;

    if ($path === '') {
        return rtrim(app_base_url(), '/') . '/';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $path = app_normalize_public_path($path);

    if (strpos($path, '?') === 0) {
        return rtrim(app_base_url(), '/') . '/' . $path;
    }

    if (strpos($path, '/') === 0) {
        return preg_replace('#^(https?://[^/]+).*$#', '$1', app_base_url()) . $path;
    }

    return rtrim(app_base_url(), '/') . '/' . ltrim($path, '/');
}

function app_public_file_path($path)
{
    $path = trim((string) $path);

    if ($path === '' || preg_match('#^https?://#i', $path) || strpos($path, 'data:') === 0) {
        return null;
    }

    $path = app_canonical_public_path($path);

    if ($path === '') {
        return null;
    }

    return app_project_root() . str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function app_ensure_directory($directory)
{
    if (is_dir($directory)) {
        return true;
    }

    return mkdir($directory, 0777, true);
}

function app_load_image_resource($sourcePath)
{
    if (! file_exists($sourcePath)) {
        return null;
    }

    $imageInfo = @getimagesize($sourcePath);

    if (! is_array($imageInfo) || ! isset($imageInfo['mime'])) {
        return null;
    }

    switch ($imageInfo['mime']) {
        case 'image/jpeg':
            return @imagecreatefromjpeg($sourcePath);
        case 'image/png':
            return @imagecreatefrompng($sourcePath);
        case 'image/gif':
            return @imagecreatefromgif($sourcePath);
        case 'image/webp':
            return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null;
        default:
            return null;
    }
}

function app_write_image_resource($resource, $targetPath, $quality = 82)
{
    if (! is_resource($resource) && ! ($resource instanceof GdImage)) {
        return false;
    }

    imageinterlace($resource, true);

    return imagejpeg($resource, $targetPath, $quality);
}

function app_generate_cover_image($sourcePath, $targetPath, $targetWidth, $targetHeight)
{
    if (! extension_loaded('gd')) {
        return false;
    }

    $sourceImage = app_load_image_resource($sourcePath);

    if (! $sourceImage) {
        return false;
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);

    if ($sourceWidth < 1 || $sourceHeight < 1) {
        imagedestroy($sourceImage);

        return false;
    }

    $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
    $resizeWidth = (int) ceil($sourceWidth * $scale);
    $resizeHeight = (int) ceil($sourceHeight * $scale);
    $offsetX = (int) floor(($resizeWidth - $targetWidth) / 2);
    $offsetY = (int) floor(($resizeHeight - $targetHeight) / 2);
    $destinationImage = imagecreatetruecolor($targetWidth, $targetHeight);

    if (! $destinationImage) {
        imagedestroy($sourceImage);

        return false;
    }

    $background = imagecolorallocate($destinationImage, 255, 255, 255);
    imagefilledrectangle($destinationImage, 0, 0, $targetWidth, $targetHeight, $background);

    $copied = imagecopyresampled(
        $destinationImage,
        $sourceImage,
        -$offsetX,
        -$offsetY,
        0,
        0,
        $resizeWidth,
        $resizeHeight,
        $sourceWidth,
        $sourceHeight
    );

    $written = $copied ? app_write_image_resource($destinationImage, $targetPath) : false;

    imagedestroy($destinationImage);
    imagedestroy($sourceImage);

    return $written;
}

function app_derived_image_path($sourcePath, $width, $height, $variant = 'share')
{
    $sourcePath = trim((string) $sourcePath);
    $width = max(1, (int) $width);
    $height = max(1, (int) $height);
    $variant = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $variant);

    if ($sourcePath === '' || preg_match('#^https?://#i', $sourcePath) || strpos($sourcePath, 'data:') === 0) {
        return $sourcePath;
    }

    $sourceFilePath = app_public_file_path($sourcePath);

    if ($sourceFilePath === null || ! file_exists($sourceFilePath)) {
        return app_canonical_public_path($sourcePath);
    }

    $relativeDirectory = '/public/assets/generated/' . trim($variant, '-');
    $targetDirectory = app_project_root() . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (! app_ensure_directory($targetDirectory)) {
        return app_canonical_public_path($sourcePath);
    }

    $fingerprint = md5($sourceFilePath . '|' . (string) @filemtime($sourceFilePath) . '|' . $width . 'x' . $height . '|v1');
    $targetRelativePath = $relativeDirectory . '/' . $fingerprint . '.jpg';
    $targetFilePath = app_project_root() . str_replace('/', DIRECTORY_SEPARATOR, $targetRelativePath);

    if (
        ! file_exists($targetFilePath)
        || (@filemtime($targetFilePath) !== false && @filemtime($sourceFilePath) !== false && filemtime($targetFilePath) < filemtime($sourceFilePath))
    ) {
        if (! app_generate_cover_image($sourceFilePath, $targetFilePath, $width, $height)) {
            return app_canonical_public_path($sourcePath);
        }
    }

    return app_canonical_public_path($targetRelativePath);
}

function app_social_share_image_path($sourcePath)
{
    return app_derived_image_path($sourcePath, 1200, 630, 'social-share');
}

function app_social_icon_image_path($sourcePath)
{
    return app_derived_image_path($sourcePath, 512, 512, 'social-icon');
}

function app_slug($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug !== '' ? $slug : 'listing';
}

function app_property_url(array $listing)
{
    $slugSource = isset($listing['title']) ? $listing['title'] : 'listing';

    if (isset($listing['location']) && trim((string) $listing['location']) !== '') {
        $slugSource .= ' ' . $listing['location'];
    }

    return app_url('property', array(
        'id' => isset($listing['id']) ? (int) $listing['id'] : 0,
        'slug' => app_slug($slugSource),
    ));
}

function app_property_absolute_url(array $listing)
{
    return app_absolute_url(app_property_url($listing));
}

function app_url_with_query_param($url, $key, $value)
{
    $separator = strpos((string) $url, '?') !== false ? '&' : '?';

    return (string) $url
        . $separator
        . rawurlencode((string) $key)
        . '='
        . rawurlencode((string) $value);
}

function app_property_share_text(array $listing)
{
    $parts = array();

    if (isset($listing['title']) && trim((string) $listing['title']) !== '') {
        $parts[] = trim((string) $listing['title']);
    }

    if (isset($listing['price']) && trim((string) $listing['price']) !== '') {
        $parts[] = trim((string) $listing['price']);
    }

    if (isset($listing['location']) && trim((string) $listing['location']) !== '') {
        $parts[] = trim((string) $listing['location']);
    }

    if ($parts === array()) {
        return 'Check out this Sandworth Homes property';
    }

    return 'Check out ' . implode(' | ', $parts) . ' on Sandworth Homes';
}

function app_property_whatsapp_share_target_url(array $listing)
{
    $shareUrl = app_property_absolute_url($listing);
    $image = isset($listing['image']) ? trim((string) $listing['image']) : '';
    $createdAt = isset($listing['createdAt']) ? trim((string) $listing['createdAt']) : '';
    $version = substr(md5($shareUrl . '|' . $image . '|' . $createdAt), 0, 12);

    return app_url_with_query_param($shareUrl, 'share', $version);
}

function app_property_whatsapp_share_url(array $listing)
{
    return 'https://wa.me/?text=' . rawurlencode(app_property_whatsapp_share_target_url($listing));
}

function app_share_button_markup(array $listing)
{
    $title = isset($listing['title']) ? (string) $listing['title'] : 'Property';
    $shareUrl = app_property_absolute_url($listing);
    $shareText = app_property_share_text($listing);
    $whatsAppUrl = app_property_whatsapp_share_url($listing);
    $whatsAppLabel = 'Share ' . $title . ' on WhatsApp';

    return sprintf(
        '<span class="share-action-group"><button type="button" class="share-button" data-share-title="%s" data-share-text="%s" data-share-url="%s" data-whatsapp-url="%s" aria-label="Share %s" title="Share %s"><span class="share-button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="M8.25 11l7-4"></path><path d="M8.25 13l7 4"></path></svg></span><span class="sr-only">Share %s</span></button><a class="whatsapp-share-link" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" title="%s"><span class="share-button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M20 11.46c0 4.66-3.86 8.44-8.62 8.44-1.5 0-2.9-.37-4.13-1.03L3.5 20l1.19-3.49A8.3 8.3 0 0 1 2.76 11.46C2.76 6.8 6.62 3 11.38 3 16.14 3 20 6.8 20 11.46Z"></path><path d="M8.2 8.52c.18-.4.37-.41.54-.42h.46c.15 0 .4.06.61.52.21.46.72 1.58.79 1.7.06.12.1.26.02.42-.08.16-.12.26-.24.4-.12.14-.26.31-.37.41-.12.11-.24.22-.1.43.14.22.62 1.01 1.34 1.64.92.79 1.7 1.04 1.94 1.16.24.12.38.1.52-.06.14-.16.58-.67.74-.9.16-.23.32-.19.54-.12.22.08 1.42.66 1.66.78.24.12.4.18.46.28.06.1.06.58-.14 1.14-.2.56-1.15 1.1-1.59 1.17-.43.07-.98.1-1.58-.1-.36-.12-.82-.27-1.41-.52-2.48-1.05-4.1-3.64-4.22-3.81-.12-.17-1-1.33-1-2.53 0-1.2.62-1.8.84-2.05Z"></path></svg></span><span class="sr-only">%s</span></a></span>',
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($shareText, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($whatsAppLabel, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($whatsAppLabel, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($whatsAppLabel, ENT_QUOTES, 'UTF-8')
    );
}

function app_meta_description($value, $limit = 160)
{
    $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/', ' ', $text));
    $limit = max(80, (int) $limit);

    if ($text === '' || strlen($text) <= $limit) {
        return $text;
    }

    $snippet = substr($text, 0, $limit + 1);
    $lastSpace = strrpos($snippet, ' ');

    if ($lastSpace !== false && $lastSpace > (int) floor($limit * 0.6)) {
        $snippet = substr($snippet, 0, $lastSpace);
    } else {
        $snippet = substr($snippet, 0, $limit);
    }

    return rtrim($snippet, " \t\n\r\0\x0B,.!?:;") . '...';
}

function app_format_datetime($value, $format = 'D, M j, Y g:i A')
{
    $timestamp = strtotime((string) $value);

    if ($timestamp === false) {
        return trim((string) $value);
    }

    return date($format, $timestamp);
}

function app_format_datetime_range($startsAt, $endsAt)
{
    $startTimestamp = strtotime((string) $startsAt);
    $endTimestamp = strtotime((string) $endsAt);

    if ($startTimestamp === false || $endTimestamp === false) {
        return trim((string) $startsAt);
    }

    return date('D, M j, Y g:i A', $startTimestamp) . ' - ' . date('g:i A', $endTimestamp);
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
            'url' => app_property_url($listing),
        );
    }

    return htmlspecialchars(json_encode($pins), ENT_QUOTES, 'UTF-8');
}
