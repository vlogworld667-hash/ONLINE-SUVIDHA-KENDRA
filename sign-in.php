<?php
session_start();

if (ob_get_level() === 0) {
    ob_start();
}

include('./includes/config.php');
include('authentication-signin.php');

define('LOGIN_OTP_EXPIRE_SECONDS', 300);
define('LOGIN_OTP_MAX_ATTEMPTS', 5);
define('LOGIN_OTP_RESEND_SECONDS', 60);

function loginCsrfToken()
{
    if (empty($_SESSION['login_csrf_token'])) {
        $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['login_csrf_token'];
}

function loginCsrfIsValid()
{
    return isset($_POST['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals(loginCsrfToken(), $_POST['csrf_token']);
}

function loginWhatsAppNumber($phone)
{
    $number = preg_replace('/\D+/', '', (string) $phone);

    if (strlen($number) === 10) {
        return '91' . $number;
    }

    return $number;
}

function maskedLoginPhone($phone)
{
    $number = preg_replace('/\D+/', '', (string) $phone);
    $length = strlen($number);

    if ($length <= 4) {
        return $number;
    }

    return str_repeat('*', $length - 4) . substr($number, -4);
}

function loginSiteName()
{
    ob_start();
    ahkweb('webname');
    $siteName = trim(ob_get_clean());

    return $siteName !== '' ? $siteName : 'Our Website';
}

function sendLoginWhatsApp($phone, $message)
{
    global $whatsappurl, $whapikey, $whsender;

    if (!function_exists('curl_init')) {
        error_log('Login WhatsApp error: PHP cURL is not enabled.');
        return false;
    }

    $host = trim((string) $whatsappurl);
    $host = preg_match('#^https?://#i', $host)
        ? rtrim($host, '/')
        : 'https://' . trim($host, '/');

    $apiUrl = $host . '/send-message?' . http_build_query([
        'api_key' => $whapikey,
        'sender' => $whsender,
        'number' => loginWhatsAppNumber($phone),
        'message' => $message
    ], '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $statusCode < 200 || $statusCode >= 300) {
        error_log(
            'Login WhatsApp error. HTTP ' . $statusCode .
            ($curlError !== '' ? ' - ' . $curlError : '')
        );
        return false;
    }

    return true;
}

function findLoginUserByUsername($username)
{
    global $ahk_conn;

    $stmt = mysqli_prepare(
        $ahk_conn,
        'SELECT * FROM users WHERE phone = ? OR email = ? LIMIT 1'
    );

    if (!$stmt) {
        error_log('Login query prepare error: ' . mysqli_error($ahk_conn));
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userData = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $userData ?: null;
}

function findLoginUserByPhone($phone)
{
    global $ahk_conn;

    $stmt = mysqli_prepare(
        $ahk_conn,
        'SELECT * FROM users WHERE phone = ? LIMIT 1'
    );

    if (!$stmt) {
        error_log('OTP user query prepare error: ' . mysqli_error($ahk_conn));
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userData = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $userData ?: null;
}

function createLoginOtp($userData)
{
    $otp = (string) random_int(100000, 999999);
    $siteName = loginSiteName();
    $userName = !empty($userData['name'])
        ? $userData['name']
        : 'Valued User';

    $message =
        "🔐 *SECURE LOGIN VERIFICATION*\n" .
        "━━━━━━━━━━━━━━━━━━━━\n\n" .
        "👋 Hello *{$userName}*,\n\n" .
        "We received a login request for your *{$siteName}* account.\n\n" .
        "🔢 *Your One-Time Password*\n" .
        "        *{$otp}*\n\n" .
        "⏳ *Valid for:* 5 minutes\n" .
        "🛡️ *Security:* Never share this OTP with anyone.\n\n" .
        "⚠️ If you did not request this login, please secure your account immediately.\n\n" .
        "━━━━━━━━━━━━━━━━━━━━\n" .
        "🔒 {$siteName} Security Team";

    if (!sendLoginWhatsApp($userData['phone'], $message)) {
        return false;
    }

    $_SESSION['pending_login_otp'] = [
        'phone' => (string) $userData['phone'],
        'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
        'expires_at' => time() + LOGIN_OTP_EXPIRE_SECONDS,
        'attempts' => 0,
        'last_sent_at' => time()
    ];

    return true;
}

function completeUserLogin($userData)
{
    global $websiteUrl;

    session_regenerate_id(true);

    $_SESSION['phone'] = $userData['phone'];
    $_SESSION['name'] = $userData['name'];
    unset($_SESSION['pending_login_otp']);

    $user = $userData['name'];
    $loginTime = date('Y-m-d H:i:s');
    $loginIp = isset($_SERVER['REMOTE_ADDR'])
        ? $_SERVER['REMOTE_ADDR']
        : 'Unknown';
    $site = rtrim((string) $websiteUrl, '/') . '/login';
    $siteName = loginSiteName();

    $message =
        "✅ *LOGIN SUCCESSFUL*\n" .
        "━━━━━━━━━━━━━━━━━━━━\n\n" .
        "👋 Hello *{$user}*,\n\n" .
        "Your *{$siteName}* account was successfully signed in.\n\n" .
        "🕒 *Time:* {$loginTime}\n" .
        "🌐 *IP Address:* {$loginIp}\n" .
        "🔗 *Login Page:* {$site}\n\n" .
        "⚠️ Wasn't you? Change your password immediately and contact support.\n\n" .
        "━━━━━━━━━━━━━━━━━━━━\n" .
        "🛡️ {$siteName} Security Team";

    sendLoginWhatsApp($userData['phone'], $message);
}

$alertIcon = '';
$alertTitle = '';
$alertText = '';
$loginSuccess = false;
$showOtpPrompt = !empty($_SESSION['pending_login_otp']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!loginCsrfIsValid()) {
        $alertIcon = 'error';
        $alertTitle = 'Request Expired';
        $alertText = 'Refresh the page and try again.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : 'login';

        if ($action === 'login') {
            unset($_SESSION['pending_login_otp']);
            $showOtpPrompt = false;

            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if ($username === '' || $password === '') {
                $alertIcon = 'warning';
                $alertTitle = 'Missing Details';
                $alertText = 'Enter your email or phone number and password.';
            } else {
                $userData = findLoginUserByUsername($username);

                if (!$userData) {
                    $alertIcon = 'error';
                    $alertTitle = 'Not Registered';
                    $alertText = 'The email or phone number is not registered.';
                } elseif ((int) $userData['status'] !== 1) {
                    $alertIcon = 'warning';
                    $alertTitle = 'Account Inactive';
                    $alertText = 'Your account is not active. Please contact support.';
                } elseif (!password_verify($password, $userData['password'])) {
                    $alertIcon = 'error';
                    $alertTitle = 'Incorrect Password';
                    $alertText = 'The password you entered is incorrect.';
                } elseif (
                    isset($userData['two_factor'])
                    && strtolower($userData['two_factor']) === 'yes'
                ) {
                    if (createLoginOtp($userData)) {
                        $alertIcon = 'success';
                        $alertTitle = '📲 OTP Sent Securely';
                        $alertText = 'Your 6-digit security code has been sent through WhatsApp.';
                        $showOtpPrompt = true;
                    } else {
                        $alertIcon = 'error';
                        $alertTitle = 'OTP Not Sent';
                        $alertText = 'Unable to send the OTP. Please try again.';
                    }
                } else {
                    completeUserLogin($userData);
                    $alertIcon = 'success';
                    $alertTitle = '🎉 Login Successful';
                    $alertText = loginSiteName() . ' - Welcome back!';
                    $loginSuccess = true;
                }
            }
        } elseif ($action === 'verify_otp') {
            $pendingOtp = isset($_SESSION['pending_login_otp'])
                ? $_SESSION['pending_login_otp']
                : null;
            $otp = trim(isset($_POST['otp']) ? $_POST['otp'] : '');

            if (!is_array($pendingOtp)) {
                $alertIcon = 'error';
                $alertTitle = 'OTP Session Expired';
                $alertText = 'Please sign in again.';
                $showOtpPrompt = false;
            } elseif (time() > (int) $pendingOtp['expires_at']) {
                unset($_SESSION['pending_login_otp']);
                $alertIcon = 'error';
                $alertTitle = 'OTP Expired';
                $alertText = 'Your OTP has expired. Please sign in again.';
                $showOtpPrompt = false;
            } elseif (!preg_match('/^\d{6}$/', $otp)) {
                $alertIcon = 'warning';
                $alertTitle = 'Invalid OTP';
                $alertText = 'Enter the 6-digit OTP.';
                $showOtpPrompt = true;
            } elseif ((int) $pendingOtp['attempts'] >= LOGIN_OTP_MAX_ATTEMPTS) {
                unset($_SESSION['pending_login_otp']);
                $alertIcon = 'error';
                $alertTitle = 'Too Many Attempts';
                $alertText = 'Please sign in again.';
                $showOtpPrompt = false;
            } elseif (!password_verify($otp, $pendingOtp['otp_hash'])) {
                $_SESSION['pending_login_otp']['attempts']++;
                $remaining = LOGIN_OTP_MAX_ATTEMPTS
                    - (int) $_SESSION['pending_login_otp']['attempts'];

                $alertIcon = 'error';
                $alertTitle = 'Incorrect OTP';

                if ($remaining <= 0) {
                    unset($_SESSION['pending_login_otp']);
                    $alertText = 'Too many incorrect attempts. Please sign in again.';
                    $showOtpPrompt = false;
                } else {
                    $alertText = 'Incorrect OTP. ' . $remaining . ' attempt(s) remaining.';
                    $showOtpPrompt = true;
                }
            } else {
                $userData = findLoginUserByPhone($pendingOtp['phone']);

                if (!$userData || (int) $userData['status'] !== 1) {
                    unset($_SESSION['pending_login_otp']);
                    $alertIcon = 'error';
                    $alertTitle = 'Login Failed';
                    $alertText = 'The account is unavailable. Please contact support.';
                    $showOtpPrompt = false;
                } else {
                    completeUserLogin($userData);
                    $alertIcon = 'success';
                    $alertTitle = '✅ Verification Complete';
                    $alertText = 'Your OTP was verified successfully. Welcome back!';
                    $loginSuccess = true;
                    $showOtpPrompt = false;
                }
            }
        } elseif ($action === 'resend_otp') {
            $pendingOtp = isset($_SESSION['pending_login_otp'])
                ? $_SESSION['pending_login_otp']
                : null;

            if (!is_array($pendingOtp)) {
                $alertIcon = 'error';
                $alertTitle = 'OTP Session Expired';
                $alertText = 'Please sign in again.';
                $showOtpPrompt = false;
            } elseif (
                time() - (int) $pendingOtp['last_sent_at']
                < LOGIN_OTP_RESEND_SECONDS
            ) {
                $wait = LOGIN_OTP_RESEND_SECONDS
                    - (time() - (int) $pendingOtp['last_sent_at']);
                $alertIcon = 'warning';
                $alertTitle = 'Please Wait';
                $alertText = 'You can resend the OTP in ' . $wait . ' second(s).';
                $showOtpPrompt = true;
            } else {
                $userData = findLoginUserByPhone($pendingOtp['phone']);

                if (
                    !$userData
                    || (int) $userData['status'] !== 1
                    || strtolower($userData['two_factor']) !== 'yes'
                ) {
                    unset($_SESSION['pending_login_otp']);
                    $alertIcon = 'error';
                    $alertTitle = 'Login Failed';
                    $alertText = 'Please sign in again.';
                    $showOtpPrompt = false;
                } elseif (createLoginOtp($userData)) {
                    $alertIcon = 'success';
                    $alertTitle = '📲 New OTP Sent';
                    $alertText = 'A fresh security code was sent through WhatsApp.';
                    $showOtpPrompt = true;
                } else {
                    $alertIcon = 'error';
                    $alertTitle = 'OTP Not Sent';
                    $alertText = 'Unable to resend the OTP. Please try again.';
                    $showOtpPrompt = true;
                }
            }
        } elseif ($action === 'cancel_otp') {
            unset($_SESSION['pending_login_otp']);
            $showOtpPrompt = false;
        }
    }
}

$otpExpiresAt = 0;
$otpResendAt = 0;
$otpMaskedPhone = 'your registered WhatsApp number';
$otpAttemptsRemaining = LOGIN_OTP_MAX_ATTEMPTS;

if (
    !empty($_SESSION['pending_login_otp'])
    && is_array($_SESSION['pending_login_otp'])
) {
    $activeOtp = $_SESSION['pending_login_otp'];
    $otpExpiresAt = (int) $activeOtp['expires_at'];
    $otpResendAt = (int) $activeOtp['last_sent_at']
        + LOGIN_OTP_RESEND_SECONDS;
    $otpMaskedPhone = maskedLoginPhone($activeOtp['phone']);
    $otpAttemptsRemaining = max(
        0,
        LOGIN_OTP_MAX_ATTEMPTS - (int) $activeOtp['attempts']
    );
}

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .secure-otp-popup {
        width: min(94vw, 470px) !important;
        padding: 0 0 24px !important;
        overflow: hidden !important;
        border: 1px solid rgba(99, 102, 241, 0.18) !important;
        border-radius: 28px !important;
        background:
            radial-gradient(circle at top right, rgba(99, 102, 241, 0.13), transparent 38%),
            #ffffff !important;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.25) !important;
    }

    .secure-otp-title {
        margin: 0 !important;
        padding: 0 24px !important;
        color: #0f172a !important;
        font-size: 26px !important;
        font-weight: 800 !important;
        letter-spacing: -0.4px !important;
    }

    .secure-otp-html {
        margin: 0 !important;
        padding: 0 26px !important;
    }

    .secure-otp-actions {
        width: calc(100% - 52px) !important;
        margin: 22px 26px 0 !important;
        gap: 10px !important;
    }

    .secure-otp-confirm,
    .secure-otp-cancel {
        min-height: 48px !important;
        margin: 0 !important;
        border-radius: 14px !important;
        font-weight: 700 !important;
    }

    .secure-otp-confirm {
        flex: 1.35 !important;
        background: linear-gradient(135deg, #4f46e5, #7c3aed) !important;
        box-shadow: 0 10px 24px rgba(79, 70, 229, 0.28) !important;
    }

    .secure-otp-cancel {
        flex: 1 !important;
        color: #475569 !important;
        background: #eef2f7 !important;
    }

    .otp-security-header {
        position: relative;
        height: 116px;
        margin-bottom: 4px;
        background: linear-gradient(135deg, #312e81, #6d28d9 58%, #9333ea);
        overflow: hidden;
    }

    .otp-security-header::before,
    .otp-security-header::after {
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.09);
        content: "";
    }

    .otp-security-header::before {
        width: 150px;
        height: 150px;
        top: -85px;
        right: -30px;
    }

    .otp-security-header::after {
        width: 100px;
        height: 100px;
        bottom: -65px;
        left: -20px;
    }

    .otp-shield {
        position: absolute;
        top: 22px;
        left: 50%;
        display: grid;
        width: 72px;
        height: 72px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 24px;
        color: #ffffff;
        background: rgba(255, 255, 255, 0.16);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
        transform: translateX(-50%) rotate(4deg);
        place-items: center;
        backdrop-filter: blur(8px);
    }

    .otp-shield svg {
        width: 38px;
        height: 38px;
        transform: rotate(-4deg);
    }

    .otp-description {
        margin: 8px 0 4px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.55;
    }

    .otp-phone {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 7px;
        padding: 6px 11px;
        border: 1px solid #dcfce7;
        border-radius: 999px;
        color: #166534;
        background: #f0fdf4;
        font-size: 13px;
        font-weight: 700;
    }

    .otp-input-row {
        display: flex;
        justify-content: center;
        gap: 9px;
        margin: 24px 0 18px;
    }

    .otp-digit {
        width: 48px;
        height: 58px;
        border: 1.5px solid #dbe3ef;
        border-radius: 14px;
        outline: none;
        color: #111827;
        background: #f8fafc;
        font-size: 25px;
        font-weight: 800;
        line-height: 58px;
        text-align: center;
        caret-color: #4f46e5;
        transition: border-color 0.18s ease, box-shadow 0.18s ease,
            background 0.18s ease, transform 0.18s ease;
    }

    .otp-digit:focus,
    .otp-digit.has-value {
        border-color: #6366f1;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        transform: translateY(-2px);
    }

    .otp-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin: 0 2px 16px;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    .otp-timer {
        color: #4f46e5;
        font-variant-numeric: tabular-nums;
        font-weight: 800;
    }

    .otp-timer.is-expired {
        color: #dc2626;
    }

    .otp-resend {
        border: 0;
        padding: 8px 12px;
        border-radius: 10px;
        color: #4f46e5;
        background: #eef2ff;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    .otp-resend:hover:not(:disabled) {
        transform: translateY(-1px);
    }

    .otp-resend:disabled {
        color: #94a3b8;
        background: #f1f5f9;
        cursor: not-allowed;
        opacity: 0.85;
    }

    .otp-security-note {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        padding: 12px 13px;
        border: 1px solid #ffedd5;
        border-radius: 13px;
        color: #9a3412;
        background: #fff7ed;
        font-size: 12px;
        line-height: 1.45;
        text-align: left;
    }

    .otp-security-note strong {
        display: block;
        margin-bottom: 2px;
        color: #7c2d12;
    }

    .otp-attempts {
        margin-top: 11px;
        color: #94a3b8;
        font-size: 11px;
        font-weight: 600;
    }

    @media (max-width: 420px) {
        .secure-otp-html {
            padding: 0 17px !important;
        }

        .secure-otp-actions {
            width: calc(100% - 34px) !important;
            margin-right: 17px !important;
            margin-left: 17px !important;
        }

        .otp-input-row {
            gap: 6px;
        }

        .otp-digit {
            width: 41px;
            height: 54px;
            border-radius: 12px;
            font-size: 22px;
            line-height: 54px;
        }
    }
</style>
<script>
    const loginOtpConfig = {
        expiresAt: <?php echo (int) $otpExpiresAt; ?> * 1000,
        resendAt: <?php echo (int) $otpResendAt; ?> * 1000,
        maskedPhone: <?php echo json_encode(
            $otpMaskedPhone,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ); ?>,
        attemptsRemaining: <?php echo (int) $otpAttemptsRemaining; ?>
    };

    (function prepareCurrentLoginForm() {
        const usernameInput = document.getElementById('username');
        const loginForm = usernameInput ? usernameInput.closest('form') : null;

        if (!loginForm) {
            return;
        }

        if (!loginForm.querySelector('input[name="action"]')) {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'login';
            loginForm.appendChild(actionInput);
        }

        if (!loginForm.querySelector('input[name="csrf_token"]')) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = <?php echo json_encode(
                loginCsrfToken(),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ); ?>;
            loginForm.appendChild(csrfInput);
        }
    })();

    function submitLoginAction(action, extraFields) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const fields = Object.assign({
            action: action,
            csrf_token: <?php echo json_encode(
                loginCsrfToken(),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            ); ?>
        }, extraFields || {});

        Object.keys(fields).forEach(function (name) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = fields[name];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    function formatOtpTime(totalSeconds) {
        const safeSeconds = Math.max(0, totalSeconds);
        const minutes = Math.floor(safeSeconds / 60);
        const seconds = safeSeconds % 60;

        return String(minutes).padStart(2, '0')
            + ':'
            + String(seconds).padStart(2, '0');
    }

    function showLoginOtpPrompt() {
        Swal.fire({
            title: 'Verify Your Identity',
            html:
                '<div class="otp-security-header">'
                    + '<div class="otp-shield">'
                        + '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">'
                            + '<path d="M12 3L5 6V11C5 15.55 7.84 19.74 12 21C16.16 19.74 19 15.55 19 11V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>'
                            + '<path d="M9.3 11.8L11.2 13.7L15.3 9.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'
                        + '</svg>'
                    + '</div>'
                + '</div>'
                + '<p class="otp-description">'
                    + 'Enter the 6-digit security code sent through WhatsApp.'
                    + '<br><span class="otp-phone">💬 +'
                    + loginOtpConfig.maskedPhone
                    + '</span>'
                + '</p>'
                + '<div class="otp-input-row" id="login-otp-inputs" role="group" aria-label="6-digit OTP">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="OTP digit 1">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="OTP digit 2">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="OTP digit 3">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="OTP digit 4">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="OTP digit 5">'
                    + '<input class="otp-digit" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" aria-label="OTP digit 6">'
                + '</div>'
                + '<div class="otp-meta">'
                    + '<span>Code expires in <span id="login-otp-expiry" class="otp-timer">05:00</span></span>'
                    + '<button type="button" id="resend-login-otp" class="otp-resend" disabled>'
                        + 'Resend in 60s'
                    + '</button>'
                + '</div>'
                + '<div class="otp-security-note">'
                    + '<span aria-hidden="true">⚠️</span>'
                    + '<span><strong>Keep your account secure</strong>'
                    + 'Never share this OTP with anyone, including support staff.</span>'
                + '</div>'
                + '<div class="otp-attempts">'
                    + loginOtpConfig.attemptsRemaining
                    + ' verification attempt(s) remaining'
                + '</div>',
            showCancelButton: true,
            confirmButtonText: '🔐 Verify & Sign In',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: false,
            customClass: {
                popup: 'secure-otp-popup',
                title: 'secure-otp-title',
                htmlContainer: 'secure-otp-html',
                actions: 'secure-otp-actions',
                confirmButton: 'secure-otp-confirm',
                cancelButton: 'secure-otp-cancel'
            },
            buttonsStyling: false,
            preConfirm: function () {
                const digits = Array.from(
                    document.querySelectorAll('.otp-digit')
                ).map(function (input) {
                    return input.value;
                }).join('');

                if (!/^\d{6}$/.test(digits)) {
                    Swal.showValidationMessage(
                        'Please enter the complete 6-digit OTP.'
                    );
                    return false;
                }

                if (Date.now() >= loginOtpConfig.expiresAt) {
                    Swal.showValidationMessage(
                        'This OTP has expired. Please sign in again.'
                    );
                    return false;
                }

                return digits;
            },
            didOpen: function () {
                const popup = Swal.getPopup();
                const confirmButton = Swal.getConfirmButton();
                const inputs = Array.from(
                    popup.querySelectorAll('.otp-digit')
                );
                const expiryElement = popup.querySelector('#login-otp-expiry');
                const resendButton = popup.querySelector('#resend-login-otp');
                let timerId = null;

                function otpValue() {
                    return inputs.map(function (input) {
                        return input.value;
                    }).join('');
                }

                function updateInputState(input) {
                    input.classList.toggle(
                        'has-value',
                        input.value !== ''
                    );
                }

                function fillOtp(value) {
                    const digits = String(value).replace(/\D/g, '').slice(0, 6);

                    inputs.forEach(function (input, index) {
                        input.value = digits[index] || '';
                        updateInputState(input);
                    });

                    const nextIndex = Math.min(digits.length, 5);
                    inputs[nextIndex].focus();

                    if (digits.length === 6) {
                        confirmButton.focus();
                    }
                }

                inputs.forEach(function (input, index) {
                    input.addEventListener('input', function () {
                        const cleanValue = input.value.replace(/\D/g, '');

                        if (cleanValue.length > 1) {
                            fillOtp(cleanValue);
                            return;
                        }

                        input.value = cleanValue.slice(-1);
                        updateInputState(input);

                        if (input.value && index < inputs.length - 1) {
                            inputs[index + 1].focus();
                            inputs[index + 1].select();
                        }

                        if (otpValue().length === 6) {
                            confirmButton.focus();
                        }
                    });

                    input.addEventListener('keydown', function (event) {
                        if (
                            event.key === 'Backspace'
                            && input.value === ''
                            && index > 0
                        ) {
                            inputs[index - 1].focus();
                            inputs[index - 1].value = '';
                            updateInputState(inputs[index - 1]);
                        }

                        if (event.key === 'ArrowLeft' && index > 0) {
                            event.preventDefault();
                            inputs[index - 1].focus();
                        }

                        if (
                            event.key === 'ArrowRight'
                            && index < inputs.length - 1
                        ) {
                            event.preventDefault();
                            inputs[index + 1].focus();
                        }
                    });

                    input.addEventListener('focus', function () {
                        input.select();
                    });
                });

                popup.querySelector('#login-otp-inputs')
                    .addEventListener('paste', function (event) {
                        event.preventDefault();
                        fillOtp(
                            event.clipboardData.getData('text')
                        );
                    });

                function updateTimers() {
                    const now = Date.now();
                    const expirySeconds = Math.max(
                        0,
                        Math.ceil((loginOtpConfig.expiresAt - now) / 1000)
                    );
                    const resendSeconds = Math.max(
                        0,
                        Math.ceil((loginOtpConfig.resendAt - now) / 1000)
                    );

                    expiryElement.textContent = formatOtpTime(expirySeconds);
                    expiryElement.classList.toggle(
                        'is-expired',
                        expirySeconds === 0
                    );

                    if (expirySeconds === 0) {
                        expiryElement.textContent = 'Expired';
                        confirmButton.disabled = true;
                    }

                    if (resendSeconds > 0) {
                        resendButton.disabled = true;
                        resendButton.textContent =
                            'Resend in ' + resendSeconds + 's';
                    } else {
                        resendButton.disabled = false;
                        resendButton.textContent = '↻ Resend OTP';
                    }
                }

                resendButton.addEventListener('click', function () {
                    if (resendButton.disabled) {
                        return;
                    }

                    resendButton.disabled = true;
                    resendButton.textContent = 'Sending...';
                    Swal.showLoading();

                    setTimeout(function () {
                        submitLoginAction('resend_otp');
                    }, 250);
                });

                updateTimers();
                timerId = window.setInterval(updateTimers, 1000);
                popup.dataset.otpTimerId = String(timerId);
                window.setTimeout(function () {
                    inputs[0].focus();
                }, 150);
            },
            willClose: function () {
                const popup = Swal.getPopup();

                if (popup && popup.dataset.otpTimerId) {
                    window.clearInterval(
                        Number(popup.dataset.otpTimerId)
                    );
                }
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                submitLoginAction('verify_otp', {
                    otp: result.value
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                submitLoginAction('cancel_otp');
            }
        });
    }

    <?php if ($alertText !== ''): ?>
    Swal.fire({
        title: <?php echo json_encode(
            $alertTitle,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ); ?>,
        text: <?php echo json_encode(
            $alertText,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ); ?>,
        icon: <?php echo json_encode(
            $alertIcon,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        ); ?>,
        <?php if ($loginSuccess): ?>
        timer: 1800,
        showConfirmButton: false,
        timerProgressBar: true
        <?php else: ?>
        confirmButtonColor: '#d33'
        <?php endif; ?>
    }).then(function () {
        <?php if ($loginSuccess): ?>
        window.location.href = 'dashboard';
        <?php elseif ($showOtpPrompt): ?>
        showLoginOtpPrompt();
        <?php endif; ?>
    });

    <?php if ($loginSuccess): ?>
    setTimeout(function () {
        window.location.href = 'dashboard';
    }, 1800);
    <?php endif; ?>
    <?php elseif ($showOtpPrompt): ?>
    showLoginOtpPrompt();
    <?php endif; ?>
</script>
