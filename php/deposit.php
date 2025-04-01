<?php 
include '../includes/config.php';
requireAuth();

// Function to fetch user details, including account number
function getUser($id) {
    global $conn;
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    return $result->fetch_assoc();
}

$error = '';
$user = getUser($_SESSION['user_id']);
$account_number = $user['account_number'];  // Fetch account number from user data

if (isset($_POST['deposit'])) {
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if ($amount > 0) {
        $conn->begin_transaction();
        try {
            // Update balance in users table
            $conn->query("UPDATE users SET balance = balance + $amount WHERE id = {$_SESSION['user_id']}");
            $conn->query("UPDATE accounts SET balance = balance + $amount WHERE user_id = {$_SESSION['user_id']}");
            // Record transaction in transactions table
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, from_account, to_account, details) VALUES (?, 'deposit', ?, ?, ?, ?)");
            $details = "Cash deposit via online banking";  // You can customize this message
            $stmt->bind_param("idsss", $_SESSION['user_id'], $amount, $account_number, $account_number, $details);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Deposit of $" . number_format($amount, 2) . " successful!";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a valid positive amount!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Funds - Online Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .deposit-container {
            max-width: 500px;
            margin: 2rem auto;
        }
        .current-balance {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="deposit-container">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="text-center">Make a Deposit</h3>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <p class="mb-1">Current Balance</p>
                        <p class="current-balance">$<?php echo number_format($user['balance'], 2); ?></p>
                    </div>

                    <form action="deposit.php" method="POST" onsubmit="return validateDeposit()">
                        <div class="mb-4">
                            <label for="amount" class="form-label">Deposit Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" required>
                            </div>
                            <small class="form-text text-muted">Minimum deposit: $0.01</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="deposit" class="btn btn-success btn-lg">
                                Deposit Funds
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function validateDeposit() {
        const amountInput = document.getElementById('amount');
        const amount = parseFloat(amountInput.value);

        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid positive amount');
            return false;
        }

        if (amount > 1000000) { // Optional limit
            alert('Deposit amount cannot exceed $1,000,000');
            return false;
        }

        return confirm(`Are you sure you want to deposit $${amount.toFixed(2)}?`);
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
