<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\NumberSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Payment extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'recorded_by_user_id',
        'number',
        'amount',
        'currency',
        'method',
        'reference',
        'paid_on',
        'notes',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'paid_on' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');

        return app(NumberSequence::class)->next($companyId, 'payment', 'PAY-'.$year.'-');
    }

    public static function recordForInvoice(Invoice $invoice, array $data, ?User $actor = null): self
    {
        if (! $invoice->canReceivePayment()) {
            throw new InvalidArgumentException('این فاکتور در وضعیت فعلی پرداخت نمی‌پذیرد.');
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new InvalidArgumentException('مبلغ پرداخت باید بزرگ‌تر از صفر باشد.');
        }

        $balance = $invoice->balance();
        if ($amount - $balance > 0.00001) {
            throw new InvalidArgumentException('مبلغ پرداخت از مانده فاکتور بیشتر است.');
        }

        $currency = $data['currency'] ?? $invoice->currency;
        if ($currency !== $invoice->currency) {
            throw new InvalidArgumentException('ارز پرداخت باید با ارز فاکتور یکی باشد.');
        }

        return DB::transaction(function () use ($invoice, $data, $actor, $amount, $currency) {
            $payment = new self([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'recorded_by_user_id' => $actor?->id,
                'number' => self::nextNumber((int) $invoice->company_id),
                'amount' => $amount,
                'currency' => $currency,
                'method' => $data['method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'paid_on' => $data['paid_on'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);
            $payment->save();

            $invoice->refreshPaymentStatus();

            return $payment->fresh(['invoice']);
        });
    }

    public function void(?string $reason = null): self
    {
        if ($this->voided_at) {
            throw new InvalidArgumentException('پرداخت قبلاً باطل شده است.');
        }

        return DB::transaction(function () use ($reason) {
            $this->voided_at = now();
            if (filled($reason)) {
                $note = trim((string) $this->notes);
                $addition = 'ابطال: '.trim($reason);
                $this->notes = $note === '' ? $addition : $note."\n".$addition;
            }
            $this->save();

            $this->invoice?->refreshPaymentStatus();

            return $this;
        });
    }
}
