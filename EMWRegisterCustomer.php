<?php
session_start();
require 'EMWConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔹 Step 1: Insert Address
    $stmt = $conn->prepare("
        INSERT INTO CustomerAddress (HouseNumber, Street, City, PostCode)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $_POST['houseNumber'],
        $_POST['street'],
        $_POST['city'],
        $_POST['postCode']
    );

    $stmt->execute();
    $addressID = $conn->insert_id;

    // 🔹 Step 2: Create Membership (default = Active)
    $stmt = $conn->prepare("
        INSERT INTO Membership (Description, MembershipStates)
        VALUES ('Exclusive Deals, Up to 30% off on all Events', 'Inactive')
    ");
    $stmt->execute();
    $membershipID = $conn->insert_id;

    // 🔹 Step 3: Insert Customer
    $stmt = $conn->prepare("
        INSERT INTO Customer
        (CustomerAddressFK, MembershipFK, FirstName, LastName, Email, Password, ContactNumber)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisssss",
        $addressID,
        $membershipID,
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['password'], // (keeping your plain password logic)
        $_POST['phone']
    );

    $stmt->execute();

    // 🔹 Step 4: Auto login
    $_SESSION['customer'] = [
        'FirstName' => $_POST['firstName'],
        'Email' => $_POST['email']
    ];

    header("Location: EMWClientDashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <input type="text" name="firstName" placeholder="First Name" required>
        <input type="text" name="lastName" placeholder="Last Name" required>

        <!-- Contact -->
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="phone" placeholder="Contact Number" required>

        <!-- Address -->
        <h3>Address</h3>
        <input type="text" name="houseNumber" placeholder="House Number (e.g. 4A)" required>
        <input type="text" name="street" placeholder="Street" required>
        <input type="text" name="city" placeholder="City" required>
        <input type="text" name="postCode" placeholder="Post Code" required>

        <button type="submit">Register</button>
    </form>

    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWLoginCustomer.php" class="btn">Login Instead</a>
</div>

</body>
</html>