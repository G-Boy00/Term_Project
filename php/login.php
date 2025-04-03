<?php 
include '../includes/config.php';
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

$error = '';
if(isset($_POST['login'])){
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            width: 400px;
            padding: 30px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            text-align: center;
        }
        .password-container {
            position: relative;
        }
        .password-container input {
            width: 100%;
            padding-right: 40px;
        }
        .password-container button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
        .password-container button i {
            font-size: 1.2rem;
            color: #007bff;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            background: #007bff;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }
        .btn-login:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h3>Online Banking Login</h3>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-4 password-container">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" id="toggle-password">
                            <i id="eye-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100 py-2">
                        Sign In
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <p>Don't have an account? <a href="register.php" class="text-primary">Register here</a></p>
                    <p><a href="forgot-password.php" class="text-muted">Forgot Password?</a></p>
                </div>
            </div>
        </div>
    </div>
    <script>
        const passwordField = document.getElementById("password");
        const eyeIcon = document.getElementById("eye-icon");
        document.getElementById("toggle-password").addEventListener("click", function () {
            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passwordField.type = "password";
                eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
            }
        });
    </script>
</body>
</html>
