<?php

namespace Tests\Feature\Import;

use App\Services\GpayStatementParser;
use PHPUnit\Framework\TestCase;

class GpayStatementParserTest extends TestCase
{
    private function sampleText(): string
    {
        // Mirrors the layout Google Pay statements produce once flattened to text.
        return <<<'TXT'
        02 Jun, 2026 07:21 AM Paid to LINGA DURAI SAKTHI VEL UPI Transaction ID: 615388393399 Paid by Tamilnad Mercantile Bank 7635 ₹28
        03 Jun, 2026 10:52 AM Received from K.P MANIKANDAN UPI Transaction ID: 124125662956 Paid to Indian Overseas Bank 5604 ₹4,000
        04 Jun, 2026 07:55 PM Self transfer to Tamilnad Mercantile Bank 7635 UPI Transaction ID: 615581109906 Paid by IndusInd Bank 1244 ₹40,000
        14 Jun, 2026 03:16 PM Paid to BSNL Prepaid UPI Transaction ID: 653144859702 Paid by IndusInd Bank 1244 ₹320.90
        TXT;
    }

    public function test_it_parses_expenses_and_income_and_skips_self_transfers(): void
    {
        $transactions = (new GpayStatementParser)->parse($this->sampleText());

        // 4 rows in, but the self-transfer is dropped.
        $this->assertCount(3, $transactions);

        $types = array_map(fn ($t) => $t->type, $transactions);
        $this->assertSame(['expense', 'income', 'expense'], $types);
    }

    public function test_it_maps_direction_amount_and_reference_correctly(): void
    {
        $transactions = (new GpayStatementParser)->parse($this->sampleText());

        $expense = $transactions[0];
        $this->assertSame('expense', $expense->type);
        $this->assertSame('2026-06-02', $expense->date);
        // PDF extraction strips the spaces inside names; the money data is exact.
        $this->assertSame('LINGADURAISAKTHIVEL', $expense->counterparty);
        $this->assertSame('615388393399', $expense->reference);
        $this->assertSame(28.0, $expense->amount);

        $income = $transactions[1];
        $this->assertSame('income', $income->type);
        $this->assertSame('K.PMANIKANDAN', $income->counterparty);
        $this->assertSame(4000.0, $income->amount); // comma stripped

        $this->assertSame(320.90, $transactions[2]->amount); // decimals preserved
    }

    public function test_it_splits_amounts_glued_to_the_next_date(): void
    {
        // Exactly how the PDF extracts: no spaces, and each amount runs straight
        // into the following transaction's date ("₹2802Jun", "₹4,00004Jun").
        $glued = 'Date&timeTransactiondetailsAmount02Jun,2026'
            .'07:21AMPaidtoLINGADURAISAKTHIVELUPITransactionID:615388393399PaidbyTamilnadMercantileBank7635'
            .'₹2803Jun,2026'
            .'10:52AMReceivedfromKPMANIKANDANUPITransactionID:124125662956PaidtoIndianOverseasBank5604'
            .'₹4,000Transactionstatement';

        $transactions = (new GpayStatementParser)->parse($glued);

        $this->assertCount(2, $transactions);
        $this->assertSame(28.0, $transactions[0]->amount);   // not 2803
        $this->assertSame(4000.0, $transactions[1]->amount); // not 4,00004
    }

    public function test_it_returns_empty_for_unrecognised_text(): void
    {
        $this->assertSame([], (new GpayStatementParser)->parse('just some random text'));
    }
}
