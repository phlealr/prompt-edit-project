<?php

namespace App\Http\Requests\Credits;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BuyCreditsRequest extends FormRequest
{
    /**
     * A whole number of dollars from 1 to 1,000 (ADR 0029).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'between:1,1000'],
        ];
    }

    /**
     * One message for every way the amount can be wrong (docs/features/balances.md).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['amount' => 'Enter a whole number of dollars between 1 and 1,000.'];
    }
}
