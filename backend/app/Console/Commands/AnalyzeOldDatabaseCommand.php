<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyzeOldDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:old-database
                            {table? : Specific table to analyze}
                            {--sample=5 : Number of sample rows to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze the structure and data of the old database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analizando base de datos antigua...');
        $this->newLine();

        try {
            $dbName = DB::connection('mysql_old')->getDatabaseName();
            $this->info("Base de datos: {$dbName}");
            $this->newLine();

            $specificTable = $this->argument('table');

            if ($specificTable) {
                $this->analyzeTable($specificTable);
            } else {
                $this->analyzeTables();
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            Log::error('Analyze old database error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Analyze all tables in the database
     */
    protected function analyzeTables(): void
    {
        $tables = DB::connection('mysql_old')
            ->select('SHOW TABLES');

        $dbName = DB::connection('mysql_old')->getDatabaseName();
        $tableKey = "Tables_in_{$dbName}";

        $this->info('Tablas encontradas:');
        $this->newLine();

        $tableData = [];

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey};

            try {
                $count = DB::connection('mysql_old')
                    ->table($tableName)
                    ->count();

                $tableData[] = [
                    'Tabla' => $tableName,
                    'Registros' => $count,
                ];
            } catch (\Exception $e) {
                $tableData[] = [
                    'Tabla' => $tableName,
                    'Registros' => 'Error',
                ];
            }
        }

        $this->table(['Tabla', 'Registros'], $tableData);

        $this->newLine();
        $this->info('Para ver detalles de una tabla específica:');
        $this->comment('php artisan analyze:old-database {tabla}');
        $this->newLine();
    }

    /**
     * Analyze a specific table
     */
    protected function analyzeTable(string $tableName): void
    {
        try {
            $this->info("Tabla: {$tableName}");
            $this->newLine();

            // Get table structure
            $columns = DB::connection('mysql_old')
                ->select("DESCRIBE {$tableName}");

            $this->info('Estructura:');
            $columnData = [];
            foreach ($columns as $column) {
                $columnData[] = [
                    'Campo' => $column->Field,
                    'Tipo' => $column->Type,
                    'Nulo' => $column->Null,
                    'Clave' => $column->Key,
                    'Default' => $column->Default ?? 'NULL',
                ];
            }
            $this->table(['Campo', 'Tipo', 'Nulo', 'Clave', 'Default'], $columnData);

            $this->newLine();

            // Get row count
            $count = DB::connection('mysql_old')
                ->table($tableName)
                ->count();

            $this->info("Total de registros: {$count}");
            $this->newLine();

            // Show sample data
            if ($count > 0) {
                $sampleSize = $this->option('sample');
                $this->info("Muestra de datos (primeros {$sampleSize} registros):");
                $this->newLine();

                $samples = DB::connection('mysql_old')
                    ->table($tableName)
                    ->limit($sampleSize)
                    ->get();

                foreach ($samples as $index => $sample) {
                    $this->info("Registro #" . ($index + 1) . ":");
                    $sampleArray = (array) $sample;

                    foreach ($sampleArray as $key => $value) {
                        if (is_string($value) && strlen($value) > 100) {
                            $value = substr($value, 0, 100) . '...';
                        }
                        $this->comment("  {$key}: " . ($value ?? 'NULL'));
                    }
                    $this->newLine();
                }
            }

            // Check for deleted_at column (soft deletes)
            $hasDeletedAt = collect($columns)->contains(function ($column) {
                return $column->Field === 'deleted_at';
            });

            if ($hasDeletedAt) {
                $deletedCount = DB::connection('mysql_old')
                    ->table($tableName)
                    ->whereNotNull('deleted_at')
                    ->count();

                $activeCount = $count - $deletedCount;

                $this->info("Registros activos: {$activeCount}");
                $this->warn("Registros eliminados (soft delete): {$deletedCount}");
                $this->newLine();
            }

            // Show statistics for important fields
            $this->showFieldStatistics($tableName, $columns);

        } catch (\Exception $e) {
            $this->error("Error analizando tabla '{$tableName}': {$e->getMessage()}");
            Log::error('Analyze table error', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show statistics for important fields
     */
    protected function showFieldStatistics(string $tableName, array $columns): void
    {
        try {
            // Check for category_id, product_id, etc.
            $relationFields = collect($columns)
                ->filter(function ($column) {
                    return str_ends_with($column->Field, '_id') &&
                           !in_array($column->Field, ['id', 'user_id']);
                })
                ->pluck('Field')
                ->toArray();

            if (!empty($relationFields)) {
                $this->info('Estadísticas de relaciones:');

                foreach ($relationFields as $field) {
                    $stats = DB::connection('mysql_old')
                        ->table($tableName)
                        ->select($field, DB::raw('COUNT(*) as count'))
                        ->groupBy($field)
                        ->orderBy('count', 'desc')
                        ->limit(5)
                        ->get();

                    if ($stats->isNotEmpty()) {
                        $this->comment("  {$field}:");
                        foreach ($stats as $stat) {
                            $this->line("    {$stat->{$field}}: {$stat->count} registros");
                        }
                    }
                }
                $this->newLine();
            }

            // Check for JSON fields
            $jsonFields = collect($columns)
                ->filter(function ($column) {
                    return str_contains(strtolower($column->Type), 'json') ||
                           str_contains(strtolower($column->Type), 'text');
                })
                ->pluck('Field')
                ->toArray();

            if (!empty($jsonFields)) {
                $this->info('Campos de texto/JSON encontrados:');
                foreach ($jsonFields as $field) {
                    $this->comment("  - {$field}");
                }
                $this->newLine();
            }

        } catch (\Exception $e) {
            // Silent fail for statistics
            Log::warning('Field statistics error', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
        }
    }
}
