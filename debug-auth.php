<?php
/**
 * Script untuk debug authentication issue
 * Jalankan: php debug-auth.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== CHECKING STUDENT AUTHENTICATION ===\n\n";

// 1. Check if students table exists
try {
    $studentsCount = DB::table('students')->count();
    echo "✅ Students table exists: {$studentsCount} records\n";
} catch (Exception $e) {
    echo "❌ Students table error: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check if users with role 'siswa' exist
try {
    $siswaUsers = DB::table('users')->where('role', 'siswa')->get();
    echo "✅ Users with role 'siswa': " . $siswaUsers->count() . " users\n";
    
    if ($siswaUsers->count() > 0) {
        echo "\nSample siswa users:\n";
        foreach ($siswaUsers->take(3) as $user) {
            echo "  - ID: {$user->id}, Email: {$user->email}, Name: {$user->name}\n";
        }
    } else {
        echo "\n⚠️  WARNING: No users with role 'siswa' found!\n";
        echo "You need to create a student user first.\n\n";
        
        // Check if there are students without users
        $studentsWithoutUsers = DB::table('students')
            ->whereNull('user_id')
            ->orWhere('user_id', 0)
            ->count();
        
        echo "Students without user accounts: {$studentsWithoutUsers}\n";
        
        if ($studentsWithoutUsers > 0) {
            echo "\nDo you want to create user accounts for students? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (trim($line) == 'yes') {
                createStudentUsers();
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Users table error: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Check students-users relationship
try {
    $studentsWithUsers = DB::table('students')
        ->whereNotNull('user_id')
        ->where('user_id', '>', 0)
        ->count();
    
    $totalStudents = DB::table('students')->count();
    
    echo "\n✅ Students with user accounts: {$studentsWithUsers}/{$totalStudents}\n";
    
    if ($studentsWithUsers < $totalStudents) {
        echo "⚠️  " . ($totalStudents - $studentsWithUsers) . " students don't have user accounts\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking relationship: " . $e->getMessage() . "\n";
}

// 4. Check middleware registration
echo "\n=== CHECKING MIDDLEWARE ===\n";
if (file_exists(__DIR__.'/app/Http/Middleware/CheckRole.php')) {
    echo "✅ CheckRole middleware exists\n";
} else {
    echo "❌ CheckRole middleware NOT FOUND\n";
}

// 5. Check routes
echo "\n=== CHECKING ROUTES ===\n";
if (file_exists(__DIR__.'/routes/web.php')) {
    $routes = file_get_contents(__DIR__.'/routes/web.php');
    if (strpos($routes, "role:siswa") !== false) {
        echo "✅ Student routes with role:siswa middleware found\n";
    } else {
        echo "❌ Student routes NOT FOUND or middleware missing\n";
    }
}

echo "\n=== DIAGNOSIS COMPLETE ===\n\n";

// Provide recommendations
echo "RECOMMENDATIONS:\n";
echo "1. Make sure you're logged in with a user that has role='siswa'\n";
echo "2. If you don't have a siswa user, run: php artisan tinker\n";
echo "   Then: User::where('role', 'admin')->first()->update(['role' => 'siswa'])\n";
echo "3. Or create a new student user with the script above\n";
echo "4. Clear cache: php artisan cache:clear\n";
echo "5. Clear session: php artisan session:flush\n\n";

function createStudentUsers() {
    echo "\n=== CREATING STUDENT USER ACCOUNTS ===\n";
    
    $students = DB::table('students')
        ->whereNull('user_id')
        ->orWhere('user_id', 0)
        ->limit(10)
        ->get();
    
    $created = 0;
    foreach ($students as $student) {
        try {
            // Create user
            $userId = DB::table('users')->insertGetId([
                'name' => $student->full_name,
                'email' => $student->email ?: "student{$student->id}@sekolah.test",
                'password' => bcrypt('password'), // Default password
                'role' => 'siswa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Update student
            DB::table('students')
                ->where('id', $student->id)
                ->update(['user_id' => $userId]);
            
            echo "✅ Created user for: {$student->full_name} (email: " . ($student->email ?: "student{$student->id}@sekolah.test") . ")\n";
            $created++;
        } catch (Exception $e) {
            echo "❌ Failed to create user for {$student->full_name}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Created {$created} student user accounts\n";
    echo "Default password for all: password\n\n";
}
