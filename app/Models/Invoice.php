<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use TomatoPHP\FilamentInvoices\Models\Invoice as BaseInvoice;

class Invoice extends BaseInvoice
{
    protected $fillable = [
        'bank_account',
        'bank_account_owner',
        'bank_iban',
        'bank_swift',
        'bank_address',
        'bank_branch',
        'bank_name',
        'bank_city',
        'bank_country',
        'is_bank_transfer',
        'currency_id',
        'from_id',
        'from_type',
        'for_id',
        'for_type',
        'order_id',
        'user_id',
        'category_id',
        'uuid',
        'name',
        'phone',
        'address',
        'type',
        'status',
        'total',
        'discount',
        'vat',
        'paid',
        'date',
        'due_date',
        'is_activated',
        'is_offer',
        'send_email',
        'shipping',
        'notes',
        'created_at',
        'updated_at',
        'token',
        'zatca_qr',
        'channel',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()?->tenant_id;
                $model->currency_id = DB::table('currencies')->where('iso', "SAR")->first()->id;
                $model->from_type = "App\Models\Tenant";
                $model->for_type = "App\Models\Customer";
                $model->vat = config('services.invoice.vat_percentage');
            }
        });
    }

    public function invoicesItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function canBePaid(): bool
    {
        return !in_array($this->status, [PaymentStatusEnum::PAID->value, PaymentStatusEnum::PROCESSING->value, PaymentStatusEnum::PENDING->value]);
    }


    public function isPaid(): bool
    {
        return $this->status == PaymentStatusEnum::PAID->value;
    }

    public function isProcessing(): bool
    {
        return $this->status == PaymentStatusEnum::PROCESSING->value;
    }

    public function markAsProcessing(string $paymentId = null): bool
    {
        if (!$this->canBePaid()) {
            return false;
        }

        return $this->update([
            'status' => PaymentStatusEnum::PROCESSING->value,
            'updated_at' => now()
        ]);
    }

    public function markAsPaid(array $paymentData = []): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        $updateData = [
            'status' => PaymentStatusEnum::PAID->value,
            'updated_at' => now()
        ];

        if (!empty($paymentData['bank_account'])) {
            $updateData['bank_account'] = $paymentData['bank_account'];
        }
        if (!empty($paymentData['bank_account_owner'])) {
            $updateData['bank_account_owner'] = $paymentData['bank_account_owner'];
        }
        if (!empty($paymentData['bank_iban'])) {
            $updateData['bank_iban'] = $paymentData['bank_iban'];
        }
        if (!empty($paymentData['bank_swift'])) {
            $updateData['bank_swift'] = $paymentData['bank_swift'];
        }
        if (!empty($paymentData['bank_address'])) {
            $updateData['bank_address'] = $paymentData['bank_address'];
        }
        if (!empty($paymentData['bank_branch'])) {
            $updateData['bank_branch'] = $paymentData['bank_branch'];
        }
        if (!empty($paymentData['bank_name'])) {
            $updateData['bank_name'] = $paymentData['bank_name'];
        }
        if (!empty($paymentData['bank_city'])) {
            $updateData['bank_city'] = $paymentData['bank_city'];
        }
        if (!empty($paymentData['bank_country'])) {
            $updateData['bank_country'] = $paymentData['bank_country'];
        }

        return $this->update($updateData);
    }

    public function markAsFailed(): bool
    {
        $updateData = [
            'status' => PaymentStatusEnum::FAILED->value,
            'updated_at' => now()
        ];

        return $this->update($updateData);
    }

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatusEnum::PAID->value);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatusEnum::PENDING->value);
    }
}
