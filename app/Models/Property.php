<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Property extends Model
{
    public const TYPES = [
        'departamento' => 'Departamento',
        'casa' => 'Casa',
        'oficina' => 'Oficina',
        'local' => 'Local comercial',
        'terreno' => 'Terreno',
    ];

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

    /**
     * Next free correlative code (CM-001, CM-002…). Codes are generated rather than
     * typed, so two advisors filling in a form at the same time cannot pick the same
     * one; the loop closes the small gap between reading the highest code and the
     * insert that follows. Non-numeric legacy suffixes simply count as zero.
     */
    public static function nextCode(string $prefix = 'CM-'): string
    {
        $highest = (int) static::where('code', 'like', $prefix.'%')
            ->pluck('code')
            ->map(fn ($code) => (int) preg_replace('/\D+/', '', mb_substr((string) $code, mb_strlen($prefix))))
            ->max();

        do {
            $code = $prefix.str_pad((string) ++$highest, 3, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
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

    public function socialImages()
    {
        return $this->hasMany(PropertySocialImage::class)
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
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
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
