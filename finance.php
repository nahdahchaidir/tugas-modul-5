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
        $message = "Token CSRF tidak valid.";
    } else {
        $type = $_POST['type'] ?? '';
        $amountInput = $_POST['amount'] ?? '';

        // Validation
        if (!is_numeric($amountInput) || (float)$amountInput <= 0) {
            $message = "Jumlah harus berupa angka desimal positif.";
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
                    $message = "Transaksi berhasil.";
                    // Regenerate CSRF token after successful transaction to prevent reuse
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $message = htmlspecialchars($result);
                }
            } else {
                $message = "Jenis transaksi tidak valid.";
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
    <title>Sistem Manajemen Keuangan Sederhana</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --success: #10B981;
            --danger: #EF4444;
            --bg-color: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        .container { 
            width: 100%;
            max-width: 600px; 
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            box-sizing: border-box;
        }
        
        h2 {
            margin-top: 0;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 25px;
            font-size: 1.5rem;
            text-align: center;
        }
        
        .balance-card {
            background-color: #F1F5F9;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border: 1px solid var(--border);
        }
        
        .balance-card h3 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        
        .balance-amount {
            font-size: 2rem;
            font-weight: 600;
            margin: 5px 0 0;
            color: var(--primary);
        }

        .message { 
            padding: 12px 15px; 
            border-radius: 6px;
            margin-bottom: 20px; 
            font-size: 0.9rem;
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            color: #B91C1C;
        }
        
        .message.success {
            background-color: #ECFDF5;
            border-color: #A7F3D0;
            color: #047857;
        }

        .form-group { margin-bottom: 20px; }
        
        label { 
            display: block; 
            margin-bottom: 6px; 
            font-size: 0.9rem;
            color: var(--text-main);
            font-weight: 500;
        }
        
        input[type="text"], select { 
            width: 100%; 
            padding: 10px 12px; 
            background: #FFFFFF;
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box; 
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        button { 
            width: 100%;
            padding: 12px; 
            background-color: var(--primary);
            color: white; 
            border: none; 
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer; 
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        
        button:hover { 
            background-color: var(--primary-hover);
        }

        h3.history-title {
            margin-top: 40px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: var(--text-main);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            color: var(--text-muted);
            padding: 20px;
            font-style: italic;
            font-size: 0.9rem;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        th, td { 
            padding: 12px 10px; 
            text-align: left; 
            border-bottom: 1px solid var(--border);
        }
        
        th { 
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-deposit {
            background-color: #ECFDF5;
            color: #047857;
            border: 1px solid #D1FAE5;
        }
        
        .type-withdrawal {
            background-color: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FEE2E2;
        }
        
        .amount-pos { color: #047857; }
        .amount-neg { color: #B91C1C; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Sistem Manajemen Keuangan Sederhana</h2>
        
        <div class="balance-card">
            <h3>Saldo Saat Ini</h3>
            <div class="balance-amount">Rp <?= htmlspecialchars(number_format($_SESSION['balance'], 2, ',', '.')) ?></div>
        </div>

        <?php if ($message): ?>
            <?php $isSuccess = strpos($message, 'berhasil') !== false; ?>
            <div class="message <?= $isSuccess ? 'success' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="type">Jenis Transaksi</label>
                <select name="type" id="type" required>
                    <option value="deposit">Setoran (Deposit)</option>
                    <option value="withdrawal">Penarikan (Withdrawal)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Jumlah (Rp)</label>
                <input type="text" name="amount" id="amount" placeholder="Contoh: 50000" required>
            </div>
            
            <button type="submit">Proses Transaksi</button>
        </form>

        <h3 class="history-title">Riwayat Transaksi Terbaru</h3>
        <?php if (empty($_SESSION['history'])): ?>
            <div class="empty-state">Belum ada transaksi.</div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($_SESSION['history']) as $txn): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 0.9rem; color: var(--text-main);"><?= htmlspecialchars(date('M d, Y', strtotime($txn['date']))) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars(date('H:i', strtotime($txn['date']))) ?></div>
                                </td>
                                <td>
                                    <span class="type-badge <?= $txn['type'] === 'deposit' ? 'type-deposit' : 'type-withdrawal' ?>">
                                        <?= htmlspecialchars($txn['type'] === 'deposit' ? 'Setoran' : 'Penarikan') ?>
                                    </span>
                                </td>
                                <td class="<?= $txn['type'] === 'deposit' ? 'amount-pos' : 'amount-neg' ?>" style="font-weight: 500; font-size: 0.95rem;">
                                    <?= $txn['type'] === 'deposit' ? '+' : '-' ?> Rp <?= htmlspecialchars(number_format($txn['amount'], 2, ',', '.')) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
