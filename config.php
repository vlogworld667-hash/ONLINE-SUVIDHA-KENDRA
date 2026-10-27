<?php
declare(strict_types=1);

$rootPath = __DIR__;

/*
 * Load the existing website database connection and $webdata.
 * The first path is for this project structure. The second supports
 * installations where this file is inside a child directory.
 */
$legacyConfigPaths = [
    $rootPath . '/includes/config.php',
    dirname($rootPath) . '/includes/config.php',
];

foreach ($legacyConfigPaths as $legacyConfigPath) {
    if (
        is_file($legacyConfigPath)
        && realpath($legacyConfigPath) !== realpath(__FILE__)
    ) {
        require_once $legacyConfigPath;
        break;
    }
}

function ds_load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || substr($line, 0, 1) === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (
            strlen($value) >= 2
            && (
                ($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function ds_env(string $key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
        default:
            return $value;
    }
}

function ds_db_value(array $data, string $key, $default = '')
{
    if (!array_key_exists($key, $data) || $data[$key] === null) {
        return $default;
    }

    $value = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
    return $value === '' ? $default : $value;
}

function ds_normalize_url(string $url, string $fallback): string
{
    $url = trim($url);

    if ($url === '') {
        $url = trim($fallback);
    }

    if ($url === '') {
        return '';
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return $fallback === ''
            ? ''
            : ds_normalize_url($fallback, '');
    }

    return rtrim($url, '/') . '/';
}

ds_load_env($rootPath . '/.env');

$webdata = isset($webdata) && is_array($webdata) ? $webdata : [];

/*
 * Fetch the registered API URL from the settings table.
 * A missing row or database error uses the official fallback URL.
 */
$databaseAuthUrl = '';

if (isset($ahk_conn) && is_object($ahk_conn) && method_exists($ahk_conn, 'query')) {
    try {
        $settingsResult = $ahk_conn->query(
            'SELECT auth_url FROM settings WHERE id = 1 LIMIT 1'
        );

        if (
            $settingsResult !== false
            && isset($settingsResult->num_rows)
            && $settingsResult->num_rows > 0
        ) {
            $settingsRow = $settingsResult->fetch_assoc();
            $databaseAuthUrl = trim((string) ($settingsRow['auth_url'] ?? ''));
        }

        if (is_object($settingsResult) && method_exists($settingsResult, 'free')) {
            $settingsResult->free();
        }
    } catch (Throwable $exception) {
        error_log('Unable to fetch settings.auth_url: ' . $exception->getMessage());
    }
}

/*
 * Database-backed website and integration settings.
 * Environment values may override secrets when required.
 */
$flexsecureapiurl = ds_normalize_url(
    (string) ds_env('FLEXSECURE_API_URL', $databaseAuthUrl),
    'https://flex4upoint.co.in/'
);
$flexsecure_key = (string) ds_env(
    'FLEXSECURE_API_KEY',
    ds_db_value($webdata, 'nsdl_api_key')
);
$flexsecure_upikey = (string) ds_env(
    'FLEXSECURE_UPI_KEY',
    ds_db_value($webdata, 'upi_key')
);
$flexsecure_upikey2 = (string) ds_env(
    'FLEXSECURE_PAYMENT_TYPE',
    ds_db_value($webdata, 'pgtype')
);
$whatsappurl = ds_normalize_url(
    (string) ds_env('WHATSAPP_API_URL', ds_db_value($webdata, 'whurl')),
    ''
);
$whapikey = (string) ds_env(
    'WHATSAPP_API_KEY',
    ds_db_value($webdata, 'whkey')
);
$flexsecure_upiUrl = ds_normalize_url(
    (string) ds_env('PAYMENT_GATEWAY_URL', ds_db_value($webdata, 'pgurl')),
    ''
);
$whsender = (string) ds_env(
    'WHATSAPP_SENDER',
    ds_db_value($webdata, 'whnumber')
);
$websiteName = (string) ds_env(
    'APP_NAME',
    ds_db_value($webdata, 'webname', 'MTG Digital Seva')
);

$appUrl = rtrim((string) ds_env('APP_URL', ''), '/');
$jwtIssuer = $appUrl !== '' ? $appUrl : 'digital-seva';
$websiteMailDomain = trim((string) ($websiteUrl ?? $appUrl));
$websiteMailDomain = preg_replace('~^https?://~i', '', $websiteMailDomain) ?? '';
$websiteMailDomain = strtolower(explode('/', $websiteMailDomain, 2)[0]);
$websiteMailDomain = preg_replace('/:\d+$/', '', $websiteMailDomain) ?? '';
$websiteMailDomain = preg_replace('/^www\./i', '', $websiteMailDomain) ?? '';

if (
    $websiteMailDomain === ''
    || filter_var('owner@' . $websiteMailDomain, FILTER_VALIDATE_EMAIL) === false
) {
    $websiteMailDomain = 'localhost';
}

return [
    'app' => [
        'name' => $websiteName,
        'url' => $appUrl,
        'env' => (string) ds_env('APP_ENV', 'production'),
        'debug' => (bool) ds_env('APP_DEBUG', false),
        'timezone' => (string) ds_env('APP_TIMEZONE', 'Asia/Kolkata'),
    ],
    'jwt' => [
        'secret' => (string) ds_env(
            'JWT_SECRET',
            'development-only-secret-change-before-production'
        ),
        'issuer' => (string) ds_env('JWT_ISSUER', $jwtIssuer),
        'access_ttl' => (int) ds_env('JWT_ACCESS_TTL', 900),
        'refresh_ttl' => (int) ds_env('JWT_REFRESH_TTL', 604800),
    ],
    'database' => [
        'path' => $rootPath . '/' . ltrim(
            (string) ds_env('DB_PATH', 'storage/app.sqlite'),
            '/\\'
        ),
        'legacy_connection' => $ahk_conn ?? null,
    ],
    'mail' => [
        'transport' => (string) ds_env('MAIL_TRANSPORT', 'log'),
        'to' => (string) ds_env(
            'CONTACT_TO_EMAIL',
            ds_db_value($webdata, 'email', 'owner@' . $websiteMailDomain)
        ),
        'from_email' => (string) ds_env(
            'MAIL_FROM_EMAIL',
            ds_db_value($webdata, 'email', 'no-reply@' . $websiteMailDomain)
        ),
        'from_name' => (string) ds_env('MAIL_FROM_NAME', $websiteName),
        'smtp' => [
            'host' => (string) ds_env('SMTP_HOST', ''),
            'port' => (int) ds_env('SMTP_PORT', 587),
            'encryption' => (string) ds_env('SMTP_ENCRYPTION', 'tls'),
            'username' => (string) ds_env('SMTP_USERNAME', ''),
            'password' => (string) ds_env('SMTP_PASSWORD', ''),
        ],
    ],
    'images' => [
        'max_bytes' => 12 * 1024 * 1024,
        'remove_bg_api_key' => (string) ds_env(
            'REMOVEBG_API_KEY',
            ds_db_value($webdata, 'remove_bg_api_key')
        ),
    ],
    'integrations' => [
        'flexsecure' => [
            'base_url' => $flexsecureapiurl,
            'api_key' => $flexsecure_key,
            'upi_key' => $flexsecure_upikey,
            'payment_type' => $flexsecure_upikey2,
            'payment_url' => $flexsecure_upiUrl,
        ],
        'whatsapp' => [
            'base_url' => $whatsappurl,
            'api_key' => $whapikey,
            'sender' => $whsender,
        ],
    ],
];
