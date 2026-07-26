<?php

namespace App\Support;

/**
 * A single transaction extracted from a payment statement, in a neutral shape
 * the importer can map onto an Expense or Income. Deliberately array-friendly
 * so a batch can be stashed in the session between preview and confirm.
 */
final readonly class ParsedTransaction
{
    public function __construct(
        public string $type,          // 'expense' | 'income'
        public string $date,          // 'Y-m-d'
        public string $counterparty,  // who was paid / who paid
        public string $reference,     // e.g. UPI Transaction ID (used for dedup)
        public float $amount,
    ) {}

    /**
     * @return array{type: string, date: string, counterparty: string, reference: string, amount: float}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'date' => $this->date,
            'counterparty' => $this->counterparty,
            'reference' => $this->reference,
            'amount' => $this->amount,
        ];
    }

    /**
     * @param  array{type: string, date: string, counterparty: string, reference: string, amount: float|string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            date: $data['date'],
            counterparty: $data['counterparty'],
            reference: $data['reference'],
            amount: (float) $data['amount'],
        );
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }
}
