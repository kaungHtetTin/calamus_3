<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Language;
use App\Traits\ApiResponse;

class LanguageController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $languages = Language::where('is_active', 1)->orderBy('sort_order', 'asc')->get();
            
            $data = $languages->map(function ($lang) {
                return [
                    'id' => (int)$lang->id,
                    'name' => $lang->name,
                    'displayName' => $lang->display_name ?: $lang->name,
                    'code' => $lang->code ?: '',
                    'moduleCode' => $lang->module_code ?: '',
                ];
            });

            return $this->successResponse($data, 200, ['total' => $data->count()]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch languages', 500);
        }
    }
}
