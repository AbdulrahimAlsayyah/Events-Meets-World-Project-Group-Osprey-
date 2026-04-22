<?php
session_start();
if (!isset($_SESSION['vendor'])) {
    header("Location: EMWLoginVendor.php");
    exit;
}

$vendor = $_SESSION['vendor'];
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
        <a href="#">Home</a>
        <a href="#">Find Vendors</a>
        <a href="EMWAboutUs.php">About</a>
        <a href="#">Contact</a>
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
            <li>Analytics</li>
            <li>Settings</li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <h2>Vendor Dashboard - <?php echo htmlspecialchars($vendor['VendorName']); ?></h2>
        <p>US1 - User Account Management</p>

        <!-- STATS -->
        <div class="stats">
            <div class="card">12<br>Bookings (MTD)</div>
            <div class="card">348<br>Profile Views</div>
            <div class="card">4.9 ⭐<br>Avg. Ratings</div>
            <div class="card">£4,820<br>Revenue (MTD)</div>
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
                <h3>Upcoming Events</h3>

                <div class="item">
                    Smith Wedding <span>Confirmed</span>
                </div>

                <div class="item">
                    Johnson Corp. <span>Payment Pending</span>
                </div>

                <div class="item">
                    Lee Dinner <span>Confirmed</span>
                </div>

                <button class="view-btn">View All</button>
            </div>

            <!-- CHART -->
            <div class="panel wide">
                <h3>Booking and Revenue Trend</h3>
                <div class="chart-placeholder">[Chart Placeholder]</div>
            </div>

            <!-- REVIEWS -->
            <div class="panel">
                <h3>Recent Reviews</h3>
                <div class="review">Review 1</div>
                <div class="review">Review 2</div>
                <div class="review">Review 3</div>
                <button class="view-btn">View All</button>
            </div>

        </div>

    </main>
</div>

<footer>
    © 2026 Events Meets World | Privacy Policy | Accessibility
</footer>

</body>
</html>