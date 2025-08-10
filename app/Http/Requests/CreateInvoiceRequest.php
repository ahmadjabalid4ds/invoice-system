<?php

namespace App\Http\Requests;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'name'                 => ['required', 'string', 'max:255'],
            'wa_number'            => ['nullable', 'string', 'max:255'],
            'to_wa_number'         => ['nullable', 'string', 'max:255'],
            'address'              => ['nullable', 'string', 'max:255'],
            'type'                 => ['nullable', 'string', 'in:push,pull'],
            'total'                => ['required', 'numeric', 'min:0'],
            'discount'             => ['required', 'numeric', 'min:0'],
            'shipping'             => ['required', 'numeric', 'min:0'],
            'vat'                  => ['required', 'numeric', 'min:0'],
            'date'                 => ['nullable', 'date'],
            'due_date'             => ['nullable', 'date'],
            'notes'                => ['nullable', 'string'],
            'qty'                  => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $senderUser = User::query()->with('tenant')->where('phone', $data['wa_number'])->first();
        $toUser = User::query()->with('tenant')->where('phone', $data['to_wa_number'])->first();
        $data['user_id'] = $senderUser?->id ?? auth()->user()?->id;
        $data['channel'] = "whatsapp";
        $data['uuid'] = (string) Str::uuid();
        if ($senderUser && $senderUser->tenant_id){
            $data['from_id'] = $senderUser->id;
            $data['tenant_id'] = $senderUser->tenant->id;
            $data['bank_account'] = $senderUser->tenant->bank_name;
            $data['bank_account_owner'] = $senderUser->tenant->bank_holder_name;
            $data['bank_name'] = $senderUser->tenant->bank_name;
            $data['bank_iban'] = $senderUser->tenant->iban;
            $data['currency_id'] = DB::table('currencies')->where('iso', "SAR")->first()->id;
            $data['from_type'] = "App\Models\Tenant";
            $data['for_type'] = "App\Models\Customer";
            $data['vat'] = SystemSetting::latest()->first()?->vat_percentage ?? 15;
        }

        if ($toUser){
            $data['for_id'] = $toUser->id;
        }

        return $data;
    }
}
