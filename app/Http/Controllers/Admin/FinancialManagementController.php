<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinancialManagementController extends Controller
{
    private function getAdminMajorScope(Request $request)
    {
        $admin = $request->user('admin');
        $raw = collect((array) ($admin?->major_scope ?? []))
            ->map(function ($item) {
                return strtolower(trim((string) $item));
            })
            ->filter()
            ->unique()
            ->values();

        if ($raw->contains('*')) {
            return collect(['*']);
        }

        $languageValues = DB::table('languages')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'code'])
            ->map(function ($row) {
                $value = strtolower(trim((string) ($row->code ?: $row->name)));
                return $value !== '' ? $value : null;
            })
            ->filter()
            ->unique()
            ->values();

        return $raw
            ->filter(function ($value) use ($languageValues) {
                return $languageValues->contains($value);
            })
            ->values();
    }

    private function filterLanguagesByScope($languages, $scope)
    {
        if ($scope->contains('*')) {
            return $languages;
        }

        if ($scope->isEmpty()) {
            return collect();
        }

        $allowed = $scope->all();
        return $languages->filter(function ($row) use ($allowed) {
            $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
            return $code !== '' && in_array($code, $allowed, true);
        })->values();
    }

    public function index(Request $request)
    {
        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        return Inertia::render('Admin/Financial', [
            'languages' => $languages,
        ]);
    }

    public function workspace(Request $request)
    {
        $selectedMajor = trim((string) $request->query('major', ''));
        $tab = strtolower(trim((string) $request->query('tab', 'overview')));
        $allowedTabs = collect(['overview', 'cost', 'payment']);
        if (!$allowedTabs->contains($tab)) {
            $tab = 'overview';
        }

        $requestedYear = (int) $request->query('year', 0);
        $requestedMonth = (int) $request->query('month', 0);

        $languages = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        $languages = $this->filterLanguagesByScope($languages, $scope);

        if ($selectedMajor === '' && $languages->count() > 0) {
            $selectedMajor = (string) ($languages->first()->code ?? '');
        }

        if ($selectedMajor !== '') {
            $normalizedSelected = strtolower(trim($selectedMajor));
            if (!$scope->contains('*') && !$languages->contains(function ($row) use ($normalizedSelected) {
                $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                return $code === $normalizedSelected;
            })) {
                abort(403);
            }
        }

        $selectedLanguage = $languages->firstWhere('code', $selectedMajor);

        $overview = null;
        $costsThisMonth = collect();
        $filter = [
            'year' => null,
            'month' => null,
            'years' => [],
        ];
        $salesMonthSeries = [];
        $salesYearSeries = [];
        $costFilter = [
            'year' => null,
            'month' => 0,
            'years' => [],
        ];
        $costCategoryYearSeries = [];
        $costCategoryAllTimeSeries = [];
        $costsPaginator = null;
        $paymentFilter = [
            'year' => null,
            'month' => 0,
            'years' => [],
            'search' => '',
        ];
        $paymentsPaginator = null;

        if ($tab === 'overview' && $selectedMajor !== '') {
            $major = strtolower(trim($selectedMajor));
            $now = Carbon::now();

            $todayStart = $now->copy()->startOfDay();
            $todayEnd = $now->copy()->endOfDay();

            $fallbackYear = (int) $now->year;
            $fallbackMonth = (int) $now->month;
            $year = $requestedYear > 0 ? $requestedYear : $fallbackYear;
            $month = ($requestedMonth >= 1 && $requestedMonth <= 12) ? $requestedMonth : $fallbackMonth;

            $selectedMonthStart = Carbon::create($year, $month, 1)->startOfDay();
            $selectedMonthEnd = $selectedMonthStart->copy()->endOfMonth()->endOfDay();
            $selectedYearStart = Carbon::create($year, 1, 1)->startOfDay();
            $selectedYearEnd = $selectedYearStart->copy()->endOfYear()->endOfDay();

            if ($year === (int) $now->year && $month === (int) $now->month) {
                $selectedMonthEnd = $now->copy()->endOfDay();
            }
            if ($year === (int) $now->year) {
                $selectedYearEnd = $now->copy()->endOfDay();
            }

            $incomeBase = DB::table('payments')
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);

            $incomeToday = (float) (clone $incomeBase)
                ->whereBetween('date', [$todayStart, $todayEnd])
                ->sum('amount');
            $incomeMonth = (float) (clone $incomeBase)
                ->whereBetween('date', [$selectedMonthStart, $selectedMonthEnd])
                ->sum('amount');
            $incomeYear = (float) (clone $incomeBase)
                ->whereBetween('date', [$selectedYearStart, $selectedYearEnd])
                ->sum('amount');
            $incomeAllTime = (float) (clone $incomeBase)->sum('amount');

            $costMonth = 0.0;
            $costYear = 0.0;
            $costAllTime = 0.0;

            if (DB::getSchemaBuilder()->hasTable('costs')) {
                $costBase = DB::table('costs')
                    ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);

                $costMonth = (float) (clone $costBase)->whereBetween('date', [$selectedMonthStart, $selectedMonthEnd])->sum('amount');
                $costYear = (float) (clone $costBase)->whereBetween('date', [$selectedYearStart, $selectedYearEnd])->sum('amount');
                $costAllTime = (float) (clone $costBase)->sum('amount');

                $costsThisMonth = DB::table('costs as c')
                    ->leftJoin('cost_categories as cc', 'cc.id', '=', 'c.cost_category_id')
                    ->whereRaw("LOWER(TRIM(COALESCE(c.major, ''))) = ?", [$major])
                    ->whereBetween('c.date', [$selectedMonthStart, $selectedMonthEnd])
                    ->orderByDesc('c.date')
                    ->orderByDesc('c.id')
                    ->limit(300)
                    ->get([
                        'c.id',
                        'c.title',
                        'c.amount',
                        'c.date',
                        'c.cost_category_id',
                        DB::raw('cc.title as category_title'),
                        'c.transfer_id',
                    ]);
            }

            $minPaymentDate = (clone $incomeBase)->min('date');
            $minCostDate = DB::getSchemaBuilder()->hasTable('costs') ? (clone DB::table('costs')->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]))->min('date') : null;
            $minDateCandidate = $minPaymentDate ?: $minCostDate;
            if ($minPaymentDate && $minCostDate) {
                $minDateCandidate = $minPaymentDate < $minCostDate ? $minPaymentDate : $minCostDate;
            }

            $minYear = $minDateCandidate ? (int) Carbon::parse($minDateCandidate)->year : (int) $now->year;
            $maxYear = (int) $now->year;
            if ($minYear > $maxYear) {
                $minYear = $maxYear;
            }

            $filter = [
                'year' => $year,
                'month' => $month,
                'years' => range($minYear, $maxYear),
            ];

            // Build sales series (payments sum)
            // Month series: daily totals for selected month
            $daysInMonth = (int) $selectedMonthStart->copy()->endOfMonth()->day;
            $initMonthSeries = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $initMonthSeries[$d] = 0.0;
            }
            $salesByDay = DB::table('payments')
                ->select([
                    DB::raw('DAY(date) as d'),
                    DB::raw('SUM(amount) as total'),
                ])
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->whereBetween('date', [$selectedMonthStart, $selectedMonthEnd])
                ->groupBy(DB::raw('DAY(date)'))
                ->get();
            foreach ($salesByDay as $row) {
                $day = (int) ($row->d ?? 0);
                $total = (float) ($row->total ?? 0);
                if ($day >= 1 && $day <= $daysInMonth) {
                    $initMonthSeries[$day] = $total;
                }
            }
            foreach ($initMonthSeries as $day => $total) {
                $salesMonthSeries[] = ['x' => $day, 'y' => round($total, 2)];
            }

            // Year series: monthly totals for selected year
            $initYearSeries = [];
            for ($m = 1; $m <= 12; $m++) {
                $initYearSeries[$m] = 0.0;
            }
            $salesByMonth = DB::table('payments')
                ->select([
                    DB::raw('MONTH(date) as m'),
                    DB::raw('SUM(amount) as total'),
                ])
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->whereBetween('date', [$selectedYearStart, $selectedYearEnd])
                ->groupBy(DB::raw('MONTH(date)'))
                ->get();
            foreach ($salesByMonth as $row) {
                $m = (int) ($row->m ?? 0);
                $total = (float) ($row->total ?? 0);
                if ($m >= 1 && $m <= 12) {
                    $initYearSeries[$m] = $total;
                }
            }
            foreach ($initYearSeries as $m => $total) {
                $salesYearSeries[] = ['x' => $m, 'y' => round($total, 2)];
            }

            $overview = [
                'income' => [
                    'today' => $incomeToday,
                    'month' => $incomeMonth,
                    'year' => $incomeYear,
                    'all_time' => $incomeAllTime,
                ],
                'cost' => [
                    'month' => $costMonth,
                    'year' => $costYear,
                    'all_time' => $costAllTime,
                ],
                'net_income' => [
                    'month' => $incomeMonth - $costMonth,
                    'year' => $incomeYear - $costYear,
                    'all_time' => $incomeAllTime - $costAllTime,
                ],
                'selected_period' => [
                    'year' => $year,
                    'month' => $month,
                    'month_start' => $selectedMonthStart->toDateTimeString(),
                    'month_end' => $selectedMonthEnd->toDateTimeString(),
                    'year_start' => $selectedYearStart->toDateTimeString(),
                    'year_end' => $selectedYearEnd->toDateTimeString(),
                ],
                'as_of' => $now->toDateTimeString(),
            ];
        }

        if ($tab === 'cost' && $selectedMajor !== '') {
            $major = strtolower(trim($selectedMajor));
            $now = Carbon::now();

            $fallbackYear = (int) $now->year;
            $year = $requestedYear > 0 ? $requestedYear : $fallbackYear;
            $month = ($requestedMonth >= 1 && $requestedMonth <= 12) ? $requestedMonth : 0;

            $selectedYearStart = Carbon::create($year, 1, 1)->startOfDay();
            $selectedYearEnd = $selectedYearStart->copy()->endOfYear()->endOfDay();
            if ($year === (int) $now->year) {
                $selectedYearEnd = $now->copy()->endOfDay();
            }

            $listStart = $selectedYearStart->copy();
            $listEnd = $selectedYearEnd->copy();
            if ($month >= 1 && $month <= 12) {
                $listStart = Carbon::create($year, $month, 1)->startOfDay();
                $listEnd = $listStart->copy()->endOfMonth()->endOfDay();
                if ($year === (int) $now->year && $month === (int) $now->month) {
                    $listEnd = $now->copy()->endOfDay();
                }
            }

            $minPaymentDate = DB::table('payments')
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->min('date');
            $minCostDate = DB::getSchemaBuilder()->hasTable('costs')
                ? (clone DB::table('costs')->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]))->min('date')
                : null;
            $minDateCandidate = $minPaymentDate ?: $minCostDate;
            if ($minPaymentDate && $minCostDate) {
                $minDateCandidate = $minPaymentDate < $minCostDate ? $minPaymentDate : $minCostDate;
            }

            $minYear = $minDateCandidate ? (int) Carbon::parse($minDateCandidate)->year : (int) $now->year;
            $maxYear = (int) $now->year;
            if ($minYear > $maxYear) {
                $minYear = $maxYear;
            }

            $costFilter = [
                'year' => $year,
                'month' => $month,
                'years' => range($minYear, $maxYear),
            ];

            if (DB::getSchemaBuilder()->hasTable('costs')) {
                $costsBase = DB::table('costs as c')
                    ->leftJoin('cost_categories as cc', 'cc.id', '=', 'c.cost_category_id')
                    ->whereRaw("LOWER(TRIM(COALESCE(c.major, ''))) = ?", [$major]);

                $costCategoryYearRows = (clone $costsBase)
                    ->whereBetween('c.date', [$selectedYearStart, $selectedYearEnd])
                    ->groupBy('c.cost_category_id', 'cc.title')
                    ->orderByDesc(DB::raw('SUM(c.amount)'))
                    ->get([
                        'c.cost_category_id',
                        DB::raw('cc.title as category_title'),
                        DB::raw('SUM(c.amount) as total'),
                    ]);

                foreach ($costCategoryYearRows as $row) {
                    $label = trim((string) ($row->category_title ?? ''));
                    $costCategoryYearSeries[] = [
                        'name' => $label !== '' ? $label : 'Uncategorized',
                        'value' => (float) ($row->total ?? 0),
                    ];
                }

                $costCategoryAllTimeRows = (clone $costsBase)
                    ->groupBy('c.cost_category_id', 'cc.title')
                    ->orderByDesc(DB::raw('SUM(c.amount)'))
                    ->get([
                        'c.cost_category_id',
                        DB::raw('cc.title as category_title'),
                        DB::raw('SUM(c.amount) as total'),
                    ]);

                foreach ($costCategoryAllTimeRows as $row) {
                    $label = trim((string) ($row->category_title ?? ''));
                    $costCategoryAllTimeSeries[] = [
                        'name' => $label !== '' ? $label : 'Uncategorized',
                        'value' => (float) ($row->total ?? 0),
                    ];
                }

                $perPage = (int) $request->query('cost_per_page', 25);
                if ($perPage <= 0) {
                    $perPage = 25;
                }
                if ($perPage > 200) {
                    $perPage = 200;
                }

                $costsPaginator = (clone $costsBase)
                    ->whereBetween('c.date', [$listStart, $listEnd])
                    ->orderByDesc('c.date')
                    ->orderByDesc('c.id')
                    ->paginate(
                        $perPage,
                        [
                            'c.id',
                            'c.title',
                            'c.amount',
                            'c.date',
                            'c.cost_category_id',
                            DB::raw('cc.title as category_title'),
                            'c.transfer_id',
                        ],
                        'cost_page'
                    )
                    ->withQueryString();
            }
        }

        if ($tab === 'payment' && $selectedMajor !== '') {
            $major = strtolower(trim($selectedMajor));
            $now = Carbon::now();

            $fallbackYear = (int) $now->year;
            $year = $requestedYear > 0 ? $requestedYear : $fallbackYear;
            $month = ($requestedMonth >= 1 && $requestedMonth <= 12) ? $requestedMonth : 0;
            $search = trim((string) $request->query('search', ''));

            $selectedYearStart = Carbon::create($year, 1, 1)->startOfDay();
            $selectedYearEnd = $selectedYearStart->copy()->endOfYear()->endOfDay();
            if ($year === (int) $now->year) {
                $selectedYearEnd = $now->copy()->endOfDay();
            }

            $listStart = $selectedYearStart->copy();
            $listEnd = $selectedYearEnd->copy();
            if ($month >= 1 && $month <= 12) {
                $listStart = Carbon::create($year, $month, 1)->startOfDay();
                $listEnd = $listStart->copy()->endOfMonth()->endOfDay();
                if ($year === (int) $now->year && $month === (int) $now->month) {
                    $listEnd = $now->copy()->endOfDay();
                }
            }

            $minPaymentDate = DB::table('payments')
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->min('date');

            $minYear = $minPaymentDate ? (int) Carbon::parse($minPaymentDate)->year : (int) $now->year;
            $maxYear = (int) $now->year;
            if ($minYear > $maxYear) {
                $minYear = $maxYear;
            }

            $paymentFilter = [
                'year' => $year,
                'month' => $month,
                'years' => range($minYear, $maxYear),
                'search' => $search,
            ];

            $paymentsBase = DB::table('payments')
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major]);

            if ($search !== '') {
                $paymentsBase->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', '%' . $search . '%')
                        ->orWhere('user_id', 'like', '%' . $search . '%')
                        ->orWhere('amount', 'like', '%' . $search . '%');
                });
            }

            $perPage = (int) $request->query('payment_per_page', 25);
            if ($perPage <= 0) {
                $perPage = 25;
            }
            if ($perPage > 200) {
                $perPage = 200;
            }

            $paymentsPaginator = $paymentsBase
                ->whereBetween('date', [$listStart, $listEnd])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(
                    $perPage,
                    [
                        'id',
                        'user_id',
                        'amount',
                        'major',
                        'courses',
                        'screenshot',
                        'approve',
                        'activated',
                        'transaction_id',
                        'date',
                    ],
                    'payment_page'
                )
                ->withQueryString();
        }

        return Inertia::render('Admin/FinancialWorkspace', [
            'languages' => $languages,
            'selectedMajor' => $selectedMajor,
            'selectedLanguage' => $selectedLanguage,
            'tab' => $tab,
            'overview' => $overview,
            'costsThisMonth' => $costsThisMonth,
            'filter' => $filter,
            'salesMonthSeries' => $salesMonthSeries,
            'salesYearSeries' => $salesYearSeries,
            'costFilter' => $costFilter,
            'costCategoryYearSeries' => $costCategoryYearSeries,
            'costCategoryAllTimeSeries' => $costCategoryAllTimeSeries,
            'costsPaginator' => $costsPaginator,
            'paymentFilter' => $paymentFilter,
            'paymentsPaginator' => $paymentsPaginator,
        ]);
    }
}
