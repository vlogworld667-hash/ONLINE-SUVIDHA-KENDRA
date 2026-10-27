<?php
ob_start();
session_start();
include('./includes/config.php');
$registrationType = 'without_otp';

$settingsResult = mysqli_query(
    $ahk_conn,
    'SELECT registration_type FROM settings LIMIT 1'
);

if ($settingsResult && mysqli_num_rows($settingsResult) > 0) {
    $settingsData = mysqli_fetch_assoc($settingsResult);
    $registrationType = $settingsData['registration_type'] ?? 'without_otp';
}

$registrationType = strtolower(trim((string) $registrationType));

// This is the OTP registration page. Redirect only when OTP is disabled.
if ($registrationType !== 'with_otp') {
    header('Location: register');
    exit;
}
/* referral code */
$ref_code = '';
if (!empty($_GET['ref'])) {
    $ref_code = base64_decode(mysqli_real_escape_string($ahk_conn, $_GET['ref']));
}
include('./template/ahkweb/authentication-signup-x.php');
/* AJAX: SEND OTP */
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    ob_clean();
    header('Content-Type: application/json');

    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Name and phone are required.']);
        exit;
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['status' => 'error', 'message' => 'Enter valid 10 digit phone number.']);
        exit;
    }

    $checkPhone = mysqli_query($ahk_conn, "SELECT id FROM users WHERE phone='" . mysqli_real_escape_string($ahk_conn, $phone) . "'");
    if (mysqli_num_rows($checkPhone) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number already registered.']);
        exit;
    }

    $otp = rand(100000, 999999);

    $_SESSION['signup_otp'] = $otp;
    $_SESSION['signup_otp_phone'] = $phone;
    $_SESSION['signup_otp_name'] = $name;
    $_SESSION['signup_otp_expiry'] = time() + 300;
    $_SESSION['signup_otp_verified'] = false;

    $otpMessage =
        "🔐 OTP Verification\n\n" .
        "Hello {$name},\n" .
        "Your signup OTP is: {$otp}\n\n" .
        "This OTP is valid for 5 minutes.\n" .
        "Do not share it with anyone.\n\n" .
        "— {$websiteName}";

    $msg = urlencode($otpMessage);

    $api_url = "https://{$whatsappurl}/send-message"
        . "?api_key={$whapikey}"
        . "&sender={$whsender}"
        . "&number=91{$phone}"
        . "&message={$msg}";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($curl_error)) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to send OTP']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'OTP sent successfully on WhatsApp.'
    ]);
    exit;
}
/* =========================================================
   AJAX: VERIFY OTP
========================================================= */
if (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    ob_clean();
    header('Content-Type: application/json');

    $phone = trim($_POST['phone'] ?? '');
    $otp   = trim($_POST['otp'] ?? '');

    if (empty($phone) || empty($otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Phone and OTP are required.']);
        exit;
    }

    if (
        empty($_SESSION['signup_otp']) ||
        empty($_SESSION['signup_otp_phone']) ||
        empty($_SESSION['signup_otp_expiry'])
    ) {
        echo json_encode(['status' => 'error', 'message' => 'Please send OTP first.']);
        exit;
    }

    if (time() > $_SESSION['signup_otp_expiry']) {
        unset($_SESSION['signup_otp'], $_SESSION['signup_otp_phone'], $_SESSION['signup_otp_expiry'], $_SESSION['signup_otp_verified']);
        echo json_encode(['status' => 'error', 'message' => 'OTP expired. Please request a new OTP.']);
        exit;
    }

    if ($phone !== $_SESSION['signup_otp_phone']) {
        echo json_encode(['status' => 'error', 'message' => 'Phone number does not match OTP request.']);
        exit;
    }

    if ($otp != $_SESSION['signup_otp']) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP.']);
        exit;
    }

    $_SESSION['signup_otp_verified'] = true;

    echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully.']);
    exit;
}

/* =========================================================
   FINAL FORM SUBMIT
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['phone']) && !isset($_POST['action'])) {

    $name     = mysqli_real_escape_string($ahk_conn, $_POST['name']);
    $email    = mysqli_real_escape_string($ahk_conn, $_POST['email']);
    $phone    = mysqli_real_escape_string($ahk_conn, $_POST['phone']);
    $usertype = mysqli_real_escape_string($ahk_conn, $_POST['usertype']);
    $password = $_POST['password'];

    $order_id = "Flx_" . rand(100000, 999999);
    $date     = date("Y-m-d");

    /* ---------------- OTP CHECK ---------------- */
    if (
        empty($_SESSION['signup_otp_verified']) ||
        $_SESSION['signup_otp_verified'] !== true ||
        empty($_SESSION['signup_otp_phone']) ||
        $_SESSION['signup_otp_phone'] !== $phone
    ) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                swal('OTP Required!', 'Please verify WhatsApp OTP before registration.', 'error');
            });
        </script>";
    } else {

        /* ---------------- BLOCK ADMIN SIGNUP ---------------- */
        if (strcasecmp($usertype, "admin") === 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    swal('Access Denied!', 'Invalid registration type.', 'error');
                });
            </script>";
            exit;
        }

        /* ---------------- DUPLICATE CHECK ---------------- */
        $check = mysqli_query($ahk_conn, "SELECT id FROM users WHERE phone='$phone' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    swal('Oops!', 'Email or phone already exists!', 'error');
                });
            </script>";
            exit;
        }

        /* ---------------- PASSWORD HASH ---------------- */
        $vpass = password_hash($password, PASSWORD_DEFAULT);

        /* ---------------- REGISTRATION FEES ---------------- */
        if ($usertype === "retailer") {
            $amount = (int)$webdata['ret_reg_fee'];
        } elseif ($usertype === "distributor") {
            $amount = (int)$webdata['dist_reg_fee'];
        } else {
            $amount = 999;
        }

        /* ---------------- FREE REGISTRATION ---------------- */
        $isFree =
            ($usertype === "retailer" && $webdata['ret_reg_fee'] == 0) ||
            ($usertype === "distributor" && $webdata['dist_reg_fee'] == 0);

        if ($isFree) {

            $res = mysqli_query($ahk_conn, "
                INSERT INTO users (name, phone, email, password, type, status, referred_by, order_id, date)
                VALUES ('$name', '$phone', '$email', '$vpass', '$usertype', '1', '$ref_code', '$order_id', '$date')
            ");

            if (!$res) {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        swal('Error!', 'Database insertion failed!', 'error');
                    });
                </script>";
                exit;
            }

            /* -------- REFERRAL BONUS -------- */
            if (!empty($ref_code)) {
                mysqli_query($ahk_conn, "
                    INSERT INTO referal (phone, referred_by, txn_no, referral_balance, status)
                    VALUES ('$phone', '$ref_code', 'REF" . rand(10000,99999) . "', '{$webdata['ref_bal']}', '1')
                ");
            }

            /* -------- WHATSAPP MESSAGE -------- */
            if (!empty($phone) && !empty($name)) {
                $message =
                    "🎉 Welcome to {$websiteName}!\n\n" .
                    "Dear {$name},\n\n" .
                    "Your {$usertype} account has been created successfully.\n\n" .
                    "📱 Mobile: {$phone}\n" .
                    "🔑 Password: {$password}\n\n" .
                    "Please keep your login details safe.\n\n" .
                    "— Support Team";

                $msg = urlencode($message);

                $api_url = "https://{$whatsappurl}/send-message"
                    . "?api_key={$whapikey}"
                    . "&sender={$whsender}"
                    . "&number=91{$phone}"
                    . "&message={$msg}";

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                curl_exec($ch);
                curl_close($ch);
            }

            // Clear OTP session after success
            unset(
                $_SESSION['signup_otp'],
                $_SESSION['signup_otp_phone'],
                $_SESSION['signup_otp_name'],
                $_SESSION['signup_otp_expiry'],
                $_SESSION['signup_otp_verified']
            );

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    swal({
                        title: 'Registration Successful!',
                        text: 'Your {$usertype} account has been created successfully.',
                        icon: 'success'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                });
            </script>";
            exit;
        }

        /* ---------------- PAID REGISTRATION ---------------- */
        $res = mysqli_query($ahk_conn, "
            INSERT INTO users (name, phone, email, password, type, status, referred_by, order_id, date)
            VALUES ('$name', '$phone', '$email', '$vpass', '$usertype', '0', '$ref_code', '$order_id', '$date')
        ");

        if (!$res) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    swal('Error!', 'Database insertion failed!', 'error');
                });
            </script>";
            exit;
        }

        /* -------- REFERRAL ENTRY -------- */
        if (!empty($ref_code)) {
            mysqli_query($ahk_conn, "
                INSERT INTO referal (phone, referred_by, txn_no, referral_balance, status)
                VALUES ('$phone', '$ref_code', 'REF" . rand(10000,99999) . "', '{$webdata['ref_bal']}', '1')
            ");
        }

        /* ---------------- PAYMENT MODE ---------------- */
        $mode = $_POST['mode'] ?? '';

        // Clear OTP session before redirecting
        unset(
            $_SESSION['signup_otp'],
            $_SESSION['signup_otp_phone'],
            $_SESSION['signup_otp_name'],
            $_SESSION['signup_otp_expiry'],
            $_SESSION['signup_otp_verified']
        );

        if ($mode === "paytm") {

            echo "<form method='post' action='./qr/pay.php' name='f2'>
                <input type='hidden' name='ORDER_ID' value='$order_id'>
                <input type='hidden' name='CUST_ID' value='$phone'>
                <input type='hidden' name='TXN_AMOUNT' value='$amount'>
            </form>
            <script>document.f2.submit();</script>";
            exit;

        } elseif ($mode === "qr") {

            mysqli_query($ahk_conn, "
                INSERT INTO wallet (phone, amount, txn_id, email, status, date, PAYMENTMODE)
                VALUES ('$phone', '$amount', '$order_id', '$email', 'pending', '$date', 'REGQR')
            ");

            echo "<form method='post' action='Flex4uPoint4in1/regpg.php' name='f2'>
                <input type='hidden' name='frompage' value='reg'>
                <input type='hidden' name='order_id' value='$order_id'>
                <input type='hidden' name='phone' value='$phone'>
                <input type='hidden' name='amount' value='$amount'>
            </form>
            <script>document.f2.submit();</script>";
            exit;
        }
    }
}

/* ---------------- PAYMENT RETURN MESSAGES ---------------- */
$successmsg = !empty($_POST['successmsg']) && $_POST['successmsg'] === "true";
$failedmsg  = !empty($_POST['failedmsg']) && $_POST['failedmsg'] === "true";
?>