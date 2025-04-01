<?php
session_start();
require_once '../includes/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, account_number, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$error = "";
$success = "";

// Handle profile update (Username, Email, Account Number)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_username = htmlspecialchars(trim($_POST['username']));
    $new_email = htmlspecialchars(trim($_POST['email']));
    $new_account = htmlspecialchars(trim($_POST['account_number']));

    // Validate fields
    if (empty($new_username) || empty($new_email) || empty($new_account)) {
        $error = "All fields are required!";
    } else {
        // Update user details
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, account_number = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_username, $new_email, $new_account, $user_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Update displayed values
            $user['username'] = $new_username;
            $user['email'] = $new_email;
            $user['account_number'] = $new_account;
        } else {
            $error = "Error updating profile!";
        }
    }
}

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_pic"])) {
    $uploadDir = "../uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $fileType = $_FILES["profile_pic"]["type"];
    
    if (!in_array($fileType, $allowedTypes)) {
        $error = "Only JPG, JPEG, and PNG files are allowed!";
    } else {
        $uploadFile = $uploadDir . "profile_" . $user_id . ".jpg";

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $uploadFile)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $profilePath = "uploads/profile_" . $user_id . ".jpg";
            $stmt->bind_param("si", $profilePath, $user_id);

            if ($stmt->execute()) {
                $success = "Profile picture updated successfully!";
                $user['profile_pic'] = $profilePath;
            } else {
                $error = "Error updating profile picture in database!";
            }
        } else {
            $error = "Error uploading profile picture!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Online Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>My Profile</h4>
                    </div>
                    <div class="card-body text-center">
                        <!-- Display Profile Picture -->
                        <img src="<?= isset($user['profile_pic']) ? '../' . $user['profile_pic'] : '../uploads/default_profile.png'; ?>" 
                             alt="Profile Picture" class="rounded-circle mb-3" width="150" height="150">

                        <!-- Display Success or Error Message -->
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <!-- Profile Update Form -->
                        <form action="profile.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       value="<?= htmlspecialchars($user['account_number']) ?>" required>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-success w-100">
                                Save Changes
                            </button>
                        </form>

                        <hr>

                        <!-- Profile Picture Upload Form -->
                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Upload New Profile Picture</label>
                                <input type="file" class="form-control" name="profile_pic" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Upload</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
