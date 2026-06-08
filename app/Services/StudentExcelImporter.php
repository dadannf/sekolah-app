<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentExcelImporter
{
    /** @var array<int, string>|null */
    private static ?array $studentColumnListing = null;

    /**
     * @param array{
     *   sheet?: int|string,
     *   header_row?: int|null,
     *   dry_run?: bool,
     *   default_grade_level?: int,
     *   default_student_status?: string,
     * } $options
     *
     * @return array{
     *   created:int,
     *   updated:int,
     *   skipped:int,
     *   errors:int,
     *   error_messages: array<int,string>,
     *   sheet_title: string,
     *   header_row: int,
     *   highest_row: int,
     *   highest_col: string,
     * }
     */
    public function import(string $filePath, array $options = []): array
    {
        $sheetOption = $options['sheet'] ?? 0;
        $forcedHeaderRow = $options['header_row'] ?? null;
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $defaultGradeLevel = (int) ($options['default_grade_level'] ?? 10);
        $defaultStudentStatus = (string) ($options['default_student_status'] ?? 'active');

        $spreadsheet = IOFactory::load($filePath);

        $worksheet = null;
        if (is_numeric($sheetOption)) {
            $index = (int) $sheetOption;
            $worksheet = $spreadsheet->getSheetCount() > $index
                ? $spreadsheet->getSheet($index)
                : $spreadsheet->getActiveSheet();
        } else {
            $worksheet = $spreadsheet->getSheetByName((string) $sheetOption) ?? $spreadsheet->getActiveSheet();
        }

        // Find header row (auto if not forced) and choose best sheet when using default sheet selection.
        $headerRow = $forcedHeaderRow ? max(1, (int) $forcedHeaderRow) : $this->detectHeaderRow($worksheet);
        if (!$forcedHeaderRow && (string) $sheetOption === '0' && $headerRow === null) {
            $bestWorksheet = null;
            $bestHeaderRow = null;
            $bestHighestRow = -1;

            foreach ($spreadsheet->getWorksheetIterator() as $ws) {
                $tryRow = $this->detectHeaderRow($ws);
                if ($tryRow === null) {
                    continue;
                }
                $tryHighestRow = (int) $ws->getHighestDataRow();
                if ($tryHighestRow > $bestHighestRow) {
                    $bestHighestRow = $tryHighestRow;
                    $bestWorksheet = $ws;
                    $bestHeaderRow = $tryRow;
                }
            }

            if ($bestWorksheet) {
                $worksheet = $bestWorksheet;
                $headerRow = $bestHeaderRow;
            }
        }

        $headerRow = $headerRow ?? 1;

        $highestRow = (int) $worksheet->getHighestDataRow();
        $highestColumn = (string) $worksheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headersByColumn = [];
        $headerToColumn = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cell = Coordinate::stringFromColumnIndex($col) . $headerRow;
            $raw = $worksheet->getCell($cell)->getValue();
            $headersByColumn[$col] = $this->normalizeHeader($raw);
            $normalized = $headersByColumn[$col];
            if ($normalized !== '' && !isset($headerToColumn[$normalized])) {
                $headerToColumn[$normalized] = $col;
            }
        }

        $col = fn (array $candidates) => $this->findColumnIndex($headersByColumn, $candidates);

        $idxNis = $col(['nis']);
        $idxNama = $col(['nama lengkap', 'nama', 'name']);
        $idxJk = $col(['jk', 'jenis kelamin', 'gender']);
        $idxNisn = $col(['nisn']);
        $idxTempat = $col(['tempat lahir', 'tempat', 'place_of_birth']);
        $idxTgl = $col(['tanggal lahir', 'tgl lahir', 'date_of_birth']);
        $idxNik = $col(['no nik', 'nik', 'nomor ik']);
        $idxMajor = $col(['jurusan', 'major']);
        $idxPreviousSchool = $col(['asal sekolah', 'sekolah asal', 'previous school', 'previous_school']);
        $idxGrade = $col(['kelas', 'tingkat', 'grade', 'current grade level', 'current_grade_level', 'current_grade']);
        $idxStatus = $col(['status', 'student status', 'student_status']);
        $idxAlamat = $col(['alamat']);
        $idxRt = $col(['rt', 'address_rt']);
        $idxRw = $col(['rw', 'address_rw']);
        $idxDusun = $col(['dusun', 'address_dusun']);
        $idxKelurahan = $col(['kelurahan', 'address_kelurahan']);
        $idxKecamatan = $col(['kecamatan', 'address_kecamatan']);
        $idxKodepos = $col(['kode pos', 'kodepos', 'address_postal_code']);
        $idxTelepon = $col(['telepon', 'telpon', 'telephone']);
        $idxHp = $col(['hp', 'handphone', 'mobile_phone']);
        $idxPhoneNumber = $col(['no hp', 'no telepon', 'nomor hp', 'phone number', 'phone_number']);
        $idxEmail = $col(['e-mail', 'email']);
        $idxAyah = $col(['nama ayah', 'ayah']);
        $idxIbu = $col(['nama ibu', 'ibu']);
        $idxReligion = $col(['agama', 'religion']);
        $idxUniformSize = $col(['ukuran seragam', 'ukuran', 'uniform size', 'uniform_size']);
        $idxFatherBirthYear = $col(['tahun lahir ayah', 'tahun lahir ayah', 'father_birth_year']);
        $idxMotherBirthYear = $col(['tahun lahir ibu', 'tahun lahir ibu', 'mother_birth_year']);

        if ($idxNis === null || $idxNama === null) {
            throw new \RuntimeException("Header wajib tidak ditemukan. Minimal harus ada kolom 'NIS' dan 'Nama Lengkap'.");
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $errorMessages = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $nis = $this->normalizeNis($this->getStringCell($worksheet, $idxNis, $row), $row);
            $name = $this->normalizeStudentName($this->getStringCell($worksheet, $idxNama, $row), $row);

            $nisn = $idxNisn ? $this->getStringCell($worksheet, $idxNisn, $row) : '';
            $nisn = $this->normalizeNisn($nisn, $row);
            $genderRaw = $idxJk ? $this->getStringCell($worksheet, $idxJk, $row) : '';
            $gender = $this->mapGender($genderRaw);

            $placeOfBirth = $idxTempat ? $this->getStringCell($worksheet, $idxTempat, $row) : null;

            $dateOfBirth = null;
            if ($idxTgl) {
                $cell = Coordinate::stringFromColumnIndex($idxTgl) . $row;
                $dateOfBirth = $this->parseDateCell($worksheet->getCell($cell)->getValue());
            }

            $nationalId = $idxNik ? $this->getStringCell($worksheet, $idxNik, $row) : null;

            $major = $idxMajor ? $this->getStringCell($worksheet, $idxMajor, $row) : null;
            $gradeRaw = $idxGrade ? $this->getStringCell($worksheet, $idxGrade, $row) : null;
            $gradeLevel = $this->parseGradeLevel($gradeRaw) ?? $defaultGradeLevel;
            $statusRaw = $idxStatus ? $this->getStringCell($worksheet, $idxStatus, $row) : null;
            $studentStatus = $this->mapStudentStatus($statusRaw) ?? $defaultStudentStatus;

            $alamat = $idxAlamat ? $this->getStringCell($worksheet, $idxAlamat, $row) : '';
            $rt = $idxRt ? $this->getStringCell($worksheet, $idxRt, $row) : '';
            $rw = $idxRw ? $this->getStringCell($worksheet, $idxRw, $row) : '';
            $dusun = $idxDusun ? $this->getStringCell($worksheet, $idxDusun, $row) : '';
            $kelurahan = $idxKelurahan ? $this->getStringCell($worksheet, $idxKelurahan, $row) : '';
            $kecamatan = $idxKecamatan ? $this->getStringCell($worksheet, $idxKecamatan, $row) : '';
            $kodepos = $idxKodepos ? $this->getStringCell($worksheet, $idxKodepos, $row) : '';
            $address = $this->buildAddress($alamat, $rt, $rw, $dusun, $kelurahan, $kecamatan, $kodepos);

            $hp = $idxHp ? $this->getStringCell($worksheet, $idxHp, $row) : '';
            $telepon = $idxTelepon ? $this->getStringCell($worksheet, $idxTelepon, $row) : '';
            $rawPhone = $idxPhoneNumber ? $this->getStringCell($worksheet, $idxPhoneNumber, $row) : '';
            if ($rawPhone === '') {
                $rawPhone = $hp !== '' ? $hp : ($telepon !== '' ? $telepon : '');
            }

            $phoneNumber = $this->normalizePhoneNumber($rawPhone);

            $email = $idxEmail ? $this->getStringCell($worksheet, $idxEmail, $row) : null;
            $fatherName = $idxAyah ? $this->getStringCell($worksheet, $idxAyah, $row) : null;
            $motherName = $idxIbu ? $this->getStringCell($worksheet, $idxIbu, $row) : null;
            $religion = $idxReligion ? $this->getStringCell($worksheet, $idxReligion, $row) : null;

            // Parse birth years safely (allow non-numeric inputs)
            $fatherBirthYearRaw = $idxFatherBirthYear ? $this->getStringCell($worksheet, $idxFatherBirthYear, $row) : '';
            $motherBirthYearRaw = $idxMotherBirthYear ? $this->getStringCell($worksheet, $idxMotherBirthYear, $row) : '';
            $fatherBirthYear = $this->parseYear($fatherBirthYearRaw);
            $motherBirthYear = $this->parseYear($motherBirthYearRaw);

            // New fields (normalize/truncate to DB limits)
            $nikRaw = $idxNik ? $this->getStringCell($worksheet, $idxNik, $row) : null;
            $nik = $nikRaw !== null ? mb_substr(preg_replace('/[^0-9A-Za-z]/', '', $nikRaw), 0, 20) : null;
            $uniformSize = $idxUniformSize ? mb_substr(trim((string) $this->getStringCell($worksheet, $idxUniformSize, $row)), 0, 10) : null;
            $previousSchool = $idxPreviousSchool ? mb_substr(trim((string) $this->getStringCell($worksheet, $idxPreviousSchool, $row)), 0, 255) : null;

            if ($email !== null && $email !== '') {
                $emailUsed = Student::query()
                    ->where('email', $email)
                    ->where('nis', '!=', $nis)
                    ->exists();

                if ($emailUsed) {
                    $email = null;
                }
            } else {
                $email = null;
            }

            if ($dryRun) {
                $created++;
                continue;
            }

            try {
                $result = DB::transaction(function () use (
                    $nis,
                    $nisn,
                    $nik,
                    $name,
                    $gender,
                    $placeOfBirth,
                    $dateOfBirth,
                    $nationalId,
                    $major,
                    $previousSchool,
                    $gradeLevel,
                    $studentStatus,
                    $address,
                    $rt,
                    $rw,
                    $kelurahan,
                    $kecamatan,
                    $kodepos,
                    $email,
                    $phoneNumber,
                    $fatherName,
                    $motherName,
                    $religion,
                    $uniformSize,
                    $fatherBirthYear,
                    $motherBirthYear,
                    $defaultGradeLevel,
                    $defaultStudentStatus,
                    $worksheet,
                    $headerToColumn,
                    $row
                ) {
                    $student = Student::query()->where('nis', $nis)->first();

                    if (!$student && $nisn !== '') {
                        $student = Student::query()->where('nisn', $nisn)->first();
                    }

                    $user = null;
                    if ($student && $student->user_id) {
                        $user = User::query()->whereKey($student->user_id)->first();
                    }

                    $user = $user ?: User::query()->where('email', $nis)->first();

                    if (!$user) {
                        $defaultPassword = 'bba#' . substr($nis, -4);
                        $user = new User();
                        $user->name = $name;
                        $user->email = $nis;
                        $user->password = md5($defaultPassword);
                        $user->role = 'siswa';
                        $user->save();
                    } else {
                        $user->name = $name;
                        if ($user->email !== $nis) {
                            $user->email = $nis;
                        }
                        if (empty($user->role)) {
                            $user->role = 'siswa';
                        }
                        $user->save();
                    }

                    $isCreate = false;
                    if (!$student) {
                        $student = new Student();
                        $isCreate = true;
                    }

                    $data = [
                        'nis' => $nis,
                        'nisn' => $nisn !== '' ? $nisn : null,
                        'nik' => $nik ?: null,
                        'name' => $name,
                        'place_of_birth' => $placeOfBirth ?: null,
                        'date_of_birth' => $dateOfBirth,
                        'gender' => $gender,
                        'religion' => $religion ?: null,
                        'email' => $email,
                        'phone_number' => $phoneNumber ?: null,
                        'address' => $address ?: null,
                        'address_rt' => $rt ?: null,
                        'address_rw' => $rw ?: null,
                        'address_kelurahan' => $kelurahan ?: null,
                        'address_kecamatan' => $kecamatan ?: null,
                        'address_postal_code' => $kodepos ?: null,
                        'father_name' => $fatherName ?: null,
                        'father_birth_year' => $fatherBirthYear ?: null,
                        'mother_name' => $motherName ?: null,
                        'mother_birth_year' => $motherBirthYear ?: null,
                        'current_grade_level' => $gradeLevel,
                        'major' => ($major !== null && trim((string) $major) !== '') ? $major : ($student->major ?? null),
                        'previous_school' => $previousSchool ?: null,
                        'uniform_size' => $uniformSize ?: null,
                        'student_status' => $studentStatus,
                    ];

                    // If template uses DB column headers, import additional fields generically.
                    $data = $this->applyAdditionalColumns($worksheet, $headerToColumn, $row, $data);

                    foreach ($data as $key => $val) {
                        $student->{$key} = $val;
                    }

                    $student->user_id = $user->id;
                    $student->save();

                    return $isCreate ? 'created' : 'updated';
                });

                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors++;
                $errorMessages[] = "Row {$row} (NIS {$nis}): {$e->getMessage()}";
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_messages' => $errorMessages,
            'sheet_title' => $worksheet->getTitle(),
            'header_row' => $headerRow,
            'highest_row' => $highestRow,
            'highest_col' => $highestColumn,
        ];
    }

    private function normalizeHeader(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return mb_strtolower($text);
    }

    /**
     * @param  array<int, string>  $headersByColumn
     * @param  array<int, string>  $candidates
     */
    private function findColumnIndex(array $headersByColumn, array $candidates): ?int
    {
        $candidates = array_map(fn ($h) => mb_strtolower(trim($h)), $candidates);
        foreach ($headersByColumn as $col => $header) {
            if ($header === '') {
                continue;
            }
            if (in_array($header, $candidates, true)) {
                return (int) $col;
            }
        }
        return null;
    }

    private function getStringCell($worksheet, int $col, int $row): string
    {
        $cell = Coordinate::stringFromColumnIndex($col) . $row;
        $cellObj = $worksheet->getCell($cell);
        return trim((string) $cellObj->getFormattedValue());
    }

    private function mapGender(?string $jk): ?string
    {
        $jk = mb_strtolower(trim((string) $jk));
        if ($jk === 'm' || $jk === 'male') {
            return 'M';
        }
        if ($jk === 'f' || $jk === 'female') {
            return 'F';
        }
        if ($jk === 'l' || $jk === 'laki-laki' || $jk === 'laki laki') {
            return 'M';
        }
        if ($jk === 'p' || $jk === 'perempuan') {
            return 'F';
        }
        return null;
    }

    /**
     * Import extra columns when headers are DB field names.
     * Only sets values for columns that exist in the students table.
     *
     * @param array<string,int> $headerToColumn
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function applyAdditionalColumns($worksheet, array $headerToColumn, int $row, array $data): array
    {
        $columns = $this->getStudentColumns();

        $rawString = function (string $header) use ($worksheet, $headerToColumn, $row): ?string {
            $h = $this->normalizeHeader($header);
            if (!isset($headerToColumn[$h])) {
                return null;
            }
            return $this->getStringCell($worksheet, (int) $headerToColumn[$h], $row);
        };

        /** @param array<int,string> $headers */
        $rawStringAny = function (array $headers) use ($rawString): ?string {
            foreach ($headers as $header) {
                $val = $rawString($header);
                if ($val !== null && trim($val) !== '') {
                    return $val;
                }
            }
            return null;
        };

        /** @param array<int,string> $aliases */
        $setIfPresent = function (string $field, array $aliases = []) use (&$data, $columns, $rawStringAny): void {
            if (!in_array($field, $columns, true)) {
                return;
            }
            if (array_key_exists($field, $data) && $data[$field] !== null && trim((string) $data[$field]) !== '') {
                return;
            }
            $val = $rawStringAny(array_values(array_unique(array_merge([$field], $aliases))));
            if ($val === null || trim($val) === '') {
                return;
            }
            $data[$field] = $val;
        };

        // Simple passthrough fields (string/numeric as-is)
        $passthrough = [
            'family_card_number',
            'child_order',
            'religion',
            'current_grade_level',
            'father_nik',
            'father_education',
            'father_job',
            'father_income',
            'mother_nik',
            'mother_education',
            'mother_job',
            'mother_income',
            'weight_kg',
            'height_cm',
            'waist_cm',
            'distance_to_school',
            'travel_time',
            'siblings_count',
            'previous_school',
            'hobby',
            'aspiration',
            'birth_certificate_registration_no',
            'shirt_size',
            'diploma_serial_no',
            'previous_school_npsn',
            'notes',
            'address',
            'address_rt',
            'address_rw',
            'address_dusun',
            'address_kelurahan',
            'address_kecamatan',
            'address_postal_code',
            'residence_type',
            'transportation',
            'participant_number',
            'kip_number',
            'telephone',
            'mobile_phone',
            'phone_number',
            'email',
            'major',
            'national_id_number',
            'student_status',
            'photo_path',
        ];

        foreach ($passthrough as $field) {
            $setIfPresent($field);
        }

        // Aliases for Indonesian templates
        $setIfPresent('family_card_number', ['no kk', 'kk', 'nomor kk', 'no. kk']);
        $setIfPresent('child_order', ['anak ke', 'anak ke-']);
        $setIfPresent('religion', ['agama']);

        $setIfPresent('father_name', ['nama ayah']);
        $setIfPresent('father_birth_year', ['tahun lahir ayah']);
        $setIfPresent('father_nik', ['nik ayah']);
        $setIfPresent('father_education', ['pendidikan ayah']);
        $setIfPresent('father_job', ['pekerjaan ayah']);
        $setIfPresent('father_income', ['penghasilan ayah']);

        $setIfPresent('mother_name', ['nama ibu']);
        $setIfPresent('mother_birth_year', ['tahun lahir ibu']);
        $setIfPresent('mother_nik', ['nik ibu']);
        $setIfPresent('mother_education', ['pendidikan ibu']);
        $setIfPresent('mother_job', ['pekerjaan ibu']);
        $setIfPresent('mother_income', ['penghasilan ibu']);

        $setIfPresent('weight_kg', ['berat badan']);
        $setIfPresent('height_cm', ['tinggi badan']);
        $setIfPresent('waist_cm', ['lingkar perut']);
        $setIfPresent('distance_to_school', ['jarak']);
        $setIfPresent('travel_time', ['waktu']);
        $setIfPresent('siblings_count', ['jumlah saudara kandung', 'jumlah saudara']);
        $setIfPresent('previous_school', ['sekolah asal']);
        $setIfPresent('hobby', ['hobi']);
        $setIfPresent('aspiration', ['cita-cita', 'cita cita']);
        $setIfPresent('birth_certificate_registration_no', ['no registrasi akta kelahiran', 'no registrasi akta', 'no. registrasi akta kelahiran']);
        $setIfPresent('shirt_size', ['ukuran baju']);
        $setIfPresent('diploma_serial_no', ['no seri ijazah', 'no. seri ijazah']);
        $setIfPresent('previous_school_npsn', ['npsn sekolah asal', 'npsn']);
        $setIfPresent('notes', ['keterangan']);

        $setIfPresent('address', ['alamat']);
        $setIfPresent('address_rt', ['rt']);
        $setIfPresent('address_rw', ['rw']);
        $setIfPresent('address_dusun', ['dusun']);
        $setIfPresent('address_kelurahan', ['kelurahan']);
        $setIfPresent('address_kecamatan', ['kecamatan']);
        $setIfPresent('address_postal_code', ['kode pos', 'kodepos']);
        $setIfPresent('residence_type', ['jenis tinggal']);
        $setIfPresent('transportation', ['alat transportasi']);

        $setIfPresent('email', ['e-mail', 'email']);
        $setIfPresent('participant_number', ['no peserta', 'nomor peserta', 'no. peserta']);
        $setIfPresent('kip_number', ['nomor kip', 'no kip', 'no. kip']);
        $setIfPresent('telephone', ['telepon', 'telpon']);
        $setIfPresent('mobile_phone', ['hp', 'handphone']);
        $setIfPresent('phone_number', ['hp', 'handphone', 'telepon', 'telpon']);
        $setIfPresent('national_id_number', ['no nik', 'nik', 'no. nik']);

        // Keep birth-year columns strictly numeric/null, never raw strings
        $yearFields = [
            'father_birth_year' => ['tahun lahir ayah'],
            'mother_birth_year' => ['tahun lahir ibu'],
        ];
        foreach ($yearFields as $field => $aliases) {
            if (!in_array($field, $columns, true)) {
                continue;
            }
            $raw = $rawStringAny(array_values(array_unique(array_merge([$field], $aliases))));
            $year = $this->parseYear($raw);
            $data[$field] = $year;
        }

        // Boolean-ish field
        if (in_array('is_kip_recipient', $columns, true)) {
            $raw = $rawStringAny(['is_kip_recipient', 'penerima kip', 'kip', 'penerima']);
            if ($raw !== null && trim($raw) !== '') {
                $data['is_kip_recipient'] = $this->mapBoolean01($raw);
            }
        }

        // Normalize phone_number fallback
        if (in_array('phone_number', $columns, true)) {
            $pn = $data['phone_number'] ?? null;
            if ($pn === null || trim((string) $pn) === '') {
                $mp = $data['mobile_phone'] ?? null;
                $tlp = $data['telephone'] ?? null;
                if ($mp !== null && trim((string) $mp) !== '') {
                    $data['phone_number'] = $mp;
                } elseif ($tlp !== null && trim((string) $tlp) !== '') {
                    $data['phone_number'] = $tlp;
                }
            }
        }

        // Ensure stored phone number never exceeds DB column length or contains spaces/punctuation.
        if (in_array('phone_number', $columns, true) && isset($data['phone_number'])) {
            $pn = trim((string) $data['phone_number']);
            $pn = preg_replace('/[^0-9+]/', '', $pn) ?? $pn;
            $data['phone_number'] = $pn === '' ? null : mb_substr($pn, 0, 20);
        }

        return $data;
    }

    /** @return array<int,string> */
    private function getStudentColumns(): array
    {
        if (self::$studentColumnListing !== null) {
            return self::$studentColumnListing;
        }

        try {
            self::$studentColumnListing = Schema::getColumnListing('students');
        } catch (\Throwable $e) {
            self::$studentColumnListing = [];
        }

        return self::$studentColumnListing;
    }

    private function mapBoolean01(string $value): int
    {
        $t = mb_strtolower(trim($value));
        if ($t === '1' || $t === 'ya' || $t === 'y' || $t === 'true' || $t === 'yes') {
            return 1;
        }
        if ($t === '0' || $t === 'tidak' || $t === 't' || $t === 'false' || $t === 'no') {
            return 0;
        }
        return is_numeric($t) && (int) $t === 1 ? 1 : 0;
    }

    private function parseGradeLevel(?string $value): ?int
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        // Allow formats like "10", "Kelas 10", etc.
        if (preg_match('/(10|11|12)/', $text, $m)) {
            $grade = (int) $m[1];
            return in_array($grade, [10, 11, 12], true) ? $grade : null;
        }

        return null;
    }

    private function mapStudentStatus(?string $value): ?string
    {
        $text = mb_strtolower(trim((string) ($value ?? '')));
        if ($text === '') {
            return null;
        }

        // Accept DB values directly
        if (in_array($text, ['active', 'inactive', 'graduated'], true)) {
            return $text;
        }

        // Indonesian variants
        if (in_array($text, ['aktif', 'active'], true)) {
            return 'active';
        }
        if (in_array($text, ['tidak aktif', 'nonaktif', 'non-aktif', 'inactive'], true)) {
            return 'inactive';
        }
        if (in_array($text, ['lulus', 'alumni', 'graduated'], true)) {
            return 'graduated';
        }

        return null;
    }

    private function parseDateCell(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $text);
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildAddress(
        string $alamat,
        string $rt,
        string $rw,
        string $dusun,
        string $kelurahan,
        string $kecamatan,
        string $kodepos
    ): ?string {
        $parts = [];

        $alamat = trim($alamat);
        if ($alamat !== '') {
            $parts[] = $alamat;
        }

        $rt = trim($rt);
        $rw = trim($rw);
        if ($rt !== '' || $rw !== '') {
            $parts[] = 'RT ' . ($rt !== '' ? $rt : '-') . '/RW ' . ($rw !== '' ? $rw : '-');
        }

        $dusun = trim($dusun);
        if ($dusun !== '') {
            $parts[] = 'Dusun ' . $dusun;
        }

        $kelurahan = trim($kelurahan);
        if ($kelurahan !== '') {
            $parts[] = 'Kel. ' . $kelurahan;
        }

        $kecamatan = trim($kecamatan);
        if ($kecamatan !== '') {
            $parts[] = 'Kec. ' . $kecamatan;
        }

        $kodepos = trim($kodepos);
        if ($kodepos !== '') {
            $parts[] = 'Kode Pos ' . $kodepos;
        }

        return empty($parts) ? null : implode(', ', $parts);
    }

    private function detectHeaderRow($worksheet): ?int
    {
        $highestRow = (int) $worksheet->getHighestDataRow();
        $highestColumn = (string) $worksheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $maxScan = min($highestRow, 30);
        for ($row = 1; $row <= $maxScan; $row++) {
            $hasNis = false;
            $hasNama = false;
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = Coordinate::stringFromColumnIndex($col) . $row;
                $raw = $worksheet->getCell($cell)->getValue();
                $header = $this->normalizeHeader($raw);
                if ($header === 'nis') {
                    $hasNis = true;
                }
                if ($header === 'nama lengkap' || $header === 'nama') {
                    $hasNama = true;
                }
                if ($hasNis && $hasNama) {
                    return $row;
                }
            }
        }

        return null;
    }

    /**
     * Extract a 4-digit year from a string if present and reasonable.
     */
    private function parseYear(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        // Find first 4-digit year between 1900 and current year
        if (preg_match('/(19\d{2}|20\d{2})/', $text, $m)) {
            $y = (int) $m[1];
            $current = (int) date('Y');
            if ($y >= 1900 && $y <= $current) {
                return $y;
            }
        }

        // Fallback: if purely numeric and length 4, accept
        if (preg_match('/^\d{4}$/', $text)) {
            $y = (int) $text;
            $current = (int) date('Y');
            if ($y >= 1900 && $y <= $current) {
                return $y;
            }
        }

        return null;
    }

    private function normalizeNis(string $value, int $row): string
    {
        $value = trim($value);
        if ($value !== '') {
            return mb_substr($value, 0, 32);
        }

        return '999999999' . $row;
    }

    private function normalizeNisn(string $value, int $row): string
    {
        $value = trim($value);
        if ($value !== '') {
            return mb_substr($value, 0, 32);
        }

        return '99999999' . $row;
    }

    private function normalizeStudentName(string $value, int $row): string
    {
        $value = trim($value);
        if ($value !== '') {
            return mb_substr($value, 0, 100);
        }

        return 'Siswa ' . $row;
    }

    private function normalizePhoneNumber(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        $parts = preg_split('/[\/\,;|]+/', $text);
        $first = trim($parts[0] ?? $text);

        $digitsOnly = preg_replace('/\D+/', '', $first) ?? '';
        if ($digitsOnly === '') {
            return null;
        }

        // Keep an explicit +62 format when the number starts with 62.
        if (str_starts_with($digitsOnly, '62')) {
            return '+62' . mb_substr($digitsOnly, 2, 18);
        }

        // Convert local mobile numbers starting with 8 into 0-prefixed numbers.
        if (str_starts_with($digitsOnly, '8')) {
            return '0' . mb_substr($digitsOnly, 0, 19);
        }

        // Preserve numbers that already start with 0.
        if (str_starts_with($digitsOnly, '0')) {
            return mb_substr($digitsOnly, 0, 20);
        }

        // Fallback: prefix 0 so the stored value stays in local format.
        return '0' . mb_substr($digitsOnly, 0, 19);
    }
}
