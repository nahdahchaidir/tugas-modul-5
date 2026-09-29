<?php
declare(strict_types=1);

class Transaction {
    public function __construct(
        private string $id,
        private string $type,
        private float $amount
    ) {}

    public function process(float &$balance): bool {
        return match($this->type) {
            'deposit' => $this->handleDeposit($balance),
            'withdrawal' => $this->handleWithdrawal($balance),
            default => false
        };
    }

    private function handleDeposit(float &$balance): bool {
        $balance += $this->amount;
        return true;
    }

    private function handleWithdrawal(float &$balance): bool {
        if ($balance >= $this->amount) {
            $balance -= $this->amount;
            return true;
        }
        return false;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getAmount(): float {
        return $this->amount;
    }
}
