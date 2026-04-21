<?php

namespace App\Http\Controllers;

use App\Services\WordOfDayService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class WordOfDayController extends Controller
{
    use ApiResponse;

    private WordOfDayService $wordOfDayService;

    public function __construct(WordOfDayService $wordOfDayService)
    {
        $this->wordOfDayService = $wordOfDayService;
    }

    public function get(Request $request)
    {
        try {
            $major = (string)$request->query('major', '');
            $timezone = $request->query('tz');

            $word = $this->wordOfDayService->getDailyWord($major, $timezone);

            return $this->successResponse($word);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Throwable $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }
}
