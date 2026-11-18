<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\BuildOperatorReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ShowOperatorReportRequest;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('auth')]
#[Authorize('view-operator-report')]
class ShowOperatorReportController extends Controller
{
    /**
     * The operator report, for operators only (ADR 0016).
     */
    public function __invoke(ShowOperatorReportRequest $request, BuildOperatorReport $buildOperatorReport): Response
    {
        return Inertia::render('reports/Index', [
            'report' => $buildOperatorReport->handle($request->start(), $request->end()),
        ]);
    }
}
