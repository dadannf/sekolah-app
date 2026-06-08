<?php
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;

// Daftar jurusan yang tersedia
$majors = [
    'Pemasaran',
    'Teknik Komputer dan Jaringan',
    'Teknik Bisnis Sepeda Motor'
];

echo "<h2>Update Major untuk Siswa</h2>";
echo "<p>Jurusan yang tersedia:</p>";
echo "<ul>";
foreach ($majors as $major) {
    echo "<li>{$major}</li>";
}
echo "</ul>";

// Ambil semua siswa
$students = Student::all();

echo "<h3>Data Siswa Saat Ini:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>NIS</th><th>Nama</th><th>Jurusan Sekarang</th></tr>";

foreach ($students as $student) {
    echo "<tr>";
    echo "<td>{$student->nis}</td>";
    echo "<td>{$student->name}</td>";
    echo "<td>" . ($student->major ?? '<i>Belum diisi</i>') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Update Jurusan Otomatis</h3>";
echo "<p>Mengisi jurusan secara merata untuk semua siswa...</p>";

// Update siswa dengan distribusi merata
$studentIds = $students->pluck('id')->toArray();
$totalStudents = count($studentIds);
$studentsPerMajor = ceil($totalStudents / count($majors));

$index = 0;
foreach ($majors as $major) {
    $batch = array_slice($studentIds, $index, $studentsPerMajor);
    
    Student::whereIn('id', $batch)->update(['major' => $major]);
    
    echo "✓ {$major}: " . count($batch) . " siswa<br>";
    
    $index += $studentsPerMajor;
}

echo "<hr>";
echo "<h3>Hasil Setelah Update:</h3>";
$students = Student::all();

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>NIS</th><th>Nama</th><th>Jurusan</th></tr>";

foreach ($students as $student) {
    echo "<tr>";
    echo "<td>{$student->nis}</td>";
    echo "<td>{$student->name}</td>";
    echo "<td><b>{$student->major}</b></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Distribusi per Jurusan:</h3>";
$distribution = Student::selectRaw('major, COUNT(*) as count')
    ->whereNotNull('major')
    ->groupBy('major')
    ->get();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Jurusan</th><th>Jumlah Siswa</th></tr>";

foreach ($distribution as $item) {
    echo "<tr>";
    echo "<td>{$item->major}</td>";
    echo "<td><b>{$item->count}</b></td>";
    echo "</tr>";
}

echo "</table>";

echo "<p style='margin-top: 20px;'><a href='/dashboard/siswa'>← Kembali ke Dashboard Siswa</a></p>";
