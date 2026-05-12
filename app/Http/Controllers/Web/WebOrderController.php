<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Orders\PlaceOrder;
use Illuminate\Http\Request;
use InvalidArgumentException;

// Browser-side place-an-order. Builds the payload from a normal HTML form
// and delegates to the same PlaceOrder service the API uses.
class WebOrderController extends Controller
{
    public function place(Request $request, Restaurant $restaurant, PlaceOrder $placeOrder)
    {
        // The form posts items keyed by menu_item_id with qty=0 meaning "skip".
        // Re-index to a numeric array and drop zero-qty rows before validation.
        $items = collect($request->input('items', []))
            ->filter(fn ($row) => (int)($row['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        // Apply the order-level note to every line. (The DB stores them per-line
        // because Uber-style apps let you customise each item, but our simple
        // form has a single "notes" field that applies to the whole order.)
        if ($note = trim((string) $request->input('notes', ''))) {
            $items = array_map(fn ($row) => $row + ['special_instructions' => $note], $items);
        }

        $request->merge(['items' => $items]);

        $data = $request->validate([
            'delivery_address'   => 'required|string|max:255',
            'delivery_latitude'  => 'required|numeric|between:-90,90',
            'delivery_longitude' => 'required|numeric|between:-180,180',
            'items'              => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.variant_id'   => 'nullable|integer|exists:menu_item_variants,id',
            'items.*.quantity'     => 'required|integer|min:1|max:50',
            'items.*.special_instructions' => 'nullable|string|max:500',
        ], [
            'items.required' => 'Pick at least one item (set quantity ≥ 1).',
        ]);

        $data['restaurant_id'] = $restaurant->id;

        try {
            $order = $placeOrder->handle($request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')
            ->with('status', "Order #{$order->id} placed. Total: ".number_format($order->total / 100, 2)." EGP");
    }

    public function show(Order $order)
    {
        abort_unless($order->customer_id === request()->user()->id, 403);

        $order->load(['restaurant', 'items.menuItem', 'rider.user', 'statusHistory']);

        return view('orders.show', compact('order'));
    }
}
