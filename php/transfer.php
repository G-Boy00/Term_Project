<?php
include '../includes/config.php';
requireAuth();

function getUser($id) {
    global $conn;
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    return $result->fetch_assoc();
}

$user = getUser($_SESSION['user_id']);
$error = '';
$success = '';

if(isset($_POST['transfer'])){
    $amount = (float) sanitizeInput($_POST['amount']);
    $account_number = sanitizeInput($_POST['account_number']);
    $details = sanitizeInput($_POST['details']);

    // Validate receiver account using prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE account_number = ?");
    $stmt->bind_param("s", $account_number);
    $stmt->execute();
    $receiver = $stmt->get_result()->fetch_assoc();


    if(!$receiver){
        $error = "Receiver account not found!";
    } elseif($user['balance'] < $amount){
        $error = "Insufficient balance!";
    } elseif($amount <= 0){
        $error = "Invalid amount!";
    } elseif($account_number == $user['account_number']){
        $error = "Cannot transfer to your own account!";
    } else {
        $conn->begin_transaction();
        try {
            // Update sender balance
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $user['id']);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
            $stmt->bind_param("di", $amount, $user['id']);
            $stmt->execute();

            // Update receiver balance
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $receiver['id']);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE accounts SET balance = balance + ? WHERE user_id = ?");
            $stmt->bind_param("di", $amount, $receiver['id']);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, from_account, to_account, details) 
                        VALUES (?, 'transfer', ?, ?, ?, ?)");

            $senderDetails = "Transfer to account: " . $receiver['account_number'] . " - " . $details;

                // Assign values to standalone variables
                $userId = (int) $user['id'];  // Ensure integer
                $transferAmount = (float) -$amount;  // Ensure float, negative for deduction
                $fromAccount = (string) $user['account_number'];  // Ensure string
                $toAccount = (string) $account_number;  // Ensure string
                $detailsText = (string) $senderDetails;  // Ensure string

                $stmt->bind_param("idsss", $userId, $transferAmount, $fromAccount, $toAccount, $detailsText);
                $stmt->execute();

            
            
            // Record sender transaction
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, from_account, to_account, details) VALUES (?, 'transfer', ?, ?, ?, ?)");

            // Record receiver transaction
            $receiverDetails = "Transfer from account: " . $user['account_number'] . " - " . $details;

                // Assign values to standalone variables
                $receiverId = (int) $receiver['id'];  // Ensure integer
                $fromAccount = (string) $user['account_number'];  // Ensure string
                $toAccount = (string) $account_number;  // Ensure string
                $transferAmount = (float) $amount;  // Ensure float
                $detailsText = (string) $receiverDetails;  // Ensure string

                // Adjusted bind_param to match 5 placeholders
                $stmt->bind_param("idsss", $receiverId, $transferAmount, $fromAccount, $toAccount, $detailsText);
                $stmt->execute();

            
            $conn->commit();
            $_SESSION['success'] = "Successfully transferred $".number_format($amount, 2)." to account ".$receiver['account_number'];
            header("Location: dashboard.php");
            exit();
        } catch(Exception $e) {
            $conn->rollback();
            $error = "Transfer failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Funds - Online Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .transfer-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .balance-display {
            font-size: 1.4rem;
            font-weight: bold;
            color: #218838;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../php/navbar.php'; ?>
    
    <div class="container">
        <div class="transfer-container">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="text-center">Funds Transfer</h3>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <p class="mb-1">Available Balance</p>
                        <p class="balance-display">$<?php echo number_format($user['balance'], 2); ?></p>
                    </div>

                    <form action="transfer.php" method="POST" onsubmit="return validateTransfer()">
                        <div class="mb-3">
                            <label for="account_number" class="form-label">Recipient Account Number</label>
                            <input type="text" class="form-control" id="account_number" name="account_number" 
                                   required pattern="[0-9]{10,20}" title="10-20 digit account number">
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Transfer Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0.01" max="<?php echo $user['balance'] ?>" required>
                            </div>
                            <small class="form-text text-muted">Maximum transferable: $<?php echo number_format($user['balance'], 2); ?></small>
                        </div>

                        <div class="mb-4">
                            <label for="details" class="form-label">Transfer Description</label>
                            <textarea class="form-control" id="details" name="details" rows="3" 
                                      maxlength="255" required></textarea>
                            <small class="form-text text-muted">Maximum 255 characters</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="transfer" class="btn btn-info btn-lg text-white">
                                Transfer Funds
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function validateTransfer() {
        const amountInput = document.getElementById('amount');
        const accountNumberInput = document.getElementById('account_number');
        const currentBalance = <?php echo $user['balance']; ?>;
        
        // Validate amount
        const amount = parseFloat(amountInput.value);
        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid positive amount');
            return false;
        }
        
        if (amount > currentBalance) {
            alert('Transfer amount cannot exceed your current balance');
            return false;
        }
        
        // Validate account number
        const accountNumber = accountNumberInput.value.trim();
        if (!/^\d{10,20}$/.test(accountNumber)) {
            alert('Please enter a valid 10-20 digit account number');
            return false;
        }
        
        // Confirm transfer
        return confirm(`Confirm transfer of $${amount.toFixed(2)} to account ${accountNumber}?`);
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>