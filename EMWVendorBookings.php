<?php
session_start();
require 'EMWConfig.php';

if (!isset($_SESSION['vendor'])) {
    header("Location: EMWLoginVendor.php");
    exit;
}

$vendorID = $_SESSION['vendor']['VendorID'];

$errorMessage = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelEvent'])) {
    $eventID = $_POST['eventID'] ?? null;

    if (!$eventID) {
        $errorMessage = "Please select an event to cancel.";
    } else {
        $check = $conn->prepare("
            SELECT 
                Eventt.EventID,
                Eventt.EventStates,
                Eventt.PaymentFK,
                Payment.PaymentID,
                Payment.RefundFK,
                Payment.TotalPrice
            FROM Eventt
            JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
            WHERE Eventt.EventID = ?
            AND Eventt.VendorFK = ?
        ");

        $check->bind_param("ii", $eventID, $vendorID);
        $check->execute();
        $selectedEvent = $check->get_result()->fetch_assoc();

        if (!$selectedEvent) {
            $errorMessage = "Event not found.";
        } elseif ($selectedEvent['EventStates'] !== 'Scheduled') {
            $errorMessage = "Only scheduled events can be cancelled.";
        } else {
            $conn->begin_transaction();

            try {
                $paymentID = $selectedEvent['PaymentID'];
                $refundID = $selectedEvent['RefundFK'];
                $totalPrice = $selectedEvent['TotalPrice'];

                if ($refundID === null) {
                    $createRefund = $conn->prepare("
                        INSERT INTO Refund (RefundStatus, RefundAmount, RefundDate)
                        VALUES (1, ?, CURDATE())
                    ");

                    $createRefund->bind_param("d", $totalPrice);
                    $createRefund->execute();

                    $refundID = $conn->insert_id;

                    $updatePayment = $conn->prepare("
                        UPDATE Payment
                        SET TransactionAlerts = 'Event Cancelled, You will be refunded',
                            PaymentSuccessful = 0,
                            RefundFK = ?
                        WHERE PaymentID = ?
                    ");

                    $updatePayment->bind_param("ii", $refundID, $paymentID);
                    $updatePayment->execute();

                } else {
                    $updateRefund = $conn->prepare("
                        UPDATE Refund
                        SET RefundStatus = 1,
                            RefundAmount = ?,
                            RefundDate = CURDATE()
                        WHERE RefundID = ?
                    ");

                    $updateRefund->bind_param("di", $totalPrice, $refundID);
                    $updateRefund->execute();

                    $updatePayment = $conn->prepare("
                        UPDATE Payment
                        SET TransactionAlerts = 'Event Cancelled, You will be refunded',
                            PaymentSuccessful = 0
                        WHERE PaymentID = ?
                    ");

                    $updatePayment->bind_param("i", $paymentID);
                    $updatePayment->execute();
                }

                $cancel = $conn->prepare("
                    UPDATE Eventt
                    SET EventStates = 'Cancelled'
                    WHERE EventID = ?
                    AND VendorFK = ?
                ");

                $cancel->bind_param("ii", $eventID, $vendorID);
                $cancel->execute();

                $conn->commit();

                $message = "Event cancelled successfully. The customer will be refunded.";

            } catch (Exception $e) {
                $conn->rollback();
                $errorMessage = "Error cancelling event.";
            }
        }
    }
}

$stmt = $conn->prepare("
    SELECT 
        Eventt.EventID,
        Eventt.EventDate,
        Eventt.EventStates,
        Eventt.EventType,
        Customer.FirstName,
        Customer.LastName,
        Customer.Email,
        Payment.TotalPrice
    FROM Eventt
    JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
    JOIN Customer ON Payment.CustomerFK = Customer.CustomerID
    WHERE Eventt.VendorFK = ?
    ORDER BY Eventt.EventDate ASC
");

$stmt->bind_param("i", $vendorID);
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$eventCount = count($events);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vendor Bookings</title>
    <link rel="stylesheet" href="EMWStyles.css">

    <style>
        .sidebar a,
        .sidebar a:visited,
        .sidebar a:hover,
        .sidebar a:active {
            color: white;
            text-decoration: none;
        }

        .events-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 900px;
        }

        .event-scroll {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .event-card-selectable {
            background: white;
            border-left: 5px solid #E15050;
            border: 2px solid transparent;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .event-card-selectable:hover {
            background: #f5f5f5;
        }

        .event-card-selectable.selected {
            border: 2px solid #2563eb;
            border-left: 5px solid #2563eb;
        }

        .event-card-selectable.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .event-card-selectable strong {
            font-size: 16px;
        }

        .scroll-note {
            text-align: center;
            font-size: 18px;
            color: #555;
        }

        .black-btn {
            background: black;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .black-btn:hover {
            background: #333;
        }

        .cancel-help {
            margin-left: 20px;
            font-size: 18px;
            color: #555;
        }

        .event-search {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .event-search input {
            width: 430px;
            max-width: 100%;
            padding: 10px;
        }

        .event-search button {
            background: black;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .event-search button:hover {
            background: #333;
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
    </style>
</head>

<body>

<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">

    <div class="nav-links">
        <a href="EMWAboutUs.php">About Us</a>
        <a href="#">Contact Customer</a>
    </div>

    <a href="EMWAboutUs.php" class="logout-btn">Log Out</a>
</div>

<div class="dashboard">
    <aside class="sidebar">
        <ul>
            <li><a href="EMWVendorDashboard.php">Return to Dashboard</a></li>
            <li><a href="EMWVendorBookings.php">Bookings</a></li>
            <li><a href="#">Enquiries</a></li>
            <li><a href="#">My Profile</a></li>
            <li><a href="#">Reviews</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
    </aside>

    <main class="main">
        <h2>Vendor Bookings</h2>

        <div class="events-box">
            <h3>All of your Bookings</h3>

            <form method="POST" id="cancelEventForm">
                <input type="hidden" name="eventID" id="selectedEventID">

                <div class="event-scroll">
                    <?php if ($eventCount > 0): ?>
                        <?php foreach ($events as $event): ?>
                            <div 
                                class="event-card-selectable <?php echo $event['EventStates'] !== 'Scheduled' ? 'disabled' : ''; ?>"
                                data-event-id="<?php echo $event['EventID']; ?>"
                                data-status="<?php echo htmlspecialchars($event['EventStates']); ?>"
                                onclick="selectEvent(this)"
                            >
                                <strong><?php echo htmlspecialchars($event['EventType']); ?></strong><br>
                                Customer: <?php echo htmlspecialchars($event['FirstName'] . ' ' . $event['LastName']); ?><br>
                                Email: <?php echo htmlspecialchars($event['Email']); ?><br>
                                Date: <?php echo htmlspecialchars($event['EventDate']); ?><br>
                                Cost: £<?php echo htmlspecialchars($event['TotalPrice']); ?><br>
                                Status: <?php echo htmlspecialchars($event['EventStates']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No bookings found.</p>
                    <?php endif; ?>
                </div>

                <p class="scroll-note">scroll down to see more bookings</p>

                <button type="button" class="black-btn" onclick="openCancelModal()">
                    Cancel Booking
                </button>

                <span class="cancel-help">Select a booking to cancel</span>

                <p style="color:red;"><?php echo $errorMessage; ?></p>
                <p style="color:green;"><?php echo $message; ?></p>

                <div id="cancelModal" class="modal">
                    <div class="modal-content">
                        <h3>Cancel Booking</h3>
                        <p>Are you sure you want to cancel this booking?</p>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" onclick="closeCancelModal()">
                                No
                            </button>

                            <button type="submit" name="cancelEvent" class="modal-confirm">
                                Yes, Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="event-search">
                <input
                    type="text"
                    id="eventSearch"
                    placeholder="Search by event type, date, status, customer or email"
                >

                <button type="button" onclick="searchEvents()">
                    Search Booking
                </button>
            </div>
        </div>
    </main>
</div>

<script>
function selectEvent(card) {
    if (card.classList.contains("disabled")) {
        alert("Only scheduled bookings can be cancelled.");
        return;
    }

    document.querySelectorAll(".event-card-selectable").forEach(eventCard => {
        eventCard.classList.remove("selected");
    });

    card.classList.add("selected");

    document.getElementById("selectedEventID").value = card.dataset.eventId;
}

function openCancelModal() {
    const selectedEventID = document.getElementById("selectedEventID").value;

    if (!selectedEventID) {
        alert("Please select a booking to cancel.");
        return;
    }

    document.getElementById("cancelModal").style.display = "block";
}

function closeCancelModal() {
    document.getElementById("cancelModal").style.display = "none";
}

function searchEvents() {
    const searchValue = document.getElementById("eventSearch").value.toLowerCase();
    const events = document.querySelectorAll(".event-card-selectable");

    events.forEach(event => {
        const text = event.innerText.toLowerCase();
        event.style.display = text.includes(searchValue) ? "block" : "none";
    });
}

window.onclick = function(event) {
    const modal = document.getElementById("cancelModal");

    if (event.target === modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>