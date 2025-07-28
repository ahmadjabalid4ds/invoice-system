<?php

namespace App\Models;

use TomatoPHP\FilamentInvoices\Models\Invoice as BaseInvoice;
use App\Models\Payment; // or wherever your related model is

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
        'token'
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // You can still override other things here too
}
