<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class TikTokFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:120'],
            'types' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:PROCESSING,COMPLETED,SUCCESS,FAILED,REVERSED,SETTLED,UNKNOWN'],
            'source' => ['nullable', 'in:account,statements'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
