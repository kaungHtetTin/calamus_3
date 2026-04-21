<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::orderBy('sort_order')->orderBy('id')->get();
        return Inertia::render('Admin/PaymentMethods', [
            'paymentMethods' => $methods,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'account_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $payload = [
            'name' => $data['name'],
            'account_name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'active' => array_key_exists('active', $data) ? (bool)$data['active'] : true,
            'sort_order' => array_key_exists('sort_order', $data) ? (int)$data['sort_order'] : 0,
        ];

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $storedPath = Storage::disk('uploads')->putFile('payment-methods/logos', $file);
            $payload['logo'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        PaymentMethod::create($payload);

        return redirect()->back(303);
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'account_name' => ['sometimes', 'required', 'string', 'max:100'],
            'account_number' => ['sometimes', 'required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $payload = [];
        foreach (['name', 'account_name', 'account_number', 'active', 'sort_order'] as $field) {
            if ($request->has($field)) {
                $payload[$field] = $data[$field];
            }
        }

        if ($request->boolean('remove_logo')) {
            $payload['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $storedPath = Storage::disk('uploads')->putFile('payment-methods/logos', $file);
            $payload['logo'] = $this->toAbsoluteUrl(Storage::disk('uploads')->url($storedPath));
        }

        if ($payload !== []) {
            $paymentMethod->update($payload);
        }

        return redirect()->back(303);
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return redirect()->back(303);
    }

    private function toAbsoluteUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $baseUrl = trim((string) config('app.url'));
        if ($baseUrl === '') {
            $baseUrl = trim((string) env('APP_URL'));
        }

        if ($baseUrl === '') {
            return $value;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

}
