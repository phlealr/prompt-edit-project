<?php

namespace App\Http\Controllers\Credits;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class ShowCreditsController extends Controller
{
    /**
     * The Credits page: the balance (shared with every page) and the buy form. The activity comes
     * with balances 04.
     */
    public function __invoke(): Response
    {
        return Inertia::render('credits/Index');
    }
}
