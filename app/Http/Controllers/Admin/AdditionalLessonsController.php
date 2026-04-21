<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdditionalLessonsController extends Controller
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

    private function ensureAdditionalCourse(Course $course): void
    {
        $major = strtolower(trim((string) ($course->major ?? '')));
        if ($major !== 'not') {
            abort(404);
        }
    }

    public function index(Request $request)
    {
        $languagesQuery = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order');

        $languages = $languagesQuery->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        if (!$scope->contains('*')) {
            if ($scope->isEmpty()) {
                $languages = collect();
            } else {
                $allowed = $scope->all();
                $languages = $languages->filter(function ($row) use ($allowed) {
                    $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                    return $code !== '' && in_array($code, $allowed, true);
                })->values();
            }
        }

        return Inertia::render('Admin/AdditionalLessons', [
            'languages' => $languages,
        ]);
    }

    public function coursesIndex(Request $request)
    {
        $courses = DB::table('courses as c')
            ->leftJoin('lessons_categories as lc', 'lc.course_id', '=', 'c.course_id')
            ->leftJoin('lessons as l', 'l.category_id', '=', 'lc.id')
            ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not'])
            ->groupBy('c.course_id', 'c.title', 'c.is_vip', 'c.active', 'c.sorting')
            ->orderByDesc('c.sorting')
            ->orderByDesc('c.course_id')
            ->get([
                'c.course_id',
                'c.title',
                'c.is_vip',
                'c.active',
                'c.sorting',
                DB::raw('COUNT(DISTINCT lc.id) as categories_count'),
                DB::raw('COUNT(DISTINCT l.id) as lessons_count'),
            ])
            ->map(function ($row) {
                return [
                    'course_id' => (int) ($row->course_id ?? 0),
                    'title' => (string) ($row->title ?? ''),
                    'is_vip' => (int) ($row->is_vip ?? 0),
                    'active' => (int) ($row->active ?? 0),
                    'sorting' => (int) ($row->sorting ?? 0),
                    'categories_count' => (int) ($row->categories_count ?? 0),
                    'lessons_count' => (int) ($row->lessons_count ?? 0),
                ];
            })
            ->values();

        return Inertia::render('Admin/AdditionalCourses', [
            'courses' => $courses,
        ]);
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sorting' => ['nullable', 'integer', 'min:0'],
            'is_vip' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
        ]);

        Course::create([
            'teacher_id' => 0,
            'title' => trim((string) $data['title']),
            'major' => 'not',
            'sorting' => (int) ($data['sorting'] ?? 0),
            'is_vip' => (int) $data['is_vip'],
            'active' => (int) $data['active'],
            'lessons_count' => 0,
            'duration' => 0,
            'fee' => 0,
            'enroll' => 0,
            'rating' => 0,
            'background_color' => '#FFFFFF',
            'cover_url' => '',
            'web_cover' => '',
            'description' => '',
            'details' => '',
            'preview' => '',
            'certificate_title' => '',
            'certificate_code' => '',
        ]);

        return redirect()->back()->with('success', 'Additional course created successfully.');
    }

    public function updateCourse(Request $request, Course $course)
    {
        $this->ensureAdditionalCourse($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sorting' => ['nullable', 'integer', 'min:0'],
            'is_vip' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
        ]);

        $course->update([
            'title' => trim((string) $data['title']),
            'sorting' => (int) ($data['sorting'] ?? 0),
            'is_vip' => (int) $data['is_vip'],
            'active' => (int) $data['active'],
            'major' => 'not',
        ]);

        return redirect()->back()->with('success', 'Additional course updated successfully.');
    }

    public function destroyCourse(Request $request, Course $course)
    {
        $this->ensureAdditionalCourse($course);

        $categoryIds = DB::table('lessons_categories')
            ->where('course_id', (int) $course->course_id)
            ->pluck('id')
            ->map(function ($value) {
                return (int) $value;
            })
            ->values();

        if ($categoryIds->count() > 0) {
            DB::table('lessons')->whereIn('category_id', $categoryIds->all())->delete();
            DB::table('lessons_categories')->whereIn('id', $categoryIds->all())->delete();
        }

        $course->delete();

        return redirect()->back()->with('success', 'Additional course deleted successfully.');
    }

    public function workspace(Request $request)
    {
        $selectedMajor = trim((string)$request->query('major', ''));
        $selectedCourseId = (int)$request->query('courseId', 0);

        $languagesQuery = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order');
        $languages = $languagesQuery->get(['code', 'display_name', 'name', 'module_code', 'image_path', 'primary_color']);

        $scope = $this->getAdminMajorScope($request);
        if (!$scope->contains('*')) {
            if ($scope->isEmpty()) {
                $languages = collect();
            } else {
                $allowed = $scope->all();
                $languages = $languages->filter(function ($row) use ($allowed) {
                    $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                    return $code !== '' && in_array($code, $allowed, true);
                })->values();
            }
        }

        if ($selectedMajor === '' && $languages->count() > 0) {
            $selectedMajor = (string)($languages->first()->code ?? '');
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

        $allCourses = DB::table('courses as c')
            ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not'])
            ->orderByDesc('c.sorting')
            ->orderByDesc('c.course_id')
            ->get(['c.course_id', 'c.title', 'c.is_vip', 'c.active', 'c.sorting']);

        $coursesQuery = DB::table('courses as c')
            ->whereRaw("LOWER(COALESCE(c.major, '')) = ?", ['not']);

        if ($selectedMajor !== '') {
            $coursesQuery->join('lessons_categories as lc', function ($join) use ($selectedMajor) {
                $join->on('lc.course_id', '=', 'c.course_id')
                    ->where('lc.major', '=', $selectedMajor);
            });
        }

        $courses = $coursesQuery
            ->distinct()
            ->orderByDesc('c.sorting')
            ->orderByDesc('c.course_id')
            ->get(['c.course_id', 'c.title', 'c.is_vip', 'c.active', 'c.sorting']);

        if ($selectedCourseId <= 0) {
            if ($courses->count() > 0) {
                $selectedCourseId = (int)($courses->first()->course_id ?? 0);
            } elseif ($allCourses->count() > 0) {
                $selectedCourseId = (int)($allCourses->first()->course_id ?? 0);
            }
        }

        $course = null;
        $categoriesWithLessons = [];
        $courseIdInScope = $courses->contains(function ($row) use ($selectedCourseId) {
            return (int)($row->course_id ?? 0) === (int)$selectedCourseId;
        });
        $courseIdExists = $allCourses->contains(function ($row) use ($selectedCourseId) {
            return (int)($row->course_id ?? 0) === (int)$selectedCourseId;
        });

        if ($selectedCourseId > 0 && $courseIdExists) {
            $course = DB::table('courses')
                ->where('course_id', $selectedCourseId)
                ->whereRaw("LOWER(COALESCE(major, '')) = ?", ['not'])
                ->first(['course_id', 'title', 'is_vip', 'active', 'sorting']);

            if ($course) {
                $categories = DB::table('lessons_categories as lc')
                    ->leftJoin('lessons as l', 'l.category_id', '=', 'lc.id')
                    ->select(
                        'lc.id',
                        'lc.course_id',
                        'lc.category',
                        'lc.category_title',
                        'lc.image_url',
                        'lc.major',
                        'lc.sort_order',
                        DB::raw('COUNT(l.id) as lessons_count')
                    )
                    ->where('lc.course_id', $selectedCourseId)
                    ->where('lc.major', $selectedMajor)
                    ->groupBy('lc.id', 'lc.course_id', 'lc.category', 'lc.category_title', 'lc.image_url', 'lc.major', 'lc.sort_order')
                    ->orderByDesc('lc.sort_order')
                    ->orderBy('lc.id')
                    ->get();

                foreach ($categories as $cat) {
                    $lessons = DB::table('lessons')
                        ->where('category_id', (int)$cat->id)
                        ->orderBy('id')
                        ->get([
                            'id',
                            'title',
                            'title_mini',
                            'duration',
                            'isVip',
                            'isVideo',
                            'link',
                            'document_link',
                            'download_url',
                            'thumbnail',
                            'date',
                            'notes',
                        ]);
                    $categoriesWithLessons[] = [
                        'id' => (int)$cat->id,
                        'course_id' => (int)$cat->course_id,
                        'category' => (string)($cat->category ?? ''),
                        'category_title' => (string)($cat->category_title ?? ''),
                        'image_url' => (string)($cat->image_url ?? ''),
                        'sort_order' => (int)($cat->sort_order ?? 0),
                        'major' => (string)($cat->major ?? ''),
                        'lessons' => $lessons->map(function ($l) {
                            return [
                                'id' => (int)$l->id,
                                'title' => (string)($l->title ?? ''),
                                'title_mini' => (string)($l->title_mini ?? ''),
                                'duration' => (int)($l->duration ?? 0),
                                'isVip' => (int)($l->isVip ?? 0),
                                'isVideo' => (int)($l->isVideo ?? 0),
                                'link' => (string)($l->link ?? ''),
                                'document_link' => (string)($l->document_link ?? ''),
                                'download_url' => (string)($l->download_url ?? ''),
                                'thumbnail' => (string)($l->thumbnail ?? ''),
                                'date' => (int)($l->date ?? 0),
                                'notes' => (string)($l->notes ?? ''),
                            ];
                        }),
                    ];
                }
            }
        }

        return Inertia::render('Admin/AdditionalLessonsWorkspace', [
            'languages' => $languages,
            'selectedMajor' => $selectedMajor,
            'selectedLanguage' => $selectedLanguage,
            'courses' => $courses,
            'allCourses' => $allCourses,
            'course' => $course,
            'courseInScope' => $courseIdInScope,
            'categories' => $categoriesWithLessons,
        ]);
    }

    public function manage(Request $request, int $courseId)
    {
        $selectedMajor = trim((string)$request->query('major', ''));

        $languagesQuery = DB::table('languages')
            ->where('is_active', 1)
            ->orderBy('sort_order');
        $languages = $languagesQuery->get(['code', 'display_name', 'name', 'module_code']);

        $scope = $this->getAdminMajorScope($request);
        if (!$scope->contains('*')) {
            if ($scope->isEmpty()) {
                abort(403);
            }
            $allowed = $scope->all();
            $languages = $languages->filter(function ($row) use ($allowed) {
                $code = strtolower(trim((string) ($row->code ?: $row->name ?: '')));
                return $code !== '' && in_array($code, $allowed, true);
            })->values();
        }

        if ($selectedMajor === '' && $languages->count() > 0) {
            $selectedMajor = (string)($languages->first()->code ?? '');
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

        $course = DB::table('courses')
            ->where('course_id', $courseId)
            ->whereRaw("LOWER(COALESCE(major, '')) = ?", ['not'])
            ->first(['course_id', 'title', 'is_vip', 'active', 'sorting']);

        if (!$course) {
            abort(404);
        }

        $categories = DB::table('lessons_categories as lc')
            ->leftJoin('lessons as l', 'l.category_id', '=', 'lc.id')
            ->select(
                'lc.id',
                'lc.course_id',
                'lc.category',
                'lc.category_title',
                'lc.image_url',
                'lc.major',
                'lc.sort_order',
                DB::raw('COUNT(l.id) as lessons_count')
            )
            ->where('lc.course_id', $courseId)
            ->where('lc.major', $selectedMajor)
            ->groupBy('lc.id', 'lc.course_id', 'lc.category', 'lc.category_title', 'lc.image_url', 'lc.major', 'lc.sort_order')
            ->orderByDesc('lc.sort_order')
            ->orderBy('lc.id')
            ->get();

        $categoriesWithLessons = [];
        foreach ($categories as $cat) {
            $lessons = DB::table('lessons')
                ->where('category_id', (int)$cat->id)
                ->orderBy('id')
                ->get([
                    'id',
                    'title',
                    'title_mini',
                    'duration',
                    'isVip',
                    'isVideo',
                    'link',
                    'document_link',
                    'download_url',
                    'thumbnail',
                    'date',
                ]);
            $categoriesWithLessons[] = [
                'id' => (int)$cat->id,
                'course_id' => (int)$cat->course_id,
                'category' => (string)($cat->category ?? ''),
                'category_title' => (string)($cat->category_title ?? ''),
                'image_url' => (string)($cat->image_url ?? ''),
                'sort_order' => (int)($cat->sort_order ?? 0),
                'major' => (string)($cat->major ?? ''),
                'lessons' => $lessons->map(function ($l) {
                    return [
                        'id' => (int)$l->id,
                        'title' => (string)($l->title ?? ''),
                        'title_mini' => (string)($l->title_mini ?? ''),
                        'duration' => (int)($l->duration ?? 0),
                        'isVip' => (int)($l->isVip ?? 0),
                        'isVideo' => (int)($l->isVideo ?? 0),
                        'link' => (string)($l->link ?? ''),
                        'document_link' => (string)($l->document_link ?? ''),
                        'download_url' => (string)($l->download_url ?? ''),
                        'thumbnail' => (string)($l->thumbnail ?? ''),
                        'date' => (int)($l->date ?? 0),
                    ];
                }),
            ];
        }

        return Inertia::render('Admin/AdditionalLessonsManage', [
            'languages' => $languages,
            'selectedMajor' => $selectedMajor,
            'course' => $course,
            'categories' => $categoriesWithLessons,
        ]);
    }
}
