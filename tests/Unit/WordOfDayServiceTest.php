<?php

namespace Tests\Unit;

use App\Services\WordOfDayService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class WordOfDayServiceTest extends TestCase
{
    public function test_stable_index_is_deterministic_for_same_input()
    {
        $service = new WordOfDayService();

        $first = $service->stableIndex('english', '2026-03-12', 50);
        $second = $service->stableIndex('english', '2026-03-12', 50);

        $this->assertSame($first, $second);
    }

    public function test_resolve_local_date_changes_by_timezone()
    {
        $service = new WordOfDayService();

        CarbonImmutable::setTestNow('2026-03-12 23:30:00 UTC');

        $this->assertSame('2026-03-13', $service->resolveLocalDate('Asia/Yangon'));
        $this->assertSame('2026-03-12', $service->resolveLocalDate('UTC'));
    }

    public function test_resolve_timezone_rejects_invalid_timezone()
    {
        $service = new WordOfDayService();

        $this->expectException(InvalidArgumentException::class);
        $service->resolveTimezone('Invalid/Timezone');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
