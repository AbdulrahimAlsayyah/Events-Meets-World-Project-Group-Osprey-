<?php
session_start();
require 'EMWConfig.php';

// Protect page: customer must be logged in
if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

// Get search/filter values
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$location = $_GET['location'] ?? '';

// Build SQL query
$sql = "
    SELECT 
        Vendor.VendorID,
        Vendor.VendorName,
        Vendor.VendorEmail,
        Vendor.Description,
        VendorType.VendorType,
        VendorLocation.VendorLocation,
        VendorLocation.VendorStreetName,
        VendorLocation.VendorBuildingNumber
    FROM Vendor
    JOIN VendorType ON Vendor.VendorTypeFK = VendorType.VendorTypeID
    JOIN VendorLocation ON Vendor.VendorLocationFK = VendorLocation.VendorLocationID
    WHERE 1 = 1
";

$params = [];
$types = "";

// Search by vendor name, type, location, or description
if (!empty($search)) {
    $sql .= "
        AND (
            Vendor.VendorName LIKE ?
            OR Vendor.Description LIKE ?
            OR VendorType.VendorType LIKE ?
            OR VendorLocation.VendorLocation LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssss";
}

// Filter by vendor type
if (!empty($type)) {
    $sql .= " AND VendorType.VendorType = ?";
    $params[] = $type;
    $types .= "s";
}

// Filter by location
if (!empty($location)) {
    $sql .= " AND VendorLocation.VendorLocation = ?";
    $params[] = $location;
    $types .= "s";
}

$sql .= " ORDER BY Vendor.VendorName ASC";

$stmt = $conn->prepare($sql);

// Bind parameters only if filters exist
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$vendors = [];

while ($row = $result->fetch_assoc()) {
    $vendors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Vendors</title>
    <link rel="stylesheet" href="EMWStyles.css">

    <style>
        /* Keep sidebar links white */
        .sidebar a,
        .sidebar a:visited,
        .sidebar a:hover,
        .sidebar a:active {
            color: white;
            text-decoration: none;
        }

        /* Page layout */
        .vendors-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 1000px;
        }

        .vendor-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .vendor-search-form input,
        .vendor-search-form select {
            padding: 10px;
            min-width: 190px;
        }

        .black-btn {
            background: black;
            color: white;
            padding: 10px 28px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .black-btn:hover {
            background: #333;
        }

        .vendor-scroll {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .vendor-card {
            background: white;
            border-left: 5px solid #E15050;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        .vendor-card strong {
            font-size: 18px;
        }

        .vendor-card p {
            margin: 6px 0;
        }

        .vendor-actions {
            margin-top: 12px;
        }

        .clear-link {
            display: inline-block;
            padding: 10px 18px;
            background: #ccc;
            color: black;
            text-decoration: none;
            border-radius: 6px;
        }

        .scroll-note {
            text-align: center;
            color: #555;
            font-size: 18px;
        }
    </style>
</head>

<body>

<!-- Top navigation -->
<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">

    <div class="nav-links">
        <a href="EMWAboutUs.php">About Us</a>
        <a href="#">Contact Vendor</a>
    </div>

    <a href="EMWAboutUs.php" class="logout-btn">Log Out</a>
</div>

<div class="dashboard">

    <!-- Sidebar -->
    <aside class="sidebar">
        <ul>
            <li><a href="EMWClientDashboard.php">Return to Dashboard</a></li>
            <li><a href="EMWCustomerBookings.php">My Events</a></li>
            <li><a href="#">Messages</a></li>
            <li><a href="EMWCustomerReviews.php">Reviews</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
    </aside>

    <!-- Main content -->
    <main class="main">
        <h2>Plan Your Perfect Event</h2>
        <p>Discover vendors • Book instantly • Manage everything</p>

        <div class="vendors-box">

            <!-- Vendor search/filter form -->
            <form method="GET" class="vendor-search-form">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search for your Vendor"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <select name="type">
                    <option value="">All Types</option>
                    <option value="Catering" <?php echo $type === 'Catering' ? 'selected' : ''; ?>>Catering</option>
                    <option value="Entertainment" <?php echo $type === 'Entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                    <option value="Photography" <?php echo $type === 'Photography' ? 'selected' : ''; ?>>Photography</option>
                </select>

                <select name="location">
                    <option value="">All Locations</option>
                    <option value="North London" <?php echo $location === 'North London' ? 'selected' : ''; ?>>North London</option>
                    <option value="East London" <?php echo $location === 'East London' ? 'selected' : ''; ?>>East London</option>
                    <option value="Central London" <?php echo $location === 'Central London' ? 'selected' : ''; ?>>Central London</option>
                    <option value="South London" <?php echo $location === 'South London' ? 'selected' : ''; ?>>South London</option>
                    <option value="West London" <?php echo $location === 'West London' ? 'selected' : ''; ?>>West London</option>
                    <option value="Maidstone" <?php echo $location === 'Maidstone' ? 'selected' : ''; ?>>Maidstone</option>
                    <option value="Rochester" <?php echo $location === 'Rochester' ? 'selected' : ''; ?>>Rochester</option>
                    <option value="Medway" <?php echo $location === 'Medway' ? 'selected' : ''; ?>>Medway</option>
                    <option value="Chelmsford" <?php echo $location === 'Chelmsford' ? 'selected' : ''; ?>>Chelmsford</option>
                    <option value="Colchester" <?php echo $location === 'Colchester' ? 'selected' : ''; ?>>Colchester</option>
                    <option value="Southend-on-Sea" <?php echo $location === 'Southend-on-Sea' ? 'selected' : ''; ?>>Southend-on-Sea</option>
                    <option value="Canterbury" <?php echo $location === 'Canterbury' ? 'selected' : ''; ?>>Canterbury</option>
                </select>

                <button type="submit" class="black-btn">Search</button>
            </form>

            <!-- Vendor results -->
            <h3>Available Vendors</h3>

            <div class="vendor-scroll">
                <?php if (count($vendors) > 0): ?>
                    <?php foreach ($vendors as $vendor): ?>
                        <div class="vendor-card">
                            <strong><?php echo htmlspecialchars($vendor['VendorName']); ?></strong>

                            <p>
                                Type: <?php echo htmlspecialchars($vendor['VendorType']); ?>
                            </p>

                            <p>
                                Location:
                                <?php echo htmlspecialchars($vendor['VendorLocation']); ?>,
                                <?php echo htmlspecialchars($vendor['VendorBuildingNumber']); ?>
                                <?php echo htmlspecialchars($vendor['VendorStreetName']); ?>
                            </p>

                            <p>
                                Email: <?php echo htmlspecialchars($vendor['VendorEmail']); ?>
                            </p>

                            <p>
                                <?php echo htmlspecialchars($vendor['Description']); ?>
                            </p>

                            <div class="vendor-actions">
                                <a 
                                    href="EMWBookAnEvent.php?vendorID=<?php echo $vendor['VendorID']; ?>" 
                                    class="black-btn"
                                >
                                    Book Vendor
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No vendors found. Try changing your search filters.</p>
                <?php endif; ?>
            </div>

            <p class="scroll-note">scroll down to see more vendors</p>
        </div>
    </main>
</div>

</body>
</html>
