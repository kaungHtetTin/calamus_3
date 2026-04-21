<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;

use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show the admin profile page.
     */
    public function edit()
    {
        return Inertia::render('Admin/Profile', [
            'admin' => Auth::guard('admin')->user(),
        ]);
    }

    /**
     * Update the admin's profile information.
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string'], // Receives base64 string
        ]);

        $data = ['name' => $request->name];

        if ($request->image && preg_match('/^data:image\/(\w+);base64,/', $request->image, $type)) {
            $ext = strtolower($type[1]);
            $imageData = base64_decode(substr($request->image, strpos($request->image, ',') + 1));
            
            if ($imageData) {
                $fileName = time() . '_' . $admin->id . '.' . $ext;
                $path = 'admin/images';

                // Save to 'uploads' disk as seen in DiscussionController
                Storage::disk('uploads')->put($path . '/' . $fileName, $imageData);
                
                // Construct full URL using APP_URL and disk's URL helper
                $data['image_url'] = env('APP_URL') . Storage::disk('uploads')->url($path . '/' . $fileName);
            }
        }

        $admin->update($data);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::guard('admin')->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }
}
