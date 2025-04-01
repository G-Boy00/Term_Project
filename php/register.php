<?php
require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if (isset($_POST['register'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    $account_number = sanitizeInput($_POST['account_number']);

    // Check if passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the 'users' table
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, account_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $account_number);

        if ($stmt->execute()) {
            // Get the last inserted user ID
            $new_user_id = $stmt->insert_id;

            // Insert the respective account into the 'accounts' table
            $stmt = $conn->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
            $stmt->bind_param("is", $new_user_id, $account_number);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Error creating account: " . $conn->error;
            }
        } else {
            $error = "Error registering user: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin-top: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }
        #password-strength {
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="form-container">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center">Create New Account</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>

                    <form action="register.php" method="POST" onsubmit="return validateForm()">
                        <div class="mb-3">
                            <label for="username" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required onkeyup="checkPasswordStrength()">
                            <small id="password-strength" class="form-text"></small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required onkeyup="checkPasswordMatch()">
                            <small id="password-match" class="form-text"></small>
                        </div>

                        <div class="mb-4">
                            <label for="account_number" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" required>
                            <small class="form-text text-muted">Your unique account identifier</small>
                        </div>

                        <button type="submit" name="register" class="btn btn-primary w-100 py-2">
                            Create Account
                        </button>
                    </form>

                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function checkPasswordStrength() {
        const password = document.getElementById("password").value;
        const strengthText = document.getElementById("password-strength");

        if (password.length < 6) {
            strengthText.innerHTML = "Weak Password";
            strengthText.style.color = "red";
        } else if (password.length < 8 || !/[A-Z]/.test(password) || !/\d/.test(password)) {
            strengthText.innerHTML = "Medium Password";
            strengthText.style.color = "orange";
        } else {
            strengthText.innerHTML = "Strong Password";
            strengthText.style.color = "green";
        }
    }

    function checkPasswordMatch() {
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const matchText = document.getElementById("password-match");

        if (password === confirmPassword && password.length > 0) {
            matchText.innerHTML = "Passwords match";
            matchText.style.color = "green";
        } else {
            matchText.innerHTML = "Passwords do not match";
            matchText.style.color = "red";
        }
    }

    function validateForm() {
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm_password").value;
        const accountNumber = document.getElementById("account_number").value;

        if (password.length < 8) {
            alert("Password must be at least 8 characters!");
            return false;
        }

        if (password !== confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }

        if (!/^\d+$/.test(accountNumber)) {
            alert("Account number must contain only numbers!");
            return false;
        }

        return true;
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
