<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UiShowcaseService;
use Inertia\Inertia;

class UiShowcaseController extends Controller
{
    protected UiShowcaseService $uiShowcaseService;

    public function __construct(UiShowcaseService $uiShowcaseService)
    {
        $this->uiShowcaseService = $uiShowcaseService;
    }

    public function index()
    {
        $data = $this->uiShowcaseService->getShowcaseData();

        return Inertia::render('Admin/UiShowcase', [
            'metrics' => $data['metrics'],
            'environments' => $data['environments'],
            'activity' => $data['activity'],
        ]);
    }
}
