<?php

namespace App\Http\Requests\Generations;

use App\Enums\Quality;
use App\Enums\Size;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerationRequest extends FormRequest
{
    /**
     * Our generation options, mirroring the provider's documented limits (ADR 0023).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:4000'],
            'size' => ['required', Rule::enum(Size::class)],
            'quality' => ['required', Rule::enum(Quality::class)],
            'reference_images' => ['array', 'max:4'],
            'reference_images.*' => ['required', 'string', 'url:http,https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reference_images.*' => 'reference image'];
    }

    /**
     * @return list<string>
     */
    public function referenceImages(): array
    {
        /** @var list<string> */
        return $this->validated('reference_images', []);
    }
}
