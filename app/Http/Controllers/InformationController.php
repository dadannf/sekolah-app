<?php

namespace App\Http\Controllers;

use App\Models\Information;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InformationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Information::with(['createdBy', 'targetStudent'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->where('is_active', 0);
                    break;
                case 'scheduled':
                    $query->where('is_active', 1)
                        ->where('start_at', '>', now());
                    break;
                case 'expired':
                    $query->where('is_active', 1)
                        ->where('end_at', '<', now());
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $informations = $query->paginate(10)->withQueryString();

        return view('information.index', compact('informations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::orderBy('name')->get();
        return view('information.create', compact('students'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            'info_type' => 'required|in:general,spp,academic',
            'is_active' => 'boolean',
            'target_class' => 'nullable|string|max:20',
            'target_student_id' => 'nullable|exists:students,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ], [
            'image.image' => 'File harus berupa gambar',
            'image.mimes' => 'Format gambar harus: JPEG, JPG, PNG, GIF, atau WEBP',
            'image.max' => 'Ukuran gambar maksimal 5MB',
        ]);

        $validated['created_by_user_id'] = Auth::id();
        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        // Handle image upload dengan metode yang lebih sederhana
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            
            Log::info('Image upload started', [
                'original_name' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'is_valid' => $image->isValid(),
                'error' => $image->getError()
            ]);
            
            // Validasi file
            if (!$image->isValid()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'File gambar tidak valid. Error code: ' . $image->getError()]);
            }
            
            try {
                // Buat nama file yang unik
                $extension = $image->getClientOriginalExtension();
                if (empty($extension)) {
                    $extension = 'jpg'; // default extension
                }
                
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                
                // Pastikan direktori ada
                $uploadPath = public_path('storage/information_images');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Pindahkan file langsung ke public/storage
                $destinationPath = $uploadPath . '/' . $fileName;
                
                if (move_uploaded_file($image->getPathname(), $destinationPath)) {
                    $validated['image_path'] = 'information_images/' . $fileName;
                    Log::info('Image uploaded successfully', ['path' => $validated['image_path']]);
                } else {
                    throw new \Exception('Gagal memindahkan file');
                }
                
            } catch (\Exception $e) {
                Log::error('Image upload failed', [
                    'error' => $e->getMessage(),
                    'file_info' => [
                        'name' => $image->getClientOriginalName(),
                        'size' => $image->getSize(),
                        'mime' => $image->getMimeType()
                    ]
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'Gagal mengupload gambar: ' . $e->getMessage()]);
            }
        }

        $information = Information::create($validated);
        $this->createInformationNotifications($information);

        return redirect()->route('information.index')
            ->with('success', 'Informasi berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Information $information)
    {
        $information->load(['createdBy', 'targetStudent']);
        return view('information.show', compact('information'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Information $information)
    {
        $students = Student::orderBy('name')->get();
        return view('information.edit', compact('information', 'students'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Information $information)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            'info_type' => 'required|in:general,spp,academic',
            'is_active' => 'boolean',
            'target_class' => 'nullable|string|max:20',
            'target_student_id' => 'nullable|exists:students,id',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ], [
            'image.image' => 'File harus berupa gambar',
            'image.mimes' => 'Format gambar harus: JPEG, JPG, PNG, GIF, atau WEBP',
            'image.max' => 'Ukuran gambar maksimal 5MB',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        // Handle image upload dengan metode yang lebih sederhana
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            
            Log::info('Image update started', [
                'original_name' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'is_valid' => $image->isValid(),
                'error' => $image->getError()
            ]);
            
            // Validasi file
            if (!$image->isValid()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'File gambar tidak valid. Error code: ' . $image->getError()]);
            }
            
            try {
                // Hapus gambar lama jika ada
                if ($information->image_path) {
                    $oldImagePath = public_path('storage/' . $information->image_path);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                        Log::info('Old image deleted', ['path' => $information->image_path]);
                    }
                }

                // Buat nama file yang unik
                $extension = $image->getClientOriginalExtension();
                if (empty($extension)) {
                    $extension = 'jpg'; // default extension
                }
                
                $fileName = time() . '_' . uniqid() . '.' . $extension;
                
                // Pastikan direktori ada
                $uploadPath = public_path('storage/information_images');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Pindahkan file langsung ke public/storage
                $destinationPath = $uploadPath . '/' . $fileName;
                
                if (move_uploaded_file($image->getPathname(), $destinationPath)) {
                    $validated['image_path'] = 'information_images/' . $fileName;
                    Log::info('Image updated successfully', ['path' => $validated['image_path']]);
                } else {
                    throw new \Exception('Gagal memindahkan file');
                }
                
            } catch (\Exception $e) {
                Log::error('Image update failed', [
                    'error' => $e->getMessage(),
                    'file_info' => [
                        'name' => $image->getClientOriginalName(),
                        'size' => $image->getSize(),
                        'mime' => $image->getMimeType()
                    ]
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'Gagal mengupload gambar: ' . $e->getMessage()]);
            }
        }

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image == '1') {
            try {
                if ($information->image_path) {
                    $oldImagePath = public_path('storage/' . $information->image_path);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $validated['image_path'] = null;
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['image' => 'Gagal menghapus gambar: ' . $e->getMessage()]);
            }
        }

        $information->update($validated);

        return redirect()->route('information.index')
            ->with('success', 'Informasi berhasil diperbarui!');
    }

    /**
     * Get information detail for modal (AJAX)
     */
    public function getDetail(Information $information)
    {
        try {
            $information->load(['createdBy', 'targetStudent']);
            
            $data = [
                'id' => $information->id,
                'title' => $information->title,
                'body' => $information->body,
                'info_type' => $information->info_type,
                'image_url' => $information->image_url,
                'is_currently_active' => $information->isCurrentlyActive(),
                'status_label' => $information->getStatusLabel(),
                'target_description' => $information->getTargetDescription(),
                'created_by_name' => $information->createdBy ? $information->createdBy->name : 'Admin',
                'created_at_formatted' => $information->created_at ? $information->created_at->format('d M Y, H:i') : '-',
                'start_at_formatted' => $information->start_at ? $information->start_at->format('d M Y, H:i') : null,
                'end_at_formatted' => $information->end_at ? $information->end_at->format('d M Y, H:i') : null,
            ];
            
            return response()->json([
                'success' => true,
                'information' => $data
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail informasi'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Information $information)
    {
        // Delete image file if exists
        if ($information->image_path) {
            $imagePath = public_path('storage/' . $information->image_path);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $information->delete();

        return redirect()->route('information.index')
            ->with('success', 'Informasi berhasil dihapus!');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Information $information)
    {
        $information->update([
            'is_active' => !$information->is_active
        ]);

        $status = $information->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Informasi berhasil {$status}!");
    }

    /**
     * Create bell notifications for admins/kepala sekolah and targeted students.
     */
    private function createInformationNotifications(Information $information): void
    {
        $performedById = Auth::id();
        $performedByName = Auth::user()?->name ?? 'System';

        $adminUserIds = User::whereIn('role', ['admin', 'kepala_sekolah'])
            ->pluck('id')
            ->toArray();

        foreach ($adminUserIds as $adminUserId) {
            \App\Models\Notification::createIfMissing(
                [
                    'user_id' => $adminUserId,
                    'type' => 'information',
                    'action' => 'created',
                    'data->information_id' => $information->id,
                ],
                [
                    'user_id' => $adminUserId,
                    'performed_by_id' => $performedById,
                    'performed_by_name' => $performedByName,
                    'type' => 'information',
                    'action' => 'created',
                    'title' => 'Informasi Baru Ditambahkan',
                    'message' => "Informasi '{$information->title}' telah ditambahkan oleh {$performedByName}",
                    'data' => [
                        'information_id' => $information->id,
                        'title' => $information->title,
                        'info_type' => $information->info_type,
                        'target_class' => $information->target_class,
                        'target_student_id' => $information->target_student_id,
                        'is_active' => (bool) $information->is_active,
                    ],
                    'changes' => null,
                ]
            );
        }

        $targetStudentUserIds = $this->resolveTargetStudentUserIds($information);

        foreach ($targetStudentUserIds as $studentUserId) {
            \App\Models\Notification::createIfMissing(
                [
                    'user_id' => $studentUserId,
                    'type' => 'information',
                    'action' => 'created',
                    'data->information_id' => $information->id,
                ],
                [
                    'user_id' => $studentUserId,
                    'performed_by_id' => $performedById,
                    'performed_by_name' => $performedByName,
                    'type' => 'information',
                    'action' => 'created',
                    'title' => 'Informasi Baru Untuk Anda',
                    'message' => "{$information->title} - informasi baru telah dipublikasikan oleh {$performedByName}",
                    'data' => [
                        'information_id' => $information->id,
                        'title' => $information->title,
                        'info_type' => $information->info_type,
                        'target_class' => $information->target_class,
                        'target_student_id' => $information->target_student_id,
                        'is_active' => (bool) $information->is_active,
                    ],
                    'changes' => null,
                ]
            );
        }
    }

    /**
     * Resolve student user recipients based on information target.
     *
     * - target_student_id: only that student
     * - target_class: all students in that class/grade
     * - none: all students
     */
    private function resolveTargetStudentUserIds(Information $information): array
    {
        if (!empty($information->target_student_id)) {
            return Student::where('id', $information->target_student_id)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
        }

        if (!empty($information->target_class)) {
            return Student::where('current_grade_level', $information->target_class)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();
        }

        return Student::whereNotNull('user_id')
            ->pluck('user_id')
            ->toArray();
    }
}