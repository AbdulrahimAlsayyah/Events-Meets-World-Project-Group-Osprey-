<?php
session_start();
require 'EMWConfig.php';

$message = '';
$error = '';

if (isset($_POST['sendCode'])) {
    $accountType = $_POST['accountType'];
    $email = trim($_POST['email']);

    if ($accountType === 'customer') {
        $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
    } else {
        $stmt = $conn->prepare("SELECT VendorID FROM Vendor WHERE VendorEmail = ?");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();

    if (!$account) {
        $error = "No account found with that email.";
    } else {
        $code = rand(1000, 9999);

        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $code;
        $_SESSION['reset_account_type'] = $accountType;

        $subject = "Your EMW password reset code";

        $emailMessage = "Hello,
        
        Your one-time password reset code is: $code
        
        If you did not request this code, please ignore this email.
        
        Events Meets World";
        
        $headers = "From: no-reply@aa1694.webhosting.canterbury.ac.uk\r\n";
        $headers .= "Reply-To: no-reply@aa1694.webhosting.canterbury.ac.uk\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        if (mail($email, $subject, $emailMessage, $headers)) {
            $message = "A reset code has been sent to your email.";
        } else {
            $error = "Could not send email. Please try again.";
        }
    }
}

if (isset($_POST['changePassword'])) {
    $accountType = $_POST['accountType'];
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);
    $newPassword = trim($_POST['newPassword']);
    $confirmPassword = trim($_POST['confirmPassword']);

    if (!isset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_account_type'])) {
        $error = "Please request a code first.";
    } elseif ($email !== $_SESSION['reset_email']) {
        $error = "Email does not match the code request.";
    } elseif ($accountType !== $_SESSION['reset_account_type']) {
        $error = "Account type does not match the code request.";
    } elseif ($code != $_SESSION['reset_code']) {
        $error = "Invalid code.";
    } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 255) {
        $error = "Password must be between 8 and 255 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        if ($accountType === 'customer') {
            $stmt = $conn->prepare("UPDATE Customer SET Password = ? WHERE Email = ?");
        } else {
            $stmt = $conn->prepare("UPDATE Vendor SET VendorPassword = ? WHERE VendorEmail = ?");
        }

        $stmt->bind_param("ss", $newPassword, $email);

        if ($stmt->execute()) {
            unset($_SESSION['reset_code'], $_SESSION['reset_email'], $_SESSION['reset_account_type']);
            $message = "Password changed successfully.";
        } else {
            $error = "Could not change password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="EMWStyles.css">
</head>
<body>

<header class="hero">
    <img src="EMW Logo 1.png" alt="EMW Logo" class="logo">
</header>

<div class="form">
    <h2>Forgotten Your Password?</h2>
    <p>Enter your email and click "Send Code" to get your one-time 4-digit code.</p>

    <form method="POST">
        <select name="accountType" required>
            <option value="">Select Account Type</option>
            <option value="customer">Customer</option>
            <option value="vendor">Vendor</option>
        </select>

        <input type="email" name="email" placeholder="Email" required>

        <input type="text" name="code" placeholder="4-digit Code" maxlength="4">

        <input type="password" name="newPassword" placeholder="Create New Password" minlength="8">

        <input type="password" name="confirmPassword" placeholder="Confirm New Password" minlength="8">

        <button type="submit" name="sendCode" class="btn">Send Code</button>
        <button type="submit" name="changePassword" class="btn">Change Password</button>
    </form>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p style="color:green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <a href="EMWLoginCustomer.php" class="btn">Return to Customer Login</a>
    <a href="EMWLoginVendor.php" class="btn">Return to Vendor Login</a>
</div>

</body>
</html>