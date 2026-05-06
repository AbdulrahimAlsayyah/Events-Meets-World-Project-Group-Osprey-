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

// Load vendors for dropdown
$vendors = $conn->query("SELECT VendorID, VendorName FROM Vendor");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eventType = $_POST['eventType'];
    $eventDate = $_POST['eventDate'];
    $vendorID  = $_POST['vendor'];

    // Get number of guests
    $guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;

    // Calculate price (£1 per guest)
    $totalPrice = $guests * 1.00;

    //BACKEND VALIDATION: ensure date is in the future
    $today = date("Y-m-d");

    if ($eventDate <= $today) {
        $message = "Error: Event date must be in the future.";
    } else {

        $conn->begin_transaction();

        try {

            // Step 1: Create EMPTY refund record
            $stmt = $conn->prepare("
                INSERT INTO Refund (RefundStatus, RefundAmount, RefundDate)
                VALUES (0, NULL, NULL)
            ");
            $stmt->execute();

            $refundID = $conn->insert_id;

            // Step 2: Insert Payment
            $paymentSuccessful = 1;

            $stmt = $conn->prepare("
                INSERT INTO Payment
                (CustomerFK, RefundFK, TotalPrice, TransactionAlerts, PaymentSuccessful, TransactionDate)
                VALUES (?, ?, ?, NULL, ?, NOW())
            ");

            $stmt->bind_param("iidi", $customerID, $refundID, $totalPrice, $paymentSuccessful);
            $stmt->execute();

            $paymentID = $conn->insert_id;

            // If payment fails → process refund
            if ($paymentSuccessful == 0) {

                $stmt = $conn->prepare("
                    UPDATE Refund
                    SET RefundStatus = 1,
                        RefundAmount = ?,
                        RefundDate = CURDATE()
                    WHERE RefundID = ?
                ");

                $stmt->bind_param("di", $totalPrice, $refundID);
                $stmt->execute();

                throw new Exception("Payment failed");
            }

            // Step 3: Insert Event
            $stmt = $conn->prepare("
                INSERT INTO Eventt
                (PaymentFK, VendorFK, EventDate, EventStates, EventType)
                VALUES (?, ?, ?, 'Scheduled', ?)
            ");

            $stmt->bind_param("iiss", $paymentID, $vendorID, $eventDate, $eventType);
            $stmt->execute();

            $conn->commit();

            $message = "Event booked successfully! Total cost: £" . number_format($totalPrice, 2);

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
        .btn { padding: 10px; background: black; color: white; border: none; }

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

        .success-message { color: green; }
        .error-message { color: red; }
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
        <h3>Dashboard</h3>
        <ul>
            <li>My Events</li>
            <li>Browse Vendors</li>
            <li>Messages</li>
            <li>Reviews</li>
            <li>Settings</li>
        </ul>
    </aside>

    <main class="main">

        <h2>Book an Event</h2>
        <p>Fill in the details below to schedule your event.</p>

        <?php if ($message): ?>
            <p class="<?php echo (strpos($message, 'Error') !== false) ? 'error-message' : 'success-message'; ?>">
                <b><?php echo $message; ?></b>
            </p>
        <?php endif; ?>

        <form method="POST" class="form">

            <label>Event Type *</label>
            <select name="eventType" required>
                <option value="">Select</option>
                <option>Wedding</option>
                <option>Birthday Party</option>
                <option>Anniversary</option>
            </select>

            <label>Event Date *</label>
            <!-- FRONTEND VALIDATION -->
            <input type="date" name="eventDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>

            <label>Select Vendor *</label>
            <select name="vendor" required>
                <option value="">Choose Vendor</option>
                <?php while ($v = $vendors->fetch_assoc()): ?>
                    <option value="<?php echo $v['VendorID']; ?>">
                        <?php echo $v['VendorName']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
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
            <button type="submit" class="btn">Book Event</button>

        </form>

    </main>

</div>

</body>
</html>
