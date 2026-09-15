<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\TourEnquiry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    // Staff dashboard: list all enquiries with the tour name
    public function index()
    {
        $enquiries = TourEnquiry::with('tour')->get();
        $result = [];
        foreach ($enquiries as $enquiry) {
            $result[] = [
                'id' => $enquiry->id,
                'name' => $enquiry->name,
                'email' => $enquiry->email,
                'tour_name' => $enquiry->tour->name,
                'status' => $enquiry->status,
            ];
        }

        return response()->json($result);
    }

    // Public enquiry form submission
    public function store(StoreEnquiryRequest $request)
    {
        $enquiry = TourEnquiry::create([
            ...$request->validated(),
            'status' => TourEnquiry::STATUS_NEW,
        ]);

        return response()->json($enquiry, 201);
    }

    public function updateStatus(Request $request, TourEnquiry $enquiry)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(TourEnquiry::STATUSES)],
        ]);

        if (! $enquiry->canTransitionTo($validated['status'])) {
            return response()->json([
                'message' => "Cannot transition status from \"{$enquiry->status}\" to \"{$validated['status']}\".",
            ], 422);
        }

        $enquiry->update(['status' => $validated['status']]);

        return response()->json($enquiry);
    }
}
