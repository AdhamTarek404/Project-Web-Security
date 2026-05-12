<?php

namespace App\Http\Controllers\Owner;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Restaurant-side order endpoints. The owner moves an order through
// placed → confirmed → preparing using these. After "preparing" the
// dispatcher (Phase 6) takes over and the rider moves it to on_the_way → delivered.
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::whereHas('restaurant', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->latest()->limit(100)->get();

        return response()->json(['data' => $orders]);
    }

    public function confirm(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        $this->authorize('manage', $order->restaurant);
        return $this->transition($order, OrderStatus::Confirmed, $request, $sm);
    }

    public function startPreparing(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        $this->authorize('manage', $order->restaurant);
        return $this->transition($order, OrderStatus::Preparing, $request, $sm);
    }

    public function cancel(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        $this->authorize('manage', $order->restaurant);
        return $this->transition($order, OrderStatus::Cancelled, $request, $sm, $request->input('reason'));
    }

    private function transition(Order $order, OrderStatus $to, Request $request, OrderStateMachine $sm, ?string $reason = null): JsonResponse
    {
        try {
            $sm->transition($order, $to, 'restaurant', $request->user()->id, $reason);
        } catch (InvalidOrderTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $order->fresh()]);
    }
}
