<?php

namespace App\Http\Controllers\MiniProgram;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Language;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CertificateController extends Controller
{
    public function show(Request $request)
    {
        $courseId = (int)($request->query('courseId') ?? $request->query('course_id'));
        $userId = $request->query('userId') ?? $request->query('user_id');

        if (!$courseId || !$userId) {
            abort(404, 'Missing course ID or user ID');
        }

        $result = $this->getCertificateData($courseId, $userId);

        if (isset($result['error'])) {
            return view('pages.certificate', ['error' => $result['error']]);
        }

        return view('pages.certificate', $result['view_data']);
    }

    private function getCertificateData($courseId, $userId)
    {
        $learner = Learner::where('user_id', $userId)->first();
        if (!$learner) {
            return ['error' => 'User not found.'];
        }

        // Check if course is completed
        $courseStats = DB::table('courses')
            ->select('courses.course_id', 'courses.lessons_count', 'courses.title as course_title', 'courses.major', 'courses.certificate_title', 'courses.certificate_code')
            ->selectRaw('count(studies.id) as learned')
            ->join('lessons_categories', 'lessons_categories.course_id', '=', 'courses.course_id')
            ->join('lessons', 'lessons.category_id', '=', 'lessons_categories.id')
            ->join('studies', 'studies.lesson_id', '=', 'lessons.id')
            ->where('courses.course_id', $courseId)
            ->where('studies.user_id', $userId)
            ->groupBy('courses.course_id', 'courses.lessons_count', 'courses.title', 'courses.major', 'courses.certificate_title', 'courses.certificate_code')
            ->first();

        if (!$courseStats) {
             return ['error' => 'Access Denied! <br> You need to learn the course completely first.'];
        }

        $lessonCount = (int)$courseStats->lessons_count;
        $learned = (int)$courseStats->learned;

        if ($learned < $lessonCount) {
            return [
                'error' => 'Access Denied! <br> You need to learn the course completely first.'
            ];
        }

        // Get or Create Certificate
        $certificate = Certificate::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->first();

        if (!$certificate) {
            $certificate = new Certificate();
            $certificate->course_id = $courseId;
            $certificate->user_id = $userId;
            $certificate->date = date('Y-m-d');
            $certificate->save();
        }

        // Encode ID (Using base64 as placeholder for legacy DigitEncoder)
        $certificateIdEncoded = base64_encode($certificate->id);

        $major = $courseStats->major;
        $language = Language::where('code', $major)->first();
        $platform = $language ? $language->certificate_title : (($major == "english") ? "English for Myanmar" : "Korean for Myanmar");
        $seal = $language ? $language->seal : (($major == "english") ? "assets/images/ee_certificate_seal.png" : "assets/images/ko_certificate_seal.png");

        // Prepare view data to match certificate.blade.php needs
        $viewData = [
            'error' => false,
            'course_id' => $courseId,
            'user_id' => $userId,
            'certificate_id' => $certificateIdEncoded,
            'certificate' => [
                'id' => $certificate->id,
                'date' => $certificate->date,
                'formatted_date' => $this->formatIssuedDate($certificate->date),
            ],
            'user' => [
                'learner_name' => $learner->learner_name,
            ],
            'course' => [
                'certificate_title' => $courseStats->certificate_title,
                'certificate_code' => $courseStats->certificate_code,
            ],
            'platform' => $platform,
            'certificate_seal' => $seal,
            'certificate_bg' => "https://www.calamuseducation.com/uploads/icons/certificate/certificate_background.png"
        ];

        return [
            'view_data' => $viewData
        ];
    }

    private function formatIssuedDate($certificate_date)
    {
        $date = new \DateTime($certificate_date);
        $year = $date->format('Y');
        $month = $date->format('M');
        $day = (int)$date->format('d');

        $suffix = 'th';
        if (!in_array(($day % 100), [11, 12, 13])) {
            switch ($day % 10) {
                case 1:  $suffix = 'st'; break;
                case 2:  $suffix = 'nd'; break;
                case 3:  $suffix = 'rd'; break;
            }
        }
        
        return "$month $day$suffix, $year";
    }

    public function imageProxy(Request $request)
    {
        $url = trim((string) $request->query('url', ''));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            abort(422, 'Invalid url');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            abort(422, 'Unsupported scheme');
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $allowedHosts = [
            'www.calamuseducation.com',
            'calamuseducation.com',
        ];
        if (!in_array($host, $allowedHosts, true)) {
            abort(403, 'Host not allowed');
        }

        $response = Http::timeout(20)->get($url);
        if (!$response->ok()) {
            abort(404, 'Image not found');
        }

        $contentType = strtolower((string) $response->header('Content-Type', ''));
        if ($contentType === '' || !str_starts_with($contentType, 'image/')) {
            abort(422, 'URL is not an image');
        }

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
