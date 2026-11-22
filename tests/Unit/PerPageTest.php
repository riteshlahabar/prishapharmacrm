<?php

namespace Tests\Unit;

use App\Core\Http\PerPage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The one page-size rule every list endpoint shares. Pure logic, so no
 * application boot — this is the cheapest place to pin the contract
 * `docs/api/v1-common.md` promises.
 */
class PerPageTest extends TestCase
{
    public function test_the_documented_defaults(): void
    {
        $this->assertSame(20, PerPage::DEFAULT);
        $this->assertSame(100, PerPage::MAX);
    }

    /** @return array<string, array{mixed}> */
    public static function unusableValues(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'not a number' => ['abc'],
            'a list' => ['1,2'],
            'an array' => [['10']],
            'zero' => [0],
            'negative' => [-5],
            'negative string' => ['-5'],
        ];
    }

    /**
     * Anything unusable is "the app did not ask", not an error: `per_page=-5`
     * once reached the database as an unlimited query and answered 500.
     */
    #[DataProvider('unusableValues')]
    public function test_an_unusable_value_falls_back_to_the_default(mixed $value): void
    {
        $this->assertSame(PerPage::DEFAULT, PerPage::clamp($value));
    }

    public function test_an_oversized_request_is_capped(): void
    {
        $this->assertSame(PerPage::MAX, PerPage::clamp(99999));
        $this->assertSame(PerPage::MAX, PerPage::clamp(101));
        $this->assertSame(PerPage::MAX, PerPage::clamp(PerPage::MAX));
    }

    public function test_a_usable_size_is_honoured(): void
    {
        $this->assertSame(1, PerPage::clamp(1));
        $this->assertSame(50, PerPage::clamp(50));
        $this->assertSame(50, PerPage::clamp('50'));
        // A numeric string with a fraction truncates rather than erroring.
        $this->assertSame(7, PerPage::clamp('7.9'));
    }

    public function test_a_caller_may_name_its_own_default(): void
    {
        $this->assertSame(25, PerPage::clamp(null, 25));
        $this->assertSame(25, PerPage::clamp('nonsense', 25));
    }
}
