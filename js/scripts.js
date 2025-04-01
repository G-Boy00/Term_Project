document.getElementById('loginForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    if (password.length < 8) {
        alert('Password must be at least 8 characters!');
        e.preventDefault();
    }
});

// Dashboard functions
function showTransferForm() {
    document.getElementById('transferForm').classList.remove('d-none');
}

function validateTransfer() {
    const amount = document.getElementById('amount').value;
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid amount');
        return false;
    }
    return true;
}