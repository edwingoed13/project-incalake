<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateLegacyTours extends Command
{
    protected $signature = 'migrate:legacy {--group= : Specific codigo_producto group ID to migrate} {--dry-run : Show what would be migrated without writing} {--all : Migrate all active tours}';
    protected $description = 'Migrate tours from old CodeIgniter tables to new Laravel tour system';

    // Old DB connection (port 3307)
    private $old;

    // Language mapping: old idioma.id_idioma → new languages.id
    private $langMap = [
        1 => 1, // ES → ES
        2 => 2, // EN → EN
        3 => 3, // FR → FR
        4 => 4, // DE → DE
        5 => 5, // BR → PT
        6 => 6, // IT → IT
    ];

    public function handle()
    {
        // Connect to old MySQL
        config(['database.connections.legacy' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'inc0910d_cms_incalake',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);

        $this->old = DB::connection('legacy');

        // Test connection
        try {
            $this->old->select('SELECT 1');
            $this->info('Connected to legacy database on port 3307.');
        } catch (\Exception $e) {
            $this->error('Cannot connect to legacy DB on port 3307. Start it with:');
            $this->line('/c/xampp/mysql/bin/mysqld.exe --port=3307 --skip-grant-tables --datadir="C:/xampp/mysql/data"');
            return 1;
        }

        $dryRun = $this->option('dry-run');
        $groupId = $this->option('group');
        $all = $this->option('all');

        if ($groupId) {
            $groups = $this->old->table('codigo_producto')
                ->where('id_codigo_producto', $groupId)
                ->get();
        } elseif ($all) {
            $groups = $this->old->table('codigo_producto')
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('producto')
                      ->whereRaw('producto.id_codigo_producto = codigo_producto.id_codigo_producto')
                      ->where('estado_producto', 1);
                })
                ->get();
        } else {
            $this->error('Specify --group=ID for a single tour or --all for all tours.');
            return 1;
        }

        $this->info("Found {$groups->count()} tour group(s) to migrate.");

        $migrated = 0;
        $skipped = 0;

        foreach ($groups as $group) {
            $result = $this->migrateGroup($group, $dryRun);
            if ($result) {
                $migrated++;
            } else {
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Migration complete: {$migrated} migrated, {$skipped} skipped.");

        return 0;
    }

    private function migrateGroup($group, bool $dryRun): bool
    {
        $groupId = $group->id_codigo_producto;

        // Get all active products for this group
        $products = $this->old->table('producto as p')
            ->leftJoin('servicio as s', 'p.id_servicio', '=', 's.id_servicio')
            ->leftJoin('idioma as i', 's.idioma_id_idioma', '=', 'i.id_idioma')
            ->where('p.id_codigo_producto', $groupId)
            ->where('p.estado_producto', 1)
            ->select('p.*', 's.uri_servicio', 's.url_servicio', 's.descripcion_pagina',
                     's.idioma_id_idioma', 'i.codigo as lang_code')
            ->orderBy('i.id_idioma')
            ->get();

        if ($products->isEmpty()) {
            return false;
        }

        // Use ES product as base, or first available
        $base = $products->firstWhere('lang_code', 'ES') ?? $products->first();
        $code = $base->codigo_producto;

        // Check if already migrated
        $existing = DB::table('tours')->where('code', $code)->first();
        if ($existing) {
            $this->line("  SKIP [{$code}] {$base->titulo_producto} (already exists as tour #{$existing->id})");
            return false;
        }

        $this->info("  Migrating [{$code}] {$base->titulo_producto} ({$products->count()} languages)");

        if ($dryRun) {
            foreach ($products as $p) {
                $this->line("    [{$p->lang_code}] {$p->titulo_producto} → /{$p->uri_servicio}");
            }
            return true;
        }

        DB::beginTransaction();

        try {
            // 1. Parse duration: "3!1" → 3 hours, "2!2" → 2 days
            $duration = $this->parseDuration($base->duracion);

            // 2. Parse departure time: "9:00 AM,12:00 PM" → "09:00"
            $departureTime = $this->parseDepartureTime($base->hora_inicio);

            // 3. Find or create city
            $cityId = $this->findOrCreateCity($base->ciudad_cercana);

            // 4. Create tour record
            $tourId = DB::table('tours')->insertGetId([
                'code' => $code,
                'primary_language_id' => 1, // ES
                'city_id' => $cityId,
                'city_name' => $base->ciudad_cercana,
                'service_type' => 'tour',
                'status' => 'published',
                'active' => true,
                'difficulty' => 'moderate',
                'capacity' => $base->capacidad ?: 99,
                'departure_time' => $departureTime,
                'departure_period' => 'AM',
                'timezone' => 'America/Lima',
                'duration_days' => $duration['days'],
                'duration_hours' => $duration['hours'],
                'tax_percentage' => $base->tasas_impuestos ?: 18,
                'advance_payment_percentage' => $base->porcentaje_adelanto ?: 100,
                'data_requirement' => $base->requerimiento_datos ?: 1,
                'require_availability' => (bool) $base->requerir_disponibilidad,
                'enable_meeting_point' => $base->tipo_recojo == 1,
                'enable_hotel_pickup' => $base->tipo_recojo == 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Create translations for each language
            foreach ($products as $product) {
                $newLangId = $this->langMap[$product->idioma_id_idioma] ?? null;
                if (!$newLangId) continue;

                // Get tab content
                $tab = $this->old->table('tab')
                    ->where('producto_id_producto', $product->id_producto)
                    ->first();

                $slug = $product->uri_servicio ?: Str::slug($product->titulo_producto);

                // Ensure unique slug
                $slugBase = $slug;
                $counter = 1;
                while (DB::table('tour_translations')->where('slug', $slug)->exists()) {
                    $slug = $slugBase . '-' . $counter++;
                }

                // Wrap HTML content as JSON string for columns with json_valid CHECK constraint
                $wrapHtml = fn($html) => $html ? json_encode($html) : null;

                DB::table('tour_translations')->insert([
                    'tour_id' => $tourId,
                    'language_id' => $newLangId,
                    'h1_title' => $product->titulo_producto,
                    'short_description' => $product->descripcion_pagina,
                    'slug' => $slug,
                    'meta_title' => mb_substr($product->titulo_producto ?? '', 0, 55),
                    'meta_description' => mb_substr(strip_tags($product->descripcion_pagina ?? ''), 0, 155),
                    'long_description' => $tab->descripcion_tab ?? null,
                    'itinerary' => $wrapHtml($tab->itinerario_ta ?? null),
                    'what_includes' => $wrapHtml($tab->incluye_tab ?? null),
                    'what_not_includes' => null,
                    'recommendations' => $wrapHtml($tab->recomendacion_tab ?? null),
                    'what_to_bring' => $wrapHtml($tab->salida_retorno_tab ?? null),
                    'policies' => $wrapHtml($product->politicas_producto ?? null),
                    'booking_texts' => json_encode([
                        'policyDescription' => $product->politicas_producto ?? '',
                        'meetingPointDescription' => $product->lugar_recojo ?? '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->line("    [{$product->lang_code}] {$product->titulo_producto}");
            }

            // 6. Migrate images (from ES product, shared)
            $images = $this->old->table('galeria_has_producto as ghp')
                ->join('galeria as g', 'g.id_galeria', '=', 'ghp.id_galeria')
                ->where('ghp.id_producto', $base->id_producto)
                ->select('g.id_galeria', 'g.url_archivo')
                ->get();

            $oldGaleriaBase = realpath(base_path('../../public_html/apps-incalake/web/galeria'));
            $storageToursDir = storage_path('app/public/tours/' . $tourId);

            if (!is_dir($storageToursDir)) {
                mkdir($storageToursDir, 0755, true);
            }

            foreach ($images as $idx => $img) {
                // Find the actual file recursively in old galeria directory
                $sourceFile = $this->findImageFile($oldGaleriaBase, $img->url_archivo);

                if ($sourceFile) {
                    $ext = pathinfo($img->url_archivo, PATHINFO_EXTENSION) ?: 'jpg';
                    $newFilename = Str::uuid() . '.' . $ext;
                    $destPath = $storageToursDir . '/' . $newFilename;

                    copy($sourceFile, $destPath);
                    $imagePath = 'tours/' . $tourId . '/' . $newFilename;
                } else {
                    // File not found, store reference anyway
                    $imagePath = 'legacy/galeria/' . $img->url_archivo;
                    $this->warn("      Image not found: {$img->url_archivo}");
                }

                DB::table('tour_media_gallery')->insert([
                    'tour_id' => $tourId,
                    'language_id' => 1,
                    'image_path' => $imagePath,
                    'alt_text' => $base->titulo_producto,
                    'title_text' => $base->titulo_producto,
                    'order' => $idx + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->line("    Images: {$images->count()}");

            // 7. Migrate prices (from ES product)
            $priceDetails = $this->old->table('detalle_precio')
                ->where('id_producto', $base->id_producto)
                ->get();

            $priceCount = 0;
            foreach ($priceDetails as $detail) {
                $prices = $this->old->table('precios')
                    ->where('id_detalle_precio', $detail->id_detalle_precio)
                    ->orderBy('cantidad')
                    ->get();

                foreach ($prices as $price) {
                    DB::table('tour_prices')->insert([
                        'tour_id' => $tourId,
                        'age_stage_id' => $detail->id_etapa_edad,
                        'nationality_id' => $detail->id_nacionalidad,
                        'min_quantity' => $price->cantidad,
                        'max_quantity' => $price->cantidad,
                        'amount' => $price->monto,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $priceCount++;
                }
            }
            $this->line("    Prices: {$priceCount}");

            // 8. Migrate availability + blocks + offers → JSON
            $avail = $this->old->table('disponibilidad')
                ->where('id_producto', $base->id_producto)
                ->first();

            $blocks = $this->old->table('bloqueo')
                ->where('id_producto', $base->id_producto)
                ->get()
                ->map(fn($b) => [
                    'id' => Str::uuid()->toString(),
                    'startDate' => substr($b->fecha_inicio, 0, 10),
                    'endDate' => substr($b->fecha_fin, 0, 10),
                    'reason' => $b->descripcion_bloqueo,
                ])->values()->toArray();

            $offers = $this->old->table('oferta')
                ->where('id_producto', $base->id_producto)
                ->get()
                ->map(fn($o) => [
                    'id' => Str::uuid()->toString(),
                    'startDate' => substr($o->fecha_inicio, 0, 10),
                    'endDate' => substr($o->fecha_fin, 0, 10),
                    'discount' => (float) $o->valor_oferta,
                    'discountType' => $o->tipo_oferta == 0 ? 'percentage' : 'amount',
                    'color' => $o->color_oferta ?: '#449d44',
                ])->values()->toArray();

            $availabilityData = [
                'start' => $avail ? substr($avail->fecha_inicio, 0, 10) : now()->format('Y-m-d'),
                'end' => $avail ? substr($avail->fecha_fin, 0, 10) : now()->addYear()->format('Y-m-d'),
                'activeDays' => $avail && $avail->dias_activos ? json_decode($avail->dias_activos, true) : [0,1,2,3,4,5,6],
                'specialDays' => [],
                'blocks' => $blocks,
                'offers' => $offers,
            ];

            DB::table('tours')->where('id', $tourId)->update([
                'availability_data' => json_encode($availabilityData),
            ]);

            $this->line("    Availability: " . count($blocks) . " blocks, " . count($offers) . " offers");

            // 9. Migrate categories
            $cats = $this->old->table('producto_has_categoria as phc')
                ->join('categoria as c', 'c.id_categoria', '=', 'phc.categoria_id_categoria')
                ->where('phc.producto_id_producto', $base->id_producto)
                ->select('c.id_codigo_categoria', 'c.nombre_categoria')
                ->get();

            foreach ($cats as $cat) {
                // Find matching category in new DB by code
                $newCat = DB::table('categories_new')
                    ->where('id', $cat->id_codigo_categoria)
                    ->first();

                if ($newCat) {
                    DB::table('tour_categories')->insertOrIgnore([
                        'tour_id' => $tourId,
                        'category_id' => $newCat->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $this->line("    Categories: {$cats->count()}");

            DB::commit();
            $this->info("    ✓ Tour #{$tourId} created successfully!");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    ✗ Error: {$e->getMessage()}");
            Log::error("Legacy migration error", [
                'group' => $groupId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    private function parseDuration(?string $duracion): array
    {
        if (!$duracion) return ['hours' => 0, 'days' => 0];

        // Take first value if comma-separated: "3!1,3!1,3!1" → "3!1"
        $first = explode(',', $duracion)[0];

        // "3!1" → quantity=3, type=1(hours) / "2!2" → quantity=2, type=2(days)
        $parts = explode('!', $first);
        $qty = (int) ($parts[0] ?? 0);
        $type = (int) ($parts[1] ?? 1);

        if ($type === 2) {
            return ['hours' => 0, 'days' => $qty];
        }
        return ['hours' => $qty, 'days' => 0];
    }

    private function parseDepartureTime(?string $horaInicio): string
    {
        if (!$horaInicio) return '08:00';

        // Take first time: "9:00 AM,12:00 PM" → "9:00 AM"
        $first = trim(explode(',', $horaInicio)[0]);

        // Parse "9:00 AM" → "09:00"
        try {
            $time = \Carbon\Carbon::createFromFormat('g:i A', $first);
            return $time->format('H:i');
        } catch (\Exception $e) {
            // Try "9:00" format
            try {
                $time = \Carbon\Carbon::createFromFormat('G:i', $first);
                return $time->format('H:i');
            } catch (\Exception $e2) {
                return '08:00';
            }
        }
    }

    private function findImageFile(?string $baseDir, string $filename): ?string
    {
        if (!$baseDir || !is_dir($baseDir)) return null;

        // Search in short-slider first (most common), then full-slider
        $searchDirs = [
            $baseDir . '/admin/short-slider',
            $baseDir . '/admin/full-slider',
            $baseDir . '/admin',
            $baseDir,
        ];

        foreach ($searchDirs as $dir) {
            if (!is_dir($dir)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->getFilename() === $filename && strpos($file->getPathname(), '/thumbs/') === false) {
                    return $file->getPathname();
                }
            }
        }

        return null;
    }

    private function findOrCreateCity(?string $ciudadCercana): int
    {
        if (!$ciudadCercana) return 1; // Default to Puno

        // Extract city name: "Isla de los Uros, Puno, Perú" → "Puno"
        $parts = array_map('trim', explode(',', $ciudadCercana));

        // Try to find by name (check each part)
        foreach ($parts as $part) {
            $city = DB::table('cities')
                ->whereRaw('LOWER(name) = ?', [strtolower($part)])
                ->first();
            if ($city) return $city->id;
        }

        // Try the second part (usually the city): "Isla de los Uros, Puno, Perú"
        if (count($parts) >= 2) {
            $city = DB::table('cities')
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($parts[1]) . '%'])
                ->first();
            if ($city) return $city->id;
        }

        // Default to Puno
        return 1;
    }
}
