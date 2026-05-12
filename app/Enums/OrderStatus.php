<?php

namespace App\Enums;

// PHP 8.1+ backed enum. Single source of truth for "what statuses exist"
// AND "what transitions are allowed". If a developer adds a new status,
// they MUST update allowedNextStates() — that's enforced by the match()
// (PHP throws UnhandledMatchError if a case is missing).
enum OrderStatus: string
{
    case Placed = 'placed';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case OnTheWay = 'on_the_way';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * The states this state can legally move to.
     *
     * Rules from the description:
     *   "placed → confirmed → preparing → on_the_way → delivered"
     *   Plus any non-terminal state can be cancelled.
     *   Delivered and Cancelled are terminal — no exits.
     *
     * @return array<int, OrderStatus>
     */
    public function allowedNextStates(): array
    {
        return match ($this) {
            self::Placed => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::OnTheWay, self::Cancelled],
            self::OnTheWay => [self::Delivered, self::Cancelled],
            self::Delivered => [],   // terminal
            self::Cancelled => [],   // terminal
        };
    }

    /**
     * Used by guards: can THIS state go to `$to`?
     */
    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedNextStates(), strict: true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedNextStates() === [];
    }

    /**
     * The matching `*_at` column on the orders table for this state.
     * Used by the state machine to stamp the right timestamp when entering.
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Placed => 'placed_at',
            self::Confirmed => 'confirmed_at',
            self::Preparing => 'preparing_at',
            self::OnTheWay => 'on_the_way_at',
            self::Delivered => 'delivered_at',
            self::Cancelled => 'cancelled_at',
        };
    }
}
