<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Models\Hotel;
    use App\Models\City;
    use App\Models\RoomCategory;
    use Illuminate\Http\Request;
    use Carbon\Carbon;

    class HotelController extends Controller
    {
        // Get all hotels with filters
        public function index(Request $request)
        {
            $query = Hotel::with(['city', 'roomCategories', 'amenities'])
                ->where('status', 'active');

            // Filter by city
            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            // Filter by city slug
            if ($request->has('city_slug')) {
                $city = City::where('slug', $request->city_slug)->first();
                if ($city) {
                    $query->where('city_id', $city->id);
                }
            }

            // Search by name or location
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('city', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by price range (dari room categories)
            if ($request->has('min_price') || $request->has('max_price')) {
                $query->whereHas('roomCategories', function ($q) use ($request) {
                    if ($request->has('min_price')) {
                        $q->where('price_per_night', '>=', $request->min_price);
                    }
                    if ($request->has('max_price')) {
                        $q->where('price_per_night', '<=', $request->max_price);
                    }
                });
            }

            // Filter by amenities
            if ($request->has('amenities')) {
                $amenities = explode(',', $request->amenities);
                foreach ($amenities as $amenityId) {
                    $query->whereHas('amenities', function ($q) use ($amenityId) {
                        $q->where('amenities.id', $amenityId);
                    });
                }
            }

            // Filter by room type
            if ($request->has('room_type')) {
                $query->whereHas('roomCategories', function ($q) use ($request) {
                    $q->where('type', $request->room_type);
                });
            }

            // Filter by availability (check-in, check-out dates)
            if ($request->has('check_in') && $request->has('check_out')) {
                $checkIn = Carbon::parse($request->check_in);
                $checkOut = Carbon::parse($request->check_out);
                $rooms = $request->input('rooms', 1);

                $query->whereHas('roomCategories', function ($q) use ($checkIn, $checkOut, $rooms) {
                    $q->whereHas('rooms', function ($q2) use ($checkIn, $checkOut, $rooms) {
                        $q2->where('status', 'available')
                            ->whereDoesntHave('bookings', function ($q3) use ($checkIn, $checkOut) {
                                $q3->whereIn('status', ['confirmed', 'active'])
                                    ->where(function ($q4) use ($checkIn, $checkOut) {
                                        $q4->whereBetween('booking_room.check_in_date', [$checkIn, $checkOut])
                                            ->orWhereBetween('booking_room.check_out_date', [$checkIn, $checkOut])
                                            ->orWhere(function ($q5) use ($checkIn, $checkOut) {
                                                $q5->where('booking_room.check_in_date', '<=', $checkIn)
                                                    ->where('booking_room.check_out_date', '>=', $checkOut);
                                            });
                                    });
                            });
                    }, '>=', $rooms);
                });
            }

            // Sorting
            if ($request->has('sort_by')) {
                switch ($request->sort_by) {
                    case 'price_low':
                        $query->join('room_categories', 'hotels.id', '=', 'room_categories.hotel_id')
                            ->select('hotels.*')
                            ->groupBy('hotels.id')
                            ->orderByRaw('MIN(room_categories.price_per_night) ASC');
                        break;
                    case 'price_high':
                        $query->join('room_categories', 'hotels.id', '=', 'room_categories.hotel_id')
                            ->select('hotels.*')
                            ->groupBy('hotels.id')
                            ->orderByRaw('MAX(room_categories.price_per_night) DESC');
                        break;
                    case 'rating':
                        $query->orderBy('average_rating', 'desc');
                        break;
                    default:
                        $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $hotels = $query->paginate($request->input('per_page', 12));

            return response()->json([
                'success' => true,
                'data' => $hotels
            ], 200);
        }

        public function getHotelImages($slug)
        {
            $hotel = Hotel::where('slug', $slug)
                ->where('status', 'active')
                ->first(['id', 'name', 'images']);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hotel not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel_id' => $hotel->id,
                    'hotel_name' => $hotel->name,
                    'images' => $hotel->image_urls, // This uses the accessor from model
                    'total_images' => count($hotel->image_urls)
                ]
            ], 200);
        }

        // Get single hotel detail
        public function show($slug)
        {
            $hotel = Hotel::with([
                'city',
                'owner',
                'roomCategories.amenities',
                'roomCategories.rooms',
                'amenities',
                'reviews.user'
            ])
                ->where('slug', $slug)
                ->where('status', 'active')
                ->first();

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hotel not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $hotel
            ], 200);
        }

        // Get room availability
        public function checkAvailability(Request $request, $hotelId)
        {
            $request->validate([
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'rooms' => 'required|integer|min:1|max:6',
                'guests_per_room' => 'required|integer|min:1|max:3',
            ]);

            $hotel = Hotel::with('roomCategories.rooms')->findOrFail($hotelId);

            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            $availability = [];

            foreach ($hotel->roomCategories as $category) {
                // Gunakan method yang sudah diperbarui
                $availableRooms = $category->getAvailableRoomsCountForPeriod(
                    $checkIn->toDateString(),
                    $checkOut->toDateString()
                );

                // Cek apakah kategori memenuhi syarat
                $hasEnoughRooms = $availableRooms >= $request->rooms;
                $hasEnoughCapacity = $category->max_guests >= $request->guests_per_room;
                $isWithinTotalRooms = $request->rooms <= $category->total_rooms;

                if ($hasEnoughRooms && $hasEnoughCapacity && $isWithinTotalRooms) {
                    $availability[] = [
                        'room_category' => $category,
                        'available_rooms' => $availableRooms,
                        'total_rooms_in_category' => $category->total_rooms,
                        'max_guests' => $category->max_guests,
                        'nights' => $nights,
                        'price_per_night' => $category->price_per_night,
                        'total_price' => $category->price_per_night * $nights * $request->rooms,
                        'can_book' => true,
                    ];
                } else {
                    // Tambahkan info mengapa tidak bisa booking
                    $availability[] = [
                        'room_category' => $category,
                        'available_rooms' => $availableRooms,
                        'total_rooms_in_category' => $category->total_rooms,
                        'max_guests' => $category->max_guests,
                        'nights' => $nights,
                        'price_per_night' => $category->price_per_night,
                        'total_price' => $category->price_per_night * $nights * $request->rooms,
                        'can_book' => false,
                        'reasons' => [
                            'not_enough_available_rooms' => !$hasEnoughRooms,
                            'exceeds_guest_capacity' => !$hasEnoughCapacity,
                            'exceeds_total_rooms' => !$isWithinTotalRooms,
                        ]
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'hotel' => $hotel->only(['id', 'name', 'slug']),
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'nights' => $nights,
                    'rooms' => $request->rooms,
                    'guests_per_room' => $request->guests_per_room,
                    'available_categories' => $availability,
                ]
            ], 200);
        }
        
        // Quick availability check untuk frontend validation
        public function quickAvailabilityCheck(Request $request)
        {
            $request->validate([
                'room_category_id' => 'required|exists:room_categories,id',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'number_of_rooms' => 'required|integer|min:1|max:6',
                'guests_per_room' => 'required|integer|min:1|max:3',
            ]);

            $roomCategory = RoomCategory::findOrFail($request->room_category_id);

            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            // Validasi dasar
            if ($request->number_of_rooms > $roomCategory->total_rooms) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum {$roomCategory->total_rooms} rooms available for {$roomCategory->name}.",
                    'available' => false,
                    'reason' => 'exceeds_total_rooms'
                ], 200);
            }

            if ($request->guests_per_room > $roomCategory->max_guests) {
                return response()->json([
                    'success' => false,
                    'message' => "Maximum {$roomCategory->max_guests} guests per room for {$roomCategory->name}.",
                    'available' => false,
                    'reason' => 'exceeds_guest_capacity'
                ], 200);
            }

            // Check availability untuk periode tertentu
            $availableRoomsCount = $roomCategory->getAvailableRoomsCountForPeriod(
                $checkIn->toDateString(),
                $checkOut->toDateString()
            );

            if ($availableRoomsCount < $request->number_of_rooms) {
                return response()->json([
                    'success' => false,
                    'message' => $availableRoomsCount == 0
                        ? "No rooms available for selected dates."
                        : "Only {$availableRoomsCount} room(s) available for selected dates.",
                    'available' => false,
                    'reason' => 'not_enough_available_rooms',
                    'available_rooms' => $availableRoomsCount
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rooms are available for booking.',
                'available' => true,
                'available_rooms' => $availableRoomsCount
            ], 200);
        }

        // Get price range for search filters
        public function getPriceRange(Request $request)
        {
            $query = RoomCategory::query();

            if ($request->has('city_id')) {
                $query->whereHas('hotel', function ($q) use ($request) {
                    $q->where('city_id', $request->city_id)
                        ->where('status', 'active');
                });
            }

            $minPrice = $query->min('price_per_night') ?? 0;
            $maxPrice = $query->max('price_per_night') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                ]
            ], 200);
        }
        // Search suggestions for dropdown
        public function searchSuggestions(Request $request)
        {
            $query = $request->input('query', '');

            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }

            // Search cities
            $cities = City::where('name', 'like', "%{$query}%")
                ->orWhere('province', 'like', "%{$query}%")
                ->withCount('hotels')
                ->limit(5)
                ->get()
                ->map(function ($city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'province' => $city->province,
                        'slug' => $city->slug,
                        'type' => 'city',
                        'display_name' => $city->name . ', ' . $city->province,
                        'hotels_count' => $city->hotels_count
                    ];
                });

            // Search hotels
            $hotels = Hotel::where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('address', 'like', "%{$query}%");
                })
                ->with('city')
                ->limit(5)
                ->get()
                ->map(function ($hotel) {
                    return [
                        'id' => $hotel->id,
                        'name' => $hotel->name,
                        'slug' => $hotel->slug,
                        'type' => 'hotel',
                        'display_name' => $hotel->name,
                        'city_name' => $hotel->city->name ?? '',
                        'province' => $hotel->city->province ?? '',
                        'image' => $hotel->images ? (is_array($hotel->images) ? $hotel->images[0] ?? null : json_decode($hotel->images)[0] ?? null) : null
                    ];
                });

            $suggestions = $cities->merge($hotels)->take(8);

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ], 200);
        }
    }
