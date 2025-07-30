<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = ['tenant_id', 'plan_id', 'used_invoices', 'starts_at', 'ends_at'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function hasInvoiceQuota(): bool
    {
        return $this->used_invoices < $this->plan->invoice_limit;
    }
}
