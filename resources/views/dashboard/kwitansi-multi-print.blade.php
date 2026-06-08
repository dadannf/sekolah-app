<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Pembayaran - {{ $payments->first()->student_name ?? 'Siswa' }}</title>
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
            background: #f8f9fa;
            flex-shrink: 0;
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

        .info-label { font-weight: bold; }
        .info-value { border-bottom: 1px dotted #333; }

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
            vertical-align: top;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .description small {
            display: block;
            margin-top: 2px;
            color: #333;
        }

        .total-section { margin-bottom: 15px; }

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

        .receipt-footer {
            margin-top: 20px;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 15px;
        }

        .signature-box { text-align: center; }
        .signature-location { font-size: 11px; margin-bottom: 5px; }
        .signature-label { font-size: 11px; margin-bottom: 60px; }
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

        .notes p { margin-bottom: 3px; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }

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
    </style>
</head>
<body>
    <div class="print-button-container no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak Kwitansi</button>
    </div>

    @php
        $firstPayment = $payments->first();
        $studentName = strtoupper($firstPayment->student_name ?? '-');
        $studentNis = $firstPayment->nis ?? '-';
        $studentClass = $firstPayment->current_grade_level ? $firstPayment->current_grade_level . ' - ' . ($firstPayment->major ?? '') : '-';
        $printDate = \Carbon\Carbon::now('Asia/Jakarta')->format('d/m/Y');
        $printTime = \Carbon\Carbon::now('Asia/Jakarta')->format('H:i:s');
        $totalAmount = $payments->sum('amount');
        $invoiceTypeLabel = 'SERAGAM';
    @endphp

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="school-logo">
                <img src="{{ asset('../css/logo.png') }}" alt="Logo Sekolah">
            </div>
            <div class="school-info">
                <h1>SMK BIT BINA AULIA</h1>
            </div>
        </div>

        <div class="receipt-title">
            <h2>BUKTI PEMBAYARAN {{ $invoiceTypeLabel }}</h2>
        </div>

        <div class="transaction-info">
            <div class="info-left">
                <div class="info-label">NO TRANS</div>
                <div class="info-value">: {{ $firstPayment->id ?? '-' }}</div>

                <div class="info-label">TANGGAL</div>
                <div class="info-value">: {{ $printDate }}</div>

                <div class="info-label">JAM CETAK</div>
                <div class="info-value">: {{ $printTime }}</div>
            </div>

            <div class="info-right">
                <div class="info-label">NIS</div>
                <div class="info-value">: {{ $studentNis }}</div>

                <div class="info-label">NAMA SISWA</div>
                <div class="info-value">: {{ $studentName }}</div>

                <div class="info-label">KELAS</div>
                <div class="info-value">: {{ $studentClass }}</div>
            </div>
        </div>

        <table class="payment-table">
            <thead>
                <tr>
                    <th style="width: 8%;" class="text-center">No.</th>
                    <th style="width: 62%;">Keterangan Pembayaran</th>
                    <th style="width: 30%;" class="text-right">Jumlah (Rp.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $index => $payment)
                    @php
                        $typeName = $payment->invoice_subtype ? ucfirst(str_replace(['_', '-'], ' ', $payment->invoice_subtype)) : 'Seragam';
                        $paymentDescription = 'Pembayaran Seragam ' . $typeName;
                        $paidDate = $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') : '-';
                        $methodLabel = strtoupper($payment->method ?? '');
                        $place = $payment->bank_name ?: ($payment->place_paid ?? '-');
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}.</td>
                        <td class="description">
                            {{ $paymentDescription }}
                            <small>Metode: {{ $methodLabel ?: '-' }} - {{ $place }}</small>
                            <small>Tanggal Bayar: {{ $paidDate }}</small>
                        </td>
                        <td class="text-right">{{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="terbilang">
                <strong>Terbilang:</strong><br>
                <em>{{ $terbilang }} Rupiah</em>
            </div>

            <div class="grand-total">
                <span>Grand Total :</span>
                <span>{{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="receipt-footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-label">Mengetahui,<br>Kepala Sekolah</div>
                    <div class="signature-name">(_____________________)</div>
                </div>
                <div class="signature-box">
                    <div class="signature-location">Bogor, {{ \Carbon\Carbon::now('Asia/Jakarta')->format('d F Y') }}</div>
                    <div class="signature-label">Yang Menerima,</div>
                    <div class="signature-name">{{ $firstPayment->received_by_name ?? '(.................................)' }}</div>
                </div>
            </div>

            <div class="notes">
                <p><strong>Catatan:</strong></p>
                <p>- Disimpan sebagai bukti pembayaran yang SAH.</p>
                <p>- Uang yang sudah dibayarkan tidak dapat diminta kembali.</p>
            </div>
        </div>
    </div>
</body>
</html>