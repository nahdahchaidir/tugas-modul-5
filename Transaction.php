<?php

class Transaction {
    public function __construct(
        private string $id,
        private string $type,
        private float $amount
    ) {}

    public function process(array &$sessionData): bool|string {
        if (!isset($sessionData['balance'])) {
            $sessionData['balance'] = 0.0;
        }

        if (!isset($sessionData['history'])) {
            $sessionData['history'] = [];
        }

        $result = match ($this->type) {
            'deposit' => $this->handleDeposit($sessionData),
            'withdrawal' => $this->handleWithdrawal($sessionData),
            default => 'Invalid transaction type',
        };

        if ($result === true) {
            $sessionData['history'][] = [
                'id' => $this->id,
                'type' => $this->type,
                'amount' => $this->amount,
                'date' => date('Y-m-d H:i:s')
            ];
            return true;
        }

        return $result;
    }

    private function handleDeposit(array &$sessionData): bool {
        $sessionData['balance'] += $this->amount;
        return true;
    }

    private function handleWithdrawal(array &$sessionData): bool|string {
        if ($sessionData['balance'] < $this->amount) {
            return 'Insufficient balance';
        }
        $sessionData['balance'] -= $this->amount;
        return true;
    }
}
