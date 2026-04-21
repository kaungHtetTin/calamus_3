<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LanguageController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Languages', [
            'languages' => Language::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:languages,name'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:20', 'unique:languages,code'],
            'module_code' => ['nullable', 'string', 'max:20', 'unique:languages,module_code'],
            'certificate_title' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'seal_file' => ['nullable', 'image', 'max:4096'],
            'firebase_topic_user' => ['nullable', 'string', 'max:150'],
            'firebase_topic_admin' => ['nullable', 'string', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $imagePath = $this->storeImage($request->file('image_file'));
        $sealPath = $this->storeImage($request->file('seal_file'));

        Language::create([
            'name' => trim($data['name']),
            'display_name' => isset($data['display_name']) ? trim((string) $data['display_name']) : null,
            'code' => isset($data['code']) ? trim((string) $data['code']) : null,
            'module_code' => isset($data['module_code']) ? trim((string) $data['module_code']) : null,
            'certificate_title' => isset($data['certificate_title']) ? trim((string) $data['certificate_title']) : null,
            'primary_color' => isset($data['primary_color']) ? strtoupper(trim((string) $data['primary_color'])) : null,
            'secondary_color' => isset($data['secondary_color']) ? strtoupper(trim((string) $data['secondary_color'])) : null,
            'image_path' => $imagePath,
            'seal' => $sealPath,
            'firebase_topic_user' => isset($data['firebase_topic_user']) ? trim((string) $data['firebase_topic_user']) : null,
            'firebase_topic_admin' => isset($data['firebase_topic_admin']) ? trim((string) $data['firebase_topic_admin']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) $data['is_active'],
        ]);

        return redirect()->back()->with('success', 'Language created successfully.');
    }

    public function update(Request $request, Language $language)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('languages', 'name')->ignore($language->id)],
            'display_name' => ['nullable', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('languages', 'code')->ignore($language->id)],
            'module_code' => ['nullable', 'string', 'max:20', Rule::unique('languages', 'module_code')->ignore($language->id)],
            'certificate_title' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'seal_file' => ['nullable', 'image', 'max:4096'],
            'firebase_topic_user' => ['nullable', 'string', 'max:150'],
            'firebase_topic_admin' => ['nullable', 'string', 'max:150'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $imagePath = $this->storeImage($request->file('image_file'));
        $sealPath = $this->storeImage($request->file('seal_file'));

        $language->update([
            'name' => trim($data['name']),
            'display_name' => isset($data['display_name']) ? trim((string) $data['display_name']) : null,
            'code' => isset($data['code']) ? trim((string) $data['code']) : null,
            'module_code' => isset($data['module_code']) ? trim((string) $data['module_code']) : null,
            'certificate_title' => isset($data['certificate_title']) ? trim((string) $data['certificate_title']) : null,
            'primary_color' => isset($data['primary_color']) ? strtoupper(trim((string) $data['primary_color'])) : null,
            'secondary_color' => isset($data['secondary_color']) ? strtoupper(trim((string) $data['secondary_color'])) : null,
            'image_path' => $imagePath ?: $language->image_path,
            'seal' => $sealPath ?: $language->seal,
            'firebase_topic_user' => isset($data['firebase_topic_user']) ? trim((string) $data['firebase_topic_user']) : null,
            'firebase_topic_admin' => isset($data['firebase_topic_admin']) ? trim((string) $data['firebase_topic_admin']) : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) $data['is_active'],
        ]);

        return redirect()->back()->with('success', 'Language updated successfully.');
    }

    public function destroy(Language $language)
    {
        $language->delete();

        return redirect()->back()->with('success', 'Language deleted successfully.');
    }

    private function storeImage(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        $fileName = time() . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $path = 'icons';
        $storedPath = Storage::disk('uploads')->putFileAs($path, $file, $fileName);

        return env('APP_URL') . Storage::disk('uploads')->url($storedPath);
    }
}
