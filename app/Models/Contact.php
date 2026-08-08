<?php

namespace App\Models;

use App\Models\Concerns\TracksFollowUp;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use TracksFollowUp;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'last_contact_at' => 'datetime',
            'next_contact_at' => 'datetime',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
