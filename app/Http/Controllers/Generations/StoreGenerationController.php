<?php

namespace App\Http\Controllers\Generations;

use App\Actions\Generations\SubmitGeneration;
use App\Enums\Quality;
use App\Enums\Size;
use App\Exceptions\NotEnoughCredits;
use App\Http\Controllers\Controller;
use App\Http\Requests\Generations\StoreGenerationRequest;
use App\ImageProviders\Exceptions\PricingUnavailable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;

#[Middleware('auth')]
class StoreGenerationController extends Controller
{
    public function __invoke(StoreGenerationRequest $request, SubmitGeneration $submitGeneration): RedirectResponse
    {
        try {
            $submitGeneration->handle(
                $request->user(),
                $request->string('prompt')->value(),
                Size::from($request->string('size')->value()),
                Quality::from($request->string('quality')->value()),
                $request->referenceImages(),
            );
        } catch (NotEnoughCredits $exception) {
            return back()->withErrors(['generation' => $exception->getMessage()]);
        } catch (PricingUnavailable) {
            return back()->withErrors(['price' => "We couldn't confirm the price, so nothing was submitted."]);
        }

        Inertia::flash('status', 'Generation submitted');

        return to_route('history');
    }
}
