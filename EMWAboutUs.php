<?php require 'EMWConfig.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Events Meets World</title>
    <link rel="stylesheet" href="EMWStyles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;800;900&display=swap" rel="stylesheet">
</head>
<body>

<header class="hero">
    <img src="EMW Logo 1.png" alt="EMW Logo" class="logo">
    <h2 class="headline">EVENTS MEETS WORLD</h2>
    <p class="tagline">A one-stop platform connecting customers and vendors seamlessly.</p>
</header>

<section class="values-section">
    <div class="values-left">
        <h2>OUR VALUES</h2>

        <div class="value">
            <p>To revolutionise the experience for consumers in the events market and act as a one-stop-shop.</p>
        </div>

        <div class="value">
            <p>To give back to the community and provide opportunities for young people within entrepreneurship.</p>
        </div>

        <div class="value">
            <p>To seamlessly link amazing vendors with great people who will become satisfied customers.</p>
        </div>
    </div>

    <div class="values-right">
        <h2>KEY THEMES</h2>
        <ul>
            <li>Organisation</li>
            <li>Collaboration</li>
            <li>Consistency</li>
            <li>Reliability</li>
            <li>User-Driven</li>
            <li>Simplicity</li>
        </ul>
    </div>
</section>

<section class="database-section">
    <h2>OUR PLATFORM</h2>
    <p>
        Events Meets World connects customers and vendors through a structured and reliable system.
        Our platform manages customer memberships, vendor services, bookings, payments, and event tracking
        through a secure relational database.
    </p>

    <div class="features">
        <div>
            <h3>Customers</h3>
            <p>Register, manage memberships, and book events with ease.</p>
        </div>

        <div>
            <h3>Vendors</h3>
            <p>Provide catering, entertainment, and photography services across multiple locations.</p>
        </div>

        <div>
            <h3>Events</h3>
            <p>Plan birthdays, weddings, and anniversaries with integrated payment and review systems.</p>
        </div>
    </div>
</section>

<div class="auth-buttons">
    <a href="EMWLoginCustomer.php" class="btn">Customer Login</a>
    <a href="EMWRegisterCustomer.php" class="btn">Customer Register</a>
    <a href="EMWLoginVendor.php" class="btn">Vendor Login</a>
    <a href="EMWRegisterVendor.php" class="btn">Vendor Register</a>
</div>

<footer>
    <p>© Events Meets World</p>
</footer>

</body>
</html>