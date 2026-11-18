<?php

namespace App\Http\Controllers\Generations;

use App\Actions\Generations\ListGenerations;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class ListGenerationsController extends Controller
{
    /**
     * The history page: the signed-in user's own generations, newest first.
     */
    public function __invoke(Request $request, ListGenerations $listGenerations): Response
    {
        return Inertia::render('generations/Index', [
            'generations' => $listGenerations->handle($request->user()),
        ]);
    }
}
