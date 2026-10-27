<?php
include('./includes/config.php');

/* ---------------- REFERRAL CODE ---------------- */
$ref_code = '';
if (!empty($_GET['ref'])) {
    $ref_code = base64_decode(mysqli_real_escape_string($ahk_conn, $_GET['ref']));
}

include('./template/ahkweb/authentication-signup.php');

/* ---------------- FORM SUBMIT ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['phone'])) {

    $name     = mysqli_real_escape_string($ahk_conn, $_POST['name']);
    $email    = mysqli_real_escape_string($ahk_conn, $_POST['email']);
    $phone    = mysqli_real_escape_string($ahk_conn, $_POST['phone']);
    $usertype = mysqli_real_escape_string($ahk_conn, $_POST['usertype']);
    $password = $_POST['password'];

    $order_id = "Flx_" . rand(100000, 999999);
    $date     = date("Y-m-d");

    /* ---------------- BLOCK ADMIN SIGNUP ---------------- */
    if (strcasecmp($usertype, "admin") === 0) {
        echo "<script>Swal.fire('Access Denied!', 'Invalid registration type.', 'error');</script>";
        exit;
    }

    /* ---------------- DUPLICATE CHECK ---------------- */
    $check = mysqli_query($ahk_conn, "SELECT id FROM users WHERE phone='$phone' OR email='$email'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>Swal.fire('Oops!', 'Email or phone already exists!', 'error');</script>";
        exit;
    }

    /* ---------------- PASSWORD HASH ---------------- */
    $vpass = password_hash($password, PASSWORD_DEFAULT);

    /* ==================== FREE REGISTRATION (FOR ALL) ==================== */
    $res = mysqli_query($ahk_conn, "
        INSERT INTO users (name, phone, email, password, type, status, referred_by, order_id, date)
        VALUES ('$name', '$phone', '$email', '$vpass', '$usertype', '1', '$ref_code', '$order_id', '$date')
    ");

    if (!$res) {
        echo "<script>Swal.fire('Error!', 'Database insertion failed!', 'error');</script>";
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

    echo "<script>
        Swal.fire({
            title: 'Registration Successful!',
            text: 'Your {$usertype} account has been created successfully.',
            icon: 'success'
        }).then(() => {
            window.location.href = 'login.php';
        });
    </script>";
    exit;
}

/* ---------------- PAYMENT RETURN MESSAGES (Optional - can keep or remove) ---------------- */
if (!empty($_POST['successmsg']) && $_POST['successmsg'] === "true") {
    echo "<script>Swal.fire('Success!', 'Your account was created successfully!', 'success');</script>";
}

if (!empty($_POST['failedmsg']) && $_POST['failedmsg'] === "true") {
    echo "<script>Swal.fire('Payment Failed', 'If payment was deducted, contact support!', 'error');</script>";
}
?>