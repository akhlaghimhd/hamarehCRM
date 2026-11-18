<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\NumberSequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Invoice extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const IDENTITY_OFFICIAL = 'official';
    public const IDENTITY_UNOFFICIAL = 'unofficial';

    protected $fillable = [
        'company_id',
        'order_id',
        'customer_id',
        'owner_user_id',
        'number',
        'title',
        'status',
        'identity_type',
        'company_financial_identity_id',
        'company_bank_account_id',
        'issuer_snapshot',
        'bank_account_snapshot',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'currency',
        'issued_on',
        'due_on',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'issuer_snapshot' => 'array',
            'bank_account_snapshot' => 'array',
            'subtotal' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'tax_amount' => 'decimal:0',
            'total' => 'decimal:0',
            'paid_amount' => 'decimal:0',
            'issued_on' => 'date',
            'due_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice): void {
            $hasActivePayment = $invoice->payments()
                ->whereNull('voided_at')
                ->exists();

            if ($hasActivePayment) {
                throw new InvalidArgumentException('فاکتور دارای پرداخت فعال قابل حذف نیست.');
            }
        });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ISSUED => 'صادر شده',
            self::STATUS_PARTIAL => 'تسویه جزئی',
            self::STATUS_PAID => 'تسویه‌شده',
            self::STATUS_CANCELLED => 'باطل',
        ];
    }

    public static function identityTypeOptions(): array
    {
        return [
            self::IDENTITY_OFFICIAL => 'رسمی',
            self::IDENTITY_UNOFFICIAL => 'غیررسمی',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function financialIdentity(): BelongsTo
    {
        return $this->belongsTo(CompanyFinancialIdentity::class, 'company_financial_identity_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_on');
    }

    public function balance(): float
    {
        return max(0, (float) $this->total - (float) $this->paid_amount);
    }

    public function canReceivePayment(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_PARTIAL], true)
            && $this->balance() > 0;
    }

    public static function nextNumber(int $companyId): string
    {
        $year = now()->format('Y');

        return app(NumberSequence::class)->next($companyId, 'invoice', 'INV-'.$year.'-');
    }

    public static function fromOrder(
        Order $order,
        string $identityType,
        ?CompanyBankAccount $bankAccount = null,
        ?CompanyFinancialIdentity $identity = null,
    ): self {
        if (! in_array($identityType, [self::IDENTITY_OFFICIAL, self::IDENTITY_UNOFFICIAL], true)) {
            throw new InvalidArgumentException('نوع هویت مالی نامعتبر است.');
        }

        if (in_array($order->status, [Order::STATUS_CANCELLED], true)) {
            throw new InvalidArgumentException('از سفارش لغو شده نمی‌توان فاکتور صادر کرد.');
        }

        if (! in_array($order->status, [Order::STATUS_CONFIRMED, Order::STATUS_IN_FULFILLMENT, Order::STATUS_COMPLETED], true)) {
            throw new InvalidArgumentException('فقط سفارش تأییدشده (یا بعد از آن) قابل صدور فاکتور است.');
        }

        return DB::transaction(function () use ($order, $identityType, $bankAccount, $identity) {
            $identity ??= CompanyFinancialIdentity::query()
                ->where('company_id', $order->company_id)
                ->where('type', $identityType)
                ->where('is_active', true)
                ->first();

            if (! $identity) {
                throw new InvalidArgumentException('هویت مالی فعال برای این نوع یافت نشد.');
            }

            if ((int) $identity->company_id !== (int) $order->company_id) {
                throw new InvalidArgumentException('هویت مالی متعلق به شرکت سفارش نیست.');
            }

            if ($identity->type !== $identityType) {
                throw new InvalidArgumentException('نوع هویت مالی با انتخاب سازگار نیست.');
            }

            if ($bankAccount) {
                if ((int) $bankAccount->company_financial_identity_id !== (int) $identity->id) {
                    throw new InvalidArgumentException('حساب بانکی باید متعلق به همان هویت مالی باشد.');
                }
                if (! $bankAccount->is_active) {
                    throw new InvalidArgumentException('حساب بانکی غیرفعال است.');
                }
            } else {
                $bankAccount = CompanyBankAccount::query()
                    ->where('company_financial_identity_id', $identity->id)
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();
            }

            $invoice = new self([
                'company_id' => $order->company_id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'owner_user_id' => $order->owner_user_id,
                'number' => self::nextNumber((int) $order->company_id),
                'title' => 'فاکتور '.$order->number,
                'status' => self::STATUS_ISSUED,
                'identity_type' => $identityType,
                'company_financial_identity_id' => $identity->id,
                'company_bank_account_id' => $bankAccount?->id,
                'issuer_snapshot' => [
                    'identity_id' => $identity->id,
                    'type' => $identity->type,
                    'version' => $identity->version,
                    'legal_name' => $identity->legal_name,
                    'national_id' => $identity->national_id,
                    'economic_code' => $identity->economic_code,
                    'registration_number' => $identity->registration_number,
                    'phone' => $identity->phone,
                    'email' => $identity->email,
                    'address' => $identity->address,
                ],
                'bank_account_snapshot' => $bankAccount ? [
                    'bank_account_id' => $bankAccount->id,
                    'version' => $bankAccount->version,
                    'label' => $bankAccount->label,
                    'account_holder' => $bankAccount->account_holder,
                    'bank_name' => $bankAccount->bank_name,
                    'account_number' => $bankAccount->account_number,
                    'iban' => $bankAccount->iban,
                    'card_number' => $bankAccount->card_number,
                    'branch' => $bankAccount->branch,
                ] : null,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total' => $order->total,
                'paid_amount' => 0,
                'currency' => $order->currency ?: 'IRR',
                'issued_on' => now()->toDateString(),
                'notes' => $order->notes,
            ]);
            $invoice->save();

            return $invoice->fresh(['customer', 'order']);
        });
    }

    public function refreshPaymentStatus(): self
    {
        $paid = (float) $this->payments()
            ->whereNull('voided_at')
            ->sum('amount');

        $this->paid_amount = $paid;

        if ($this->status === self::STATUS_CANCELLED) {
            $this->save();

            return $this;
        }

        if ($paid <= 0) {
            $this->status = self::STATUS_ISSUED;
        } elseif ($paid + 0.00001 >= (float) $this->total) {
            $this->status = self::STATUS_PAID;
            $this->paid_amount = (float) $this->total;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        if ($this->status === self::STATUS_CANCELLED) {
            throw new InvalidArgumentException('فاکتور قبلاً باطل شده است.');
        }

        if ((float) $this->paid_amount > 0) {
            throw new InvalidArgumentException('فاکتور دارای پرداخت قابل ابطال مستقیم نیست؛ ابتدا پرداخت‌ها را باطل کنید.');
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();

        if (filled($reason)) {
            $note = trim((string) $this->notes);
            $addition = 'ابطال: '.trim($reason);
            $this->notes = $note === '' ? $addition : $note."\n".$addition;
        }

        $this->save();

        return $this;
    }
}
