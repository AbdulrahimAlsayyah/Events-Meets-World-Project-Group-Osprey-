<?php
session_start();
require 'EMWConfig.php';

if (!isset($_SESSION['customer'])) {
    header("Location: EMWLoginCustomer.php");
    exit;
}

$customerID = $_SESSION['customer']['CustomerID'];

$errorMessage = '';
$message = '';

// Update existing review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateReview'])) {
    $reviewID = $_POST['reviewID'] ?? null;
    $reviewContent = trim($_POST['reviewContent'] ?? '');
    $rating = $_POST['rating'] ?? null;

    if (!$reviewID || !$rating) {
        $errorMessage = "Please select a review and enter a rating.";
    } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $errorMessage = "Rating must be between 1 and 5.";
    } elseif (strlen($reviewContent) > 250) {
        $errorMessage = "Review cannot exceed 250 characters.";
    } else {
        $update = $conn->prepare("
            UPDATE Review
            SET ReviewContent = ?, Rating = ?, ReviewDate = CURDATE()
            WHERE ReviewID = ?
            AND CustomerFK = ?
        ");

        $update->bind_param("siii", $reviewContent, $rating, $reviewID, $customerID);

        if ($update->execute()) {
            $message = "Review updated successfully.";
        } else {
            $errorMessage = "Error updating review.";
        }
    }
}

// Create new review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createReview'])) {
    $vendorID = $_POST['vendorID'] ?? null;
    $reviewContent = trim($_POST['newReviewContent'] ?? '');
    $rating = $_POST['newRating'] ?? null;

    if (!$vendorID || !$rating) {
        $errorMessage = "Please select a vendor and enter a rating.";
    } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $errorMessage = "Rating must be between 1 and 5.";
    } elseif (strlen($reviewContent) > 250) {
        $errorMessage = "Review cannot exceed 250 characters.";
    } else {
        $check = $conn->prepare("
            SELECT Eventt.EventID
            FROM Eventt
            JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
            WHERE Payment.CustomerFK = ?
            AND Eventt.VendorFK = ?
            AND Eventt.EventStates = 'Completed'
            LIMIT 1
        ");

        $check->bind_param("ii", $customerID, $vendorID);
        $check->execute();
        $completedBooking = $check->get_result()->fetch_assoc();

        $duplicate = $conn->prepare("
            SELECT ReviewID
            FROM Review
            WHERE CustomerFK = ?
            AND VendorFK = ?
        ");

        $duplicate->bind_param("ii", $customerID, $vendorID);
        $duplicate->execute();
        $existingReview = $duplicate->get_result()->fetch_assoc();

        if (!$completedBooking) {
            $errorMessage = "You can only review vendors after a completed event.";
        } elseif ($existingReview) {
            $errorMessage = "You have already reviewed this vendor. Select it above to update your review.";
        } else {
            $insert = $conn->prepare("
                INSERT INTO Review (CustomerFK, VendorFK, ReviewContent, Rating, ReviewDate)
                VALUES (?, ?, ?, ?, CURDATE())
            ");

            $insert->bind_param("iisi", $customerID, $vendorID, $reviewContent, $rating);

            if ($insert->execute()) {
                $message = "Review created successfully.";
            } else {
                $errorMessage = "Error creating review.";
            }
        }
    }
}

// Existing reviews
$stmt = $conn->prepare("
    SELECT Review.ReviewID, Review.ReviewContent, Review.Rating, Review.ReviewDate,
           Vendor.VendorID, Vendor.VendorName
    FROM Review
    JOIN Vendor ON Review.VendorFK = Vendor.VendorID
    WHERE Review.CustomerFK = ?
    ORDER BY Review.ReviewDate DESC
");

$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];

while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

$reviewCount = count($reviews);

// Vendors customer can review
$stmt2 = $conn->prepare("
    SELECT DISTINCT Vendor.VendorID, Vendor.VendorName
    FROM Eventt
    JOIN Payment ON Eventt.PaymentFK = Payment.PaymentID
    JOIN Vendor ON Eventt.VendorFK = Vendor.VendorID
    LEFT JOIN Review 
        ON Review.VendorFK = Vendor.VendorID 
        AND Review.CustomerFK = Payment.CustomerFK
    WHERE Payment.CustomerFK = ?
    AND Eventt.EventStates = 'Completed'
    AND Review.ReviewID IS NULL
    ORDER BY Vendor.VendorName ASC
");

$stmt2->bind_param("i", $customerID);
$stmt2->execute();
$result2 = $stmt2->get_result();

$reviewableVendors = [];

while ($row = $result2->fetch_assoc()) {
    $reviewableVendors[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Reviews</title>
    <link rel="stylesheet" href="EMWStyles.css">

    <style>
        .sidebar a,
        .sidebar a:visited,
        .sidebar a:hover,
        .sidebar a:active {
            color: white;
            text-decoration: none;
        }

        .reviews-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 900px;
            margin-bottom: 25px;
        }

        .review-scroll {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .review-card-selectable {
            background: white;
            border-left: 5px solid #E15050;
            border: 2px solid transparent;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .review-card-selectable:hover {
            background: #f5f5f5;
        }

        .review-card-selectable.selected {
            border: 2px solid #2563eb;
            border-left: 5px solid #2563eb;
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

        .review-help {
            margin-left: 20px;
            font-size: 18px;
            color: #555;
        }

        .review-search {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .review-search input {
            width: 430px;
            max-width: 100%;
            padding: 10px;
        }

        .review-search button {
            background: black;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .review-form-area {
            margin-top: 20px;
            display: grid;
            gap: 12px;
            max-width: 650px;
        }

        .review-form-area textarea,
        .review-form-area select,
        .modal-content textarea,
        .modal-content select {
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
        }

        .char-note {
            font-size: 13px;
            color: #555;
            margin-top: -6px;
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
            width: 450px;
            margin: 12% auto;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
        }

        .modal-content textarea,
        .modal-content select {
            margin-bottom: 12px;
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

        .error-text {
            color: red;
        }

        .success-text {
            color: green;
        }
    </style>
</head>

<body>

<div class="top-nav">
    <img src="EMW Logo 1.png" class="logo">

    <div class="nav-links">
        <a href="EMWAboutUs.php">About Us</a>
        <a href="#">Contact Vendor</a>
    </div>

    <a href="EMWAboutUs.php" class="logout-btn">Log Out</a>
</div>

<div class="dashboard">

    <aside class="sidebar">
        <ul>
            <li><a href="EMWClientDashboard.php">Return to Dashboard</a></li>
            <li><a href="EMWCustomerBookings.php">My Events</a></li>
            <li><a href="EMWBrowseVendors.php">Browse Vendors</a></li>
            <li><a href="#">Messages</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
    </aside>

    <main class="main">
        <h2>Reviews</h2>

        <div class="reviews-box">
            <h3>Your Existing Reviews</h3>

            <form method="POST" id="updateReviewForm">
                <input type="hidden" name="reviewID" id="selectedReviewID">

                <div class="review-scroll">
                    <?php if ($reviewCount > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div 
                                class="review-card-selectable"
                                data-review-id="<?php echo $review['ReviewID']; ?>"
                                data-content="<?php echo htmlspecialchars($review['ReviewContent'] ?? '', ENT_QUOTES); ?>"
                                data-rating="<?php echo $review['Rating']; ?>"
                                onclick="selectReview(this)"
                            >
                                <strong><?php echo htmlspecialchars($review['VendorName']); ?></strong><br>
                                Rating: <?php echo htmlspecialchars($review['Rating']); ?>/5<br>
                                Review: <?php echo htmlspecialchars($review['ReviewContent'] ?? 'No review content'); ?><br>
                                Date: <?php echo htmlspecialchars($review['ReviewDate']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>You have not made any reviews yet.</p>
                    <?php endif; ?>
                </div>

                <button type="button" class="black-btn" onclick="openUpdateModal()">
                    Update Review
                </button>

                <span class="review-help">Select a review to update</span>

                <div id="updateModal" class="modal">
                    <div class="modal-content">
                        <h3>Update Review</h3>

                        <select name="rating" id="updateRating" required>
                            <option value="">Select Rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>

                        <textarea 
                            name="reviewContent" 
                            id="updateReviewContent" 
                            maxlength="250"
                            placeholder="Update your review. Maximum 250 characters."
                        ></textarea>

                        <p class="char-note">Maximum 250 characters.</p>

                        <div class="modal-actions">
                            <button type="button" class="modal-cancel" onclick="closeUpdateModal()">
                                Cancel
                            </button>

                            <button type="submit" name="updateReview" class="modal-confirm">
                                Save Update
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="review-search">
                <input
                    type="text"
                    id="reviewSearch"
                    placeholder="Search reviews based on vendor, rating, date or review content"
                >

                <button type="button" onclick="searchReviews()">
                    Search Review
                </button>
            </div>
        </div>

        <div class="reviews-box">
            <h3>Create a New Review</h3>

            <form method="POST" class="review-form-area">
                <select name="vendorID" required>
                    <option value="">Select a vendor that have completed an event for you to review</option>

                    <?php foreach ($reviewableVendors as $vendor): ?>
                        <option value="<?php echo $vendor['VendorID']; ?>">
                            <?php echo htmlspecialchars($vendor['VendorName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="newRating" required>
                    <option value="">Select Rating</option>
                    <option value="1">1 Star</option>
                    <option value="2">2 Stars</option>
                    <option value="3">3 Stars</option>
                    <option value="4">4 Stars</option>
                    <option value="5">5 Stars</option>
                </select>

                <textarea 
                    name="newReviewContent" 
                    maxlength="250"
                    placeholder="Write your review. Maximum 250 characters."
                ></textarea>

                <p class="char-note">Maximum 250 characters.</p>

                <button type="submit" name="createReview" class="black-btn">
                    Create Review
                </button>
            </form>

            <p class="error-text"><?php echo $errorMessage; ?></p>
            <p class="success-text"><?php echo $message; ?></p>
        </div>
    </main>
</div>

<script>
function selectReview(card) {
    document.querySelectorAll(".review-card-selectable").forEach(reviewCard => {
        reviewCard.classList.remove("selected");
    });

    card.classList.add("selected");

    document.getElementById("selectedReviewID").value = card.dataset.reviewId;
    document.getElementById("updateRating").value = card.dataset.rating;
    document.getElementById("updateReviewContent").value = card.dataset.content;
}

function openUpdateModal() {
    const selectedReviewID = document.getElementById("selectedReviewID").value;

    if (!selectedReviewID) {
        alert("Please select a review to update.");
        return;
    }

    document.getElementById("updateModal").style.display = "block";
}

function closeUpdateModal() {
    document.getElementById("updateModal").style.display = "none";
}

function searchReviews() {
    const searchValue = document.getElementById("reviewSearch").value.toLowerCase();
    const reviews = document.querySelectorAll(".review-card-selectable");

    reviews.forEach(review => {
        const text = review.innerText.toLowerCase();
        review.style.display = text.includes(searchValue) ? "block" : "none";
    });
}

window.onclick = function(event) {
    const updateModal = document.getElementById("updateModal");

    if (event.target === updateModal) {
        updateModal.style.display = "none";
    }
}
</script>

</body>
</html>