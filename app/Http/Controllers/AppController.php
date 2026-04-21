<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    use ApiResponse;

    /**
     * Get active apps
     */
    public function index()
    {
        $apps = App::where('show_on', '>', 0)
            ->orderBy('active_course', 'desc')
            ->get();

        $formattedApps = $apps->map(function ($app) {
            return [
                'id' => (int)$app->id,
                'name' => $app->name,
                'description' => $this->ensureUtf8($app->description),
                'url' => $app->url,
                'cover' => $app->cover,
                'icon' => $app->icon,
                'type' => $app->type,
                'activeCourse' => (int)$app->active_course,
                'studentLearning' => $app->student_learning,
                'major' => $app->major,
            ];
        });

        return $this->successResponse($formattedApps, 200, ['total' => $formattedApps->count()]);
    }

    /**
     * Check for app updates
     */
    public function checkUpdate(Request $request)
    {
        $request->validate([
            'packageId' => 'required|string',
            'platform' => 'required|string|in:android,ios',
            'versionCode' => 'required|integer',
        ]);

        $packageId = $request->packageId;
        $platform = $request->platform;
        $clientVersionCode = (int)$request->versionCode;

        $app = App::where('package_id', $packageId)
            ->where('platform', $platform)
            ->first();

        if (!$app) {
            return $this->errorResponse('App not found for the specified package ID and platform', 404);
        }

        $updateAvailable = false;
        $forceUpdate = false;

        if ($app->latest_version_code > $clientVersionCode) {
            $updateAvailable = true;
            if ($app->min_version_code > $clientVersionCode || $app->force_update) {
                $forceUpdate = true;
            }
        }

        $responseData = [
            'updateAvailable' => $updateAvailable,
            'forceUpdate' => $forceUpdate,
            'latestVersionCode' => (int)$app->latest_version_code,
            'latestVersionName' => $app->latest_version_name,
            'updateUrl' => $app->url,
            'updateMessage' => $app->update_message,
        ];

        return $this->successResponse($responseData, 200);
    }

    private function ensureUtf8($str)
    {
        if ($str === null || $str === '') return (string)$str;
        if (!mb_check_encoding($str, 'UTF-8')) return mb_convert_encoding($str, 'UTF-8', 'auto');
        return $str;
    }
}
