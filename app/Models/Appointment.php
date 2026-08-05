<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function property() { return $this->belongsTo(Property::class); }
}
