<?php
declare(strict_types=1);
require_once 'Transaction.php';

session_start();

// Inisialisasi variabel sesi jika belum ada
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 0.0;
}
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token CSRF tidak valid.';
    } else {
        $amountInput = $_POST['amount'] ?? '';
        $type = $_POST['type'] ?? '';

        // Validasi jumlah transaksi sebagai angka desimal positif
        if (!is_numeric($amountInput) || (float)$amountInput <= 0) {
            $error = 'Jumlah harus berupa angka desimal positif.';
        } else {
            $amount = (float)$amountInput;

            // Gunakan ekspresi match untuk mencocokkan jenis transaksi
            $isValidType = match($type) {
                'deposit', 'withdrawal' => true,
                default => false,
            };

            if (!$isValidType) {
                $error = 'Jenis transaksi tidak valid.';
            } else {
                $transaction = new Transaction(uniqid(), $type, $amount);
                
                $currentBalance = $_SESSION['balance'];
                
                // Proses transaksi dan tolak penarikan bila saldo tidak mencukupi
                if ($transaction->process($currentBalance)) {
                    $_SESSION['balance'] = $currentBalance;
                    
                    $_SESSION['history'][] = [
                        'id' => $transaction->getId(),
                        'type' => $transaction->getType(),
                        'amount' => $transaction->getAmount(),
                        'date' => date('Y-m-d H:i:s')
                    ];
                    $success = 'Transaksi berhasil diproses.';
                } else {
                    $error = 'Saldo tidak mencukupi untuk penarikan.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Keuangan Sederhana</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .balance { font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #0056b3; }
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #0056b3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #004494; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistem Manajemen Keuangan Sederhana</h1>

        <!-- Menampilkan sisa saldo secara aman menggunakan htmlspecialchars -->
        <div class="balance">
            Saldo: Rp <?= htmlspecialchars(number_format($_SESSION['balance'], 2, ',', '.')) ?>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="type">Jenis Transaksi:</label>
                <select name="type" id="type" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Penarikan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="amount">Jumlah:</label>
                <input type="number" step="0.01" min="0.01" name="amount" id="amount" required>
            </div>
            
            <button type="submit">Proses Transaksi</button>
        </form>

        <h2>Riwayat Transaksi</h2>
        <?php if (empty($_SESSION['history'])): ?>
            <p>Belum ada riwayat transaksi.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Menampilkan riwayat transaksi secara aman -->
                    <?php foreach (array_reverse($_SESSION['history']) as $trx): ?>
                        <tr>
                            <td><?= htmlspecialchars($trx['id']) ?></td>
                            <td><?= htmlspecialchars($trx['date']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($trx['type'])) ?></td>
                            <td>Rp <?= htmlspecialchars(number_format($trx['amount'], 2, ',', '.')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
