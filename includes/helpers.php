<?php
function requireAuth() {
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function getUser($id) {
    global $conn;
    $result = $conn->query("SELECT * FROM users WHERE id = $id");
    return $result->fetch_assoc();
}

function getTransactions($user_id, $limit = null) {
    global $conn;
    $query = "SELECT * FROM transactions WHERE user_id = $user_id ORDER BY timestamp DESC";
    if($limit) $query .= " LIMIT $limit";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTransactionColor($type) {
    switch($type) {
        case 'deposit': return 'success';
        case 'withdraw': return 'danger';
        case 'transfer': return 'info';
        default: return 'secondary';
    }
}
?>