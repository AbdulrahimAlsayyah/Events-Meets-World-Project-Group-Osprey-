<?php
session_start();
require 'phpConfig.php';

if (!isset($_SESSION['user'])) {
    header("Location: phpLogin.php");
    exit;
}

$user = $_SESSION['user'];
$isClient = $user['role'] === 'client';
$bookings = [];

// Fetch bookings based on role
if ($isClient) {
    // Client sees their own bookings
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user['id']);
} else {
    // Vendor sees all pending/recent bookings
    $stmt = $conn->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email 
        FROM bookings b 
        JOIN users u ON b.client_id = u.id 
        WHERE b.status IN ('pending', 'contacted') 
        ORDER BY b.created_at DESC 
        LIMIT 50
    ");
}

$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings - Eventify</title>
  <link rel="stylesheet" href="cssStyles.css">
  <style>
    .bookings-container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
    .bookings-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-contacted { background: #dbeafe; color: #1e40af; }
    .status-booked { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    .booking-card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-left: 4px solid #2563eb; }
    .booking-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
    .booking-meta { display: flex; gap: 1rem; font-size: 0.9rem; color: #64748b; flex-wrap: wrap; }
    .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .action-btn { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 500; }
    .btn-contact { background: #10b981; color: white; }
    .btn-view { background: #2563eb; color: white; }
    @media (max-width: 768px) { .booking-header { flex-direction: column; gap: 0.5rem; } }
  </style>
</head>
<body>
  <div class="bookings-container">
    <!-- Header -->
    <div class="bookings-header">
      <div>
        <h1><?= $isClient ? 'My Booking Requests' : 'New Customer Requests' ?></h1>
        <p><?= $isClient ? 'Track your event bookings' : 'Contact customers to offer services' ?></p>
      </div>
      <a href="<?= $isClient ? 'clientDashboard.php' : 'vendorDashboard.php' ?>" class="btn" style="background: #6b7280;">← Back to Dashboard</a>
    </div>

    <?php if (empty($bookings)): ?>
      <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px;">
        <h3><?= $isClient ? 'No bookings yet' : 'No new requests' ?></h3>
        <p>
          <?= $isClient 
            ? 'Book your first event <a href="bookAnEvent.php" style="color: #2563eb;">here</a>!'
            : 'Customers will appear here when they submit requests.'
          ?>
        </p>
      </div>
    <?php else: ?>
      <!-- Bookings List -->
      <?php foreach ($bookings as $booking): ?>
        <div class="booking-card">
          <div class="booking-header">
            <div>
              <h3><?= htmlspecialchars($booking['event_type']) ?></h3>
              <div class="booking-meta">
                <span>📅 <?= date('M j, Y', strtotime($booking['event_date'])) ?></span>
                <span>👥 <?= $booking['guests'] ?? 'Not specified' ?> guests</span>
                <span>💰 $<?= number_format($booking['budget'], 2) ?></span>
                <?php if (!$isClient): ?>
                  <span>👤 <?= htmlspecialchars($booking['client_name']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <span class="status-badge status-<?= $booking['status'] ?>">
              <?= ucfirst($booking['status']) ?>
            </span>
          </div>
          
          <p style="color: #374151; margin-bottom: 1.5rem; line-height: 1.6;">
            <?= nl2br(htmlspecialchars($booking['details'])) ?>
          </p>

          <div class="action-buttons">
            <?php if ($isClient): ?>
              <!-- Client actions -->
              <span style="color: #64748b; font-size: 0.85rem;">
                Waiting for vendor response...
              </span>
            <?php else: ?>
              <!-- Vendor actions -->
              <a href="mailto:<?= htmlspecialchars($booking['client_email']) ?>?subject=Eventify: <?= $booking['event_type'] ?> Quote&body=Hi <?= $booking['client_name'] ?>,%0D%0AI'd love to help with your <?= $booking['event_type'] ?> on <?= $booking['event_date'] ?>!" 
                 class="action-btn btn-contact">📧 Contact Now</a>
              <a href="#" class="action-btn btn-view" onclick="alert('View full profile & chat')">👁️ View Profile</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination (Future) -->
    <div style="text-align: center; margin-top: 2rem; color: #9ca3af;">
      Showing <?= count($bookings) ?> of <?= count($bookings) ?> bookings
    </div>
  </div>
</body>
</html>