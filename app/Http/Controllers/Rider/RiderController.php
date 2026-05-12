<?php

namespace App\Http\Controllers\Rider;

use App\Enums\OrderStatus;
use App\Events\RiderLocationUpdated;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\UpdateLocationRequest;
use App\Models\Order;
use App\Models\Rider;
use App\Services\Orders\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Endpoints used by the rider mobile app. Every action is gated to
// the authenticated rider's own row.
class RiderController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $rider = Rider::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['vehicle_type' => 'bike']
        );
        return response()->json(['data' => $rider]);
    }

    /**
     * POST /api/rider/location
     * Called every few seconds by the rider mobile app. We update the
     * stored GPS so the dispatch algorithm has fresh coordinates.
     *
     * In Phase 10 we'll broadcast this over Reverb so the admin live map
     * sees the rider's marker move in real time.
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $rider = $this->resolveRider($request);

        $rider->current_latitude = $request->validated('latitude');
        $rider->current_longitude = $request->validated('longitude');
        $rider->last_location_at = now();
        $rider->save();

        // Phase 10: broadcast over Reverb so the admin live map moves
        // this rider's pin in real time.
        RiderLocationUpdated::dispatch($rider);

        return response()->json(['data' => $rider]);
    }

    /**
     * POST /api/rider/duty
     * Body: { is_on_duty: bool }
     * Going off-duty makes the rider invisible to the dispatcher.
     */
    public function toggleDuty(Request $request): JsonResponse
    {
        $request->validate(['is_on_duty' => ['required', 'boolean']]);
        $rider = $this->resolveRider($request);

        $rider->is_on_duty = $request->boolean('is_on_duty');
        // A rider going off-duty cannot be available.
        $rider->is_available = $rider->is_on_duty;
        $rider->save();

        return response()->json(['data' => $rider]);
    }

    /**
     * POST /api/rider/orders/{order}/picked-up
     * Rider moves order from preparing → on_the_way.
     */
    public function pickedUp(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        $rider = $this->resolveRider($request);
        abort_unless($order->rider_id === $rider->id, 403);

        try {
            $sm->transition($order, OrderStatus::OnTheWay, 'rider', $request->user()->id);
        } catch (InvalidOrderTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['data' => $order->fresh()]);
    }

    /**
     * POST /api/rider/orders/{order}/delivered
     * Rider moves order from on_the_way → delivered. Also frees the rider.
     */
    public function delivered(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        $rider = $this->resolveRider($request);
        abort_unless($order->rider_id === $rider->id, 403);

        try {
            $sm->transition($order, OrderStatus::Delivered, 'rider', $request->user()->id);
        } catch (InvalidOrderTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Free the rider so dispatch can hand them the next order.
        if ($rider->is_on_duty) {
            $rider->is_available = true;
            $rider->save();
        }

        return response()->json(['data' => $order->fresh()]);
    }

    private function resolveRider(Request $request): Rider
    {
        return Rider::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['vehicle_type' => 'bike']
        );
    }
}
