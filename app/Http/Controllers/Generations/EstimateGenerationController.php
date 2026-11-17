<?php

namespace App\Http\Controllers\Generations;

use App\Actions\Generations\EstimateGeneration;
use App\Enums\Quality;
use App\Enums\Size;
use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\EstimateGenerationRequest;
use App\ImageProviders\Exceptions\PricingUnavailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class EstimateGenerationController extends Controller
{
    /**
     * The estimate as JSON for the generate form (ADR 0033).
     */
    public function __invoke(EstimateGenerationRequest $request, EstimateGeneration $estimateGeneration): JsonResponse
    {
        try {
            $estimate = $estimateGeneration->handle(
                (string) $request->validated('prompt'),
                Size::from($request->string('size')->value()),
                Quality::from($request->string('quality')->value()),
                $request->referenceImages(),
            );
        } catch (PricingUnavailable) {
            return response()->json(['message' => 'Price unavailable right now'], 503);
        }

        return response()->json([
            'minimum' => $estimate->minimum,
            'maximum' => $estimate->maximum,
        ]);
    }
}
