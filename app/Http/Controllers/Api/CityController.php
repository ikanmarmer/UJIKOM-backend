<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    // Get all cities
    public function index(Request $request)
    {
        $query = City::withCount('hotels');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%");
            });
        }

        $cities = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $cities
        ], 200);
    }

    // Get city by slug
    public function show($slug)
    {
        $city = City::where('slug', $slug)
            ->withCount('hotels')
            ->first();

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $city
        ], 200);
    }

    // Search cities and hotels
    public function search(Request $request)
    {
        $search = $request->input('query', '');

        $cities = City::where('name', 'like', "%{$search}%")
            ->orWhere('province', 'like', "%{$search}%")
            ->withCount('hotels')
            ->limit(5)
            ->get();

        $hotels = \App\Models\Hotel::where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->with('city')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'cities' => $cities,
                'hotels' => $hotels,
            ]
        ], 200);
    }
}
