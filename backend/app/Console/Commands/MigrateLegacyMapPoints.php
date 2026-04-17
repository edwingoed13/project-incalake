<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyMapPoints extends Command
{
    protected $signature = 'tours:migrate-map-points
                            {--tour= : Migrate only this tour code (e.g. ES001)}
                            {--dry-run : Show what would be migrated without writing}';

    protected $description = 'Migrate map points from legacy tab.mapa_tab JSON to tour_map_points. Replaces existing points per tour.';

    /**
     * Legacy tipo → new enum mapping.
     * If legacy only uses "1", all become punto_parada (user can edit later).
     */
    protected array $typeMap = [
        '1' => 'punto_parada',
        '2' => 'restaurant',
        '3' => 'lugar_turistico',
        '4' => 'aeropuerto',
        '5' => 'estacion_tren',
        '6' => 'terminal_terrestre',
        '7' => 'museo',
        '8' => 'punto_reunion',
        '9' => 'otro',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyCode = $this->option('tour');

        $this->info('Migrating legacy map points (mapa_tab JSON) to tour_map_points...');
        if ($dryRun) $this->warn('DRY-RUN mode: no changes will be written');

        // Get legacy tabs with map data, joined with producto to get codigo_producto,
        // matched with new tours table. Deduplicate by tour (multiple tabs per tour = 1 per language).
        $query = DB::table('tab')
            ->join('producto', 'producto.id_producto', '=', 'tab.producto_id_producto')
            ->join('tours', 'tours.code', '=', 'producto.codigo_producto')
            ->whereNotNull('tab.mapa_tab')
            ->where('tab.mapa_tab', '!=', '')
            ->select('tours.id as tour_id', 'tours.code', 'tab.mapa_tab', 'producto.id_producto');

        if ($onlyCode) {
            $query->where('tours.code', $onlyCode);
        }

        $rows = $query->get();

        // Deduplicate: keep only one mapa_tab per tour (the first encountered)
        $byTour = [];
        foreach ($rows as $r) {
            if (!isset($byTour[$r->tour_id])) {
                $byTour[$r->tour_id] = $r;
            }
        }

        $this->info('Tours with map data: ' . count($byTour));

        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($byTour as $tourId => $row) {
            try {
                $json = json_decode($row->mapa_tab, true);
                $lugares = $json['lugares'] ?? [];

                if (empty($lugares) || !is_array($lugares)) {
                    $skipped++;
                    continue;
                }

                $points = [];
                foreach ($lugares as $idx => $lugar) {
                    $coords = trim((string) ($lugar['coordenadas'] ?? ''));
                    if ($coords === '') continue;

                    $points[] = [
                        'tour_id' => $tourId,
                        'name' => (string) ($lugar['nombre'] ?? 'Point ' . ($idx + 1)),
                        'description' => $lugar['descripcion'] ?? null,
                        'coordinates' => $coords,
                        'type' => $this->typeMap[(string) ($lugar['tipo'] ?? '1')] ?? 'punto_parada',
                        'order' => (int) ($lugar['orden'] ?? ($idx + 1)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (empty($points)) {
                    $skipped++;
                    continue;
                }

                if (!$dryRun) {
                    DB::transaction(function () use ($tourId, $points) {
                        DB::table('tour_map_points')->where('tour_id', $tourId)->delete();
                        DB::table('tour_map_points')->insert($points);
                    });
                }

                $migrated++;
                $this->line("  [{$row->code}] tour_id={$tourId} → " . count($points) . ' points');
            } catch (\Exception $e) {
                $failed++;
                $this->warn("  [{$row->code}] FAILED: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Migrated: {$migrated}");
        if ($skipped > 0) $this->warn("Skipped (empty/invalid JSON): {$skipped}");
        if ($failed > 0) $this->error("Failed: {$failed}");

        return self::SUCCESS;
    }
}
