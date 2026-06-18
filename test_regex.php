<?php
require 'vendor/autoload.php';

$rawText = "OVO Berhasil\n4 Apr 2020, 20.32\nRP892.000\nDari\nMUHAMAD IRFAN\nOVO - 081225636394\nPenerima\nROBBY FIRMANSYAH\nBANK BCA - 8030510993\nLIHAT DETAIL";

$months = '(?:januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|peb|feb|mar|apr|jun|jul|agu|agt|sep|okt|nov|des|january|february|march|may|june|july|august|october|december|aug|oct|dec)';
if (preg_match('/\b(\d{1,2}[\/\-\s]+(?:' . $months . '|\d{1,2})[\/\-\s]+\d{2,4}(?:[\s,]+(?:pukul|jam)?\s*\d{2}[:\.]\d{2}(?:[:\.]\d{2})?)?)\b/i', $rawText, $matches)) {
    echo "Matched: " . $matches[1] . "\n";
} else {
    echo "No match\n";
}
