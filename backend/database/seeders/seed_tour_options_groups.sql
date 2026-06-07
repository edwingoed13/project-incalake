-- ============================================================================
-- Tour Options — seed grouping for QA / testing
--
-- Links 3 sibling groups from the live catalog so the option-selector UI on
-- the detail page has real data to render:
--
--   GROUP A: Uros + Taquile clásico 1D    (parent: 306 Compartido — cheapest)
--   GROUP B: Uros + Taquile Lancha Rápida (parent: 122 +Guía)
--   GROUP C: Uros + Amantani + Taquile 2D1N (parent: 86  +Guía Privado)
--
-- Apply order:
--   1. Run the migration first   (php artisan migrate)
--   2. Run this seed in MySQL    (mysql -u root db_name < seed_tour_options_groups.sql)
--   3. Bump the listing cache    (CacheService::bumpToursVersion or restart)
--
-- Reversible: every UPDATE sets parent_tour_id back to NULL.
-- ============================================================================

-- ---------- GROUP A — Uros + Taquile clásico 1D ----------
-- 306: "Tour a Los Uros y Taquile clásico 1D - Compartido"  <-- PARENT
-- 120: "Tour Uros y Taquile Clásico + Guía Privado en 1 día"
-- 121: "Tour PRIVADO a Los Uros y la Isla de Taquile en 1 día"

UPDATE tours SET parent_tour_id = NULL,
                 option_label   = 'Compartido',
                 option_color   = 'blue'
WHERE id = 306;

UPDATE tours SET parent_tour_id = 306,
                 option_label   = '+ Guía Privado',
                 option_color   = 'violet'
WHERE id = 120;

UPDATE tours SET parent_tour_id = 306,
                 option_label   = 'Privado',
                 option_color   = 'amber'
WHERE id = 121;

-- ---------- GROUP B — Uros + Taquile Lancha Rápida ----------
-- 122: "Tour Uros y Taquile (Lancha Rápida + Guía Privado)"  <-- PARENT
-- 123: "Tour Privado a los Uros Y Taquile (Lancha Rapida)"

UPDATE tours SET parent_tour_id = NULL,
                 option_label   = '+ Guía Privado',
                 option_color   = 'violet'
WHERE id = 122;

UPDATE tours SET parent_tour_id = 122,
                 option_label   = 'Privado',
                 option_color   = 'amber'
WHERE id = 123;

-- ---------- GROUP C — Uros + Amantani + Taquile 2D1N ----------
-- 86:  "Tour Uros, Taquile y Amantani en 2 Dias + Guía Privado"  <-- PARENT
-- 87:  "Tour Privado a Amantani, los Uros Y Taquile en 2 Días"

UPDATE tours SET parent_tour_id = NULL,
                 option_label   = '+ Guía Privado',
                 option_color   = 'violet'
WHERE id = 86;

UPDATE tours SET parent_tour_id = 86,
                 option_label   = 'Privado',
                 option_color   = 'amber'
WHERE id = 87;

-- ============================================================================
-- Verify
-- ============================================================================
-- SELECT t.id, t.option_label, t.option_color, t.parent_tour_id, tr.h1_title
-- FROM tours t
-- LEFT JOIN tour_translations tr ON tr.tour_id = t.id AND tr.language_id = 1
-- WHERE t.id IN (306, 120, 121, 122, 123, 86, 87)
-- ORDER BY COALESCE(t.parent_tour_id, t.id), t.id;
