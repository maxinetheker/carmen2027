<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expected_close' => 'date', 'value' => 'decimal:2'];
    }

    public function lead() { return $this->belongsTo(Lead::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function property() { return $this->belongsTo(Property::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
}
