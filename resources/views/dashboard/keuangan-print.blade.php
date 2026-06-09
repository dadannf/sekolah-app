<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $invoiceTypes = $payments->pluck('invoice_type')->filter()->unique()->values()->all();
        $isExamReport = false;
        if (isset($reportType) && $reportType === 'ujian') {
            $isExamReport = true;
        } else {
            $isExamReport = collect($invoiceTypes)->intersect(['pts','pas'])->isNotEmpty();
        }
    @endphp
    <title>{{ $isExamReport ? 'Laporan Pembayaran PTS/PAS -' : 'Laporan Keuangan SPP -' }} {{ $scope === 'monthly' ? $monthNames[$month] : ($scope === 'semester' ? 'Semester ' . ucfirst($semester) : 'Tahunan') }} {{ $year }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            padding: 20px;
            background: #fff;
        }
        
        .report-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }
        
        /* Header Section */
        .report-header {
            border-bottom: 3px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        
        .header-top {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
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
            overflow: hidden;
        }
        
        .school-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .header-info {
            flex: 1;
        }
        
        .header-info h2 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .header-info h3 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
        }
        
        .header-details {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 4px 8px;
            font-size: 11px;
        }
        
        .header-details .label {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .header-details .value {
            border-bottom: 1px solid #333;
            padding-bottom: 2px;
        }
        
        /* Title Section */
        .report-title {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 2px solid #000;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10px;
        }
        
        .data-table thead {
            background: #f8f9fa;
        }
        
        .data-table th {
            border: 1px solid #000;
            padding: 8px 5px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 9px;
            vertical-align: middle;
        }
        
        .data-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            vertical-align: middle;
        }
        
        .data-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        
        .fw-bold { font-weight: bold; }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-verified {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Print Button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #0b5ed7;
        }
        
        /* Print Styles */
        @media print {
            body { 
                padding: 0;
                background: white;
            }
            
            .print-button { 
                display: none !important; 
            }
            
            .report-container {
                max-width: 100%;
            }
            
            .data-table {
                page-break-inside: auto;
            }
            
            .data-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .data-table thead {
                display: table-header-group;
            }
            
            @page {
                margin: 1cm;
                size: A4;
            }
        }
        
        /* Footer */
        .report-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            font-size: 10px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-box .title {
            font-weight: bold;
            margin-bottom: 50px;
        }
        
        .signature-box .name {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            display: inline-block;
            min-width: 150px;
        }
        
        /* Summary Box */
        .summary-box {
            margin-top: 15px;
            padding: 10px;
            border: 2px solid #000;
            background: #f8f9fa;
        }
        
        .summary-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            padding: 4px 0;
        }
        
        .summary-row .label {
            font-weight: bold;
        }
        
        .summary-row .value {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @php 
        use Carbon\Carbon; 
        
        // Calculate totals
        $totalAmount = $payments->sum('amount');
        $totalRecords = $payments->count();
        
        // Period text
        $periodText = '';
        if ($scope === 'monthly' && isset($monthNames[$month])) {
            $periodText = $monthNames[$month] . ' ' . $year;
        } elseif ($scope === 'semester') {
            $periodText = 'Semester ' . ($semester === 'genap' ? 'Genap (Januari - Juni)' : 'Ganjil (Juli - Desember)') . ' ' . $year;
        } else {
            $periodText = 'Tahun Ajaran ' . $year . '/' . ($year + 1);
        }
    @endphp

    <button class="print-button" onclick="window.print()">
        <i class="fas fa-print"></i> CETAK LAPORAN
    </button>

    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <div class="header-top">
                <div class="school-logo">
                    <img src="{{ asset('logo.png') }}" alt="Logo Sekolah">
                </div>
                <div class="header-info">
                    <h2>SMK BIT BINA AULIA</h2>
                    <h3>
                        @if($isExamReport)
                            DATA PEMBAYARAN PTS &amp; PAS {{ strtoupper($scope === 'monthly' ? 'BULANAN' : ($scope === 'semester' ? 'SEMESTER ' . strtoupper($semester) : 'TAHUNAN')) }}
                        @else
                            DATA PEMBAYARAN SPP {{ strtoupper($scope === 'monthly' ? 'BULANAN' : ($scope === 'semester' ? 'SEMESTER ' . strtoupper($semester) : 'TAHUNAN')) }}
                        @endif
                    </h3>
                    <div class="header-details">
                        <div class="label">Periode</div>
                        <div class="value">: {{ $periodText }}</div>
                        <div class="label">Tahun Ajaran</div>
                        <div class="value">: {{ $year }}/{{ $year + 1 }}</div>
                        <div class="label">Tanggal Cetak</div>
                        <div class="value">: {{ Carbon::now('Asia/Jakarta')->format('d F Y, H:i') }} WIB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="report-title">
            @if($isExamReport)
                DATA PEMBAYARAN PTS &amp; PAS
            @else
                DATA PEMBAYARAN TAGIHAN UANG SPP
            @endif
        </div>

        <!-- Data Table -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px;">NO</th>
                    <th style="width: 180px;">NAMA SISWA</th>
                    <th style="width: 70px;">KELAS</th>
                    <th style="width: 150px;">JURUSAN</th>
                    <th style="width: 100px;">JML BAYAR</th>
                    <th style="width: 90px;">SPP BULAN</th>
                    <th style="width: 90px;">TGL BAYAR</th>
                    <th style="width: 80px;">METODE</th>
                    <th style="width: 120px;">TEMPAT BAYAR</th>
                    <th style="width: 80px;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $index => $row)
                    @php
                        $paidDate = $row->paid_at ? Carbon::parse($row->paid_at)->format('d-m-Y') : '-';
                        $paidTime = $row->paid_at ? Carbon::parse($row->paid_at)->format('H:i') : '';
                        $methodLabel = $row->method === 'cash' ? 'TUNAI' : 'TRANSFER';
                        $place = $row->bank_name ?: '-';
                        $statusLabel = strtoupper($row->status ?? 'PENDING');
                        $statusClass = $row->status === 'verified' ? 'status-verified' : ($row->status === 'rejected' ? 'status-rejected' : 'status-pending');
                        // Get month name from invoice_month
                        $monthName = $monthNames[$row->invoice_month] ?? '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-start fw-bold">{{ strtoupper($row->name) }}</td>
                        <td class="text-center">{{ $row->current_grade_level ? $row->current_grade_level : '-' }}</td>
                        <td class="text-start">{{ $row->major ?? '-' }}</td>
                        <td class="text-end fw-bold">{{ number_format($row->amount, 0, ',', '.') }}</td>
                        <td class="text-center fw-bold">{{ $monthName }}</td>
                        <td class="text-center">
                            {{ $paidDate }}
                            @if($paidTime)
                                <br><small style="font-size: 8px;">{{ $paidTime }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $methodLabel }}</td>
                        <td class="text-center">{{ $place }}</td>
                        <td class="text-center">
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 30px; color: #666;">
                            <strong>TIDAK ADA DATA PEMBAYARAN PADA PERIODE INI</strong>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($payments->count() > 0)
        <!-- Summary -->
        <div class="summary-box">
            <div class="summary-row">
                <div class="label">TOTAL TRANSAKSI PEMBAYARAN</div>
                <div class="value">{{ $totalRecords }} Transaksi</div>
            </div>
            <div class="summary-row">
                <div class="label">TOTAL NOMINAL PEMBAYARAN</div>
                <div class="value">Rp {{ number_format($totalAmount, 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Footer with Signatures -->
        <div class="report-footer">
            <div class="signature-box">
                <div class="title">Mengetahui,<br>Kepala Sekolah</div>
                <div class="name">(_____________________)</div>
            </div>
            <div class="signature-box">
                <div class="title">{{ Carbon::now('Asia/Jakarta')->format('d F Y') }}<br>Petugas Keuangan</div>
                <div class="name">(_____________________)</div>
            </div>
        </div>
        @endif
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
