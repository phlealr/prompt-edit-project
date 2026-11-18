<?php

namespace App\Http\Requests\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowOperatorReportRequest extends FormRequest
{
    /** Without dates, the report covers the last 30 UTC days ending today (ADR 0030). */
    private const int DEFAULT_DAYS = 30;

    /**
     * Missing dates get their defaults first, so a start after the default end is still rejected.
     */
    protected function prepareForValidation(): void
    {
        $end = $this->query('end') ?: CarbonImmutable::today('UTC')->toDateString();
        $start = $this->query('start') ?: CarbonImmutable::parse((string) $end, 'UTC')->subDays(self::DEFAULT_DAYS - 1)->toDateString();

        $this->merge(['start' => $start, 'end' => $end]);
    }

    /**
     * Any range whose start is not after its end, with no maximum (ADR 0030).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['end.after_or_equal' => 'The start date must be on or before the end date.'];
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('start')->value(), 'UTC');
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('end')->value(), 'UTC');
    }
}
