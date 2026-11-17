<?php

namespace App\Http\Controllers\Generations;

use App\Enums\Quality;
use App\Enums\Size;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
class CreateGenerationController extends Controller
{
    /**
     * The generate page, offering every size and quality (ADR 0023).
     */
    public function __invoke(): Response
    {
        return Inertia::render('generations/Create', [
            'sizes' => array_column(Size::cases(), 'value'),
            'qualities' => array_column(Quality::cases(), 'value'),
            'defaults' => ['size' => Size::Square->value, 'quality' => Quality::Medium->value],
        ]);
    }
}
