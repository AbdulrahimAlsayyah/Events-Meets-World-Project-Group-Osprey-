<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

$customer = $_SESSION['customer'];
?>

<link rel="stylesheet" href="EMWStyles.css">

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

    <aside class="sidebar">
        <h3>Dashboard</h3>
        <ul>
            <li>My Events</li>
            <li>My Bookings</li>
            <li>Messages</li>
            <li>Reviews</li>
            <li>Settings</li>
        </ul>
    </aside>

    <main class="main">
        <h2>Client Dashboard - Welcome <?php echo htmlspecialchars($customer['FirstName']); ?></h2>

        <div class="stats">
            <div class="card">3<br>Upcoming Events</div>
            <div class="card">2<br>Unread Messages</div>
            <div class="card">1<br>Reviews Pending</div>
        </div>

        <div class="events">
            <h3>Upcoming Events</h3>
            <div class="event">Smith Wedding - Confirmed</div>
            <div class="event">Johnson Corporate - Pending</div>
            <div class="event">Birthday Party - Pending</div>
        </div>

        <div class="messages">
            <h3>Recent Messages</h3>
            <p>Hi Jane</p>
            <p>Hi there...</p>
        </div>
    </main>

</div>