<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PlaceOrderRequest;
use App\Models\Order;
use App\Services\Orders\OrderStateMachine;
use App\Services\Orders\PlaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function place(PlaceOrderRequest $request, PlaceOrder $action): JsonResponse
    {
        try {
            $order = $action->handle($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $order], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        // Customers can only see their own orders.
        abort_unless($order->customer_id === $request->user()->id, 403);

        $order->load(['items', 'statusHistory', 'restaurant:id,name,slug']);

        return response()->json(['data' => $order]);
    }

    public function cancel(Request $request, Order $order, OrderStateMachine $sm): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        try {
            $sm->transition($order, OrderStatus::Cancelled, 'customer', $request->user()->id, 'Customer cancelled');
        } catch (InvalidOrderTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $order->fresh()]);
    }
}
