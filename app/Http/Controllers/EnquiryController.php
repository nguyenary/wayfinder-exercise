<?php

namespace App\Http\Controllers;

use App\Models\TourEnquiry;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    // Dashboard cho nhân viên: liệt kê toàn bộ enquiry kèm tên tour
    public function index()
    {
        $enquiries = TourEnquiry::all();
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

    // Tiếp nhận submit từ form công khai
    public function store(Request $request)
    {
        $enquiry = TourEnquiry::create($request->all());
        return response()->json($enquiry, 201);
    }
}
