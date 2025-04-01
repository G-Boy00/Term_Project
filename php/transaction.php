<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Get transactions
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE from_account IN (
        SELECT account_number FROM accounts WHERE user_id = ?
    ) OR to_account IN (
        SELECT account_number FROM accounts WHERE user_id = ?
    )
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user']['id'], $_SESSION['user']['id']]);
$transactions = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="dashboard-container">
    <h2>Transaction History</h2>
    
    <table class="table table-striped mt-4">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>From Account</th>
                <th>To Account</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= date('M d, Y H:i', strtotime($transaction['created_at'])) ?></td>
                    <td><?= $transaction['type'] ?></td>
                    <td><?= $transaction['from_account'] ?></td>
                    <td><?= $transaction['to_account'] ?></td>
                    <td>$<?= number_format($transaction['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>