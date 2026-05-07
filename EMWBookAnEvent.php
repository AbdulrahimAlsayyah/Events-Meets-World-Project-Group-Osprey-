<?php
session_start();
require 'EMWConfig.php';

$message = "";

// Ensure customer logged in
if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

$customerID = $_SESSION['customer']['CustomerID'];

// Get vendor ID from Browse Vendors page URL
$selectedVendorID = $_GET['vendorID'] ?? $_POST['vendorID'] ?? null;

if (!$selectedVendorID) {
    header("Location: EMWBookAnEvent.php");
    exit;
}

// Load selected vendor
$stmtVendor = $conn->prepare("
    SELECT VendorID, VendorName
    FROM Vendor
    WHERE VendorID = ?
");

$stmtVendor->bind_param("i", $selectedVendorID);
$stmtVendor->execute();
$vendorResult = $stmtVendor->get_result();
$selectedVendor = $vendorResult->fetch_assoc();

if (!$selectedVendor) {
    header("Location: EMWBookAnEvent.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventType = $_POST['eventType'];
    $eventDate = $_POST['eventDate'];
    $vendorID  = $_POST['vendorID'];

    $guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;
    $totalPrice = $guests * 1.00;

    $today = date("Y-m-d");

    if ($eventDate <= $today) {
        $message = "Error: Event date must be in the future.";
    } else {

        $conn->begin_transaction();

        try {
            // Step 1: Create empty refund record
            $stmt = $conn->prepare("
                INSERT INTO Refund (RefundStatus, RefundAmount, RefundDate)
                VALUES (0, NULL, NULL)
            ");
            $stmt->execute();

            $refundID = $conn->insert_id;

            // Step 2: Insert payment
            $paymentSuccessful = 1;

            $stmt = $conn->prepare("
                INSERT INTO Payment
                (CustomerFK, RefundFK, TotalPrice, TransactionAlerts, PaymentSuccessful, TransactionDate)
                VALUES (?, ?, ?, NULL, ?, NOW())
            ");

            $stmt->bind_param("iidi", $customerID, $refundID, $totalPrice, $paymentSuccessful);
            $stmt->execute();

            $paymentID = $conn->insert_id;

            // Step 3: Insert event for selected vendor
            $stmt = $conn->prepare("
                INSERT INTO Eventt
                (PaymentFK, VendorFK, EventDate, EventStates, EventType)
                VALUES (?, ?, ?, 'Scheduled', ?)
            ");

            $stmt->bind_param("iiss", $paymentID, $vendorID, $eventDate, $eventType);
            $stmt->execute();

            $conn->commit();

            $message = "Event booked successfully with " . htmlspecialchars($selectedVendor['VendorName']) . 
                       "! Total cost: £" . number_format($totalPrice, 2);

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Booking failed. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Event</title>
    <link rel="stylesheet" href="EMWStyles.css">

    <style>
        .sidebar a,
        .sidebar a:visited,
        .sidebar a:hover,
        .sidebar a:active {
            color: white;
            text-decoration: none;
        }

        .btn {
            padding: 12px 35px;
            background: black;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn:hover {
            background: #333;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #999;
            box-sizing: border-box;
            font-size: 15px;
        }

        .success-message {
            color: green;
        }

        .error-message {
            color: red;
        }
    </style>
</head>

<body>

<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">
    <div class="nav-links">
        <a href="EMWAboutUs.php">About Us</a>
        <a href="#">Contact Vendors</a>
    </div>
    <a href="EMWAboutUs.php" class="logout-btn">Log Out</a>
</div>

<div class="dashboard">

    <aside class="sidebar">
        <ul>
            <li><a href="EMWBrowseVendors.php">Go back to Browse Vendors</a></li>
        </ul>
    </aside>

    <main class="main">

        <h2>Book an Event</h2>
        <p>Fill in the details below to schedule your event.</p>

        <?php if ($message): ?>
            <p class="<?php echo (strpos($message, 'Error') !== false || strpos($message, 'failed') !== false) ? 'error-message' : 'success-message'; ?>">
                <b><?php echo $message; ?></b>
            </p>
        <?php endif; ?>

        <form method="POST" class="form">

            <input type="hidden" name="vendorID" value="<?php echo htmlspecialchars($selectedVendor['VendorID']); ?>">
            
            <label>You are Booking an Event From This Vendor</label>
            <input 
                type="text" 
                value="<?php echo htmlspecialchars($selectedVendor['VendorName']); ?>" 
                readonly
            >

            <label>Event Type *</label>
            <select name="eventType" required>
                <option value="">Select</option>
                <option value="Wedding">Wedding</option>
                <option value="Birthday Party">Birthday Party</option>
                <option value="Anniversary">Anniversary</option>
            </select>

            <label>Event Date *</label>
            <input type="date" name="eventDate" min="<?php echo date('Y-m-d', strtotime('+2 day')); ?>" required>

            <label>Number of Guests</label>
            <input type="number" name="guests" min="1" step="1" value="50">

            <label>Venue / Location</label>
            <input type="text" name="location">

            <label>Special Requirements</label>
            <textarea name="requirements"></textarea>

            <h3>Payment (Simulated)</h3>

            <input type="text" name="cardNumber" placeholder="Card Number">
            <input type="text" name="expiryDate" placeholder="Expiry Date">
            <input type="text" name="cvv" placeholder="CVV">

            <br><br>
            <button type="submit" class="btn" onclick="this.disabled=true; this.form.submit();">
                Book Event
            </button>

        </form>

    </main>

</div>

</body>
</html>
