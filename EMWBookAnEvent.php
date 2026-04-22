<?php
session_start();
require 'phpConfig.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
    header("Location: phpLogin.php");
    exit;
}

$user = $_SESSION['user'];
$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_type = $_POST['event_type'];
    $date = $_POST['date'];
    $guests = $_POST['guests'];
    $budget = $_POST['budget'];
    $details = $_POST['details'];

    // Save booking request to database
    $stmt = $conn->prepare("INSERT INTO bookings (client_id, event_type, event_date, guests, budget, details, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("isssds", $user['id'], $event_type, $date, $guests, $budget, $details);
    
    if ($stmt->execute()) {
        $success = "Booking request submitted successfully! Vendors will contact you soon.";
    } else {
        $message = "Error submitting booking. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book An Event - Eventify</title>
  <link rel="stylesheet" href="cssStyles.css">
  <style>
    .booking-container { max-width: 700px; margin: 2rem auto; padding: 2rem; }
    .booking-form { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem; }
    .full-width { grid-column: 1 / -1; }
    textarea { resize: vertical; min-height: 120px; }
    .vendor-suggestions { background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin: 1.5rem 0; }
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="booking-container">
    <div style="text-align: center; margin-bottom: 2rem;">
      <a href="clientDashboard.php" class="btn" style="background: #6b7280;">← Back to Dashboard</a>
    </div>

    <div class="booking-form">
      <h2>📅 Book Your Event</h2>
      <p style="color: #64748b; margin-bottom: 2rem;">Fill out details and our verified vendors will send you personalized quotes.</p>
      
      <?php if ($success): ?>
        <div class="error" style="background: #ecfdf5; color: #166534; border-left-color: #10b981;">
          <?= $success ?>
        </div>
      <?php endif; ?>
      
      <?php if ($message): ?>
        <div class="error"><?= $message ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-row">
          <div class="form-group">
            <label>Your Name</label>
            <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
          </div>
          <div class="form-group">
            <label>Your Email</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Event Type *</label>
            <select name="event_type" required>
              <option value="">Select event type...</option>
              <option value="Wedding">💒 Wedding</option>
              <option value="Birthday">🎂 Birthday Party</option>
              <option value="Corporate">🏢 Corporate Event</option>
              <option value="Workshop">📚 Workshop/Seminar</option>
              <option value="Baby Shower">👶 Baby Shower</option>
              <option value="Anniversary">💕 Anniversary</option>
              <option value="Other">🎉 Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Event Date *</label>
            <input type="date" name="date" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Expected Guests</label>
            <input type="number" name="guests" min="1" max="1000" placeholder="e.g. 50">
          </div>
          <div class="form-group">
            <label>Budget (USD) *</label>
            <input type="number" name="budget" min="50" max="50000" step="50" required placeholder="e.g. 1500">
          </div>
        </div>

        <div class="form-group full-width">
          <label>Event Details *</label>
          <textarea name="details" required placeholder="Describe your event: venue preference, theme, special requirements..."></textarea>
        </div>

        <div class="vendor-suggestions">
          <h4>💡 Pro Tip:</h4>
          <p>Our top vendors will see your request and send personalized quotes within 24 hours. Be specific about your needs for best matches!</p>
        </div>

        <button type="submit" style="width: 100%; padding: 1.25rem; font-size: 18px; background: linear-gradient(135deg, #10b981, #059669);">Send Booking Request</button>
      </form>
    </div>
  </div>
</body>
</html>