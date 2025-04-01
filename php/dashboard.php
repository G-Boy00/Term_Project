<?php 
include '../includes/config.php';
requireAuth();
function getUser($id) {
    global $conn;
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    return $result->fetch_assoc();
    // $result = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit");
}
function getTransactions($user_id, $limit = null) {
    global $conn;
    $query = "SELECT * FROM transactions WHERE user_id = $user_id ORDER BY timestamp DESC";
    if($limit) $query .= " LIMIT $limit";
    // $result = $conn->query($query);
    // return $result->fetch_all(MYSQLI_ASSOC);
    $result = $conn->query("SELECT * FROM transactions WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit"); 
}
$user = getUser($_SESSION['user_id']);
$transactions = getTransactions($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">Account Summary</div>
                    <div class="card-body">
                        <h5><?= $user['username'] ?></h5>
                        <p class="text-muted">Account: <?= $user['account_number'] ?></p>
                        <h3 class="text-success">$<?= number_format($user['balance'], 2) ?></h3>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="deposit.php" class="btn btn-success">Deposit</a>
                            <a href="withdraw.php" class="btn btn-warning">Withdraw</a>
                            <a href="transfer.php" class="btn btn-info">Transfer</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Transaction History -->
                <?php include 'transaction_history.php'; ?>
            </div>
        </div>
    </div>
</body>
</html>