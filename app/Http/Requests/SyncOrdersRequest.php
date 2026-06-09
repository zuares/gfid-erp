<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_from' => ['required', 'integer'],
            'time_to'   => ['required', 'integer'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
