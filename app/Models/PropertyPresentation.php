<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropertyPresentation extends Model
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

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_path ? Storage::disk($this->pdf_disk)->url($this->pdf_path) : null;
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

    public function getTemplateLabelAttribute(): string
    {
        return config("brochure_templates.templates.{$this->template_key}.label", $this->template_key);
    }

    /**
     * Problems that did not stop the PDF but that the advisor still needs to see —
     * a croquis that could not be drawn, for instance.
     *
     * @return string[]
     */
    public function getWarningsAttribute(): array
    {
        return array_values(array_filter((array) ($this->ai_content['warnings'] ?? [])));
    }
}
