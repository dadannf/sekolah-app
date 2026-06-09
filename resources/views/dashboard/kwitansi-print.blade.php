<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran - {{ $payment->student_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            padding: 20px;
            background: #fff;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 20px;
            background: white;
        }
        
        /* Header */
        .receipt-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 3px solid #000;
            margin-bottom: 20px;
        }
        
        .school-logo {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            flex-shrink: 0;
            background: #f8f9fa;
            
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .school-info {
            flex: 1;
            text-align: center;
        }
        
        .school-info h1 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .school-info p {
            font-size: 11px;
            margin-bottom: 2px;
        }
        
        /* Title */
        .receipt-title {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f0f0f0;
            border: 2px solid #000;
        }
        
        .receipt-title h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        /* Transaction Info */
        .transaction-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-left, .info-right {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 5px;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .info-value {
            border-bottom: 1px dotted #333;
        }
        
        /* Payment Table */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .payment-table th {
            background: #000;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }
        
        .payment-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            font-size: 11px;
        }
        
        .payment-table .text-center {
            text-align: center;
        }
        
        .payment-table .text-right {
            text-align: right;
        }
        
        .payment-table .description {
            font-weight: normal;
        }
        
        /* Total Section */
        .total-section {
            margin-bottom: 15px;
        }
        
        .terbilang {
            font-style: italic;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f0f0f0;
            border: 2px solid #000;
            font-weight: bold;
            font-size: 14px;
        }
        
        /* Footer */
        .receipt-footer {
            margin-top: 20px;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 15px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-location {
            font-size: 11px;
            margin-bottom: 5px;
        }
        
        .signature-label {
            font-size: 11px;
            margin-bottom: 60px;
        }
        
        .signature-name {
            font-size: 12px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }
        
        .notes {
            margin-top: 15px;
            padding: 10px;
            background: #fffef0;
            border-left: 3px solid #ffc107;
            font-size: 10px;
        }
        
        .notes p {
            margin-bottom: 3px;
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                margin: 1cm;
            }
        }
        
        /* Print Button */
        .print-button-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="print-button-container no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Kwitansi
        </button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="school-logo">
                  <img src="{{ asset('logo.png') }}" alt="Logo Sekolah">
            </div>
            <div class="school-info">
                <h1>SMK BIT BINA AULIA</h1>
            </div>
        </div>

        <!-- Title -->
        <div class="receipt-title">
            <h2>BUKTI PEMBAYARAN 
            @switch($invoiceType)
                @case('spp')
                    SPP
                    @break
                @case('uniform')
                    SERAGAM
                    @break
                @case('pts')
                    PTS
                    @break
                @case('pas')
                    PAS
                    @break
                @default
                    SISWA
            @endswitch
            </h2>
        </div>

        <!-- Transaction Info -->
        <div class="transaction-info">
            <div class="info-left">
                <div class="info-label">NO TRANS</div>
                <div class="info-value">: {{ $transactionNo }}</div>
                
                <div class="info-label">TANGGAL</div>
                <div class="info-value">: {{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }}</div>
                
                <div class="info-label">JAM CETAK</div>
                <div class="info-value">: {{ \Carbon\Carbon::now('Asia/Jakarta')->format('H:i:s') }}</div>
            </div>
            
            <div class="info-right">
                <div class="info-label">NIS</div>
                <div class="info-value">: {{ $payment->nis }}</div>
                
                <div class="info-label">NAMA SISWA</div>
                <div class="info-value">: {{ strtoupper($payment->student_name) }}</div>
                
                <div class="info-label">KELAS</div>
                <div class="info-value">: {{ $payment->current_grade_level ? $payment->current_grade_level . ' - ' . ($payment->major ?? '') : '-' }}</div>
            </div>
        </div>

        <!-- Payment Table -->
        <table class="payment-table">
            <thead>
                <tr>
                    <th style="width: 8%;" class="text-center">No.</th>
                    <th style="width: 62%;">Keterangan Pembayaran</th>
                    <th style="width: 30%;" class="text-right">Jumlah (Rp.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1.</td>
                    <td class="description">
                        {{ $paymentDescription }}
                        @if($payment->method === 'cash')
                            <br><small>Metode: TUNAI - {{ $payment->bank_name ?? '-' }}</small>
                        @else
                            <br><small>Metode: TRANSFER - {{ $payment->bank_name ?? '-' }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="terbilang">
                <strong>Terbilang:</strong><br>
                <em>{{ $terbilang }} Rupiah</em>
            </div>
            
            <div class="grand-total">
                <span>Grand Total :</span>
                <span>{{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="signature-section">
                <div class="signature-box">
                    <!-- Left side empty for parent signature -->
                </div>
                <div class="signature-box">
                    <div class="signature-location">
                        Bogor, {{ \Carbon\Carbon::parse($payment->paid_at)->format('d F Y') }}
                    </div>
                    <div class="signature-label">
                        Yang Menerima,
                    </div>
                    <div class="signature-name">
                        {{ $payment->received_by_name ?? '(.................................)' }}
                    </div>
                </div>
            </div>
            
            <div class="notes">
                <p><strong>Catatan:</strong></p>
                <p>- Disimpan sebagai bukti pembayaran yang SAH.</p>
                <p>- Uang yang sudah dibayarkan tidak dapat diminta kembali.</p>
                @if($payment->reference_no)
                <p>- {{ $payment->reference_no }}</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
