<?php

/**
 * Pins the test database BEFORE anything boots.
 *
 * phpunit.xml's <env> entries write to $_ENV and putenv() but NOT to $_SERVER,
 * and Laravel's Env repository reads $_SERVER as well. docker-compose exports
 * DB_CONNECTION=mysql into the backend container, so $_SERVER kept winning:
 * env('DB_CONNECTION') returned 'mysql' even with force="true" in phpunit.xml.
 *
 * The consequence was not subtle — the suite ran against the configured MySQL
 * database and RefreshDatabase truncated it on every run. Verified with a
 * canary row: created it, ran a single test, and it was gone.
 *
 * Setting all three stores here means no environment can point the tests at a
 * real database, whether they run in Docker, in CI, or on a laptop.
 */
/**
 * Not sqlite, despite what phpunit.xml used to ask for: several migrations use
 * raw MySQL (`ALTER TABLE … MODIFY` on enums), so the suite can't even build
 * the schema on sqlite — and testing on a different engine than production
 * would give false confidence about enums and JSON columns anyway.
 *
 * Instead: same engine, same credentials, a DIFFERENT DATABASE. Only the name
 * is overridden, so this follows whatever host/user the environment already
 * provides while making it impossible to point the suite at the working one.
 *
 * The database must exist; create it once with:
 *   docker compose exec mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" \
 *     -e "CREATE DATABASE IF NOT EXISTS incalake_tours_test"
 */
$currentDatabase = $_SERVER['DB_DATABASE'] ?? $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'incalake_tours';
$testDatabase = str_ends_with($currentDatabase, '_test') ? $currentDatabase : $currentDatabase . '_test';

$testEnvironment = [
    'APP_ENV' => 'testing',
    'DB_DATABASE' => $testDatabase,
];

foreach ($testEnvironment as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
}

require __DIR__ . '/../vendor/autoload.php';
