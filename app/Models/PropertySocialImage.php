<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropertySocialImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'ai_content' => 'array',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk($this->image_disk)->url($this->image_path) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'queued' => 'En cola',
            'processing' => 'Generando…',
            'done' => 'Lista',
            'failed' => 'Error',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    public function getFormatLabelAttribute(): string
    {
        return [
            'cuadrado' => 'Cuadrada 1:1',
            'vertical' => 'Vertical 2:3',
            'horizontal' => 'Horizontal 3:2',
        ][$this->format] ?? ucfirst((string) $this->format);
    }

    /** @return string[] */
    public function getWarningsAttribute(): array
    {
        return array_values(array_filter((array) ($this->ai_content['warnings'] ?? [])));
    }
}
