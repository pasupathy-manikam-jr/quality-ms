<?php

namespace Tests\Feature\Foundation;

use App\Support\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_count_up_per_prefix_and_restart_each_year(): void
    {
        Carbon::setTestNow('2026-12-31 23:00');

        $this->assertSame('NCR-2026-0001', Sequence::next('NCR'));
        $this->assertSame('NCR-2026-0002', Sequence::next('NCR'));
        $this->assertSame('CAPA-2026-0001', Sequence::next('CAPA'));

        Carbon::setTestNow('2027-01-01 08:00');

        $this->assertSame('NCR-2027-0001', Sequence::next('NCR'));
    }
}
