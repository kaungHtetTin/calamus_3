<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Teachers', [
            'teachers' => Teacher::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTeacher($request);
        Teacher::create($data);

        return redirect()->back()->with('success', 'Teacher created successfully.');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validateTeacher($request, $teacher);
        $teacher->update($data);

        return redirect()->back()->with('success', 'Teacher updated successfully.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return redirect()->back()->with('success', 'Teacher deleted successfully.');
    }

    private function validateTeacher(Request $request, ?Teacher $teacher = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:255'],
            'profile_image' => ['nullable', 'string'],
            'rank' => ['required', 'integer', 'min:0'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'qualification' => ['required', 'string'],
            'experience' => ['required', 'integer', 'min:0'],
            'total_course' => ['required', 'integer', 'min:0'],
        ]);

        $croppedProfile = $this->storeBase64Image((string) ($data['profile_image'] ?? ''));
        $existingProfile = $teacher ? (string) $teacher->profile : '';
        $inputProfile = isset($data['profile']) ? trim((string) $data['profile']) : '';
        $resolvedProfile = $croppedProfile ?: ($inputProfile !== '' ? $inputProfile : $existingProfile);

        if ($resolvedProfile === '') {
            throw ValidationException::withMessages([
                'profile' => 'Profile image is required.',
            ]);
        }

        return [
            'name' => trim($data['name']),
            'profile' => $resolvedProfile,
            'rank' => (int) $data['rank'],
            'facebook' => isset($data['facebook']) ? trim((string) $data['facebook']) : '',
            'telegram' => isset($data['telegram']) ? trim((string) $data['telegram']) : '',
            'youtube' => isset($data['youtube']) ? trim((string) $data['youtube']) : '',
            'description' => trim($data['description']),
            'qualification' => trim($data['qualification']),
            'experience' => (int) $data['experience'],
            'total_course' => (int) $data['total_course'],
        ];
    }

    private function storeBase64Image(string $base64): ?string
    {
        if ($base64 === '') {
            return null;
        }

        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            throw ValidationException::withMessages([
                'profile' => 'Invalid image format.',
            ]);
        }

        $ext = strtolower($type[1]);
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            throw ValidationException::withMessages([
                'profile' => 'Unsupported image type.',
            ]);
        }

        $imageData = base64_decode(substr($base64, strpos($base64, ',') + 1));
        if ($imageData === false) {
            throw ValidationException::withMessages([
                'profile' => 'Failed to process image.',
            ]);
        }

        if (strlen($imageData) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'profile' => 'Image is too large (max 5MB).',
            ]);
        }

        $fileName = time() . '_' . uniqid() . '.' . $ext;
        $path = 'teachers';
        Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);

        return env('APP_URL') . Storage::disk('uploads')->url($path . '/' . $fileName);
    }
}
