<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
#[Authorize('view-operator-report')]
class ShowOperatorReportController extends Controller
{
    /**
     * The operator report, for operators only (ADR 0016). Its figures come with operator-report 02.
     */
    public function __invoke(): Response
    {
        return Inertia::render('reports/Index');
    }
}
