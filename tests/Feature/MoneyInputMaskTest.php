<?php

namespace Tests\Feature;

use App\Support\MoneyInput;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Regression test for the rupiah input-masking bug: typing "333" then "3"
 * (meaning to reach "3333") rendered as "3000333". Root cause was passing
 * the intended "0 decimals" as the mask's third argument, which is
 * actually the thousands-separator slot — the integer 0 got string-coerced
 * and spliced into the digit stream at each group boundary.
 *
 * Because the bug only manifests from incremental typing (a single
 * one-shot `$money($input, ...)` call on a complete value never showed
 * it), these tests replay keystrokes one at a time through the real
 * masking algorithm vendored in livewire.js, extracted into
 * tests/js/money-mask-check.mjs. This requires Node, which the project
 * already depends on for building panel assets (npm run build).
 */
class MoneyInputMaskTest extends TestCase
{
    public function test_mask_expression_places_precision_as_the_fourth_argument_not_the_third(): void
    {
        // Locks the exact call shape so the bug (0 passed where the
        // thousands separator belongs) can't silently come back.
        $this->assertSame("\$money(\$input, ',', '.', 0)", (string) MoneyInput::mask());
    }

    public function test_typing_digits_one_at_a_time_produces_the_correctly_grouped_value(): void
    {
        // Exact scenario from the bug report: type 3, 3, 3, then 3 again.
        $result = $this->simulateTyping((string) MoneyInput::mask(), '3333');

        $this->assertSame(['3', '33', '333', '3.333'], $result['steps']);
        $this->assertSame('3.333', $result['final']);
        $this->assertSame('3333', $result['stripped']);
    }

    public function test_typing_past_the_second_thousands_group_stays_correct(): void
    {
        $result = $this->simulateTyping((string) MoneyInput::mask(), '33333');

        $this->assertSame('33.333', $result['final']);
        $this->assertSame('33333', $result['stripped']);
    }

    public function test_the_previous_broken_argument_order_is_demonstrably_wrong(): void
    {
        // Documents why the fix was necessary: the old call site
        // (`$money($input, '.', 0)`) corrupts the value as soon as a
        // fourth digit is typed, exactly as reported.
        $result = $this->simulateTyping("\$money(\$input, '.', 0)", '3333');

        $this->assertNotSame('3.333', $result['final']);
        $this->assertNotSame('3333', $result['stripped']);
    }

    /**
     * @return array{steps: array<int, string>, final: string, stripped: string}
     */
    private function simulateTyping(string $maskExpression, string $digits): array
    {
        $script = base_path('tests/js/money-mask-check.mjs');

        $result = Process::run(['node', $script, $maskExpression, $digits]);

        $this->assertTrue(
            $result->successful(),
            "money-mask-check.mjs failed (node required): {$result->errorOutput()}"
        );

        return json_decode($result->output(), associative: true);
    }
}
