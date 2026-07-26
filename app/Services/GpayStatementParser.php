<?php

namespace App\Services;

use App\Support\ParsedTransaction;
use Illuminate\Support\Carbon;
use Smalot\PdfParser\Parser as PdfParser;

class GpayStatementParser
{
    /**
     * Matches one statement row. PDF text extraction strips every space and
     * even glues each amount onto the *next* row's date, e.g.
     *   "...Bank7635₹2802Jun,202607:30AMPaidto..."
     * so the pattern works on whitespace-free text and pins the amount with a
     * strict money grammar plus a look-ahead: the amount must be followed by the
     * next transaction's 2-digit date, a page header, or end of text. That is
     * what splits "₹2802Jun" into ₹28 + 02Jun rather than ₹2802.
     */
    private const ROW_PATTERN = '/(?<date>\d{2}[A-Za-z]{3},\d{4})\d{1,2}:\d{2}[AP]M(?<dir>Paidto|Receivedfrom|Selftransferto)(?<name>.*?)UPITransactionID:(?<ref>\d+)Paid(?:by|to)(?<bank>.*?)₹(?<amt>\d{1,3}(?:,\d{3})*(?:\.\d{2})?)(?=\d{2}[A-Za-z]{3},\d{4}|Transactionstatement|Page\d|$)/u';

    /**
     * Extract, then parse, an uploaded statement PDF.
     *
     * @return list<ParsedTransaction>
     */
    public function parseFile(string $path): array
    {
        return $this->parse($this->extractText($path));
    }

    /**
     * Pull the raw text out of a PDF. Kept separate so parse() can be unit
     * tested on plain strings without a real PDF.
     */
    public function extractText(string $path): string
    {
        return (new PdfParser)->parseFile($path)->getText();
    }

    /**
     * Parse statement text into transactions. Self-transfers are dropped (they
     * are not real income or expense), and rows that fail to parse are skipped
     * rather than aborting the whole import.
     *
     * @return list<ParsedTransaction>
     */
    public function parse(string $text): array
    {
        $text = $this->normalise($text);

        if (! preg_match_all(self::ROW_PATTERN, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $transactions = [];

        foreach ($matches as $match) {
            $direction = $match['dir'];

            // Self transfers move money between the user's own accounts.
            if ($direction === 'Selftransferto') {
                continue;
            }

            $date = $this->parseDate($match['date']);

            if ($date === null) {
                continue;
            }

            $transactions[] = new ParsedTransaction(
                type: $direction === 'Receivedfrom' ? 'income' : 'expense',
                date: $date,
                counterparty: trim($match['name']),
                reference: $match['ref'],
                amount: (float) str_replace(',', '', $match['amt']),
            );
        }

        return $transactions;
    }

    /**
     * Strip *all* whitespace. PDF extraction already drops the spaces within
     * fields, and removing newlines too lets the row pattern treat each
     * transaction as one continuous run of characters.
     */
    private function normalise(string $text): string
    {
        return (string) preg_replace('/\s+/u', '', $text);
    }

    /**
     * "02Jun,2026" -> "2026-06-02", or null if unparseable.
     */
    private function parseDate(string $raw): ?string
    {
        // Re-insert separators the extraction removed: "02Jun,2026" -> "02 Jun 2026".
        $spaced = preg_replace('/(\d{2})([A-Za-z]{3}),(\d{4})/', '$1 $2 $3', $raw);

        try {
            return Carbon::createFromFormat('d M Y', $spaced)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return Carbon::parse($spaced)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
