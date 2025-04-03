<?php
include __DIR__ . '/../includes/config.php';
requireAuth();

$user_id = $_SESSION['user_id'];

// Get transactions with proper error handling
$transactions = [];
$query = "SELECT type, from_account, to_account, amount, status, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link href="../css/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- <?php include __DIR__ . '/../php/navbar.php'; ?> -->

    <div class="container mt-4">
        <h2>Transaction History</h2>
        
        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">No transactions found.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>From Account</th>
                        <th>To Account</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('M d, Y H:i', strtotime($transaction['created_at'])) ?></td>
                            <td>
                                <?php 
                                    $type = strtolower($transaction['type']); 
                                    echo ucfirst($type);
                                ?>
                            </td>
                            <td><?= $transaction['from_account'] ?? 'N/A' ?></td>
                            <td><?= $transaction['to_account'] ?? 'N/A' ?></td>
                            <td class="<?= ($type === 'deposit') ? 'text-success' : 'text-danger' ?>">
                                $<?= number_format($transaction['amount'], 2) ?>
                            </td>
                            <td><?= ucfirst($transaction['status'] ?? 'Completed') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
