<?php 
session_start();
require 'EMWConfig.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare query
    $stmt = $conn->prepare("SELECT * FROM Customer WHERE Email = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Your requested password check
        if ($user && $password === $user['Password']) {

            // tore FULL user row (important for dashboard)
            $_SESSION['customer'] = $user;

            header("Location: EMWClientDashboard.php");
            exit;

        } else {
            $message = "Invalid email or password";
        }

    } else {
        $message = "Something went wrong. Try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login</title>

    <link rel="stylesheet" href="EMWStyles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;800;900&display=swap" rel="stylesheet">
</head>
<body>

<header class="hero">
    <img src="EMW Logo 1.png" alt="EMW Logo" class="logo">
</header>

<div class="form">

    <h2>Customer Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <!-- Error message -->
    <?php if (!empty($message)): ?>
        <p style="color:red;"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Navigation -->
    <a href="EMWAboutUs.php" class="btn">⬅ Back to About</a>
    <a href="EMWRegisterCustomer.php" class="btn">Register Instead</a>

</div>

</body>
</html>
