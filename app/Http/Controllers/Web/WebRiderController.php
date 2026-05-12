<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Browser-side rider self-service: toggle duty + manual location ping.
class WebRiderController extends Controller
{
    public function toggleDuty(Request $request)
    {
        $rider = $request->user()->rider;
        abort_unless($rider, 404);

        $rider->is_on_duty = ! $rider->is_on_duty;
        // When going off-duty, also clear availability so the dispatcher skips you.
        if (! $rider->is_on_duty) {
            $rider->is_available = false;
        } else {
            $rider->is_available = true;
        }
        $rider->save();

        return back()->with('status', $rider->is_on_duty ? 'You are now ON duty.' : 'You are now OFF duty.');
    }

    public function updateLocation(Request $request)
    {
        $data = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $rider = $request->user()->rider;
        abort_unless($rider, 404);

        $rider->update([
            'current_latitude'  => $data['latitude'],
            'current_longitude' => $data['longitude'],
            'last_location_at'  => now(),
        ]);

        return back()->with('status', 'Location updated.');
    }
}
