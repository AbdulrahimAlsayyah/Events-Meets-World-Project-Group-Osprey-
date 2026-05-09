<?php
session_start();
require 'EMWConfig.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Clean inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $vendorType = trim($_POST['vendorType']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $street = trim($_POST['street']);
    $buildingNumber = trim($_POST['buildingNumber']);
    $contactFirstName = trim($_POST['contactFirstName']);
    $contactLastName = trim($_POST['contactLastName']);
    $contactNumber = trim($_POST['contactNumber']);
    $altNumber = trim($_POST['altNumber']);

    // Allowed dropdown values
    $allowedVendorTypes = ['Catering', 'Entertainment', 'Photography'];
    $allowedLocations = [
        'North London',
        'East London',
        'Central London',
        'South London',
        'West London',
        'Maidstone',
        'Rochester',
        'Medway',
        'Chelmsford',
        'Colchester',
        'Southend-on-Sea',
        'Canterbury'
    ];

    // Validation
    if (strlen($name) < 2 || strlen($name) > 100) {
        $message = "Business name must be between 2 and 100 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $message = "Please enter a valid business email.";
    } elseif (strlen($password) < 8 || strlen($password) > 255) {
        $message = "Password must be between 8 and 255 characters.";
    } elseif (!in_array($vendorType, $allowedVendorTypes)) {
        $message = "Please select a valid vendor type.";
    } elseif (strlen($description) < 10 || strlen($description) > 1000) {
        $message = "Description must be between 10 and 1000 characters.";
    } elseif (!in_array($location, $allowedLocations)) {
        $message = "Please select a valid location.";
    } elseif (strlen($street) < 2 || strlen($street) > 25) {
        $message = "Street name must be between 2 and 25 characters.";
    } elseif (strlen($buildingNumber) < 1 || strlen($buildingNumber) > 5) {
        $message = "Building number must be between 1 and 5 characters.";
    } elseif (!preg_match('/[0-9]/', $buildingNumber)) {
        $message = "Building number must contain at least one number.";
    } elseif (!empty($contactFirstName) && (strlen($contactFirstName) < 2 || strlen($contactFirstName) > 50)) {
        $message = "Contact first name must be between 2 and 50 characters.";
    } elseif (!empty($contactFirstName) && preg_match('/[0-9]/', $contactFirstName)) {
        $message = "Contact first name must not contain numbers.";
    } elseif (!empty($contactLastName) && (strlen($contactLastName) < 2 || strlen($contactLastName) > 50)) {
        $message = "Contact last name must be between 2 and 50 characters.";
    } elseif (!empty($contactLastName) && preg_match('/[0-9]/', $contactLastName)) {
        $message = "Contact last name must not contain numbers.";
    } elseif (preg_match('/[A-Za-z]/', $contactNumber)) {
        $message = "Contact number must not contain letters.";
    } elseif (!empty($altNumber) && preg_match('/[A-Za-z]/', $altNumber)) {
        $message = "Alternative number must not contain letters.";
    } elseif (!empty($altNumber) && $altNumber === $contactNumber) {
        $message = "Alternative number must not be the same as contact number.";
    } else {

        // Check if email already exists
        $check = $conn->prepare("SELECT VendorID FROM Vendor WHERE VendorEmail = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "Email already registered.";
        } else {

            // Step 1: Insert Location
            $stmt = $conn->prepare("
                INSERT INTO VendorLocation (VendorLocation, VendorStreetName, VendorBuildingNumber)
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "sss",
                $location,
                $street,
                $buildingNumber
            );

            if (!$stmt->execute()) {
                $message = "Error saving Vendor Location.";
            } else {

                $locationID = $conn->insert_id;

                // Step 2: Insert Contact
                $stmt = $conn->prepare("
                    INSERT INTO VendorContact (ContactFirstName, ContactLastName, ContactNumber, AlternativeNumber)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "ssss",
                    $contactFirstName,
                    $contactLastName,
                    $contactNumber,
                    $altNumber
                );

                if (!$stmt->execute()) {
                    $message = "Error saving Vendor Contact.";
                } else {

                    $contactID = $conn->insert_id;

                    // Step 3: Get VendorTypeID
                    $stmt = $conn->prepare("SELECT VendorTypeID FROM VendorType WHERE VendorType = ?");
                    $stmt->bind_param("s", $vendorType);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $type = $result->fetch_assoc();

                    if (!$type) {
                        $message = "Invalid vendor type.";
                    } else {

                        $typeID = $type['VendorTypeID'];

                        // Step 4: Insert Vendor
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
                            $name,
                            $email,
                            $password,
                            $description
                        );

                        if ($stmt->execute()) {

                            // Get full vendor row for session
                            $vendorID = $conn->insert_id;

                            $getVendor = $conn->prepare("SELECT * FROM Vendor WHERE VendorID = ?");
                            $getVendor->bind_param("i", $vendorID);
                            $getVendor->execute();
                            $vendor = $getVendor->get_result()->fetch_assoc();

                            // Store full vendor row in session
                            $_SESSION['vendor'] = $vendor;

                            header("Location: EMWVendorDashboard.php");
                            exit;

                        } else {
                            $message = "Error creating Vendor account.";
                        }
                    }
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
    <title>Vendor Register</title>

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
        <input 
            type="text" 
            name="name" 
            placeholder="Business Name" 
            minlength="2"
            maxlength="100"
            title="Business name must be between 2 and 100 characters."
            required
        >

        <input 
            type="email" 
            name="email" 
            placeholder="Business Email" 
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

        <!-- Vendor Type -->
        <select name="vendorType" required>
            <option value="">Select Vendor Type</option>
            <option value="Catering">Catering</option>
            <option value="Entertainment">Entertainment</option>
            <option value="Photography">Photography</option>
        </select>

        <!-- Description -->
        <textarea 
            name="description" 
            placeholder="Business Description" 
            minlength="10"
            maxlength="1000"
            required
        ></textarea>

        <!-- Location -->
        <h3>Location</h3>

        <select name="location" required>
            <option value="">Select Area</option>
            <option value="North London">North London</option>
            <option value="East London">East London</option>
            <option value="Central London">Central London</option>
            <option value="South London">South London</option>
            <option value="West London">West London</option>
            <option value="Maidstone">Maidstone</option>
            <option value="Rochester">Rochester</option>
            <option value="Medway">Medway</option>
            <option value="Chelmsford">Chelmsford</option>
            <option value="Colchester">Colchester</option>
            <option value="Southend-on-Sea">Southend-on-Sea</option>
            <option value="Canterbury">Canterbury</option>
        </select>

        <input 
            type="text" 
            name="street" 
            placeholder="Street" 
            minlength="2"
            maxlength="25"
            pattern="[A-Za-z\s'-]+"
            title="Street name must be a valid street name"
            required
        >

        <input 
            type="text" 
            name="buildingNumber" 
            placeholder="Building Number" 
            minlength="1"
            maxlength="5"
            pattern=".*[0-9].*"
            title="Building number must contain at least one number."
            required
        >

        <!-- Contact -->
        <h3>Contact Person</h3>

        <input 
            type="text" 
            name="contactFirstName" 
            placeholder="First Name"
            minlength="2"
            maxlength="50"
            pattern="[A-Za-z\s'-]+"
            title="First name must not contain numbers."
        >

        <input 
            type="text" 
            name="contactLastName" 
            placeholder="Last Name"
            minlength="2"
            maxlength="50"
            pattern="[A-Za-z\s'-]+"
            title="Last name must not contain numbers."
        >

        <input 
            type="text" 
            name="contactNumber" 
            placeholder="Phone Number" 
            minlength="7"
            maxlength="20"
            pattern="[^A-Za-z]+"
            title="Phone number must not contain letters."
            required
        >

        <input 
            type="text" 
            name="altNumber" 
            placeholder="Alternative Number"
            minlength="7"
            maxlength="20"
            pattern="[^A-Za-z]+"
            title="Alternative number must not be the same as Phone Number."
        >

        <button type="submit">Register</button>

        <?php if (!empty($message)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

    </form>

    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWLoginVendor.php" class="btn">Login Instead</a>

</div>

</body>
</html>
