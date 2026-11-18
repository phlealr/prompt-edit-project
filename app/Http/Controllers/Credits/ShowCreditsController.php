<?php

namespace App\Http\Controllers\Credits;

use App\Actions\Credits\SummarizeCredits;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class ShowCreditsController extends Controller
{
    /**
     * The Credits page: the balance (shared with every page), the held amount, the buy form and
     * the activity.
     */
    public function __invoke(Request $request, SummarizeCredits $summarizeCredits): Response
    {
        return Inertia::render('credits/Index', [
            'summary' => $summarizeCredits->handle($request->user()),
        ]);
    }
}
