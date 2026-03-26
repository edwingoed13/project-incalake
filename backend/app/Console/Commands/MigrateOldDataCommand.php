<?php

namespace App\Console\Commands;

use App\Models\AdditionalTab;
use App\Models\Category;
use App\Models\CategoryCode;
use App\Models\Gallery;
use App\Models\Language;
use App\Models\PriceDetail;
use App\Models\Product;
use App\Models\ProductCode;
use App\Models\Tab;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateOldDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:old-data
                            {--dry-run : Run migration without saving data}
                            {--fresh : Clean existing data before migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from old database (incalake_new) to new CMS database';

    /**
     * ID mappings for old -> new records
     */
    protected array $categoryMapping = [];
    protected array $productMapping = [];
    protected array $galleryMapping = [];

    /**
     * Statistics counters
     */
    protected array $stats = [
        'categories' => ['success' => 0, 'failed' => 0],
        'services' => ['success' => 0, 'failed' => 0],
        'products' => ['success' => 0, 'failed' => 0],
        'galleries' => ['success' => 0, 'failed' => 0],
        'prices' => ['success' => 0, 'failed' => 0],
        'tabs' => ['success' => 0, 'failed' => 0],
    ];

    /**
     * Default user ID for created records
     */
    protected int $adminUserId;

    /**
     * Default service ID for products
     */
    protected int $defaultServiceId;

    /**
     * Language IDs
     */
    protected int $englishLangId;
    protected int $spanishLangId;

    /**
     * Start time for performance tracking
     */
    protected float $startTime;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = microtime(true);

        $this->info('🚀 Migrando datos del sistema antiguo...');
        $this->newLine();

        // Verify connections
        if (!$this->verifyConnections()) {
            return 1;
        }

        // Get admin user and languages
        if (!$this->setupRequirements()) {
            return 1;
        }

        // Clean data if --fresh option
        if ($this->option('fresh')) {
            if (!$this->confirm('¿Estás seguro de limpiar todos los datos existentes?', false)) {
                $this->warn('Migración cancelada.');
                return 1;
            }
            $this->cleanExistingData();
        }

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se guardarán datos en la BD');
            $this->newLine();
        }

        // Execute migration steps
        $this->migrateCategories($isDryRun);
        $this->migrateServices($isDryRun);
        $this->migrateProducts($isDryRun);
        $this->migrateGalleries($isDryRun);
        $this->migratePrices($isDryRun);
        $this->migrateTabsAndHighlights($isDryRun);

        // Show summary
        $this->showSummary();

        return 0;
    }

    /**
     * Verify database connections
     */
    protected function verifyConnections(): bool
    {
        try {
            // Test old DB connection
            $oldDbName = DB::connection('mysql_old')->getDatabaseName();
            $this->info("✓ Conectado a BD antigua: {$oldDbName}");

            // Test new DB connection
            $newDbName = DB::connection('mysql')->getDatabaseName();
            $this->info("✓ Conectado a BD nueva: {$newDbName}");

            $this->newLine();
            return true;
        } catch (\Exception $e) {
            $this->error("✗ Error de conexión: {$e->getMessage()}");
            Log::error('Database connection error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Setup required data (admin user, languages)
     */
    protected function setupRequirements(): bool
    {
        try {
            // Get or create admin user
            $admin = User::where('email', 'admin@incalake.com')->first();
            if (!$admin) {
                $this->warn('Usuario admin no encontrado. Usando user_id = 1');
                $this->adminUserId = 1;
            } else {
                $this->adminUserId = $admin->id;
            }

            // Get language IDs
            $englishLang = Language::where('code', 'en')->first();
            $spanishLang = Language::where('code', 'es')->first();

            if (!$englishLang || !$spanishLang) {
                $this->error('✗ Idiomas no encontrados. Ejecuta los seeders primero.');
                return false;
            }

            $this->englishLangId = $englishLang->id;
            $this->spanishLangId = $spanishLang->id;

            // Get or create default service code
            $defaultServiceCode = DB::table('service_codes')->where('code', 'default-migration')->first();
            if (!$defaultServiceCode) {
                $serviceCodeId = DB::table('service_codes')->insertGetId([
                    'code' => 'default-migration',
                    'user_id' => $this->adminUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $serviceCodeId = $defaultServiceCode->id;
            }

            // Get or create default service
            $defaultService = DB::table('services')->where('url', 'default-migration')->first();
            if (!$defaultService) {
                $serviceId = DB::table('services')->insertGetId([
                    'url' => 'default-migration',
                    'page_title' => 'Default Service',
                    'page_description' => 'Service created during migration',
                    'language_id' => $this->englishLangId,
                    'service_code_id' => $serviceCodeId,
                    'uri' => 'default-migration',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->defaultServiceId = $serviceId;
            } else {
                $this->defaultServiceId = $defaultService->id;
            }

            return true;
        } catch (\Exception $e) {
            $this->error("✗ Error configurando requisitos: {$e->getMessage()}");
            Log::error('Setup requirements error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Clean existing data
     */
    protected function cleanExistingData(): void
    {
        $this->info('Limpiando datos existentes...');

        DB::beginTransaction();
        try {
            // Delete in correct order (respecting foreign keys)
            DB::table('prices')->delete();
            DB::table('price_details')->delete();
            DB::table('additional_tabs')->delete();
            DB::table('tabs')->delete();
            DB::table('product_gallery')->delete();
            DB::table('product_category')->delete();
            DB::table('products')->delete();
            DB::table('product_codes')->delete();
            DB::table('categories')->delete();
            DB::table('category_codes')->delete();
            DB::table('galleries')->whereNotIn('id', [1, 2, 3])->delete(); // Keep default galleries

            DB::commit();
            $this->info('✓ Datos limpiados correctamente');
            $this->newLine();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("✗ Error limpiando datos: {$e->getMessage()}");
            Log::error('Clean data error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Migrate categories
     */
    protected function migrateCategories(bool $isDryRun): void
    {
        $this->info('[1/6] Migrando categorías...');

        try {
            $oldCategories = DB::connection('mysql_old')
                ->table('categories')
                ->get();

            $total = $oldCategories->count();

            if ($total === 0) {
                $this->warn('  ⚠ No hay categorías para migrar');
                $this->newLine();
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($oldCategories as $oldCategory) {
                try {
                    if (!$isDryRun) {
                        // Create category code
                        $categoryCode = CategoryCode::create([
                            'code' => Str::slug($oldCategory->name_en ?? $oldCategory->name),
                            'image_id' => null, // Will be updated later if needed
                        ]);

                        // Create category in English
                        $categoryEn = Category::create([
                            'name' => $oldCategory->name_en ?? $oldCategory->name,
                            'description' => $oldCategory->description_en ?? $oldCategory->description ?? '',
                            'language_id' => $this->englishLangId,
                            'category_code_id' => $categoryCode->id,
                            'user_id' => $this->adminUserId,
                        ]);

                        // Create category in Spanish
                        Category::create([
                            'name' => $oldCategory->name_es ?? $oldCategory->name,
                            'description' => $oldCategory->description_es ?? $oldCategory->description ?? '',
                            'language_id' => $this->spanishLangId,
                            'category_code_id' => $categoryCode->id,
                            'user_id' => $this->adminUserId,
                        ]);

                        // Store mapping
                        $this->categoryMapping[$oldCategory->id] = $categoryEn->id;
                    }

                    $this->stats['categories']['success']++;
                } catch (\Exception $e) {
                    $this->stats['categories']['failed']++;
                    Log::error('Category migration error', [
                        'old_id' => $oldCategory->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$this->stats['categories']['success']} categorías migradas correctamente");
            if ($this->stats['categories']['failed'] > 0) {
                $this->warn("  ✗ {$this->stats['categories']['failed']} errores");
            }
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error migrando categorías: {$e->getMessage()}");
            Log::error('Categories migration error', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Migrate services
     */
    protected function migrateServices(bool $isDryRun): void
    {
        $this->info('[2/6] Migrando servicios...');

        try {
            $oldServices = DB::connection('mysql_old')
                ->table('services')
                ->whereNull('deleted_at')
                ->get();

            $total = $oldServices->count();

            if ($total === 0) {
                $this->info('  ✓ 0 servicios (no hay datos)');
                $this->newLine();
                return;
            }

            // TODO: Implement service migration when table structure is known
            $this->warn('  ⚠ Migración de servicios pendiente de implementar');
            $this->newLine();

        } catch (\Exception $e) {
            $this->warn("  ⚠ No se encontró tabla 'services' en BD antigua");
            Log::warning('Services table not found', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Migrate products/tours
     */
    protected function migrateProducts(bool $isDryRun): void
    {
        $this->info('[3/6] Migrando productos/tours...');

        try {
            $oldProducts = DB::connection('mysql_old')
                ->table('products')
                ->whereNull('deleted_at')
                ->get();

            $total = $oldProducts->count();

            if ($total === 0) {
                $this->warn('  ⚠ No hay productos para migrar');
                $this->newLine();
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($oldProducts as $oldProduct) {
                try {
                    if (!$isDryRun) {
                        // Create product code
                        $productCode = ProductCode::create([
                            'code' => $oldProduct->code ?? 'TOUR-' . $oldProduct->id,
                            'user_id' => $this->adminUserId,
                        ]);

                        // Create product
                        $product = Product::create([
                            'title' => $oldProduct->title ?? $oldProduct->name ?? 'Untitled Tour',
                            'subtitle' => $oldProduct->subtitle ?? '',
                            'code' => $oldProduct->code ?? 'TOUR-' . $oldProduct->id,
                            'nearest_city' => $oldProduct->nearest_city ?? '',
                            'nearest_airport' => $oldProduct->nearest_airport ?? '',
                            'service_id' => $oldProduct->service_id ?? $this->defaultServiceId,
                            'start_time' => $oldProduct->start_time ?? '09:00:00',
                            'duration' => $oldProduct->duration ?? '1 day',
                            'capacity' => $oldProduct->capacity ?? 10,
                            'attachments' => $oldProduct->attachments ?? null,
                            'product_code_id' => $productCode->id,
                            'status' => $oldProduct->status ?? 1,
                            'policies' => $oldProduct->policies ?? null,
                            'booking_anticipation' => $oldProduct->booking_anticipation ?? 24,
                            'data_requirement' => $this->mapDataRequirement($oldProduct->data_requirement ?? 'not_required'),
                            'multiple_forms' => $oldProduct->multiple_forms ?? false,
                        ]);

                        // Associate categories
                        if (!empty($oldProduct->category_id)) {
                            $categoryIds = is_string($oldProduct->category_id)
                                ? explode(',', $oldProduct->category_id)
                                : [$oldProduct->category_id];

                            $newCategoryIds = [];
                            foreach ($categoryIds as $oldCatId) {
                                if (isset($this->categoryMapping[$oldCatId])) {
                                    $newCategoryIds[] = $this->categoryMapping[$oldCatId];
                                }
                            }

                            if (!empty($newCategoryIds)) {
                                $product->categories()->attach($newCategoryIds);
                            }
                        }

                        // Store mapping
                        $this->productMapping[$oldProduct->id] = $product->id;
                    }

                    $this->stats['products']['success']++;
                } catch (\Exception $e) {
                    $this->stats['products']['failed']++;
                    Log::error('Product migration error', [
                        'old_id' => $oldProduct->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$this->stats['products']['success']} productos migrados");
            if ($this->stats['products']['failed'] > 0) {
                $this->warn("  ✗ {$this->stats['products']['failed']} errores");
            }
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error migrando productos: {$e->getMessage()}");
            Log::error('Products migration error', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Migrate galleries
     */
    protected function migrateGalleries(bool $isDryRun): void
    {
        $this->info('[4/6] Migrando galerías...');

        try {
            $oldGalleries = DB::connection('mysql_old')
                ->table('galleries')
                ->get();

            $total = $oldGalleries->count();

            if ($total === 0) {
                $this->warn('  ⚠ No hay galerías para migrar');
                $this->newLine();
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($oldGalleries as $oldGallery) {
                try {
                    if (!$isDryRun) {
                        // Create gallery
                        $gallery = Gallery::create([
                            'file_url' => $oldGallery->file_url ?? $oldGallery->image_url ?? '',
                            'file_details' => $oldGallery->file_details ?? $oldGallery->description ?? '',
                            'file_type' => $oldGallery->file_type ?? 1, // 1 = image
                            'file_folder' => $oldGallery->file_folder ?? 'products',
                            'user_id' => $this->adminUserId,
                        ]);

                        // Associate with product if exists
                        if (!empty($oldGallery->product_id) && isset($this->productMapping[$oldGallery->product_id])) {
                            $newProductId = $this->productMapping[$oldGallery->product_id];
                            DB::table('product_gallery')->insert([
                                'product_id' => $newProductId,
                                'gallery_id' => $gallery->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // Store mapping
                        $this->galleryMapping[$oldGallery->id] = $gallery->id;
                    }

                    $this->stats['galleries']['success']++;
                } catch (\Exception $e) {
                    $this->stats['galleries']['failed']++;
                    Log::error('Gallery migration error', [
                        'old_id' => $oldGallery->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$this->stats['galleries']['success']} imágenes asociadas");
            if ($this->stats['galleries']['failed'] > 0) {
                $this->warn("  ✗ {$this->stats['galleries']['failed']} errores");
            }
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error migrando galerías: {$e->getMessage()}");
            Log::error('Galleries migration error', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Migrate prices
     */
    protected function migratePrices(bool $isDryRun): void
    {
        $this->info('[5/6] Migrando precios...');

        try {
            $oldPrices = DB::connection('mysql_old')
                ->table('product_prices')
                ->get();

            $total = $oldPrices->count();

            if ($total === 0) {
                $this->warn('  ⚠ No hay precios para migrar');
                $this->newLine();
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($oldPrices as $oldPrice) {
                try {
                    if (!$isDryRun && isset($this->productMapping[$oldPrice->product_id])) {
                        $newProductId = $this->productMapping[$oldPrice->product_id];

                        // Create price detail (assuming adult by default)
                        $priceDetail = PriceDetail::create([
                            'product_id' => $newProductId,
                            'age_stage_id' => 1, // Assuming 1 = Adult
                            'nationality_id' => null, // Default for all nationalities
                            'min_age' => $oldPrice->min_age ?? 18,
                            'max_age' => $oldPrice->max_age ?? 999,
                        ]);

                        // Create price
                        DB::table('prices')->insert([
                            'price_detail_id' => $priceDetail->id,
                            'effective_from' => $oldPrice->effective_from ?? now(),
                            'effective_to' => $oldPrice->effective_to ?? now()->addYear(),
                            'amount' => $oldPrice->amount ?? $oldPrice->price ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->stats['prices']['success']++;
                    }
                } catch (\Exception $e) {
                    $this->stats['prices']['failed']++;
                    Log::error('Price migration error', [
                        'old_id' => $oldPrice->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$this->stats['prices']['success']} precios migrados");
            if ($this->stats['prices']['failed'] > 0) {
                $this->warn("  ✗ {$this->stats['prices']['failed']} errores");
            }
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error migrando precios: {$e->getMessage()}");
            Log::error('Prices migration error', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Migrate tabs and highlights (itineraries)
     */
    protected function migrateTabsAndHighlights(bool $isDryRun): void
    {
        $this->info('[6/6] Migrando itinerarios y highlights...');

        try {
            // Migrate main tabs
            $oldProducts = DB::connection('mysql_old')
                ->table('products')
                ->get();

            $total = $oldProducts->count();

            if ($total === 0) {
                $this->warn('  ⚠ No hay tabs para migrar');
                $this->newLine();
                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            DB::beginTransaction();

            foreach ($oldProducts as $oldProduct) {
                try {
                    if (!$isDryRun && isset($this->productMapping[$oldProduct->id])) {
                        $newProductId = $this->productMapping[$oldProduct->id];

                        // Create main tab
                        Tab::create([
                            'description' => $oldProduct->description ?? '',
                            'itinerary' => $oldProduct->itinerary ?? '',
                            'includes' => $oldProduct->includes ?? '',
                            'information' => $oldProduct->information ?? '',
                            'map' => $oldProduct->map ?? '',
                            'recommendations' => $oldProduct->recommendations ?? '',
                            'departure_return' => $oldProduct->departure_return ?? '',
                            'product_id' => $newProductId,
                        ]);

                        // Create highlights as additional tab if exists
                        if (!empty($oldProduct->highlights)) {
                            AdditionalTab::create([
                                'icon' => 'star',
                                'name' => 'Highlights',
                                'content' => $oldProduct->highlights,
                                'product_id' => $newProductId,
                            ]);
                        }

                        $this->stats['tabs']['success']++;
                    }
                } catch (\Exception $e) {
                    $this->stats['tabs']['failed']++;
                    Log::error('Tab migration error', [
                        'old_id' => $oldProduct->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✓ {$this->stats['tabs']['success']} tabs migrados");
            if ($this->stats['tabs']['failed'] > 0) {
                $this->warn("  ✗ {$this->stats['tabs']['failed']} errores");
            }
            $this->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Error migrando tabs: {$e->getMessage()}");
            Log::error('Tabs migration error', ['error' => $e->getMessage()]);
            $this->newLine();
        }
    }

    /**
     * Show migration summary
     */
    protected function showSummary(): void
    {
        $this->newLine();
        $this->info('✓ Migración completada exitosamente!');
        $this->newLine();

        $this->info('Resumen:');
        $this->table(
            ['Tipo', 'Exitosos', 'Errores', 'Total'],
            [
                [
                    'Categorías',
                    $this->stats['categories']['success'],
                    $this->stats['categories']['failed'],
                    $this->stats['categories']['success'] + $this->stats['categories']['failed']
                ],
                [
                    'Servicios',
                    $this->stats['services']['success'],
                    $this->stats['services']['failed'],
                    $this->stats['services']['success'] + $this->stats['services']['failed']
                ],
                [
                    'Productos',
                    $this->stats['products']['success'],
                    $this->stats['products']['failed'],
                    $this->stats['products']['success'] + $this->stats['products']['failed']
                ],
                [
                    'Galerías',
                    $this->stats['galleries']['success'],
                    $this->stats['galleries']['failed'],
                    $this->stats['galleries']['success'] + $this->stats['galleries']['failed']
                ],
                [
                    'Precios',
                    $this->stats['prices']['success'],
                    $this->stats['prices']['failed'],
                    $this->stats['prices']['success'] + $this->stats['prices']['failed']
                ],
                [
                    'Tabs',
                    $this->stats['tabs']['success'],
                    $this->stats['tabs']['failed'],
                    $this->stats['tabs']['success'] + $this->stats['tabs']['failed']
                ],
            ]
        );

        $totalSuccess = array_sum(array_column($this->stats, 'success'));
        $totalFailed = array_sum(array_column($this->stats, 'failed'));
        $totalRecords = $totalSuccess + $totalFailed;

        $elapsedTime = round(microtime(true) - $this->startTime, 2);

        $this->newLine();
        $this->info("Total de registros: {$totalRecords}");
        $this->info("Tiempo transcurrido: {$elapsedTime}s");

        if ($totalFailed > 0) {
            $this->newLine();
            $this->warn("⚠️  {$totalFailed} registros fallaron. Revisa los logs para más detalles.");
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('⚠️  MODO DRY-RUN: No se guardaron cambios en la base de datos');
        }

        $this->newLine();
    }

    /**
     * Map data_requirement string to integer
     */
    protected function mapDataRequirement($value): int
    {
        // If it's already an integer, return it
        if (is_numeric($value)) {
            return (int) $value;
        }

        // Map string values to integers
        // 1=ask data before purchase, 2=after purchase, 3=no passenger data required
        return match(strtolower($value)) {
            'required', 'before', 'before_purchase' => 1,
            'after', 'after_purchase' => 2,
            'not_required', 'none', 'no_data' => 3,
            default => 3, // Default to not required
        };
    }
}
