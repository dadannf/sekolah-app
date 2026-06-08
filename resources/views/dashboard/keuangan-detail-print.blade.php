<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Siswa - {{ $payment->student->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 15px;
            background: #fff;
        }
        
        .report-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
        }
        
        /* Header Section */
        .report-header {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header-top {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 5px;
        }
        
        .school-logo {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .header-info {
            flex: 1;
        }
        
        .header-info h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .header-info h2 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .header-info p {
            font-size: 9px;
            margin-bottom: 2px;
        }
        
        /* Student Info Section */
        .student-info {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 10px 15px;
        }
        
        .student-row {
            display: grid;
            grid-template-columns: 150px 20px 1fr;
            gap: 5px;
            margin-bottom: 5px;
        }
        
        .student-label {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .student-colon {
            font-weight: bold;
        }
        
        .student-value {
            font-weight: normal;
        }
        
        /* Payment Table */
        .payment-section {
            margin-bottom: 15px;
        }
        
        .payment-section h3 {
            background: #000;
            color: #fff;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        
        thead {
            background: #f0f0f0;
        }
        
        th {
            padding: 6px 5px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #000;
            vertical-align: middle;
        }
        
        td {
            padding: 5px;
            font-size: 9px;
            border: 1px solid #000;
            vertical-align: middle;
        }
        
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        
        /* Print Styles - LANDSCAPE MODE */
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            .report-container {
                max-width: 100%;
            }
            
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
            
            /* Ensure one page only */
            .report-container {
                page-break-after: avoid;
                page-break-inside: avoid;
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
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="print-button-container no-print">
        <button class="btn-print" onclick="window.print()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <path d="M6 14h12v8H6z"/>
            </svg>
            Cetak Laporan
        </button>
    </div>

    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <div class="header-top">
                <div class="school-logo">
                    <img src="{{ asset('../css/logo.png') }}" alt="Logo Sekolah">
                </div>
                <div class="header-info">
                    <h1>SMK BIT BINA AULIA</h1>
                    <h2>DATA PEMBAYARAN SEMESTER GANJIL {{ $payment->invoice_year }}/{{ $payment->invoice_year + 1 }}</h2>
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <div class="student-row">
                <div class="student-label">NAMA SISWA</div>
                <div class="student-colon">:</div>
                <div class="student-value">{{ strtoupper($payment->student->name) }}</div>
            </div>
            <div class="student-row">
                <div class="student-label">NIS</div>
                <div class="student-colon">:</div>
                <div class="student-value">{{ $payment->student->nis }}</div>
            </div>
            <div class="student-row">
                <div class="student-label">KELAS</div>
                <div class="student-colon">:</div>
                <div class="student-value">
                    @if($payment->student->current_grade_level)
                        KELAS {{ $payment->student->current_grade_level }}
                    @else
                        -
                    @endif
                </div>
            </div>
            <div class="student-row">
                <div class="student-label">JURUSAN</div>
                <div class="student-colon">:</div>
                <div class="student-value">{{ strtoupper($payment->student->major ?? '-') }}</div>
            </div>
        </div>

        <!-- Payment Table -->
        <div class="payment-section">
            <h3>Data Pembayaran Tagihan Uang Kuliah</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 3%;">NO</th>
                        <th style="width: 15%;">NOMOR TAGIHAN</th>
                        <th style="width: 5%;">NO URUT</th>
                        <th style="width: 15%;">PEMBAYARAN</th>
                        <th style="width: 12%;">JML BAYAR</th>
                        <th style="width: 10%;">STATUS BAYAR</th>
                        <th style="width: 12%;">TGL BAYAR</th>
                        <th style="width: 12%;">CHANNEL</th>
                        <th style="width: 16%;">TEMPAT BAYAR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyPayments as $monthly)
                        <tr>
                            <td class="text-center">{{ $monthly['no'] }}</td>
                            <td class="text-center">
                                {{ $payment->student->id }}{{ $payment->invoice_year }}{{ str_pad($monthly['no'], 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="text-center">{{ $monthly['no'] }}</td>
                            <td>{{ strtoupper($monthly['month']) }}</td>
                            <td class="text-right">{{ number_format($monthly['amount'], 0, ',', '.') }}</td>
                            <td class="text-center">LUNAS</td>
                            <td class="text-center">
                                @if($monthly['paid_at'])
                                    {{ date('Y-m-d H:i:s', strtotime($monthly['paid_at'])) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                @if($monthly['method'])
                                    {{ strtoupper($monthly['method']) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($monthly['location'])
                                    {{ $monthly['location'] }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 18px;">
                                <strong>TIDAK ADA DATA PEMBAYARAN TERVERIFIKASI</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
