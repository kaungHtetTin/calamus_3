<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AdministrationController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index()
    {
        return Inertia::render('Admin/Administration', [
            'admins' => Admin::latest()->get(),
            'availablePermissions' => Permission::all(),
            'availableLanguages' => Language::all(),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
            'password' => ['required', Password::defaults()],
            'access' => ['nullable', 'array'],
            'major_scope' => ['nullable', 'array'],
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'access' => $request->access ?: [],
            'major_scope' => $request->major_scope ?: [],
        ]);

        return redirect()->back()->with('success', 'Admin created successfully.');
    }

    /**
     * Update the specified admin user.
     */
    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', Password::defaults()],
            'access' => ['nullable', 'array'],
            'major_scope' => ['nullable', 'array'],
        ]);

        $data = [
            'name' => $request->name,
            'access' => $request->access ?: [],
            'major_scope' => $request->major_scope ?: [],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return redirect()->back()->with('success', 'Admin updated successfully.');
    }

    /**
     * Remove the specified admin user.
     */
    public function destroy(Admin $admin)
    {
        // Prevent deleting yourself
        if ($admin->id === auth('admin')->id()) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }

        $admin->delete();

        return redirect()->back()->with('success', 'Admin deleted successfully.');
    }
}
