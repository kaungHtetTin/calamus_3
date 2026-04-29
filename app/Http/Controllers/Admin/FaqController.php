<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get([
                'id',
                'question',
                'answer',
                'active',
                'sort_order',
                'created_at',
                'updated_at',
            ]);

        return Inertia::render('Admin/Faqs', [
            'faqs' => $faqs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        Faq::create([
            'question' => trim((string) $data['question']),
            'answer' => trim((string) $data['answer']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->back(303);
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question' => ['sometimes', 'required', 'string', 'max:500'],
            'answer' => ['sometimes', 'required', 'string', 'max:10000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $payload = [];

        if ($request->has('question')) {
            $payload['question'] = trim((string) $data['question']);
        }
        if ($request->has('answer')) {
            $payload['answer'] = trim((string) $data['answer']);
        }
        if ($request->has('sort_order')) {
            $payload['sort_order'] = (int) ($data['sort_order'] ?? 0);
        }
        if ($request->has('active')) {
            $payload['active'] = (bool) ($data['active'] ?? false);
        }

        if ($payload !== []) {
            $faq->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->back(303);
    }
}
