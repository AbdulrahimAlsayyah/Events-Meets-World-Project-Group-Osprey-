<?php 
session_start();
require 'EMWConfig.php';

if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

$customer = $_SESSION['customer'];
$customerID = $customer['CustomerID'];

// Get upcoming events
$stmt = $conn->prepare("
    SELECT Eventt.EventID, Eventt.EventDate, Eventt.EventStates, Eventt.EventType,
           Vendor.VendorName
    FROM Eventt
    JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
    JOIN Vendor ON Eventt.VendorFK = Vendor.VendorID
    WHERE Payment.CustomerFK = ?
    AND Eventt.EventStates != 'Cancelled'
    ORDER BY Eventt.EventDate ASC
");

$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();

// Store events
$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

// Count events
$eventCount = count($events);

// Get sent reviews
$stmt2 = $conn->prepare("
    SELECT Review.ReviewID
    FROM Review
    JOIN Customer ON Review.CustomerFK = Customer.CustomerID
    WHERE Review.CustomerFK = ?
");

$stmt2->bind_param("i", $customerID);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Store reviews
$reviews = [];
while ($row = $result2->fetch_assoc()) {
    $reviews[] = $row;
}

// Count reviews
$reviewCount = count($reviews);

$errorMessage = '';
$message = '';
$errorMessage2 = '';
$message2 = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createMembership'])) {

    // Get logged-in customer
    $customerID = $_SESSION['customer']['CustomerID'];

    // Get current membership
    $check = $conn->prepare("
        SELECT Membership.MembershipID, Membership.MembershipStates
        FROM Membership
        JOIN Customer ON Membership.MembershipID = Customer.MembershipFK
        WHERE Customer.CustomerID = ?
    ");

    $check->bind_param("i", $customerID);
    $check->execute();
    $result = $check->get_result();
    $membership = $result->fetch_assoc();

    if ($membership) {

        // Already active
        if ($membership['MembershipStates'] === 'Active') {

            $errorMessage = "You already have an active membership.";

        } else if ($membership['MembershipStates'] === 'Inactive') {

            // UPDATE existing membership
            $update = $conn->prepare("
                UPDATE Membership
                SET MembershipStates = 'Active',
                    StartDate = CURDATE(),
                    EndDate = NULL
                WHERE MembershipID = ?
            ");

            $update->bind_param("i", $membership['MembershipID']);

            if ($update->execute()) {

                // Update session
                $_SESSION['customer']['MembershipFK'] = $membership['MembershipID'];

                $message = "Membership activated successfully!";

            } else {
                $errorMessage = "Error updating membership.";
            }
        } else {

            // UPDATE existing membership
            $update = $conn->prepare("
                UPDATE Membership
                SET MembershipStates = 'Active',
                    EndDate = NULL
                WHERE MembershipID = ?
            ");

            $update->bind_param("i", $membership['MembershipID']);

            if ($update->execute()) {

                // Update session
                $_SESSION['customer']['MembershipFK'] = $membership['MembershipID'];

                $message = "Membership activated successfully!";

            } else {
                $errorMessage = "Error updating membership.";
            }
        }

    } else {
        $errorMessage = "No membership found for this user.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelMembership'])) {

    $customerID = $_SESSION['customer']['CustomerID'];

    // Step 1: Get current membership state
    $check = $conn->prepare("
        SELECT Membership.MembershipID, Membership.MembershipStates
        FROM Membership
        JOIN Customer ON Membership.MembershipID = Customer.MembershipFK
        WHERE Customer.CustomerID = ?
    ");

    $check->bind_param("i", $customerID);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {

        $membership = $result->fetch_assoc();

        // Already cancelled
        if ($membership['MembershipStates'] === 'Cancelled') {
            $errorMessage2 = "Membership is already cancelled.";
        } else {

            // Step 2: Cancel membership
            $stmt = $conn->prepare("
                UPDATE Membership
                SET MembershipStates = 'Cancelled',
                    EndDate = CURDATE()
                WHERE MembershipID = ?
            ");

            $stmt->bind_param("i", $membership['MembershipID']);

            if ($stmt->execute()) {
                $message2 = "Membership cancelled successfully.";
            } else {
                $errorMessage2 = "Error cancelling membership.";
            }
        }

    } else {
        $errorMessage2 = "No active membership found.";
    }
}

?>

<link rel="stylesheet" href="EMWStyles.css">

<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">
    <div class="nav-links">
        <a href="EMWAboutUs.php">About Us</a>
        <a href="#">Contact Vendor</a>
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
        <h2>
            Client Dashboard - Welcome 
            <?php echo htmlspecialchars($customer['FirstName']); ?>
        </h2>

        <!-- STATS -->
        <div class="stats">
            <div class="card">
                <?php echo $eventCount; ?><br>Upcoming Events
            </div>
            <div class="card">0<br>Unread Messages</div>
            <div class="card"><?php echo $reviewCount; ?><br>Reviews Sent</div>
        </div>

        <!-- EVENTS -->
        <div class="events">
            <h3>Upcoming Events</h3>

            <?php if ($eventCount > 0): ?>
                <?php foreach ($events as $event): ?>
                    <div class="event">
                        <strong><?php echo $event['EventType']; ?></strong><br>
                        Vendor: <?php echo $event['VendorName']; ?><br>
                        Date: <?php echo $event['EventDate']; ?><br>
                        Status: <?php echo $event['EventStates']; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No upcoming events.</p>
            <?php endif; ?>

        </div>

        <!-- MESSAGES (placeholder) -->
        <div class="messages">
            <h3>Recent Messages</h3>
            <p>No messages yet.</p>
        </div>
    <div class="form2">
        <h2>Memberships</h2>
    
        <form method="POST">
            <!-- Membership Type -->
            <select name="MembershipType" required>
                <option value="">Select Membership Type</option>
                <option value="Standard">Standard - Exclusive Deals, Up to 30% off on all Events</option>
            </select>
    
            <button type="submit" name="createMembership">
                Become a Member
            </button>
            <p style="color:red;"><?php echo $errorMessage; ?></p>
            <p style="color:green;"><?php echo $message; ?></p>
        </form>
        <form method="POST" onsubmit="return confirmCancel()">

            <button type="submit" name="cancelMembership">
                Cancel Membership
            </button>
        
            <p style="color:red;"><?php echo $errorMessage2; ?></p>
            <p style="color:green;"><?php echo $message2; ?></p>

        </form>

        <script>
        function confirmCancel() {
            return confirm("Are you sure you want to cancel your membership?");
        }
        </script>

    
    </div>

    </main>

</div>
