<?php
ob_start();
session_start();
include('./includes/config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');



// ----------------------
// WhatsApp Functions
// ----------------------

// Send OTP via WhatsApp
function sendWhatsAppOTP($mobile, $otp) {
    $message = "🔐 Your OTP for password reset is: *$otp*.\nIt is valid for 5 minutes.\n- Online Services";
    return sendWhatsAppMessage($mobile, $message);
}

// General WhatsApp message sender
function sendWhatsAppMessage($mobile, $message) {
    global $whatsappurl, $whapikey;

    $recipient = "91" . $mobile; // Add country code
    $apiUrl = "https://$whatsappurl/api/send-text"; // ✅ Correct endpoint

    // Use GET parameters in URL
    $params = http_build_query([
        'api_key' => $whapikey,
        'number'  => $recipient,
        'message' => $message
    ]);

    $finalUrl = $apiUrl . '?' . $params;

    $ch = curl_init($finalUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);

    if ($response === false) {
        // error_log("cURL Error (WhatsApp): " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    // Log raw response for debugging
    // error_log("WhatsApp API Response: " . $response);

    $decoded = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // error_log("WhatsApp API JSON Decode Error: " . json_last_error_msg());
        return false;
    }

    if (isset($decoded['status']) && $decoded['status'] === true) {
        return true;
    }

    // error_log("WhatsApp API failed: " . print_r($decoded, true));
    return false;
}

// Confirmation message after password reset
function sendConfirmation($name, $mobile) {
    $domain = $_SERVER['HTTP_HOST'];
    $message = "🌟 *DEAR* _" . strtoupper($name) . "_ 🌟\n\n" .
               "🔐 *Password Update Successful!*\n\n" .
               "✅ Your password has been changed.\n\n" .
               "📞 *Mobile:* $mobile\n\n" .
               "🔗 *Login:* https://$domain/login\n\n" .
               "🤝 *Online Services*";

    sendWhatsAppMessage($mobile, $message);
}

// ----------------------
// STEP 1: Phone Verification
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    $stmt = mysqli_prepare($ahk_conn, "SELECT id, name FROM users WHERE phone=?");
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $otp = rand(100000, 999999);
        $_SESSION['otp']        = $otp;
        $_SESSION['otp_time']   = time();
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_phone'] = $phone;

        if (sendWhatsAppOTP($phone, $otp)) {
            $_SESSION['step']        = 2;
            $_SESSION['success_msg'] = "OTP sent to your WhatsApp number.";
        } else {
            $_SESSION['step']      = 1;
            $_SESSION['error_msg'] = "Failed to send OTP. Please check your number or try again.";
        }
    } else {
        $_SESSION['step']      = 1;
        $_SESSION['error_msg'] = "Phone number not found.";
    }
    mysqli_stmt_close($stmt);
}

// ----------------------
// STEP 2: OTP Verification
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    if (!isset($_SESSION['otp']) || time() - $_SESSION['otp_time'] > 300) {
        $_SESSION['step']      = 1;
        $_SESSION['error_msg'] = "OTP expired. Please request a new one.";
    } elseif ($enteredOtp != $_SESSION['otp']) {
        $_SESSION['step']      = 2;
        $_SESSION['error_msg'] = "Invalid OTP. Try again.";
    } else {
        $_SESSION['step']        = 3;
        $_SESSION['success_msg'] = "OTP verified. You can now set a new password.";
    }
}

// ----------------------
// STEP 3: Password Reset
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'], $_POST['confirm_password'])) {
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $_SESSION['step']      = 3;
        $_SESSION['error_msg'] = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['step']      = 3;
        $_SESSION['error_msg'] = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt   = mysqli_prepare($ahk_conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hashed, $_SESSION['user_id']);

        if (mysqli_stmt_execute($stmt)) {
            sendConfirmation($_SESSION['user_name'], $_SESSION['user_phone']);
            $_SESSION['success_msg'] = "Password updated successfully!";
            $_SESSION['redirect']    = true;

            // Clear session sensitive data
            unset($_SESSION['step'], $_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_phone']);
        } else {
            $_SESSION['step']      = 3;
            $_SESSION['error_msg'] = "Password update failed. Try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

// ----------------------
// STEP FIX: This must come last
// ----------------------
$step = $_SESSION['step'] ?? 1;
?>




<!-- The HTML part continues... -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Forgot Password | <?php echo ahkweb('webname'); ?></title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="index, follow">
    <meta name="copyright" content="Print Portal, https://flex4upoint.site/">
    <meta name="author" content="FleXSecure Web Official Private Limited">
    <meta name="description" content="FleXSecure Web Official - PAN Online Services">
    <meta name="google-site-verification" content="l5mrMD-ugyR9mh-46yg0wUnY18dVCRYubZSfFbPsesc" />
    <meta property="og:type" content="website">
    <meta property="og:title" content="PRINT PORTAL - Aadhar Print Portal, Login, PVC Card, Smart Card Online">
    <meta property="og:description" content="PRINT PORTAL | AADHAAR PRINT | DIGITAL FAST PRINT | ONLINE PVC AADHAAR SMART CARD">
    <meta property="og:url" content="https://flex4upoint.site/">

    <!-- Favicon -->
    <link rel="shortcut icon" href="../admin/uploads/rpmn.png">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="../assets/LOGINPAGES/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/LOGINPAGES/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/LOGINPAGES/font/flaticon.css">
    <link rel="stylesheet" href="../assets/LOGINPAGES/style.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <!-- Responsive Custom Styles -->
    <style>
        @media (max-width: 767px) {
            #myDiv { display: none; }
        }
        @media (min-width: 767px) {
            #myD { display: none; }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="ldr"></div>
    <div id="preloader" class="preloader">
        <div class='inner'>
            <div class='line1'></div>
            <div class='line2'></div>
            <div class='line3'></div>
        </div>
    </div>

    <!-- Forgot Password Section -->
    <section class="fxt-template-animation fxt-template-layout34 sendDiv" data-bg-image="../assets/LOGINPAGES/img/elements/bgrpmn.png">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 mt-5">
                    <div class="fxt-form p-4 shadow bg-white rounded">
                        <div class="text-center mb-4">
                            <img src="../admin/uploads/rpmn.png" width="100" alt="Logo">
                            <h4>Forgot Password</h4>
                            <p>Enter your registered phone number.</p>
                        </div>

                       <?php if ($step == 1): ?>
    <form method="POST">
        <input type="text" name="phone" required placeholder="Registered Phone" class="form-control mb-3">
        <button type="submit" class="btn btn-primary btn-block">Send OTP</button>
    </form>
<?php elseif ($step == 2): ?>
    <form method="POST">
        <input type="text" name="otp" required placeholder="Enter OTP" class="form-control mb-3">
        <button type="submit" class="btn btn-warning btn-block">Verify OTP</button>
    </form>
<?php elseif ($step == 3): ?>
    <form method="POST">
        <input type="password" name="new_password" required placeholder="New Password" class="form-control mb-2">
        <input type="password" name="confirm_password" required placeholder="Confirm Password" class="form-control mb-3">
        <button type="submit" class="btn btn-success btn-block">Reset Password</button>
    </form>
<?php endif; ?>


                       <div class="mt-3 text-center">
    <a href="/auth">Back to Login</a>
</div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/LOGINPAGES/js/bootstrap.min.js"></script>
    <script src="../assets/LOGINPAGES/js/imagesloaded.pkgd.min.js"></script>
    <script src="../assets/LOGINPAGES/js/validator.min.js"></script>
    <script src="../assets/LOGINPAGES/js/main.js"></script>
    <script>
        // Hide preloader
        window.addEventListener('load', function () {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.style.display = 'none';
            }
        });
    </script>

    <!-- SweetAlert Notifications -->
    <script>
        <?php if (isset($_SESSION['error_msg'])): ?>
            swal("Error", "<?php echo addslashes($_SESSION['error_msg']); ?>", "error");
            <?php unset($_SESSION['error_msg']); ?>
        <?php elseif (isset($_SESSION['success_msg'])): ?>
            swal("Success", "<?php echo addslashes($_SESSION['success_msg']); ?>", "success")
            <?php if (isset($_SESSION['redirect'])): ?>
                .then(() => {
                    window.location.href = "login";
                });
                <?php unset($_SESSION['redirect']); ?>
            <?php else: ?>;
            <?php endif; ?>
            <?php unset($_SESSION['success_msg']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
