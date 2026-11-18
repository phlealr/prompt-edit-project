<?php

namespace App\Http\Controllers\Credits;

use App\Actions\Credits\CalculateBalance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class ShowCreditsController extends Controller
{
    /**
     * The Credits page: the balance and the buy form. The activity comes with balances 04.
     */
    public function __invoke(Request $request, CalculateBalance $calculateBalance): Response
    {
        return Inertia::render('credits/Index', [
            'balance' => $calculateBalance->handle($request->user()),
        ]);
    }
}
