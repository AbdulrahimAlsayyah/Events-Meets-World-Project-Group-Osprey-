<?php
session_start();
require 'EMWConfig.php';

$message = "";

// Ensure customer logged in
if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

$customerID = $_SESSION['customer']['CustomerID'];

// Get Locations for the filter dropdown
$locations_query = "SELECT * FROM VendorLocation";
$locations_result = mysqli_query($conn, $locations_query);

//Type of vendors filtered
$types_query = "SELECT * FROM VendorType";
$types_result =  mysqli_query($conn, $types_query);
?>

<!DOCTYPE html>
<html> lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Vendors</title>
<link rel="stylesheet" href="EMWStyles.css">

 <style>
    .container { display: flex; }
    .sidebar { width: 200px; background: #d9534f; color: white; height: 100vh; padding: 20px; }
    .sidebar a { color: white; display: block; padding: 10px 0; text-decoration: none; } 
    .main-content { flex: 1; padding: 20px; }
    .vendor-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .vendor-card { border: 1px solid #ddd; padding: 15px; text-align: center; }
    .filter-bar { margin-bottom: 20px; background: #f4f4f4; padding: 15px; }
  </style>
</head>
<body>
  
<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">
<div class="container"> 
    <nav class="sidebar">
       <a href="Dashboard.php">Dashboard</a> 
       <a href="MyEvents.php">My Events</a>
       <a href="EMWBrowseVendors.php"style="font-weight:bold;">Browse Vendors</a>
       <a href="Messages.php">Messages</a>
       <a href="Settings.php">Settings</a>
    </nav>
  
    <main class="main-content"> 
      <h1>Plan Your Perfect Event</h1> 
      <p>Discover vendors • Book instantly • Manage everything</p>

<section class="filter-bar"> 
    <form method="GET" action="EMWBrowseVendors.php"> 
         <input type="text" name="search" placeholder="Search for your Vendor">
      
         <select name="type"> 
              <option value="">All Types</option> 
              <?php while($type = mysqli_fetch_assoc($types_result)): ?> 
                   <option value="<?php echo $type['VendorTypeID']; ?>"><?php echo $type['VendorType']; ?></option> 
              <?php endwhile; ?> 
         </select> 
      
      <select name="location"> 
           <option value="">All Locations</option> 
           <?php while($loc = mysqli_fetch_assoc($locations_result)): ?> 
              <option value="<?php echo $loc['VendorLocationID']; ?>"><?php echo $loc['VendorLocation']; ?></option> 
           <?php endwhile; ?> 
      </select> 
      
      <button type="submit">Search</button> 
    </form> 
</section>

<?php 
// Building the query 
$sql = "SELECT v.*, l.VendorLocation 
        FROM Vendors v 
        JOIN VendorLocation l ON v.VendorLocationFK = l.VendorLocationID 
        WHERE 1=1"; 
if (!empty($_GET['type'])) { 
    $type = mysqli_real_escape_string($conn, $_GET['type']); 
    $sql .= " AND v.VendorTypeFK = '$type'"; 
} 
if(!empty($_GET['location'])) { 
   $loc = mysqli_real_escape_string($conn, $_GET['location']); 
   $sql .= " AND v.VendorLocationFK = '$loc'"; 
} 
if (!empty($_GET['search'])) { 
    $search = mysqli_real_escape_string($conn, $_GET['search']); 
    $sql .= " AND v.VendorName LIKE '%$search%'"; 
} 
  // Limit to most popular (top 3) if no filters are applied, or show all results 
  $sql .= " LIMIT 6"; 
  $result = mysqli_query($conn, $sql); 
  ?> 
      
  <div class="vendor-grid"> 
        <?php if(mysqli_num_rows($result) > 0): ?> 
          <?php while($row = mysqli_fetch_assoc($result)): ?> 
            <div class="vendor-card"> 
              <div style="background:#eee; height:150px; margin-bottom:10px;">Vendor Photo</div> 
                <h3><?php echo$row['VendorName']; ?></h3> 
                <p>Location: <?php echo $row['VendorLocation']; ?></p>
                <p><?php echosubstr($row['Description'], 0, 50); ?>...</p> 
                <a href="EMWBookAnEvent.php?vendor_id=<?php echo $row['VendorID']; ?>" class="btn">View Profile / Book</a> 
              </div> 
           <?php endwhile; ?> 
         <?php else: ?> 
          <p>No vendors found matching your criteria.</p> 
         <?php endif; ?> 
       </div> 
     </main> 
   </div> 

</body> 
</html>
