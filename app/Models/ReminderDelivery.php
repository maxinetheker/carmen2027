<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'sent_at' => 'datetime'];
    }
}
