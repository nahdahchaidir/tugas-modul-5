<?php
session_start();

require_once 'Transaction.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize balance and history if not set
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = "Invalid CSRF token.";
    } else {
        $type = $_POST['type'] ?? '';
        $amountInput = $_POST['amount'] ?? '';

        // Validation
        if (!is_numeric($amountInput) || (float)$amountInput <= 0) {
            $message = "Amount must be a positive decimal number.";
        } else {
            $amount = (float)$amountInput;
            
            // Generate a unique ID for the transaction
            $id = uniqid('txn_');

            // Using match expression to validate type
            $isValidType = match ($type) {
                'deposit', 'withdrawal' => true,
                default => false,
            };

            if ($isValidType) {
                $transaction = new Transaction($id, $type, $amount);
                $result = $transaction->process($_SESSION);

                if ($result === true) {
                    $message = "Transaction successful.";
                    // Regenerate CSRF token after successful transaction to prevent reuse
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $message = htmlspecialchars($result);
                }
            } else {
                $message = "Invalid transaction type.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: auto; }
        .message { padding: 10px; background-color: #f0f0f0; margin-bottom: 15px; border-left: 4px solid #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #eee; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Simple Finance Management System</h2>
        
        <h3>Current Balance: Rp<?= htmlspecialchars(number_format($_SESSION['balance'], 2, ',', '.')) ?></h3>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="type">Transaction Type:</label>
                <select name="type" id="type" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="text" name="amount" id="amount" required>
            </div>
            
            <button type="submit">Process Transaction</button>
        </form>

        <h3>Transaction History</h3>
        <?php if (empty($_SESSION['history'])): ?>
            <p>No transactions yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($_SESSION['history']) as $txn): ?>
                        <tr>
                            <td><?= htmlspecialchars($txn['id']) ?></td>
                            <td><?= htmlspecialchars($txn['date']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($txn['type'])) ?></td>
                            <td>Rp<?= htmlspecialchars(number_format($txn['amount'], 2, ',', '.')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
