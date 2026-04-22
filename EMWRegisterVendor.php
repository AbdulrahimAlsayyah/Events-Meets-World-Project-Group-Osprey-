<?php
session_start();
require 'EMWConfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔹 Step 1: Insert Location
    $stmt = $conn->prepare("
        INSERT INTO VendorLocation (VendorLocation, VendorStreetName, VendorBuildingNumber)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "sss",
        $_POST['location'],
        $_POST['street'],
        $_POST['buildingNumber']
    );

    $stmt->execute();
    $locationID = $conn->insert_id;

    // 🔹 Step 2: Insert Contact
    $stmt = $conn->prepare("
        INSERT INTO VendorContact (ContactFirstName, ContactLastName, ContactNumber, AlternativeNumber)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $_POST['contactFirstName'],
        $_POST['contactLastName'],
        $_POST['contactNumber'],
        $_POST['altNumber']
    );

    $stmt->execute();
    $contactID = $conn->insert_id;

    // 🔹 Step 3: Get VendorTypeID
    $stmt = $conn->prepare("SELECT VendorTypeID FROM VendorType WHERE VendorType = ?");
    $stmt->bind_param("s", $_POST['vendorType']);
    $stmt->execute();
    $result = $stmt->get_result();
    $type = $result->fetch_assoc();
    $typeID = $type['VendorTypeID'];

    // 🔹 Step 4: Insert Vendor
    $stmt = $conn->prepare("
        INSERT INTO Vendor
        (VendorTypeFK, VendorLocationFK, VendorContactFK, VendorName, VendorEmail, VendorPassword, Description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iiissss",
        $typeID,
        $locationID,
        $contactID,
        $_POST['name'],
        $_POST['email'],
        $_POST['password'], // keeping your plain password logic
        $_POST['description']
    );

    $stmt->execute();

    // 🔹 Step 5: Auto login
    $_SESSION['vendor'] = [
        'VendorName' => $_POST['name'],
        'VendorEmail' => $_POST['email']
    ];

    header("Location: EMWVendorDashboard.php");
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

    <h2>Vendor Register</h2>

    <form method="POST">

        <!-- Business Info -->
        <input type="text" name="name" placeholder="Business Name" required>
        <input type="email" name="email" placeholder="Business Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <!-- Vendor Type -->
        <select name="vendorType" required>
            <option value="">Select Vendor Type</option>
            <option value="Catering">Catering</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Photography">Photography</option>
        </select>

        <!-- Description -->
        <textarea name="description" placeholder="Business Description" required></textarea>

        <!-- Location -->
        <h3>Location</h3>
        <select name="location" required>
            <option value="">Select Area</option>
            <option>North London</option>
            <option>East London</option>
            <option>Central London</option>
            <option>South London</option>
            <option>West London</option>
            <option>Maidstone</option>
            <option>Rochester</option>
            <option>Medway</option>
            <option>Chelmsford</option>
            <option>Colchester</option>
            <option>Southend-on-Sea</option>
            <option>Canterbury</option>
        </select>

        <input type="text" name="street" placeholder="Street Name" required>
        <input type="text" name="buildingNumber" placeholder="Building Number" required>

        <!-- Contact -->
        <h3>Contact Person</h3>
        <input type="text" name="contactFirstName" placeholder="First Name">
        <input type="text" name="contactLastName" placeholder="Last Name">
        <input type="text" name="contactNumber" placeholder="Phone Number" required>
        <input type="text" name="altNumber" placeholder="Alternative Number">

        <button type="submit">Register</button>

    </form>

    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWLoginVendor.php" class="btn">Login Instead</a>

</div>

</body>
</html>