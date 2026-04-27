<?php
session_start();
require 'EMWConfig.php';

if (!isset($_SESSION['vendor'])) {
    header("Location: EMWLoginVendor.php");
    exit;
}

$vendor = $_SESSION['vendor'];
$vendorID = $vendor['VendorID'];

// Get upcoming events
$stmt = $conn->prepare("
    SELECT Eventt.EventID, Eventt.EventDate, Eventt.EventStates, Eventt.EventType, Customer.CustomerID, Customer.FirstName, Customer.LastName, Payment.TotalPrice
    FROM Eventt
    JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
    JOIN Customer ON Payment.CustomerFK = Customer.CustomerID
    WHERE Eventt.VendorFK = ?
    ORDER BY Eventt.EventDate ASC
");

$stmt->bind_param("i", $vendorID);
$stmt->execute();
$result = $stmt->get_result();

// Store events
$events = [];
$totalPrice = 0;
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
   if (strtoupper($row['EventStates']) !== 'Cancelled') {
        $totalPrice += $row['TotalPrice'];
    }
}

// Count events
$eventCount = count($events);

// Get sent reviews
$stmt2 = $conn->prepare("
    SELECT Review.ReviewID, Review.ReviewContent, Review.Rating, Review.ReviewDate, Customer.CustomerID, Customer.FirstName, Customer.LastName
    FROM Review
    JOIN Customer ON Review.CustomerFK = Customer.CustomerID
    WHERE Review.VendorFK = ?
    ORDER BY Review.ReviewDate ASC
");

$stmt2->bind_param("i", $vendorID);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Store reviews
$reviews = [];
$totalRating = 0; 
$reviewCountValid = 0;  
while ($row = $result2->fetch_assoc()) {
    $reviews[] = $row;
    if (!empty($row['Rating']) && is_numeric($row['Rating'])) {  // ← NEW: Only valid ratings
        $totalRating += $row['Rating'];
        $reviewCountValid++;
    }
}

// Calculate average rating
$averageRating = $reviewCountValid > 0 ? round($totalRating / $reviewCountValid, 1) : 0;

// Count reviews
$reviewCount = count($reviews);

// Get ALL successful payments for this vendor (via Eventt)
$chartStmt = $conn->prepare("
    SELECT 
        DATE(Payment.TransactionDate) as date,
        SUM(Payment.TotalPrice) as totalRevenue,
        COUNT(Payment.PaymentID) as totalBookings
    FROM Payment
    JOIN Eventt ON Payment.PaymentID = Eventt.PaymentFK
    WHERE Eventt.VendorFK = ?
    AND Payment.PaymentSuccessful = 1
    GROUP BY DATE(Payment.TransactionDate)
    ORDER BY date ASC
");

$chartStmt->bind_param("i", $vendorID);
$chartStmt->execute();
$result = $chartStmt->get_result();

$chartData = [];

while ($row = $result->fetch_assoc()) {
    $chartData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Dashboard</title>
    <link rel="stylesheet" href="EMWStyles.css">
</head>

<body>

<!-- TOP NAV -->
<div class="top-nav">
    <img src="EMW Logo 1.png" alt="EMW Logo" class="logo">
    <div class="nav-links">
        <a href="#">Your Analytics</a>
        <a href="EMWAboutUs.php">About Us</a>
    </div>
    <a href="EMWAboutUs.php" class="logout-btn">Log Out</a>
</div>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <ul>
            <li>Dashboard</li>
            <li>Bookings</li>
            <li>Enquiries</li>
            <li>My Profile</li>
            <li>Reviews</li>
            <li>Settings</li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <h2>Vendor Dashboard - <?php echo htmlspecialchars($vendor['VendorName']); ?></h2>
        <p>US1 - User Account Management</p>

        <!-- STATS -->
        <div class="stats">
            <div class="card"><?php echo $eventCount; ?><br>Bookings</div>
            <div class="card">348<br>Profile Views</div>
            <div class="card"><?php echo $averageRating; ?> ⭐<br>Avg. Ratings</div>
            <div class="card">£<?php echo $totalPrice; ?><br>Revenue (MTD)</div>
        </div>

        <!-- GRID -->
        <div class="grid">

            <!-- LEFT COLUMN -->
            <div class="panel">
                <h3>Pending Enquiries (7 new)</h3>

                <div class="item">
                    Jane Smith <span>Wedding 15 Mar 2026 - 120 Guests</span>
                    <button>Respond</button>
                </div>

                <div class="item">
                    Tom Roberts <span>Corporate - 50 Guests</span>
                    <button>Respond</button>
                </div>

                <div class="item">
                    David Lee <span>Private Dinner - 14 Guests</span>
                    <button>Respond</button>
                </div>

                <button class="view-btn">View All</button>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="panel">
                <h3>Upcoming/Recent Events</h3>

                <?php if ($eventCount > 0): ?>
                    <?php $count = 0; ?>
                    <?php foreach ($events as $event): ?>
                        
                        <?php if ($count >= 3) break; ?>  <!-- LIMIT TO 3 -->
            
                        <div class="event">
                            <strong><?php echo $event['EventType']; ?></strong><br>
                            Customer: <?php echo $event['FirstName']; ?> <?php echo $event['LastName']; ?><br>
                            Date: <?php echo $event['EventDate']; ?><br>
                            Cost: £<?php echo $event['TotalPrice']; ?><br>
                            Status: <?php echo $event['EventStates']; ?>
                        </div>
            
                        <?php $count++; ?>
                    <?php endforeach; ?>
            
                <?php else: ?>
                    <p>No upcoming events.</p>
                <?php endif; ?>
            </div>

            <!-- CHART -->
            <div class="panel wide chart-panel">
                <h3>Booking and Revenue Trend</h3>

                <div class="chart-layout">
                    
                    <!-- FILTERS (LEFT SIDE) -->
                    <div class="chart-filters">
                        <label><input type="checkbox" onclick="filterChart(7)"> Last 7 Days</label>
                        <label><input type="checkbox" onclick="filterChart(30)"> Last Month</label>
                        <label><input type="checkbox" onclick="filterChart(180)"> 6 Months</label>
                        <label><input type="checkbox" onclick="filterChart(365)"> Last Year</label>
                    </div>
            
                    <!-- CHART (RIGHT SIDE) -->
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
            
                </div>
            </div>

            <!-- REVIEWS -->
            <div class="panel">
                <h3>Recent Reviews</h3>

                <?php if ($reviewCount > 0): ?>
                    <?php $count = 0; ?>
                    <?php foreach ($reviews as $review): ?>
                        
                        <?php if ($count >= 3) break; ?>
            
                        <div class="event">
                            Customer: <?php echo $review['FirstName']; ?> <?php echo $review['LastName']; ?><br>
                            Date: <?php echo $review['ReviewDate']; ?><br>
                            Rating: <?php echo $review['Rating']; ?><br>
                            Review: <?php echo $review['ReviewContent']; ?>
                        </div>
            
                        <?php $count++; ?>
                    <?php endforeach; ?>
            
                <?php else: ?>
                    <p>You don't have any Reviews yet.</p>
                <?php endif; ?>
            </div>

        </div>

    </main>
</div>

<footer>
    © 2026 Events Meets World | Privacy Policy | Accessibility
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const rawData = <?php echo json_encode($chartData); ?>;

let chart;

// Filter data by days
function getFilteredData(days) {
    const now = new Date();

    return rawData.filter(item => {
        const date = new Date(item.date);
        const diff = (now - date) / (1000 * 60 * 60 * 24);
        return diff <= days;
    });
}

// Build chart
function buildChart(data) {

    const labels = data.map(i => i.date);
    const revenue = data.map(i => i.totalRevenue);
    const bookings = data.map(i => i.totalBookings);

    const ctx = document.getElementById('revenueChart').getContext('2d');

    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: revenue,
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'Bookings',
                    data: bookings,
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Checkbox behavior (only ONE)
function filterChart(days) {

    // uncheck all first
    document.querySelectorAll('.chart-filters input').forEach(cb => cb.checked = false);

    // check selected
    event.target.checked = true;

    const filtered = getFilteredData(days);
    buildChart(filtered);
}

// Default load (last month)
window.onload = function () {
    buildChart(getFilteredData(30));
};
</script>

</body>
</html>
