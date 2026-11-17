<?php

namespace App\Http\Requests\Generations;

use App\Enums\Quality;
use App\Enums\Size;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstimateGenerationRequest extends FormRequest
{
    /**
     * The form as the user is typing it: the prompt may still be empty and a reference image field
     * may still be blank. Only the number of reference images matters to the estimate.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['nullable', 'string', 'max:4000'],
            'size' => ['required', Rule::enum(Size::class)],
            'quality' => ['required', Rule::enum(Quality::class)],
            'reference_images' => ['array', 'max:4'],
            'reference_images.*' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<string>
     */
    public function referenceImages(): array
    {
        /** @var list<string|null> $referenceImages */
        $referenceImages = $this->validated('reference_images', []);

        return array_map(fn (?string $url): string => (string) $url, $referenceImages);
    }
}
