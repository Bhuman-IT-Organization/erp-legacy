<?php
/**
 * Apply an idempotent .sql file to the database this install is configured to use.
 *
 * Usage (from the project root):
 *   php application/sql/apply_sql.php application/sql/attendance_phase1_tables.sql
 *
 * Reads the connection from application/config/app-config.php rather than taking a
 * database name, so it cannot be pointed at the wrong install by accident. Intended
 * for schema files that are additive and safe to re-run - it does not wrap anything
 * in a transaction, because DDL is not transactional in MySQL/MariaDB anyway.
 */

$sqlFile = $argv[1] ?? null;

if (!$sqlFile || !is_file($sqlFile)) {
    fwrite(STDERR, "usage: php application/sql/apply_sql.php <file.sql>\n");
    exit(1);
}

$cfgFile = __DIR__ . '/../config/app-config.php';

if (!is_file($cfgFile)) {
    fwrite(STDERR, "cannot find application/config/app-config.php\n");
    exit(1);
}

$src = file_get_contents($cfgFile);
$cfg = [];
foreach (['APP_DB_HOSTNAME', 'APP_DB_USERNAME', 'APP_DB_PASSWORD', 'APP_DB_NAME'] as $k) {
    if (preg_match("/define\(\s*'" . $k . "'\s*,\s*'([^']*)'\s*\)/", $src, $m)) {
        $cfg[$k] = $m[1];
    }
}

if (!isset($cfg['APP_DB_NAME'])) {
    fwrite(STDERR, "could not read the database name from app-config.php\n");
    exit(1);
}

echo 'file    : ' . $sqlFile . "\n";
echo 'database: ' . $cfg['APP_DB_NAME'] . ' @ ' . $cfg['APP_DB_HOSTNAME'] . "\n\n";

$db = new mysqli($cfg['APP_DB_HOSTNAME'], $cfg['APP_DB_USERNAME'], $cfg['APP_DB_PASSWORD'], $cfg['APP_DB_NAME']);

if ($db->connect_errno) {
    fwrite(STDERR, 'connect failed: ' . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

// Strip whole-line comments, then split on statement terminators. Adequate here
// because these schema files contain no semicolons inside string literals.
$clean = preg_replace('/^\s*--.*$/m', '', file_get_contents($sqlFile));
$statements = array_filter(array_map('trim', explode(';', $clean)), function ($s) {
    return $s !== '';
});

$ok = 0;
$failed = 0;

foreach ($statements as $stmt) {
    $label = preg_replace('/\s+/', ' ', substr($stmt, 0, 100));

    if ($db->query($stmt) === true) {
        $ok++;
        $affected = $db->affected_rows;
        echo '  OK   ' . $label . ($affected > 0 ? "  ({$affected} row(s))" : '') . "\n";
    } else {
        $failed++;
        echo '  FAIL ' . $label . "\n       -> " . $db->error . "\n";
    }
}

echo "\napplied: $ok, failed: $failed\n";

exit($failed > 0 ? 1 : 0);
