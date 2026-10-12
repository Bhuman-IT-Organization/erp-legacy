<?php
/**
 * Schema Comparison Script
 * Compares nextgen_inova.sql and nextgen_live.sql against the MASTER (nextgen_latest150726.sql)
 */

echo "=============================================================\n";
echo "  SCHEMA COMPARISON TOOL\n";
echo "  Master: nextgen_latest150726.sql\n";
echo "  Comparing: nextgen_inova.sql & nextgen_live.sql\n";
echo "=============================================================\n\n";

// File paths
$masterFile = __DIR__ . '/nextgen_latest150726.sql';
$inovaFile  = __DIR__ . '/nextgen_inova.sql';
$liveFile   = __DIR__ . '/nextgen_live.sql';

// Check files exist
foreach ([$masterFile => 'MASTER', $inovaFile => 'INOVA', $liveFile => 'LIVE'] as $file => $label) {
    if (!file_exists($file)) {
        die("ERROR: $label file not found: $file\n");
    }
}

/**
 * Parse a SQL file and extract table names and their columns with types
 */
function parseSchema($filePath) {
    $content = file_get_contents($filePath);
    $tables = [];

    // Match CREATE TABLE blocks
    // Pattern: CREATE TABLE `tablename` ( ... ) followed by engine/charset info or semicolon
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\((.*?)\)\s*(?:ENGINE|TYPE|;)/is', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $tableName = $match[1];
        $tableBody = $match[2];

        $columns = [];

        // Match column definitions: lines starting with `column_name` followed by type
        preg_match_all('/`([^`]+)`\s+([A-Za-z]+(?:\([^)]*\))?(?:\s+unsigned)?)/i', $tableBody, $colMatches, PREG_SET_ORDER);

        foreach ($colMatches as $colMatch) {
            $colName = $colMatch[1];
            $colType = strtolower(trim($colMatch[2]));

            // Skip keys/indexes that use backtick names (PRIMARY KEY, KEY, INDEX, UNIQUE, CONSTRAINT)
            // We only want actual column definitions
            $columns[$colName] = $colType;
        }

        $tables[$tableName] = $columns;
    }

    return $tables;
}

echo "Parsing MASTER file...\n";
$masterSchema = parseSchema($masterFile);
echo "  Found " . count($masterSchema) . " tables\n";

echo "Parsing INOVA file...\n";
$inovaSchema = parseSchema($inovaFile);
echo "  Found " . count($inovaSchema) . " tables\n";

echo "Parsing LIVE file...\n";
$liveSchema = parseSchema($liveFile);
echo "  Found " . count($liveSchema) . " tables\n";

echo "\n";

// --- COMPARISON: INOVA vs MASTER ---
echo "=============================================================\n";
echo "  INOVA vs MASTER COMPARISON\n";
echo "=============================================================\n\n";

// Tables in MASTER missing from INOVA
$missingInInova = array_diff_key($masterSchema, $inovaSchema);
echo "--- Tables in MASTER missing from INOVA (" . count($missingInInova) . ") ---\n";
if (empty($missingInInova)) {
    echo "  None\n";
} else {
    foreach (array_keys($missingInInova) as $tbl) {
        echo "  - $tbl\n";
    }
}
echo "\n";

// Extra tables in INOVA not in MASTER
$extraInInova = array_diff_key($inovaSchema, $masterSchema);
echo "--- Extra tables in INOVA not in MASTER (" . count($extraInInova) . ") ---\n";
if (empty($extraInInova)) {
    echo "  None\n";
} else {
    foreach (array_keys($extraInInova) as $tbl) {
        echo "  - $tbl\n";
    }
}
echo "\n";

// Columns missing in INOVA for common tables
echo "--- Columns in MASTER missing from INOVA (common tables) ---\n";
$commonInova = array_intersect_key($masterSchema, $inovaSchema);
$colDiffsFound = false;
foreach ($commonInova as $tableName => $masterCols) {
    $inovaCols = $inovaSchema[$tableName];
    $missingCols = array_diff_key($masterCols, $inovaCols);
    if (!empty($missingCols)) {
        $colDiffsFound = true;
        echo "  Table: $tableName\n";
        foreach ($missingCols as $col => $type) {
            echo "    - $col ($type)\n";
        }
    }
}
if (!$colDiffsFound) {
    echo "  None\n";
}
echo "\n";

// --- COMPARISON: LIVE vs MASTER ---
echo "=============================================================\n";
echo "  LIVE vs MASTER COMPARISON\n";
echo "=============================================================\n\n";

// Tables in MASTER missing from LIVE
$missingInLive = array_diff_key($masterSchema, $liveSchema);
echo "--- Tables in MASTER missing from LIVE (" . count($missingInLive) . ") ---\n";
if (empty($missingInLive)) {
    echo "  None\n";
} else {
    foreach (array_keys($missingInLive) as $tbl) {
        echo "  - $tbl\n";
    }
}
echo "\n";

// Extra tables in LIVE not in MASTER
$extraInLive = array_diff_key($liveSchema, $masterSchema);
echo "--- Extra tables in LIVE not in MASTER (" . count($extraInLive) . ") ---\n";
if (empty($extraInLive)) {
    echo "  None\n";
} else {
    foreach (array_keys($extraInLive) as $tbl) {
        echo "  - $tbl\n";
    }
}
echo "\n";

// Columns missing in LIVE for common tables
echo "--- Columns in MASTER missing from LIVE (common tables) ---\n";
$commonLive = array_intersect_key($masterSchema, $liveSchema);
$colDiffsFound = false;
foreach ($commonLive as $tableName => $masterCols) {
    $liveCols = $liveSchema[$tableName];
    $missingCols = array_diff_key($masterCols, $liveCols);
    if (!empty($missingCols)) {
        $colDiffsFound = true;
        echo "  Table: $tableName\n";
        foreach ($missingCols as $col => $type) {
            echo "    - $col ($type)\n";
        }
    }
}
if (!$colDiffsFound) {
    echo "  None\n";
}
echo "\n";

echo "=============================================================\n";
echo "  COMPARISON COMPLETE\n";
echo "=============================================================\n";
