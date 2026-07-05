<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequisitionReviewRequest;
use App\Models\Requisition;
use App\Services\RequisitionService;
use Illuminate\Http\RedirectResponse;

class RequisitionReviewController extends Controller
{
    public function __invoke(RequisitionReviewRequest $request, Requisition $requisition, RequisitionService $service): RedirectResponse
    {
        $service->review(
            $requisition,
            $request->string('status')->toString(),
            $request->validated('approved_amount') ? (float) $request->validated('approved_amount') : null,
            $request->validated('admin_note'),
            $request->user(),
        );

        return back()->with('success', 'Requisition reviewed.');
    }
}
