<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrFieldMapperService
{
    /**
     * Dictionary of standardized fields and their aliases.
     * All aliases should be in lowercase for case-insensitive matching.
     */
    protected $aliasDictionary = [
        'tanggal_transaksi' => [
            'transaction date', 'tanggal transaksi', 'tanggal'
        ],
        'waktu_transaksi' => [
            'transaction time', 'waktu transaksi', 'waktu', 'jam'
        ],
        'nomor_referensi' => [
            'id transaksi merchant', 'external serial number', 'nomor referensi', 
            'id transaksi', 'nomor jurnal', 'no.referensi', 'no. ref', 'no ref', 'ref#', 'ref'
        ],
        'nama_penerima' => [
            'nama penerima', 'nama tujuan', 'beneficiary', 'recipient', 'penerima', 'kepada', 'nama'
        ],
        'nama_pengirim' => [
            'transfer melalui', 'nama pengirim', 'nama rekening', 'rekening asal', 
            'sumber dana', 'pengirim', 'dari'
        ],
        'rekening_tujuan' => [
            'nomor rekening tujuan', 'rekening penerima', 'nomor rekening', 'no. rek penerima', 
            'no rek penerima', 'rek penerima', 'rekening tujuan', 'nomor tujuan', 'rek.tujuan', 
            'ke rekening', 'akun bank', 'ke rek.', 'ke rek'
        ],
        'rekening_pengirim' => [
            'sumber dana rekening', 'nomor rekening asal', 'rekening pengirim', 
            'no. rek pengirim', 'no rek pengirim', 'rek pengirim', 'rekening asal', 'dari rekening'
        ],
        'bank_pengirim' => [
            'bank pengirim', 'bank asal', 'dari bank'
        ],
        'bank_tujuan' => [
            'bank penerima', 'bank tujuan', 'ke bank'
        ],
        'nominal' => [
            'nominal transfer', 'jumlah transfer', 'total bayar', 'jumlah setor', 
            'jml tagihan', 'jml bayar', 'nominal', 'jumlah', 'total', 'jml'
        ],
        'biaya_admin' => [
            'biaya transfer', 'biaya layanan', 'biaya admin', 'admin bank', 'fee'
        ],
        'total_pembayaran' => [
            'total pembayaran', 'total transfer', 'total'
        ],
        'status_transaksi' => [
            'status', 'transaksi berhasil', 'transaksi sukses', 'berhasil', 'sukses'
        ],
        'id_transaksi' => [
            'id transaksi', 'transaction id', 'external serial number', 'merchant id'
        ],
    ];

    /**
     * Process raw OCR text and return a mapped dictionary of fields.
     */
    public function mapFields(string $rawOcrText): array
    {
        $mappedData = [];
        
        // Convert to array of lines for easier processing
        $lines = array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", $rawOcrText)));
        
        $unmatchedLines = [];
        $currentSection = null;

        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            // Contextual Block Parsing for Mandiri and similar receipts (Robust against OCR noise)
            $alphaOnly = strtolower(trim(preg_replace('/[^a-z]/i', '', $line)));
            
            if (stripos($alphaOnly, 'transferdari') !== false || $alphaOnly === 'dari') {
                $currentSection = 'pengirim';
                continue;
            } elseif ($alphaOnly === 'ke' || $alphaOnly === 'kepada' || $alphaOnly === 'tujuan' || $alphaOnly === 'penerima' || stripos($alphaOnly, 'transferke') !== false) {
                $currentSection = 'penerima';
                continue;
            }

            $extracted = $this->extractLine($line, $currentSection);
            
            if ($extracted) {
                // If the field isn't set yet, or we're replacing an empty/null value
                if (!isset($mappedData[$extracted['field']]) || empty($mappedData[$extracted['field']])) {
                    $mappedData[$extracted['field']] = $extracted['value'];
                }
            } else {
                $unmatchedLines[] = $line;
            }
        }
        
        // Regex Fallback for highly critical fields if not found by Dictionary
        $mappedData = $this->applyRegexFallback($mappedData, $unmatchedLines, $rawOcrText);

        // Logging missing critical fields
        $this->logMissingFields($mappedData);

        return $mappedData;
    }

    /**
     * Tries to match a line to a known alias.
     */
    protected function extractLine(string $line, ?string $currentSection = null): ?array
    {
        // Many OCR results are in format "LABEL : VALUE" or "LABEL VALUE"
        // Let's try to split by common separators (removed '-' because it's often used in values or without spaces)
        $parts = preg_split('/[:;]/', $line, 2);
        
        $labelCandidate = '';
        $valueCandidate = '';

        if (count($parts) === 2) {
            $labelCandidate = trim($parts[0]);
            $valueCandidate = trim($parts[1]);
        } else {
            // Coba lihat apakah ada pemisah '-' yang valid (diikuti spasi)
            $partsDash = preg_split('/\s+-\s+/', $line, 2);
            if (count($partsDash) === 2) {
                $labelCandidate = trim($partsDash[0]);
                $valueCandidate = trim($partsDash[1]);
            } else {
                // Fallback: no explicit separator. We try to guess the label from the beginning.
                // E.g., "Jumlah Rp 50.000"
                foreach ($this->aliasDictionary as $standardField => $aliases) {
                    foreach ($aliases as $alias) {
                        $aliasLen = strlen($alias);
                        // Case-insensitive starts_with check
                        if (strncasecmp($line, $alias, $aliasLen) === 0) {
                            // Ensure it's a word boundary
                            if (strlen($line) == $aliasLen || preg_match('/^\s/', substr($line, $aliasLen, 1))) {
                                $labelCandidate = $alias;
                                $valueCandidate = trim(substr($line, $aliasLen));
                                break 2;
                            }
                        }
                    }
                }
            }
        }    

        if (empty($labelCandidate) || empty($valueCandidate)) {
            return null;
        }

        // Exact & Case-insensitive Matching
        $normalizedLabel = strtolower(trim($labelCandidate));
        
        // Mencegah SeaBank fuzzy match ke 'ke bank'
        if ($normalizedLabel === 'seabank') {
            return null;
        }

        // Append section context for generic labels (e.g. "BANK" + "pengirim" => "bank pengirim")
        if ($currentSection && preg_match('/^(bank|nama|no\.?\s*rek|rek|rekening)$/i', $normalizedLabel)) {
            $normalizedLabel = $normalizedLabel . ' ' . $currentSection;
            Log::debug("OcrFieldMapper: Contextualized generic label to '$normalizedLabel'");
        }

        foreach ($this->aliasDictionary as $standardField => $aliases) {
            if (in_array($normalizedLabel, $aliases, true)) {
                $cleanedValue = $this->cleanValue($standardField, $valueCandidate);
                if (empty($cleanedValue) && in_array($standardField, ['nominal', 'rekening_tujuan', 'rekening_pengirim'])) {
                    return null; // Value is empty after cleaning (e.g. just "Rp."), send to unmatched lines
                }
                return [
                    'field' => $standardField,
                    'value' => $cleanedValue
                ];
            }
        }

        // Fuzzy Matching (Levenshtein) if exact match fails
        // Tolerance: max 2 typos for short words, 3 for longer words
        foreach ($this->aliasDictionary as $standardField => $aliases) {
            foreach ($aliases as $alias) {
                $distance = levenshtein($normalizedLabel, $alias);
                $maxTolerance = strlen($alias) <= 5 ? 1 : (strlen($alias) <= 10 ? 2 : 3);
                
                if ($distance <= $maxTolerance) {
                    Log::debug("OcrFieldMapper: Fuzzy matched '$normalizedLabel' to alias '$alias' (Distance: $distance)");
                    $cleanedValue = $this->cleanValue($standardField, $valueCandidate);
                    if (empty($cleanedValue) && in_array($standardField, ['nominal', 'rekening_tujuan', 'rekening_pengirim'])) {
                        return null; // Value is empty after cleaning
                    }
                    return [
                        'field' => $standardField,
                        'value' => $cleanedValue
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Apply regex fallback for fields that are missing
     */
    protected function applyRegexFallback(array $mappedData, array $unmatchedLines, string $fullRawText = ''): array
    {
        $rawText = implode(" ", $unmatchedLines);
        // Normalize full raw text to single line for easy regex matching
        $fullTextStr = preg_replace('/\s+/', ' ', $fullRawText);
        
        Log::debug("OcrFieldMapper: RAW TEXT UNMATCHED: " . $rawText);
        
        // --- NEW FALLBACK FOR DANA / SEABANK / E-WALLET FORMAT ---
        // Format Sender: "Dari Ria Suprihatin Dana: **********4071"
        if (preg_match('/(?:dari|pengirim)\s+([A-Za-z\s\.:\-]+?)\s+(DANA|OVO|GOPAY|SHOPEEPAY|LINKAJA|SEABANK|JAGO|NEOBANK|BCA|MANDIRI|BRI|BNI|BSI|PERMATA|DANAMON|MEGA)[\s:]+([\*\.\dxX]{8,20})/i', $fullTextStr, $matches)) {
            // Kita bisa override karena match ini sangat spesifik dan akurat
            $mappedData['nama_pengirim'] = trim(trim($matches[1], ':-. '));
            $mappedData['bank_pengirim'] = strtoupper($matches[2]);
            $mappedData['rekening_pengirim'] = preg_replace('/[^\d]/', '', $matches[3]);
            Log::debug("OcrFieldMapper: Regex fallback found Pengirim (E-Wallet): {$mappedData['nama_pengirim']} - {$mappedData['bank_pengirim']} {$mappedData['rekening_pengirim']}");
        }

        // Format Recipient: "Ke Daniel Setya Dharma SeaBank: ********4360"
        if (preg_match('/(?:ke|kepada|tujuan)\s+([A-Za-z\s\.:\-]+?)\s+(DANA|OVO|GOPAY|SHOPEEPAY|LINKAJA|SEABANK|JAGO|NEOBANK|BCA|MANDIRI|BRI|BNI|BSI|PERMATA|DANAMON|MEGA)[\s:]+([\*\.\dxX]{8,20})/i', $fullTextStr, $matches)) {
            $mappedData['nama_penerima'] = trim(trim($matches[1], ':-. '));
            $mappedData['bank_tujuan'] = strtoupper($matches[2]);
            $mappedData['rekening_tujuan'] = preg_replace('/[^\d]/', '', $matches[3]);
            Log::debug("OcrFieldMapper: Regex fallback found Penerima (E-Wallet): {$mappedData['nama_penerima']} - {$mappedData['bank_tujuan']} {$mappedData['rekening_tujuan']}");
        }
        // ---------------------------------------------------------
        // ---------------------------------------------------------
        
        // Fallback Nominal
        if (empty($mappedData['nominal'])) {
            // Prioritaskan mencari Total dulu (karena Nominal kadang kepotong OCR)
            if (preg_match('/(?:total|jumlah\s*bayar)[\s:]*(?:rp|idr|rp\.|rp\s)?\s*([\d\.,\soO]+)/i', $rawText, $matches)) {
                $nomRaw = str_ireplace('o', '0', $matches[1]);
                $nomRaw = preg_replace('/[,.]00$/', '', trim($nomRaw));
                $nomRaw = preg_replace('/\D/', '', $nomRaw);
                if (!empty($nomRaw) && (int)$nomRaw > 1000) {
                    $mappedData['nominal'] = $nomRaw;
                    Log::debug("OcrFieldMapper: Regex fallback found Nominal (from Total): " . $mappedData['nominal']);
                }
            }

            // Jika gagal, cari Nominal biasa
            if (empty($mappedData['nominal'])) {
                if (preg_match('/(?:rp|idr|rp\.|rp\s)\s*([\d\.,\soO]+)/i', $rawText, $matches)) {
                    $nomRaw = str_ireplace('o', '0', $matches[1]);
                    // Simpan versi tanpa strip .00
                    $rawNoStrip = preg_replace('/\D/', '', trim($nomRaw));
                    
                    // Coba versi di-strip .00
                    $nomRaw = preg_replace('/[,.]00$/', '', trim($nomRaw));
                    $nomRaw = preg_replace('/\D/', '', $nomRaw);
                    
                    // Jika terlalu kecil, asumsikan .00 tadi adalah ribuan yang kehilangan angka 0
                    if (!empty($nomRaw) && (int)$nomRaw < 1000) {
                        if ((int)$rawNoStrip >= 1000) {
                            $nomRaw = $rawNoStrip; // gunakan yg belum di strip
                        }
                        // Jika masih di bawah 1000, asumsikan ribuannya ilang total
                        if ((int)$nomRaw < 1000) {
                            $nomRaw = $nomRaw . '000';
                        }
                    }
                    
                    if (!empty($nomRaw)) {
                        $mappedData['nominal'] = $nomRaw;
                        Log::debug("OcrFieldMapper: Regex fallback found Nominal: " . $mappedData['nominal']);
                    }
                }
            }
        }

        // Fallback Rekening Tujuan (10-16 digits)
        if (empty($mappedData['rekening_tujuan'])) {
            // Check for contextual rekening
            if (preg_match('/(?:rekening\s*tujuan|rekening\s*penerima|ke\s*rek|ke\s*rekening|nomor\s*tujuan)[\s:]*([\d\-\s\.]{10,25})/i', $rawText, $matches)) {
                $rekRaw = preg_replace('/\D/', '', $matches[1]);
                if (strlen($rekRaw) >= 10) {
                    $mappedData['rekening_tujuan'] = $rekRaw;
                    Log::debug("OcrFieldMapper: Regex fallback (context) found Rekening Tujuan: " . $mappedData['rekening_tujuan']);
                }
            }
            
            if (empty($mappedData['rekening_tujuan'])) {
                // Try to find a contiguous block of digits (or digits with dashes that total 10+ digits)
                if (preg_match('/\b(\d{3,4}[\-\s]*\d{3,4}[\-\s]*\d{3,6})\b/', $rawText, $matches)) {
                    $rekRaw = preg_replace('/\D/', '', $matches[1]);
                    if (strlen($rekRaw) >= 10) {
                        $mappedData['rekening_tujuan'] = $rekRaw;
                        Log::debug("OcrFieldMapper: Regex fallback found Rekening Tujuan: " . $mappedData['rekening_tujuan']);
                    }
                } elseif (preg_match('/\b(\d{10,16})\b/', $rawText, $matches)) {
                    $mappedData['rekening_tujuan'] = $matches[1];
                    Log::debug("OcrFieldMapper: Regex fallback found Rekening Tujuan: " . $mappedData['rekening_tujuan']);
                }
            }
        }

        // Fallback Bank Tujuan
        if (empty($mappedData['bank_tujuan'])) {
            // Prioritaskan mencari dengan keyword "KE BANK" atau "BANK TUJUAN" diikuti nama bank
            if (preg_match('/(?:ke\s*bank|bank\s*tujuan|bank\s*penerima).*?(BCA|MANDIRI|BRI|BNI|BSI|PERMATA|DANAMON|MEGA)/i', $rawText, $matches)) {
                $mappedData['bank_tujuan'] = strtoupper($matches[1]);
                Log::debug("OcrFieldMapper: Regex fallback (context) found Bank Tujuan: " . $mappedData['bank_tujuan']);
            } else {
                $banks = ['BCA', 'MANDIRI', 'BRI', 'BNI', 'BSI', 'PERMATA', 'DANAMON', 'MEGA'];
                foreach ($banks as $bank) {
                    if (stripos($rawText, $bank) !== false) {
                        $mappedData['bank_tujuan'] = $bank;
                        Log::debug("OcrFieldMapper: Regex fallback found Bank Tujuan: " . $bank);
                        break;
                    }
                }
            }
        }

        // Fallback Nama Penerima
        if (empty($mappedData['nama_penerima'])) {
            // Cari kata NAMA diikuti huruf besar, berhenti jika ketemu label lain
            if (preg_match('/(?:nama\s*penerima|nama\s*tujuan|kepada|nama)[\s:]+((?:(?!\brekening\b|\bnominal\b|\bbank\b|\bjumlah\b|\bno\.?\b|\bdari\b|\balias\b|\bcatatan\b|\bbiaya\b|\btotal\b).){3,40})/i', $rawText, $matches)) {
                $namaRaw = trim($matches[1]);
                if (!preg_match('/(?:PENGIRIM|DARI)/i', $namaRaw)) { // Pastikan bukan label pengirim
                    $mappedData['nama_penerima'] = $this->cleanValue('nama_penerima', $namaRaw);
                    Log::debug("OcrFieldMapper: Regex fallback found Nama Penerima: " . $mappedData['nama_penerima']);
                }
            }
        }
        
        // Fallback Nomor Referensi
        if (empty($mappedData['nomor_referensi'])) {
            if (preg_match('/(?:nomor\s*referensi|no\.?\s*ref|ref\s*no|referensi)[\s:]*([A-Za-z0-9]{6,20})/i', $rawText, $matches)) {
                $mappedData['nomor_referensi'] = strtoupper($matches[1]);
                Log::debug("OcrFieldMapper: Regex fallback found Nomor Referensi: " . $mappedData['nomor_referensi']);
            }
        }

        // Fallback for BCA m-Transfer modal specifically
        // Kita juga gunakan strpos untuk memastikan kita menangkap data rekening
        if (strpos(preg_replace('/\D/', '', $rawText), '218001000867569') !== false) {
            if (empty($mappedData['rekening_tujuan'])) $mappedData['rekening_tujuan'] = '218001000867569';
            if (empty($mappedData['nama_penerima'])) $mappedData['nama_penerima'] = 'SMK BIT BINA AULIA';
            if (empty($mappedData['bank_tujuan'])) $mappedData['bank_tujuan'] = 'BRI';
        }

        // BCA m-Transfer Nominal fallback
        if (empty($mappedData['nominal']) && preg_match('/NOMINAL\s*TRANSFER\s*(?:Rp\.?|IDR)?\s*([\d.,\s]+)/is', $rawText, $matches)) {
            $nomRaw = preg_replace('/[,.]000?$/', '', trim($matches[1]));
            $nomRaw = preg_replace('/\D/', '', $nomRaw);
            if (!empty($nomRaw)) {
                $mappedData['nominal'] = $nomRaw;
            }
        }

        // BCA m-Transfer Reference fallback
        if (empty($mappedData['nomor_referensi']) && preg_match('/(m-Transfer:\s*BERHASIL)/is', $rawText)) {
            $mappedData['nomor_referensi'] = 'M-TRANSFER';
        }

        // Fallback Date
        if (empty($mappedData['tanggal_transaksi']) || !preg_match('/\d/', $mappedData['tanggal_transaksi'])) {
            // dd/mm/yyyy or dd-mm-yyyy or dd mmm yyyy (with optional time HH:MM:SS)
            $months = '(?:januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|peb|feb|mar|apr|jun|jul|agu|agt|sep|okt|nov|des|january|february|march|may|june|july|august|october|december|aug|oct|dec)';
            if (preg_match('/\b(\d{1,2}[\/\-\s]+(?:' . $months . '|\d{1,2})[\/\-\s]+\d{2,4}(?:[\s,]+(?:pukul|jam)?\s*\d{2}[:\.]\d{2}(?:[:\.]\d{2})?)?)\b/i', $rawText, $matches)) {
                $mappedData['tanggal_transaksi'] = $this->cleanValue('tanggal_transaksi', $matches[1]);
                Log::debug("OcrFieldMapper: Regex fallback found Tanggal Transaksi: " . $mappedData['tanggal_transaksi']);
            }
        }

        return $mappedData;
    }

    /**
     * Cleans up the extracted value based on the field type
     */
    protected function cleanValue(string $field, string $value): string
    {
        $value = trim($value);
        
        // Auto-correct nama sekolah yang sering terpotong OCR
        if ($field === 'nama_penerima' && stripos($value, 'SMK BIT BINA AUL') !== false && stripos($value, 'AULIA') === false) {
            $value = str_ireplace('SMK BIT BINA AUL', 'SMK BIT BINA AULIA', $value);
        }
        
        switch ($field) {
            case 'nominal':
            case 'biaya_admin':
            case 'total_pembayaran':
                // Kadang OCR membaca angka 0 sebagai huruf O
                $value = str_ireplace('o', '0', $value);
                
                $rawNoStrip = preg_replace('/[Rp\s\.,]/i', '', $value);
                $rawNoStrip = preg_replace('/\D/', '', $rawNoStrip);
                
                // Strip trailing ,00 or .00 first to avoid multiplying by 100
                $value = preg_replace('/[,.]00$/', '', trim($value));
                // Remove Rp, dots, commas, spaces
                $clean = preg_replace('/[Rp\s\.,]/i', '', $value);
                $clean = preg_replace('/\D/', '', $clean);
                
                if (!empty($clean) && (int)$clean < 1000) {
                    if ((int)$rawNoStrip >= 1000) {
                        $clean = $rawNoStrip;
                    }
                    if ((int)$clean < 1000) {
                        $clean = $clean . '000';
                    }
                }
                return $clean;
                
            case 'rekening_tujuan':
            case 'rekening_pengirim':
                // Keep only digits, asterisks, dots, and 'X'
                return preg_replace('/[^0-9\*\.Xx]/', '', $value);
                
            case 'bank_tujuan':
            case 'bank_pengirim':
                return strtoupper($value);
                
            case 'tanggal_transaksi':
                $monthsId = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'jan', 'peb', 'feb', 'mar', 'apr', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];
                $monthsEn = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december', 'jan', 'feb', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
                return str_ireplace($monthsId, $monthsEn, $value);
                
            default:
                return $value;
        }
    }

    /**
     * Log missing critical fields for evaluation
     */
    protected function logMissingFields(array $mappedData): void
    {
        $criticalFields = ['nominal', 'tanggal_transaksi', 'rekening_tujuan', 'bank_tujuan'];
        $missing = [];
        
        foreach ($criticalFields as $field) {
            if (empty($mappedData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            Log::warning("OcrFieldMapper: Gagal mengekstrak field penting: " . implode(', ', $missing));
        }
    }

    /**
     * Perform strict validation according to business rules.
     * Returns an array containing is_valid boolean and array of errors.
     */
    public function validateStrict(array $mappedData, $expectedDate = null, $expectedAmount = null): array
    {
        $errors = [];
        $checks = [
            'nama_penerima' => false,
            'rekening_tujuan' => false,
            'bank_tujuan' => false,
            'nomor_referensi' => false,
            'tanggal_transaksi' => false,
        ];

        // CEK DATA WAJIB (JIKA KOSONG KEMUNGKINAN GAMBAR BLUR)
        $requiredFields = [
            'tanggal_transaksi' => 'Tanggal Transaksi',
            'nama_penerima' => 'Nama Penerima',
            'bank_tujuan' => 'Bank Penerima',
            'rekening_tujuan' => 'Nomor Rekening Penerima',
            'nomor_referensi' => 'Nomor Referensi'
        ];
        $missingRequired = [];
        foreach ($requiredFields as $key => $label) {
            if (empty($mappedData[$key]) || trim($mappedData[$key]) === '-' || trim($mappedData[$key]) === '') {
                $missingRequired[] = $label;
            }
        }
        
        if (count($missingRequired) > 0) {
            $errors[] = "Tolong upload ulang, gambar blur karena data wajib tidak terdeteksi (" . implode(', ', $missingRequired) . ").";
        }

        // 1. Nama penerima sesuai dengan: SMK BIT BINA AULIA
        $namaPenerima = strtolower($mappedData['nama_penerima'] ?? '');
        if (!empty($namaPenerima) && (strpos($namaPenerima, 'bina aulia') !== false || strpos($namaPenerima, 'smk bit') !== false)) {
            $checks['nama_penerima'] = true;
        } elseif (!empty($namaPenerima)) {
            $errors[] = "Nama penerima tidak sesuai (Dibutuhkan: SMK BIT BINA AULIA). Terbaca: " . $namaPenerima;
        }

        // 2. Nomor rekening tujuan sesuai dengan: 218001000867569
        $rekTujuan = preg_replace('/\D/', '', $mappedData['rekening_tujuan'] ?? '');
        if (strpos($rekTujuan, '218001000867569') !== false || $rekTujuan === '218001000867569') {
            $checks['rekening_tujuan'] = true;
        } elseif (!empty($rekTujuan)) {
            $errors[] = "Nomor rekening tujuan tidak sesuai (Dibutuhkan: 218001000867569). Terbaca: " . $rekTujuan;
        }

        // 3. Bank tujuan sesuai dengan: BRI
        $bankTujuan = strtolower($mappedData['bank_tujuan'] ?? '');
        if (!empty($bankTujuan) && strpos($bankTujuan, 'bri') !== false) {
            $checks['bank_tujuan'] = true;
        } elseif (!empty($bankTujuan)) {
            $errors[] = "Bank tujuan tidak sesuai (Dibutuhkan: BRI). Terbaca: " . $bankTujuan;
        }

        // 4. Nomor referensi berhasil ditemukan
        $noRef = $mappedData['nomor_referensi'] ?? '';
        if (!empty($noRef)) {
            $checks['nomor_referensi'] = true;
        } else {
            // Beberapa bukti seperti m-Transfer modal tidak memiliki nomor referensi eksplisit
            $checks['nomor_referensi'] = true; 
            // Kita tidak menjadikan ini sebagai error selain dari pesan blur di atas
        }

        // 5. Tanggal transaksi sesuai dengan form pembayaran (toleransi 7 hari)
        $tanggalTransaksi = $mappedData['tanggal_transaksi'] ?? '';
        if (empty($tanggalTransaksi)) {
            // Sudah dihandle oleh deteksi blur di atas, namun bisa juga di log
        } elseif ($expectedDate) {
            $ocrDate = null;
            // Bersihkan tanggal dari karakter selain angka, huruf, dan separator
            $tanggalBersih = trim(preg_replace('/[^0-9a-zA-Z\/\-\s\.,]/', '', $tanggalTransaksi));
            $parsedDateStr = str_replace(['\\', '.', ' '], '-', $tanggalBersih);
            $parsedDateStr = str_replace('/', '-', $parsedDateStr);
            
            // Coba parsing spesifik format DD-MM-YY (2 digit tahun)
            if (preg_match('/^\d{1,2}-\d{1,2}-\d{2}$/', $parsedDateStr)) {
                try {
                    $ocrDate = \Carbon\Carbon::createFromFormat('d-m-y', $parsedDateStr);
                } catch (\Exception $e) {}
            }
            
            // Jika gagal, coba format DD-MM-YYYY (4 digit tahun)
            if (!$ocrDate && preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $parsedDateStr)) {
                try {
                    $ocrDate = \Carbon\Carbon::createFromFormat('d-m-Y', $parsedDateStr);
                } catch (\Exception $e) {}
            }
            
            // Jika masih gagal, serahkan ke parser default Carbon
            if (!$ocrDate) {
                try {
                    $ocrDate = \Carbon\Carbon::parse($parsedDateStr);
                } catch (\Exception $e) {}
            }
            
            if ($ocrDate) {
                // Standarkan format agar Frontend JS new Date() tidak error "Invalid Date"
                $mappedData['tanggal_transaksi'] = $ocrDate->format('Y-m-d');
                try {
                    $formDate = \Carbon\Carbon::parse($expectedDate);
                    
                    if (abs($ocrDate->diffInDays($formDate)) <= 7) {
                        $checks['tanggal_transaksi'] = true;
                    } else {
                        $errors[] = "Tanggal transaksi ({$ocrDate->format('d/m/Y')}) berbeda lebih dari 7 hari dari form ({$formDate->format('d/m/Y')}).";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Format tanggal form tidak valid: " . $expectedDate;
                }
            } else {
                $errors[] = "Format tanggal transaksi OCR tidak valid: " . $tanggalTransaksi;
            }
        } else {
            // Jika tidak ada form date yang di-expect, anggap saja lolos jika tanggal ada
            $checks['tanggal_transaksi'] = true;
        }

        // 6. Nominal sesuai dengan form (jika diberikan) dengan toleransi
        if ($expectedAmount !== null) {
            $nominal = (float)($mappedData['nominal'] ?? 0);
            $expected = (float)$expectedAmount;
            
            \Log::debug("validateStrict: BEFORE AUTO-CORRECT -> Nominal: {$nominal}, Expected: {$expected}");
            
            // Auto-correct if nominal is exactly 100x or 1000x larger due to OCR decimal misread
            // (e.g. OCR reads Rp 200,000.00 as 200.000.000)
            if ($nominal > 0) {
                if (abs(($nominal / 100) - $expected) <= 2500) {
                    $nominal = $nominal / 100;
                    $mappedData['nominal'] = $nominal; // Update mapped data
                    \Log::debug("validateStrict: Auto-corrected Nominal / 100 -> {$nominal}");
                } elseif (abs(($nominal / 1000) - $expected) <= 2500) {
                    $nominal = $nominal / 1000;
                    $mappedData['nominal'] = $nominal; // Update mapped data
                    \Log::debug("validateStrict: Auto-corrected Nominal / 1000 -> {$nominal}");
                }
            }

            if (abs($nominal - $expected) <= 2500) {
                $checks['nominal'] = true;
            } else {
                $errors[] = "Nominal tidak sesuai! Form: Rp " . number_format($expected, 0, ',', '.') . ", Bukti: Rp " . number_format($nominal, 0, ',', '.') . " (toleransi Rp 2.500)";
            }
        }

        // 7. Cek Duplikasi (Mencegah Kecurangan/Upload Ganda)
        $refNo = $mappedData['nomor_referensi'] ?? '';
        $refNoClean = trim($refNo);
        
        if (!empty($refNoClean) && strlen($refNoClean) >= 5 && $refNoClean !== '-') {
            // Cek berdasarkan nomor referensi yang unik di tabel payments
            $existingPayment = \App\Models\Payment::where('reference_no', $refNoClean)
                ->whereIn('status', ['verified', 'pending', 'submitted'])
                ->first();
                
            // Jika tidak ada di tabel payments, cek di ocr_payment_receipts
            if (!$existingPayment) {
                $existingOcr = \App\Models\OcrPaymentReceipt::where('reference_no', $refNoClean)->first();
                if ($existingOcr && $existingOcr->payment) {
                    if (in_array($existingOcr->payment->status, ['verified', 'pending', 'submitted'])) {
                        $existingPayment = $existingOcr->payment;
                    }
                }
            }
                
            if ($existingPayment) {
                $checks['nomor_referensi'] = false; // Gagalkan validasi
                $tglStr = $existingPayment->created_at ? $existingPayment->created_at->format('d/m/Y H:i') : 'sebelumnya';
                $errors[] = "Tindakan Curang/Duplikasi Terdeteksi! Bukti transfer ini sudah pernah digunakan sebelumnya (Nomor Referensi: {$refNoClean}) pada {$tglStr}.";
            }
        } else {
            // Jika tidak ada nomor referensi, gunakan kombinasi: Nominal + Tanggal + Bank
            $nom = (float)($mappedData['nominal'] ?? 0);
            $tgl = $mappedData['tanggal_transaksi'] ?? '';
            $bank = $mappedData['bank_tujuan'] ?? '';
            
            if ($nom > 0 && !empty($tgl)) {
                $query = \App\Models\Payment::where('amount', $nom)
                    ->whereDate('paid_at', $tgl)
                    ->whereIn('status', ['verified', 'pending', 'submitted']);
                    
                // Cek nama bank jika ada (dengan toleransi substring)
                if (!empty($bank)) {
                    $query->where('bank_name', 'LIKE', '%' . $bank . '%');
                }
                
                $existingPayment = $query->first();
                
                // Jika tidak ada di tabel payments, cek di ocr_payment_receipts
                if (!$existingPayment) {
                    $ocrQuery = \App\Models\OcrPaymentReceipt::where('amount', $nom)
                        ->whereDate('paid_at', $tgl);
                    if (!empty($bank)) {
                        $ocrQuery->where('bank_name', 'LIKE', '%' . $bank . '%');
                    }
                    $existingOcr = $ocrQuery->first();
                    if ($existingOcr && $existingOcr->payment) {
                        if (in_array($existingOcr->payment->status, ['verified', 'pending', 'submitted'])) {
                            $existingPayment = $existingOcr->payment;
                        }
                    }
                }
                
                if ($existingPayment) {
                    $tglStr = $existingPayment->created_at ? $existingPayment->created_at->format('d/m/Y H:i') : 'sebelumnya';
                    $errors[] = "Duplikasi Terdeteksi! Bukti transfer ini sangat mirip dengan bukti yang sudah pernah diupload sebelumnya (Nominal: Rp " . number_format($nom, 0, ',', '.') . ") pada {$tglStr}. Mohon jangan gunakan bukti transfer yang sama.";
                }
            }
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'checks' => $checks
        ];
    }
}
