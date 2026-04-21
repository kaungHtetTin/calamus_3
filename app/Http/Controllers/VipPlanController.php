<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use App\Models\Language;
use App\Models\Course;
use App\Models\PackagePlan;
use App\Models\PaymentMethod;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VipPlanController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $authUser = auth('sanctum')->user();
        $purchasedCoursesByMajor = $authUser ? $this->getPurchasedCourseIdsByMajor((int) $authUser->user_id) : [];

        $languages = Language::where('is_active', 1)->orderBy('sort_order')->get();
        $packagePlans = PackagePlan::where('active', 1)->orderBy('sort_order')->get();
        $courses = Course::where('active', 1)->orderBy('sorting')->get();
        $paymentMethods = PaymentMethod::where('active', 1)->orderBy('sort_order')->get();

        $planCourseIds = [];
        if (Schema::hasTable('package_plan_courses')) {
            $planIdColumn = Schema::hasColumn('package_plan_courses', 'package_plan_id')
                ? 'package_plan_id'
                : (Schema::hasColumn('package_plan_courses', 'plan_id') ? 'plan_id' : null);
            $courseIdColumn = Schema::hasColumn('package_plan_courses', 'course_id')
                ? 'course_id'
                : (Schema::hasColumn('package_plan_courses', 'course') ? 'course' : null);

            if ($planIdColumn && $courseIdColumn) {
                $rows = DB::table('package_plan_courses')
                    ->whereIn($planIdColumn, $packagePlans->pluck('id')->all())
                    ->get([$planIdColumn . ' as plan_id', $courseIdColumn . ' as course_id']);

                foreach ($rows as $row) {
                    $pid = (int) ($row->plan_id ?? 0);
                    $cid = (int) ($row->course_id ?? 0);
                    if ($pid > 0 && $cid > 0) {
                        $planCourseIds[$pid][] = $cid;
                    }
                }

                foreach ($planCourseIds as $pid => $ids) {
                    $ids = array_values(array_unique(array_map('intval', $ids)));
                    sort($ids);
                    $planCourseIds[$pid] = $ids;
                }
            }
        }

        $languageData = [];

        foreach ($languages as $language) {
            $langCode = $language->code;

            // Filter courses for this language
            $langCourses = $courses->where('major', $langCode)->map(function ($course) {
                return [
                    "id" => (int)$course->course_id,
                    "name" => $course->title,
                    "price" => (int)$course->fee,
                    "priceLabel" => number_format($course->fee) . " kyats",
                    "blueMark" => true,
                    "remark" => "",
                    "isFree" => $course->fee <= 0
                ];
            })->values();

            // Filter package plans for this language
            $langPlans = $packagePlans->where('major', $langCode)->map(function ($plan) use ($planCourseIds) {
                $courseIds = $planCourseIds[$plan->id] ?? ($plan->courses ?? []);
                return [
                    "id" => (int)$plan->id,
                    "name" => $plan->name,
                    "price" => (int)$plan->price,
                    "priceLabel" => number_format($plan->price) . " kyats",
                    "blueMark" => true,
                    "remark" => $plan->description,
                    "tier" => $this->getTierByName($plan->name),
                    "color" => $this->getColorByTier($this->getTierByName($plan->name)),
                    "savings" => "", // Optional: calculate based on individual course prices if needed
                    "courses" => $courseIds // array of course IDs
                ];
            })->values();

            $languageData[] = [
                "id" => $langCode,
                "name" => $language->display_name . " Language",
                "icon" => $this->getLanguageIcon($langCode),
                "courses" => $langCourses,
                "bundlePlans" => $langPlans
            ];
        }

        $formattedPaymentMethods = $paymentMethods->map(function ($method) {
            return [
                "name" => $method->name,
                "accountName" => $method->account_name,
                "accountNumber" => $method->account_number,
                "logo" => $method->logo
            ];
        });

        $data = [
            "languages" => $languageData,
            "paymentMethods" => $formattedPaymentMethods,
            "purchasedCoursesByMajor" => $purchasedCoursesByMajor,
            "contact" => [
                "phone" => ["09 250 10 99 68", "09 40 30 88 566"],
                "facebook" => "https://www.facebook.com/calamuseducation",
                "viber" => "viber://chat?number=959250109968"
            ]
        ];

        return $this->successResponse($data);
    }

    private function getPurchasedCourseIdsByMajor(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $result = [];

        if (Schema::hasTable('payments')) {
            $payments = Payment::query()
                ->where('user_id', $userId)
                ->get(['major', 'courses', 'activated']);

            foreach ($payments as $payment) {
                $major = strtolower(trim((string) ($payment->major ?? '')));
                if ($major === '') {
                    continue;
                }

                $courseIds = collect($payment->courses ?? [])
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn ($value) => $value > 0)
                    ->unique()
                    ->values()
                    ->all();

                if ($courseIds === []) {
                    continue;
                }

                if (!array_key_exists($major, $result)) {
                    $result[$major] = [];
                }

                $result[$major] = array_values(array_unique(array_merge($result[$major], $courseIds)));
            }
        }

        if (Schema::hasTable('vipusers')) {
            $hasVipUserId = Schema::hasColumn('vipusers', 'user_id');
            $hasVipPhone = Schema::hasColumn('vipusers', 'phone');
            $vipUserColumn = $hasVipUserId ? 'user_id' : ($hasVipPhone ? 'phone' : null);
            if ($vipUserColumn) {
                $hasVipCourseId = Schema::hasColumn('vipusers', 'course_id');
                $hasVipCourse = Schema::hasColumn('vipusers', 'course');
                $vipCourseColumn = $hasVipCourseId ? 'course_id' : ($hasVipCourse ? 'course' : null);

                if ($vipCourseColumn) {
                    $hasVipMajor = Schema::hasColumn('vipusers', 'major');

                    if ($hasVipMajor) {
                        $rows = DB::table('vipusers')
                            ->where($vipUserColumn, $userId)
                            ->get([$vipCourseColumn . ' as course_id', 'major']);

                        foreach ($rows as $row) {
                            $major = strtolower(trim((string) ($row->major ?? '')));
                            $courseId = (int) ($row->course_id ?? 0);
                            if ($major === '' || $courseId <= 0) {
                                continue;
                            }
                            if (!array_key_exists($major, $result)) {
                                $result[$major] = [];
                            }
                            $result[$major][] = $courseId;
                        }
                    } elseif (Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_id') && Schema::hasColumn('courses', 'major')) {
                        $rows = DB::table('vipusers')
                            ->leftJoin('courses', 'vipusers.' . $vipCourseColumn, '=', 'courses.course_id')
                            ->where('vipusers.' . $vipUserColumn, $userId)
                            ->get(['vipusers.' . $vipCourseColumn . ' as course_id', 'courses.major as major']);

                        foreach ($rows as $row) {
                            $major = strtolower(trim((string) ($row->major ?? '')));
                            $courseId = (int) ($row->course_id ?? 0);
                            if ($major === '' || $courseId <= 0) {
                                continue;
                            }
                            if (!array_key_exists($major, $result)) {
                                $result[$major] = [];
                            }
                            $result[$major][] = $courseId;
                        }
                    }

                    foreach ($result as $major => $courseIds) {
                        $result[$major] = array_values(array_unique(array_map('intval', $courseIds)));
                    }
                }
            }
        }

        ksort($result);
        foreach ($result as $major => $courseIds) {
            sort($courseIds);
            $result[$major] = $courseIds;
        }

        return $result;
    }

    private function getTierByName($name)
    {
        $name = strtolower($name);
        if (str_contains($name, 'silver')) return 'silver';
        if (str_contains($name, 'gold')) return 'gold';
        if (str_contains($name, 'diamond')) return 'diamond';
        if (str_contains($name, 'ruby')) return 'ruby';
        return 'standard';
    }

    private function getColorByTier($tier)
    {
        return match ($tier) {
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'diamond' => '#b9f2ff',
            'ruby' => '#ffb9b9',
            default => '#E0E0E0',
        };
    }

    private function getLanguageIcon($code)
    {
        return match ($code) {
            'english' => '🇬🇧',
            'korea' => '🇰🇷',
            'chinese' => '🇨🇳',
            'japanese' => '🇯🇵',
            'russian' => '🇷🇺',
            default => '🌐',
        };
    }
}
