<?php

namespace Tests\Unit;

use App\Http\Middleware\NormalizePersianDigits;
use Illuminate\Http\Request;
use Tests\TestCase;

class NormalizePersianDigitsTest extends TestCase
{
    public function test_persian_and_arabic_digits_are_normalized_in_livewire_updates_without_mutating_signed_snapshot(): void
    {
        $snapshot = '{"data":{"amount":"۱۲۳"}}';
        $request = Request::create('/livewire/update', 'POST', [
            'components' => [[
                'snapshot' => $snapshot,
                'checksum' => 'signed-checksum',
                'updates' => [
                    'amount' => '۱۲۳٬۴۵۶',
                    'quantity' => '١٢٣',
                ],
            ]],
        ]);

        (new NormalizePersianDigits())->handle($request, fn () => response('ok'));

        $this->assertSame('123٬456', $request->input('components.0.updates.amount'));
        $this->assertSame('123', $request->input('components.0.updates.quantity'));
        $this->assertSame($snapshot, $request->input('components.0.snapshot'));
        $this->assertSame('signed-checksum', $request->input('components.0.checksum'));
    }
}
