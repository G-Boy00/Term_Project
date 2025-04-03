<?php
require_once __DIR__ . '/../includes/config.php';
requireAuth();

function getUser($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get current user
$user = getUser($_SESSION['user_id']);
if (!$user) {
    die("Error: User not found.");
}

$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    die("Error: User balance not found. Contact support.");
}

$user['balance'] = $userData['balance'];  // Fetch balance from users table


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $description = $_POST['description'] ?? '';
    $fromAccount = $user['account_number'];
    $details = empty($description) ? "Cash withdrawal" : $description;

    if ($amount === false || $amount <= 0) {
        $error = "Invalid withdrawal amount.";
    } elseif ($amount > $user['balance']) {
        $error = "Insufficient funds. Your balance is $" . number_format($user['balance'], 2);
    } else {
        $conn->begin_transaction();
        try {
            // 1. Deduct balance from the accounts table
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
            $stmt->bind_param("di", $amount, $user['id']);
            if (!$stmt->execute()) {
              throw new Exception("Balance update failed in accounts: " . $stmt->error);
            }

            // Update balance in users table too
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $user['id']);
            if (!$stmt->execute()) {
                throw new Exception("Balance update failed in users: " . $stmt->error);
            }


            // 2. Record transaction (Ensure 'withdraw' matches the expected value in transaction history)
            $stmt = $conn->prepare("INSERT INTO transactions 
                (user_id, type, amount, from_account, to_account, details, created_at) 
                VALUES (?, 'withdrawal', ?, ?, ?, ?, NOW())");
            
            if (!$stmt) {
                throw new Exception("Transaction prepare error: " . $conn->error);
            }

            $stmt->bind_param("idsss", $user['id'], $amount, $fromAccount, $fromAccount, $details);
            if (!$stmt->execute()) {
                throw new Exception("Transaction insert error: " . $stmt->error);
            }

            $conn->commit();
            $success = "Successfully withdrew $" . number_format($amount, 2);
            // Refresh user balance after withdrawal
            $user = getUser($_SESSION['user_id']);
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Funds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .withdraw-container { max-width: 500px; margin: 2rem auto; }
        .balance-display { font-size: 1.4rem; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../php/navbar.php'; ?>
    
    <div class="container">
        <div class="withdraw-container">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h3 class="text-center mb-0">Withdraw Funds</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <p class="mb-1 text-muted">Available Balance</p>
                        <p class="balance-display">$<?= number_format($user['balance'], 2) ?></p>
                    </div>

                    <form method="POST" id="withdrawForm">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" max="<?= $user['balance'] ?>" 
                                       required>
                            </div>
                            <small class="form-text text-muted">
                                Maximum withdrawable: $<?= number_format($user['balance'], 2) ?>
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="2" maxlength="255"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="withdraw" class="btn btn-warning btn-lg">
                                Withdraw Funds
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('withdrawForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amount').value);
        const balance = <?= $user['balance'] ?>;
        
        if (isNaN(amount) || amount <= 0) {
            alert('Enter a valid amount.');
            e.preventDefault();
        } else if (amount > balance) {
            alert(`Insufficient funds. You have $${balance.toFixed(2)}.`);
            e.preventDefault();
        } else if (!confirm(`Confirm withdrawal of $${amount.toFixed(2)}?`)) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
