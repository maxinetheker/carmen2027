<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Property extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'show_in_hero' => 'boolean',
            'price' => 'decimal:2',
            'area' => 'decimal:2',
            'bathrooms' => 'decimal:1',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class)
            ->orderBy('sort_order');
    }

    public function features()
    {
        return $this->hasMany(PropertyFeature::class)
            ->orderBy('sort_order');
    }

    public function youtubeVideos()
    {
        return $this->hasMany(PropertyYoutubeVideo::class)
            ->orderBy('sort_order');
    }

    public function presentations()
    {
        return $this->hasMany(PropertyPresentation::class)
            ->orderByDesc('id');
    }

    public function documents()
    {
        return $this->hasMany(PropertyDocument::class)
            ->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderByDesc('show_in_hero')
            ->orderByDesc('priority')
            ->orderByDesc('featured')
            ->orderByDesc('updated_at');
    }

    public function getOperationLabelAttribute(): string
    {
        return ['venta' => 'Venta', 'alquiler' => 'Alquiler'][$this->operation]
            ?? ucfirst((string) $this->operation);
    }

    public function getStatusLabelAttribute(): string
    {
        return ['available' => 'Disponible', 'reserved' => 'Reservada', 'sold' => 'Vendida'][$this->status]
            ?? ucfirst((string) $this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst((string) $this->type);
    }

    public function getBathroomsLabelAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->bathrooms, 1, '.', ''), '0'), '.');
    }

    public function getCoverUrlAttribute(): string
    {
        $media = $this->relationLoaded('media')
            ? $this->media->firstWhere('is_cover', true) ?? $this->media->firstWhere('type', 'image')
            : $this->media()->where('type', 'image')->orderByDesc('is_cover')->first();

        return $media?->url ?: $this->image_url ?: '/images/property-1.jpg';
    }
}
