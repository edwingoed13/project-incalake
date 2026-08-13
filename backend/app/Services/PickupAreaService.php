<?php

namespace App\Services;

use App\Models\Tour;

/**
 * Decides whether a pickup address falls inside a tour's pickup area, and how
 * far outside it is when it doesn't.
 *
 * Two shapes, chosen per tour:
 *
 *  - radius:  a circle around pickup_center_lat/lng. Distance outside is
 *             simply (distance to centre − radius).
 *  - polygon: an arbitrary drawn area. "Distance outside" is the distance to
 *             the nearest EDGE, not to some centre — a hotel one block past a
 *             long thin zone is one block away, however far the centroid is.
 *
 * Both return the same shape so the surcharge rule downstream stays identical.
 * Distances are kilometres.
 */
class PickupAreaService
{
    private const EARTH_RADIUS_KM = 6371;

    /**
     * @return array{inside: bool, distance_outside_km: float, distance_km: float}
     *   distance_km is kept for display and for the existing bookings column:
     *   distance to the centre for a radius, to the nearest edge for a polygon.
     */
    public function evaluate(Tour $tour, float $lat, float $lng): array
    {
        if ($tour->pickup_area_type === 'polygon') {
            return $this->evaluatePolygon($tour, $lat, $lng);
        }

        return $this->evaluateRadius($tour, $lat, $lng);
    }

    private function evaluateRadius(Tour $tour, float $lat, float $lng): array
    {
        $distance = $this->haversine(
            $lat,
            $lng,
            (float) $tour->pickup_center_lat,
            (float) $tour->pickup_center_lng
        );
        $radius = (float) ($tour->pickup_radius_km ?: 0);
        $inside = $distance <= $radius;

        return [
            'inside' => $inside,
            'distance_outside_km' => $inside ? 0.0 : $distance - $radius,
            'distance_km' => $distance,
        ];
    }

    private function evaluatePolygon(Tour $tour, float $lat, float $lng): array
    {
        $rings = $this->rings($tour);

        // A polygon type with no usable shape must not silently accept
        // everyone: that would give free pickup worldwide. Treat it as outside
        // with no measurable distance, so the caller falls back to its
        // "outside" branch rather than charging a made-up amount.
        if (empty($rings)) {
            return ['inside' => false, 'distance_outside_km' => 0.0, 'distance_km' => 0.0];
        }

        foreach ($rings as $ring) {
            if ($this->containsPoint($ring, $lat, $lng)) {
                return ['inside' => true, 'distance_outside_km' => 0.0, 'distance_km' => 0.0];
            }
        }

        // Outside every ring — the relevant distance is to the closest one.
        $nearest = null;
        foreach ($rings as $ring) {
            $d = $this->distanceToRing($ring, $lat, $lng);
            $nearest = $nearest === null ? $d : min($nearest, $d);
        }

        return [
            'inside' => false,
            'distance_outside_km' => $nearest,
            'distance_km' => $nearest,
        ];
    }

    /**
     * pickup_area holds either a single ring ([{lat,lng}, …]) or several
     * ([[{lat,lng}, …], …]) — a tour can cover two separate neighbourhoods.
     * Normalised here so the rest of the class only ever sees a list of rings.
     *
     * @return array<int, array<int, array{lat: float, lng: float}>>
     */
    private function rings(Tour $tour): array
    {
        $area = $tour->pickup_area;
        if (!is_array($area) || empty($area)) {
            return [];
        }

        $first = reset($area);
        $rings = (is_array($first) && !isset($first['lat'])) ? $area : [$area];

        $out = [];
        foreach ($rings as $ring) {
            if (!is_array($ring)) {
                continue;
            }
            $points = [];
            foreach ($ring as $p) {
                if (!is_array($p) || !isset($p['lat'], $p['lng'])) {
                    continue;
                }
                $points[] = ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']];
            }
            // Fewer than three points is a line, not an area.
            if (count($points) >= 3) {
                $out[] = $points;
            }
        }

        return $out;
    }

    /**
     * Ray casting: count how many edges a ray from the point crosses. Odd means
     * inside. Works on raw lat/lng — over a city the distortion is far below
     * the precision anyone cares about here.
     *
     * @param array<int, array{lat: float, lng: float}> $ring
     */
    private function containsPoint(array $ring, float $lat, float $lng): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = $ring[$i]['lat'];
            $xi = $ring[$i]['lng'];
            $yj = $ring[$j]['lat'];
            $xj = $ring[$j]['lng'];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /** Shortest distance from the point to any edge of the ring, in km. */
    private function distanceToRing(array $ring, float $lat, float $lng): float
    {
        $min = null;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $d = $this->distanceToSegment(
                $lat, $lng,
                $ring[$j]['lat'], $ring[$j]['lng'],
                $ring[$i]['lat'], $ring[$i]['lng']
            );
            $min = $min === null ? $d : min($min, $d);
        }

        return $min ?? 0.0;
    }

    /**
     * Distance from a point to a segment. Projected onto a local flat plane
     * first (longitude degrees shrink with latitude) so the "closest point on
     * the segment" maths is done in metres rather than degrees, which would
     * skew every result away from the equator.
     */
    private function distanceToSegment(
        float $lat, float $lng,
        float $latA, float $lngA,
        float $latB, float $lngB
    ): float {
        $latRef = deg2rad($lat);
        $kx = cos($latRef) * 111.32; // km per degree of longitude at this latitude
        $ky = 110.574;               // km per degree of latitude

        $px = ($lng - $lngA) * $kx;
        $py = ($lat - $latA) * $ky;
        $bx = ($lngB - $lngA) * $kx;
        $by = ($latB - $latA) * $ky;

        $lengthSquared = $bx * $bx + $by * $by;
        // Degenerate edge (two identical vertices): fall back to the endpoint.
        $t = $lengthSquared > 0 ? (($px * $bx + $py * $by) / $lengthSquared) : 0.0;
        $t = max(0.0, min(1.0, $t));

        $dx = $px - $t * $bx;
        $dy = $py - $t * $by;

        return sqrt($dx * $dx + $dy * $dy);
    }

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
