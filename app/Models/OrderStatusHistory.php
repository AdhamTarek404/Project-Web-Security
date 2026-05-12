<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// APPEND-ONLY model — the "event sourcing-inspired order history" from
// the description. We never UPDATE or DELETE these rows.
//
// Rules enforced in code:
//   - $timestamps = false because we manage occurred_at/created_at manually
//   - No mass-assigning to existing rows (only ::create() is used)
//   - No fillable for updates anywhere in the codebase
class OrderStatusHistory extends Model
{
    // Match the table name from the migration exactly.
    protected $table = 'order_status_history';

    // We set occurred_at / created_at ourselves in OrderStateMachine.
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'reason',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
