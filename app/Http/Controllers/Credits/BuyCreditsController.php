<?php

namespace App\Http\Controllers\Credits;

use App\Actions\Credits\BuyCredits;
use App\Http\Controllers\Controller;
use App\Http\Requests\Credits\BuyCreditsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;

#[Middleware('auth')]
class BuyCreditsController extends Controller
{
    /**
     * Buys credits and returns the user to the page they were on, Credits or Generate.
     */
    public function __invoke(BuyCreditsRequest $request, BuyCredits $buyCredits): RedirectResponse
    {
        $amount = $buyCredits->handle($request->user(), $request->integer('amount'));

        Inertia::flash('purchase', "Added {$amount->forUser()} to your balance");

        return back();
    }
}
