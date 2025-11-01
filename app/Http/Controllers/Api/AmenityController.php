<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index(Request $request)
    {
        $query = Amenity::query();

        $amenities = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $amenities
        ], 200);
    }
}
