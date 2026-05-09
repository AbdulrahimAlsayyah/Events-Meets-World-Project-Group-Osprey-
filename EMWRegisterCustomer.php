<?php
session_start();
require 'EMWConfig.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Clean inputs
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $houseNumber = trim($_POST['houseNumber']);
    $street = trim($_POST['street']);
    $city = trim($_POST['city']);
    $postCode = trim($_POST['postCode']);

    // Validation
    if (strlen($firstName) < 2 || strlen($firstName) > 50) {
        $message = "First name must be between 2 and 50 characters.";
    } elseif (preg_match('/[0-9]/', $firstName)) {
        $message = "First name must not contain numbers.";
    } elseif (strlen($lastName) < 2 || strlen($lastName) > 50) {
        $message = "Last name must be between 2 and 50 characters.";
    } elseif (preg_match('/[0-9]/', $lastName)) {
        $message = "Last name must not contain numbers.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8 || strlen($password) > 255) {
        $message = "Password must be between longer than 8";
    } elseif (preg_match('/[A-Za-z]/', $phone)) {
        $message = "Contact number must not contain letters.";
    } elseif (strlen($houseNumber) < 1 || strlen($houseNumber) > 5) {
        $message = "House number must be between 1 and 5 characters.";
    } elseif (!preg_match('/[0-9]/', $houseNumber)) {
        $message = "House number must contain at least one number.";
    } elseif (strlen($street) < 2 || strlen($street) > 25) {
        $message = "Street must be between 2 and 25 characters.";
    } elseif (strlen($city) < 2 || strlen($city) > 25) {
        $message = "City must be between 2 and 25 characters.";
    } elseif (preg_match('/[0-9]/', $city)) {
        $message = "City must not contain numbers.";
    } elseif (strlen($postCode) < 5 || strlen($postCode) > 10) {
        $message = "Post code must be between 5 and 10 characters.";
    } elseif (!preg_match('/[0-9]/', $postCode)) {
        $message = "Post code must contain at least one number.";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "Email already registered.";
        } else {

            // Step 1: Insert Address
            $stmt = $conn->prepare("
                INSERT INTO CustomerAddress (HouseNumber, Street, City, PostCode)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssss",
                $houseNumber,
                $street,
                $city,
                $postCode
            );

            if (!$stmt->execute()) {
                $message = "Error saving address.";
            } else {

                $addressID = $conn->insert_id;

                // Step 2: Create Membership
                $stmt = $conn->prepare("
                    INSERT INTO Membership (Description, MembershipStates)
                    VALUES ('Exclusive Deals, Up to 30% off on all Events', 'Inactive')
                ");
                $stmt->execute();
                $membershipID = $conn->insert_id;

                // Step 3: Insert Customer
                $stmt = $conn->prepare("
                    INSERT INTO Customer
                    (CustomerAddressFK, MembershipFK, FirstName, LastName, Email, Password, ContactNumber)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iisssss",
                    $addressID,
                    $membershipID,
                    $firstName,
                    $lastName,
                    $email,
                    $password,
                    $phone
                );

                if ($stmt->execute()) {

                    // Get full user row for session
                    $userID = $conn->insert_id;

                    $getUser = $conn->prepare("SELECT * FROM Customer WHERE CustomerID = ?");
                    $getUser->bind_param("i", $userID);
                    $getUser->execute();
                    $user = $getUser->get_result()->fetch_assoc();

                    // Store full customer row in session
                    $_SESSION['customer'] = $user;

                    header("Location: EMWClientDashboard.php");
                    exit;

                } else {
                    $message = "Error creating account.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Register</title>

    <link rel="stylesheet" href="EMWStyles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;800;900&display=swap" rel="stylesheet">
</head>
<body>

<header class="hero">
    <img src="EMW Logo 1.png" alt="EMW Logo" class="logo">
</header>

<div class="form">
    <h2>Customer Register</h2>

    <form method="POST">

        <!-- Personal Info -->
        <input 
            type="text" 
            name="firstName" 
            placeholder="First Name" 
            minlength="2"
            maxlength="50"
            pattern="[A-Za-z\s'-]+"
            title="First name must not contain numbers."
            required
        >

        <input 
            type="text" 
            name="lastName" 
            placeholder="Last Name" 
            minlength="2"
            maxlength="50"
            pattern="[A-Za-z\s'-]+"
            title="Last name must not contain numbers."
            required
        >

        <!-- Contact -->
        <input 
            type="email" 
            name="email" 
            placeholder="Email" 
            maxlength="255"
            required
        >

        <input 
            type="password" 
            name="password" 
            placeholder="Password" 
            minlength="8"
            maxlength="255"
            required
        >

        <input 
            type="text" 
            name="phone" 
            placeholder="Contact Number" 
            minlength="7"
            maxlength="14"
            pattern="[^A-Za-z]+"
            title="Contact number must not contain letters."
            required
        >

        <!-- Address -->
        <h3>Address</h3>

        <input 
            type="text" 
            name="houseNumber" 
            placeholder="House Number (e.g. 4A)" 
            minlength="1"
            maxlength="5"
            pattern=".*[0-9].*"
            title="House number must contain at least one number."
            required
        >

        <input 
            type="text" 
            name="street" 
            placeholder="Street" 
            minlength="2"
            maxlength="25"
            title="Street name must be a valid street name"
            required
        >

        <input 
            type="text" 
            name="city" 
            placeholder="City" 
            minlength="2"
            maxlength="25"
            pattern="[A-Za-z\s'-]+"
            title="City must not contain numbers."
            required
        >

        <input 
            type="text" 
            name="postCode" 
            placeholder="Post Code" 
            minlength="5"
            maxlength="10"
            pattern=".*[0-9].*"
            title="Post code must contain at least one number."
            required
        >

        <button type="submit">Register</button>
    </form>

    <?php if (!empty($message)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWLoginCustomer.php" class="btn">Login Instead</a>
</div>

</body>
</html>
