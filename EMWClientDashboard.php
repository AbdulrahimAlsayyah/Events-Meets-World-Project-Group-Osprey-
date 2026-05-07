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
    AND Eventt.EventStates = 'Scheduled'
    ORDER BY Eventt.EventDate ASC
");

$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

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

$reviews = [];
while ($row = $result2->fetch_assoc()) {
    $reviews[] = $row;
}

$reviewCount = count($reviews);

$errorMessage = '';
$message = '';
$errorMessage2 = '';
$message2 = '';

// Activate membership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createMembership'])) {

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

        if ($membership['MembershipStates'] === 'Active') {
            $errorMessage = "You already have an active membership.";
        } else {
            $update = $conn->prepare("
                UPDATE Membership
                SET MembershipStates = 'Active',
                    StartDate = CURDATE(),
                    EndDate = NULL
                WHERE MembershipID = ?
            ");

            $update->bind_param("i", $membership['MembershipID']);

            if ($update->execute()) {
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

// Cancel membership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelMembership'])) {

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

        if ($membership['MembershipStates'] === 'Cancelled') {
            $errorMessage2 = "Membership is already cancelled.";
        } else if ($membership['MembershipStates'] === 'Inactive') {
            $errorMessage2 = "No active membership found.";
        } else {
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
        $errorMessage2 = "No membership found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="EMWStyles.css">

    <style>
        .sidebar a,
        .sidebar a:visited,
        .sidebar a:hover,
        .sidebar a:active {
            color: white;
            text-decoration: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }

        .modal-content {
            background: white;
            width: 400px;
            margin: 15% auto;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-cancel {
            background: #ccc;
            color: black;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-confirm {
            background: black;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form2 button {
            background: black;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .form2 button:hover {
            background: #333;
        }
    </style>
</head>

<body>

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
            <li><a href="EMWCustomerBookings.php">My Events</a></li>
            <li><a href="EMWBrowseVendors.php">Browse Vendors</a></li>
            <li>Messages</li>
            <li><a href="EMWCustomerReviews.php">Reviews</a></li>
            <li>Settings</li>
        </ul>
    </aside>

    <main class="main">
        <h2>
            Client Dashboard - Welcome 
            <?php echo htmlspecialchars($customer['FirstName']); ?>
        </h2>

        <div class="stats">
            <div class="card">
                <?php echo $eventCount; ?><br>Upcoming Events
            </div>
            <div class="card">0<br>Unread Messages</div>
            <div class="card"><?php echo $reviewCount; ?><br>Reviews Sent</div>
        </div>

        <div class="events">
            <h3>Upcoming Events</h3>

            <?php if ($eventCount > 0): ?>
                <?php $count = 0; ?>
                <?php foreach ($events as $event): ?>
                    <?php if ($count >= 3) break; ?>
                    
                    <div class="event">
                        <strong><?php echo htmlspecialchars($event['EventType']); ?></strong><br>
                        Vendor: <?php echo htmlspecialchars($event['VendorName']); ?><br>
                        Date: <?php echo htmlspecialchars($event['EventDate']); ?><br>
                        Status: <?php echo htmlspecialchars($event['EventStates']); ?>
                    </div>
                    
                    <?php $count++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No upcoming events.</p>
            <?php endif; ?>
        </div>

        <div class="messages">
            <h3>Recent Messages</h3>
            <p>No messages yet.</p>
        </div>

        <div class="form2">
            <h2>Memberships</h2>
        
            <form method="POST">
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

            <button type="button" onclick="openMembershipModal()">
                Cancel Membership
            </button>

            <p style="color:red;"><?php echo $errorMessage2; ?></p>
            <p style="color:green;"><?php echo $message2; ?></p>

            <div id="membershipCancelModal" class="modal">
                <div class="modal-content">
                    <h3>Cancel Membership</h3>
                    <p>Are you sure you want to cancel your membership?</p>

                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" onclick="closeMembershipModal()">
                            No
                        </button>

                        <form method="POST">
                            <button type="submit" name="cancelMembership" class="modal-confirm">
                                Yes, Cancel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </main>

</div>

<script>
function openMembershipModal() {
    document.getElementById("membershipCancelModal").style.display = "block";
}

function closeMembershipModal() {
    document.getElementById("membershipCancelModal").style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById("membershipCancelModal");

    if (event.target === modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>
