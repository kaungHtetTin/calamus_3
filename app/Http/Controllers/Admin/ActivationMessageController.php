<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationMessage;
use App\Models\Language;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivationMessageController extends Controller
{
    public function index()
    {
        $activationMessages = ActivationMessage::query()
            ->orderByDesc('id')
            ->get(['id', 'message', 'major', 'created_at', 'updated_at']);

        $languages = Language::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'display_name', 'image_path', 'primary_color']);

        return Inertia::render('Admin/ActivationMessages', [
            'activationMessages' => $activationMessages,
            'languageOptions' => $languages->map(function ($l) {
                return [
                    'id' => (int) $l->id,
                    'code' => (string) ($l->code ?: $l->name),
                    'name' => (string) ($l->display_name ?: $l->name),
                    'image_path' => (string) ($l->image_path ?? ''),
                    'primary_color' => (string) ($l->primary_color ?? ''),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'major' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ActivationMessage::create([
            'major' => strtolower(trim((string) $data['major'])),
            'message' => trim((string) $data['message']),
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, ActivationMessage $activationMessage)
    {
        $data = $request->validate([
            'major' => ['sometimes', 'required', 'string', 'max:20'],
            'message' => ['sometimes', 'required', 'string', 'max:5000'],
        ]);

        $payload = [];
        if ($request->has('major')) {
            $payload['major'] = strtolower(trim((string) $data['major']));
        }
        if ($request->has('message')) {
            $payload['message'] = trim((string) $data['message']);
        }

        if ($payload !== []) {
            $activationMessage->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(ActivationMessage $activationMessage)
    {
        $activationMessage->delete();
        return redirect()->back(303);
    }
}
