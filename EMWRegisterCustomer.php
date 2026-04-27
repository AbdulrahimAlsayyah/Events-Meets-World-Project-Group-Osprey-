<?php
session_start();
require 'EMWConfig.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if email already exists
    $check = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
    $check->bind_param("s", $_POST['email']);
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
            $_POST['houseNumber'],
            $_POST['street'],
            $_POST['city'],
            $_POST['postCode']
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
                $_POST['firstName'],
                $_POST['lastName'],
                $_POST['email'],
                $_POST['password'], // keeping your plain password logic
                $_POST['phone']
            );

            if ($stmt->execute()) {

                // Get full user row for session
                $userID = $conn->insert_id;

                $getUser = $conn->prepare("SELECT * FROM Customer WHERE CustomerID = ?");
                $getUser->bind_param("i", $userID);
                $getUser->execute();
                $user = $getUser->get_result()->fetch_assoc();

                // Store FULL row (important fix)
                $_SESSION['customer'] = $user;

                header("Location: EMWClientDashboard.php");
                exit;

            } else {
                $message = "Error creating account.";
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

    <!-- Error message -->
    <?php if (!empty($message)): ?>
        <p style="color:red;"><?php echo $message; ?></p>
    <?php endif; ?>

    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWLoginCustomer.php" class="btn">Login Instead</a>
</div>

</body>
</html>
