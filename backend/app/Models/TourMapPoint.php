<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourMapPoint extends Model
{
    protected $fillable = [
        'tour_id',
        'name',
        'description',
        'coordinates',
        'type',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get latitude and longitude as array
     */
    public function getLatLngAttribute(): array
    {
        $coords = explode(',', $this->coordinates);
        return [
            'lat' => (float) ($coords[0] ?? 0),
            'lng' => (float) ($coords[1] ?? 0),
        ];
    }

    /**
     * Get type label in Spanish
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'punto_parada' => 'Punto de Parada',
            'restaurant' => 'Restaurant',
            'lugar_turistico' => 'Lugar Turístico',
            'aeropuerto' => 'Aeropuerto',
            'estacion_tren' => 'Estación de Tren',
            'terminal_terrestre' => 'Terminal Terrestre (Bus)',
            'museo' => 'Museo',
            'punto_reunion' => 'Punto de Reunión',
            'otro' => 'Otro',
            default => $this->type,
        };
    }
}
