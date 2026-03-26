-- ================================================
-- QUERIES DE VERIFICACIÓN POST-MIGRACIÓN
-- ================================================
-- Ejecutar estas queries después de migrar datos
-- para verificar que todo se migró correctamente
-- ================================================

-- ========================================
-- 1. CONTEO DE REGISTROS
-- ========================================

-- Base de datos ANTIGUA (incalake_new)
USE incalake_new;

SELECT 'ANTIGUA - Categories' AS tabla, COUNT(*) AS total FROM categories WHERE deleted_at IS NULL
UNION ALL
SELECT 'ANTIGUA - Products', COUNT(*) FROM products WHERE deleted_at IS NULL
UNION ALL
SELECT 'ANTIGUA - Galleries', COUNT(*) FROM galleries WHERE deleted_at IS NULL
UNION ALL
SELECT 'ANTIGUA - Product Prices', COUNT(*) FROM product_prices WHERE deleted_at IS NULL;

-- Base de datos NUEVA (inc0910d_cms_incalake)
USE inc0910d_cms_incalake;

SELECT 'NUEVA - Category Codes' AS tabla, COUNT(*) AS total FROM category_codes WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Categories (en+es)', COUNT(*) FROM categories WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Product Codes', COUNT(*) FROM product_codes WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Products', COUNT(*) FROM products WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Galleries', COUNT(*) FROM galleries WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Price Details', COUNT(*) FROM price_details WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Prices', COUNT(*) FROM prices WHERE deleted_at IS NULL
UNION ALL
SELECT 'NUEVA - Tabs', COUNT(*) FROM tabs WHERE deleted_at IS NULL;

-- ========================================
-- 2. VERIFICAR CATEGORÍAS
-- ========================================

USE inc0910d_cms_incalake;

-- Categorías por idioma (debería haber 2 por cada category_code)
SELECT
    cc.code,
    COUNT(c.id) AS total_traducciones,
    GROUP_CONCAT(l.code) AS idiomas
FROM category_codes cc
LEFT JOIN categories c ON c.category_code_id = cc.id
LEFT JOIN languages l ON l.id = c.language_id
WHERE cc.deleted_at IS NULL
GROUP BY cc.id, cc.code;

-- ========================================
-- 3. VERIFICAR PRODUCTOS
-- ========================================

-- Productos sin categorías (NO debería haber)
SELECT
    p.id,
    p.title,
    COUNT(pc.category_id) AS total_categorias
FROM products p
LEFT JOIN product_category pc ON pc.product_id = p.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title
HAVING total_categorias = 0;

-- Productos con categorías
SELECT
    p.id,
    p.title,
    COUNT(pc.category_id) AS total_categorias
FROM products p
LEFT JOIN product_category pc ON pc.product_id = p.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title
ORDER BY total_categorias DESC
LIMIT 10;

-- ========================================
-- 4. VERIFICAR GALERÍAS
-- ========================================

-- Productos con galerías
SELECT
    p.id,
    p.title,
    COUNT(pg.gallery_id) AS total_imagenes
FROM products p
LEFT JOIN product_gallery pg ON pg.product_id = p.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title
ORDER BY total_imagenes DESC
LIMIT 10;

-- Productos SIN galerías (pueden existir)
SELECT
    p.id,
    p.title
FROM products p
LEFT JOIN product_gallery pg ON pg.product_id = p.id
WHERE p.deleted_at IS NULL
    AND pg.gallery_id IS NULL
LIMIT 10;

-- ========================================
-- 5. VERIFICAR PRECIOS
-- ========================================

-- Productos con precios
SELECT
    p.id,
    p.title,
    COUNT(pd.id) AS total_price_details,
    COUNT(pr.id) AS total_prices
FROM products p
LEFT JOIN price_details pd ON pd.product_id = p.id
LEFT JOIN prices pr ON pr.price_detail_id = pd.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title
ORDER BY total_prices DESC
LIMIT 10;

-- Productos SIN precios (NO debería haber si tienen precios en BD antigua)
SELECT
    p.id,
    p.title
FROM products p
LEFT JOIN price_details pd ON pd.product_id = p.id
WHERE p.deleted_at IS NULL
    AND pd.id IS NULL
LIMIT 10;

-- ========================================
-- 6. VERIFICAR TABS
-- ========================================

-- Productos con tabs
SELECT
    p.id,
    p.title,
    CASE WHEN t.id IS NOT NULL THEN 'Sí' ELSE 'No' END AS tiene_tab,
    COUNT(at.id) AS total_additional_tabs
FROM products p
LEFT JOIN tabs t ON t.product_id = p.id
LEFT JOIN additional_tabs at ON at.product_id = p.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title, t.id
ORDER BY total_additional_tabs DESC
LIMIT 10;

-- Productos SIN tabs (pueden existir)
SELECT
    p.id,
    p.title
FROM products p
LEFT JOIN tabs t ON t.product_id = p.id
WHERE p.deleted_at IS NULL
    AND t.id IS NULL
LIMIT 10;

-- ========================================
-- 7. VERIFICAR INTEGRIDAD REFERENCIAL
-- ========================================

-- Categorías huérfanas (NO debería haber)
SELECT
    c.id,
    c.name,
    c.category_code_id
FROM categories c
LEFT JOIN category_codes cc ON cc.id = c.category_code_id
WHERE c.deleted_at IS NULL
    AND cc.id IS NULL;

-- Productos huérfanos (NO debería haber)
SELECT
    p.id,
    p.title,
    p.product_code_id
FROM products p
LEFT JOIN product_codes pc ON pc.id = p.product_code_id
WHERE p.deleted_at IS NULL
    AND pc.id IS NULL;

-- ========================================
-- 8. COMPARACIÓN ANTIGUA vs NUEVA
-- ========================================

-- Comparar totales
SELECT
    (SELECT COUNT(*) FROM incalake_new.products WHERE deleted_at IS NULL) AS productos_antiguos,
    (SELECT COUNT(*) FROM inc0910d_cms_incalake.products WHERE deleted_at IS NULL) AS productos_nuevos,
    (SELECT COUNT(*) FROM incalake_new.products WHERE deleted_at IS NULL) -
    (SELECT COUNT(*) FROM inc0910d_cms_incalake.products WHERE deleted_at IS NULL) AS diferencia;

-- ========================================
-- 9. VERIFICAR DATOS ESPECÍFICOS
-- ========================================

-- Ver primer producto completo
SELECT
    p.*,
    pc.code AS product_code,
    GROUP_CONCAT(DISTINCT c.name) AS categorias,
    COUNT(DISTINCT pg.gallery_id) AS total_imagenes,
    COUNT(DISTINCT pd.id) AS total_precios
FROM products p
LEFT JOIN product_codes pc ON pc.id = p.product_code_id
LEFT JOIN product_category pcat ON pcat.product_id = p.id
LEFT JOIN categories c ON c.id = pcat.category_id
LEFT JOIN product_gallery pg ON pg.product_id = p.id
LEFT JOIN price_details pd ON pd.product_id = p.id
WHERE p.deleted_at IS NULL
GROUP BY p.id
ORDER BY p.id ASC
LIMIT 1;

-- ========================================
-- 10. ESTADÍSTICAS GENERALES
-- ========================================

-- Resumen completo
SELECT
    'Category Codes' AS entidad,
    COUNT(*) AS total,
    MIN(created_at) AS primera_creacion,
    MAX(created_at) AS ultima_creacion
FROM category_codes WHERE deleted_at IS NULL
UNION ALL
SELECT 'Categories', COUNT(*), MIN(created_at), MAX(created_at)
FROM categories WHERE deleted_at IS NULL
UNION ALL
SELECT 'Product Codes', COUNT(*), MIN(created_at), MAX(created_at)
FROM product_codes WHERE deleted_at IS NULL
UNION ALL
SELECT 'Products', COUNT(*), MIN(created_at), MAX(created_at)
FROM products WHERE deleted_at IS NULL
UNION ALL
SELECT 'Galleries', COUNT(*), MIN(created_at), MAX(created_at)
FROM galleries WHERE deleted_at IS NULL
UNION ALL
SELECT 'Price Details', COUNT(*), MIN(created_at), MAX(created_at)
FROM price_details WHERE deleted_at IS NULL
UNION ALL
SELECT 'Prices', COUNT(*), MIN(created_at), MAX(created_at)
FROM prices WHERE deleted_at IS NULL
UNION ALL
SELECT 'Tabs', COUNT(*), MIN(created_at), MAX(created_at)
FROM tabs WHERE deleted_at IS NULL
UNION ALL
SELECT 'Additional Tabs', COUNT(*), MIN(created_at), MAX(created_at)
FROM additional_tabs WHERE deleted_at IS NULL;

-- ========================================
-- 11. VERIFICAR CAMPOS IMPORTANTES
-- ========================================

-- Productos con campos vacíos
SELECT
    'Sin título' AS problema,
    COUNT(*) AS total
FROM products
WHERE (title IS NULL OR title = '') AND deleted_at IS NULL
UNION ALL
SELECT 'Sin código', COUNT(*)
FROM products
WHERE (code IS NULL OR code = '') AND deleted_at IS NULL
UNION ALL
SELECT 'Sin duración', COUNT(*)
FROM products
WHERE (duration IS NULL OR duration = '') AND deleted_at IS NULL
UNION ALL
SELECT 'Capacidad = 0', COUNT(*)
FROM products
WHERE capacity = 0 AND deleted_at IS NULL;

-- ========================================
-- 12. VERIFICAR DUPLICADOS
-- ========================================

-- Product codes duplicados (NO debería haber)
SELECT
    code,
    COUNT(*) AS total
FROM product_codes
WHERE deleted_at IS NULL
GROUP BY code
HAVING COUNT(*) > 1;

-- Category codes duplicados (NO debería haber)
SELECT
    code,
    COUNT(*) AS total
FROM category_codes
WHERE deleted_at IS NULL
GROUP BY code
HAVING COUNT(*) > 1;

-- ========================================
-- 13. QUERIES DE LIMPIEZA (SI NECESARIO)
-- ========================================

-- ADVERTENCIA: Solo ejecutar si necesitas limpiar datos

-- Limpiar todos los datos de migración (PELIGROSO!)
/*
USE inc0910d_cms_incalake;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM prices;
DELETE FROM price_details;
DELETE FROM additional_tabs;
DELETE FROM tabs;
DELETE FROM product_gallery;
DELETE FROM product_category;
DELETE FROM products;
DELETE FROM product_codes;
DELETE FROM categories;
DELETE FROM category_codes;
DELETE FROM galleries WHERE id > 3; -- Mantener galerías por defecto

SET FOREIGN_KEY_CHECKS = 1;
*/

-- ========================================
-- 14. VERIFICAR USUARIOS Y PERMISOS
-- ========================================

-- Ver quién creó los registros
SELECT
    u.name AS usuario,
    u.email,
    COUNT(DISTINCT p.id) AS productos_creados,
    COUNT(DISTINCT c.id) AS categorias_creadas
FROM users u
LEFT JOIN products p ON p.created_by = u.id OR p.user_id = u.id
LEFT JOIN categories c ON c.user_id = u.id
GROUP BY u.id, u.name, u.email;

-- ========================================
-- FIN DE QUERIES DE VERIFICACIÓN
-- ========================================
