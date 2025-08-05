<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CreateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'for_type'             => ['required', 'string'],
            'for_id'               => ['required', 'integer'],
            'from_type'            => ['required', 'string'],
            'from_id'              => ['required', 'integer'],
            'branch_id'            => ['nullable', 'exists:branches,id'],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'name'                 => ['required', 'string', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:255'],
            'address'              => ['nullable', 'string', 'max:255'],
            'type'                 => ['nullable', 'string', 'in:push,pull'],
            'total'                => ['required', 'numeric', 'min:0'],
            'discount'             => ['required', 'numeric', 'min:0'],
            'shipping'             => ['required', 'numeric', 'min:0'],
            'vat'                  => ['required', 'numeric', 'min:0'],
            'paid'                 => ['required', 'numeric', 'min:0'],
            'date'                 => ['nullable', 'date'],
            'due_date'             => ['nullable', 'date'],
            'is_offer'             => ['nullable', 'boolean'],
            'insert_in_to_inventory' => ['nullable', 'boolean'],
            'send_email'           => ['nullable', 'boolean'],
            'currency_id'          => ['nullable', 'exists:currencies,id'],
            'is_bank_transfer'     => ['nullable', 'boolean'],
            'bank_account'         => ['nullable', 'string', 'max:255'],
            'bank_account_owner'   => ['nullable', 'string', 'max:255'],
            'bank_iban'            => ['nullable', 'string', 'max:255'],
            'bank_swift'           => ['nullable', 'string', 'max:255'],
            'bank_address'         => ['nullable', 'string', 'max:255'],
            'bank_branch'          => ['nullable', 'string', 'max:255'],
            'bank_name'            => ['nullable', 'string', 'max:255'],
            'bank_city'            => ['nullable', 'string', 'max:255'],
            'bank_country'         => ['nullable', 'string', 'max:255'],
            'notes'                => ['nullable', 'string'],
            'channel'                => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['uuid'] = (string) Str::uuid();
        $data['user_id'] = auth()->user()->id;

        return $data;
    }
}
