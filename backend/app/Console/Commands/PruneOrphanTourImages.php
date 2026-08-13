<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes tour image files that nothing in the database points at.
 *
 * Two sources of orphans:
 *
 *  1. tours/temp/ — Step 5 of the wizard uploads straight to disk, before the
 *     tour is saved. Files only leave that folder when a save moves them into
 *     tours/{id}/. Abandon the wizard (or discard a draft) and they stay there
 *     forever. Nothing ever cleaned this folder.
 *
 *  2. tours/{id}/ — files whose row went away by a route that didn't delete
 *     them (hard-deleted tours, interrupted saves).
 *
 * Deleting the wrong file means losing a production photo with no undo, so:
 * dry-run is the default, only files older than --days are eligible, and the
 * command refuses to run if the set of referenced paths comes back empty —
 * which would mean a broken query, not an empty gallery.
 */
class PruneOrphanTourImages extends Command
{
    protected $signature = 'tours:prune-images
                            {--days=7 : Only consider files older than this many days}
                            {--force : Actually delete. Without it, nothing is removed}
                            {--limit=0 : Stop after this many deletions (0 = no limit)}
                            {--json : Print the full candidate list as JSON and stop. Implies no deletion}';

    protected $description = 'Delete tour image files no database row references';

    /**
     * Every column that can hold a path into the tours/ tree. Missing one of
     * these would make the command delete a file that IS in use.
     */
    private const REFERENCING_COLUMNS = [
        'tour_media_gallery' => ['image_path', 'original_path'],
        'tours' => ['featured_image_path', 'thumbnail_path', 'attachments_path'],
        'tour_translations' => ['og_image_path', 'twitter_image_path'],
    ];

    /**
     * Tables whose free text and JSON can embed an image path rather than hold
     * it in a column of its own.
     *
     * The rich-text editor uploads to tours/temp/ and drops the returned URL
     * straight into the description HTML; meeting points keep theirs inside a
     * JSON blob. Neither is a path column, so scanning only the list above
     * declared those images orphaned — this command would have deleted content
     * that pages were actively displaying.
     */
    private const SCANNED_TABLES = ['tour_translations', 'tours'];

    /** Any tours/… path appearing inside text, wherever it sits. */
    private const PATH_PATTERN = '#tours/[A-Za-z0-9_\-./%()\x{00C0}-\x{024F} ]+?\.[A-Za-z0-9]{2,5}#u';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $force = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));
        $disk = Storage::disk('public');

        $referenced = $this->referencedPaths();

        // A query that silently returns nothing (renamed table, wrong column)
        // would make every file on disk look orphaned. Refuse rather than wipe.
        if ($referenced->isEmpty()) {
            $this->error('No referenced image paths found at all. That points to a broken query, not an empty gallery. Aborting without deleting anything.');
            return self::FAILURE;
        }

        // Silent in --json mode: any human line here lands in the same stream
        // and makes the payload unparseable for whoever asked for JSON.
        if (!$this->option('json')) {
            $this->info(sprintf('Referenced paths in DB: %d', $referenced->count()));
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $candidates = [];
        $skippedRecent = 0;

        foreach ($disk->allFiles('tours') as $path) {
            if ($referenced->has($path)) {
                continue;
            }
            // Never touch something that may still be mid-upload or mid-edit.
            if ($disk->lastModified($path) > $cutoff) {
                $skippedRecent++;
                continue;
            }
            $candidates[] = $path;
        }

        if ($limit > 0) {
            $candidates = array_slice($candidates, 0, $limit);
        }

        // The human output truncates to 15 lines, which is fine for deciding
        // but useless for acting — backing these up before deletion needs every
        // path. Never deletes: this is the "let me look first" mode.
        if ($this->option('json')) {
            $this->line(json_encode([
                'count' => count($candidates),
                'skipped_recent' => $skippedRecent,
                'paths' => array_values($candidates),
            ]));

            return self::SUCCESS;
        }

        if (empty($candidates)) {
            $this->info(sprintf('Nothing to prune. (%d file(s) skipped for being newer than %d day(s).)', $skippedRecent, $days));
            return self::SUCCESS;
        }

        $bytes = 0;
        foreach ($candidates as $path) {
            try {
                $bytes += $disk->size($path);
            } catch (\Throwable) {
                // Vanished between listing and sizing — harmless.
            }
        }

        $this->newLine();
        $this->line(sprintf(
            '%d orphan file(s), %s. %d skipped for being newer than %d day(s).',
            count($candidates),
            $this->humanBytes($bytes),
            $skippedRecent,
            $days
        ));

        $temp = array_filter($candidates, fn ($p) => str_starts_with($p, 'tours/temp/'));
        $this->line(sprintf('  tours/temp/ (never saved): %d', count($temp)));
        $this->line(sprintf('  tours/{id}/ (row gone):    %d', count($candidates) - count($temp)));
        $this->newLine();

        foreach (array_slice($candidates, 0, 15) as $path) {
            $this->line('  ' . $path);
        }
        if (count($candidates) > 15) {
            $this->line(sprintf('  ... and %d more', count($candidates) - 15));
        }

        if (!$force) {
            $this->newLine();
            $this->warn('DRY RUN — nothing deleted. Re-run with --force to delete.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;
        foreach ($candidates as $path) {
            // Re-check: a save may have claimed the file since the scan.
            if ($this->referencedPaths()->has($path)) {
                continue;
            }
            try {
                $disk->delete($path) ? $deleted++ : $failed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('  %s: %s', $path, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf('Deleted %d file(s), %s freed.', $deleted, $this->humanBytes($bytes)));
        if ($failed > 0) {
            $this->warn(sprintf('%d file(s) could not be deleted.', $failed));
        }

        return self::SUCCESS;
    }

    /**
     * Paths referenced anywhere, as a lookup keyed by path.
     *
     * Deliberately queried through the DB facade rather than Eloquent: model
     * scopes would hide soft-deleted tours, and a soft-deleted tour can be
     * restored — its photos must survive.
     *
     * Memoised per run; the post-delete re-check calls it again.
     */
    private ?\Illuminate\Support\Collection $referencedCache = null;

    private function referencedPaths(): \Illuminate\Support\Collection
    {
        if ($this->referencedCache !== null) {
            return $this->referencedCache;
        }

        $paths = collect();

        foreach (self::REFERENCING_COLUMNS as $table => $columns) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (!\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                    continue;
                }
                DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->pluck($column)
                    ->each(function ($value) use ($paths) {
                        $paths->put(ltrim((string) $value, '/'), true);
                    });
            }
        }

        $this->collectEmbeddedPaths($paths);

        return $this->referencedCache = $paths;
    }

    /**
     * Pull tours/… paths out of every text-ish column of the scanned tables.
     *
     * Deliberately broad rather than a curated column list: a new rich-text
     * field or JSON blob holding an image would otherwise silently become
     * deletable, and the cost of a false "referenced" is a file kept, while
     * the cost of a miss is a photo gone from a live page.
     */
    private function collectEmbeddedPaths(\Illuminate\Support\Collection $paths): void
    {
        foreach (self::SCANNED_TABLES as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $columns = [];
            foreach (\Illuminate\Support\Facades\Schema::getColumnListing($table) as $column) {
                $type = \Illuminate\Support\Facades\Schema::getColumnType($table, $column);
                if (in_array($type, ['text', 'string', 'json', 'longtext', 'mediumtext'], true)) {
                    $columns[] = $column;
                }
            }
            if (empty($columns)) {
                continue;
            }

            // Chunked: descriptions carry a lot of HTML and these tables hold
            // one row per tour per language.
            DB::table($table)->select($columns)->orderBy(
                \Illuminate\Support\Facades\Schema::hasColumn($table, 'id') ? 'id' : $columns[0]
            )->chunk(200, function ($rows) use ($paths) {
                foreach ($rows as $row) {
                    foreach ((array) $row as $value) {
                        if (!is_string($value) || $value === '' || !str_contains($value, 'tours/')) {
                            continue;
                        }
                        if (preg_match_all(self::PATH_PATTERN, $value, $matches)) {
                            foreach ($matches[0] as $match) {
                                // Editor URLs arrive percent-encoded and often
                                // absolute; store what the disk listing shows.
                                $paths->put(ltrim(urldecode($match), '/'), true);
                            }
                        }
                    }
                }
            });
        }
    }

    private function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf('%.1f %s', $value, $units[$i]);
    }
}
