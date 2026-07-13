<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Watchlist;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    private function getUserId(): int
    {
        return auth()->id() ?? 1;
    }

    public function index()
    {
        $items = Watchlist::where('user_id', $this->getUserId())
            ->with('country')
            ->get()
            ->pluck('country');

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $watchlist = Watchlist::firstOrCreate([
            'user_id' => $this->getUserId(),
            'country_id' => $request->country_id,
        ]);

        return response()->json(['status' => 'added', 'watchlist' => $watchlist]);
    }

    public function destroy($countryId)
    {
        Watchlist::where('user_id', $this->getUserId())
            ->where('country_id', $countryId)
            ->delete();

        return response()->json(['status' => 'removed']);
    }
}
