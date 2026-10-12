<?php
/**
 * backend_check.php - backend identity and reconciliation inventory
 * ---------------------------------------------------------------------------
 * WHAT WE ALREADY KNOW
 *   Two datatable payloads from the same url, same login, minutes apart:
 *
 *     Response A : 88 purchase orders. id 89 = PO00000092, MITCO, Approved.
 *                  PO00000093 and PO00000094 absent. ids 87/88 Approved.
 *     Response B : 89 purchase orders. id 89 = PO00000093, A2Y, Draft.
 *                  id 90 = PO00000094. PO00000092 absent. ids 87/88 Sent for
 *                  Approval.
 *
 *   No single database can produce both. If B were later, PO00000092 would
 *   still exist. If A were later, 93 and 94 would still exist and a new order
 *   would have taken id 91 rather than reusing 89. iTotalRecords is a COUNT(*),
 *   and 88 and 89 cannot both be true at once.
 *
 *   So port 5225 forwards to MORE THAN ONE backend, each with its own database,
 *   both copied from a common ancestor and both since written to. Node A owns
 *   PO00000092 and the approvals of 87 and 88. Node B owns PO00000093 and
 *   PO00000094. Neither is a superset of the other.
 *
 *   That also explains the automatic logouts. SESS_DRIVER is 'database', so
 *   sessions live in tblsessions. Log in on one node, get routed to the other,
 *   and your session id is not in that database - is_staff_logged_in() returns
 *   false and AdminController sends you to the login screen. Nothing expired.
 *
 * WHAT THIS SCRIPT IS FOR NOW
 *   Identifying which node answered, and dumping a full purchase order
 *   inventory so the two nodes can be diffed and reconciled before one of them
 *   is taken out of the rotation. Anything present on only one node is at risk
 *   of being lost.
 *
 * HOW TO USE
 *   1. Change TOKEN below.
 *   2. Upload next to index.php in the live web root.
 *   3. Load it repeatedly until you have captured BOTH nodes. Section 3 tells
 *      you which one you got - watch @@hostname and the row counts. Logging out
 *      and back in, or using a different browser, tends to flip you over.
 *   4. Save one output per node and diff section 10.
 *   5. DELETE THE FILE FROM THE SERVER.
 *
 * SAFETY
 *   Read only - SELECT, SHOW and COUNT only, no writes. Token gated. Never
 *   prints the database password. It does expose internal hostnames, so remove
 *   it as soon as you have both outputs.
 */

const TOKEN = 'CHANGE-ME-NOW';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!hash_equals(TOKEN, isset($_GET['token']) ? (string) $_GET['token'] : '')) {
    http_response_code(404);
    echo "Not found\n";
    exit;
}

function section($title)
{
    echo "\n" . str_repeat('=', 74) . "\n" . $title . "\n" . str_repeat('=', 74) . "\n";
}

function kv($key, $value)
{
    printf("  %-30s %s\n", $key . ':', $value === null ? '(null)' : $value);
}

echo "BACKEND IDENTITY + PO HISTORY PROBE\n";
kv('generated', date('Y-m-d H:i:s T'));
kv('script', __FILE__);

/* =========================================================================
 * 1. Which web server answered?
 * ====================================================================== */
section('1. WEB SERVER IDENTITY   <-- compare between the two browsers');

kv('gethostname()', function_exists('gethostname') ? gethostname() : php_uname('n'));
kv('php_uname', php_uname());
kv('php version', PHP_VERSION);
kv('php sapi', PHP_SAPI);

foreach (['SERVER_ADDR', 'SERVER_PORT', 'SERVER_NAME', 'HTTP_HOST', 'SERVER_SOFTWARE',
          'DOCUMENT_ROOT', 'REMOTE_ADDR', 'HTTPS', 'REQUEST_SCHEME'] as $k) {
    kv('$_SERVER[' . $k . ']', isset($_SERVER[$k]) ? $_SERVER[$k] : '(unset)');
}

echo "\n  Proxy headers - a load balancer or reverse proxy in front sets these:\n";
$sawProxy = false;
foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_PROTO',
          'HTTP_X_FORWARDED_PORT', 'HTTP_X_REAL_IP', 'HTTP_FORWARDED', 'HTTP_VIA'] as $k) {
    if (isset($_SERVER[$k])) {
        kv('$_SERVER[' . $k . ']', $_SERVER[$k]);
        $sawProxy = true;
    }
}
if (!$sawProxy) {
    echo "  (none present)\n";
}

kv('browser', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '(unset)');

/* =========================================================================
 * 2. Which code base and which database is it pointed at?
 * ====================================================================== */
section('2. APPLICATION CONFIG');

// Normally this file sits beside index.php. The parent lookup lets it also run
// from a subfolder such as tools/ without being moved.
$configPath = null;
$appRoot    = null;
foreach ([__DIR__, dirname(__DIR__)] as $base) {
    if (is_file($base . '/application/config/app-config.php')) {
        $configPath = $base . '/application/config/app-config.php';
        $appRoot    = $base;
        break;
    }
}

if ($configPath === null) {
    kv('app-config.php', 'NOT FOUND under ' . __DIR__);
    echo "\n  Put this file in the same folder as index.php. Cannot continue.\n";
    exit;
}

kv('app-config.php', $configPath);
kv('  modified', date('Y-m-d H:i:s', filemtime($configPath)));
kv('  size / md5', filesize($configPath) . ' bytes / ' . md5_file($configPath));

if (is_file($appRoot . '/index.php')) {
    kv('index.php modified', date('Y-m-d H:i:s', filemtime($appRoot . '/index.php')));
}

// Fingerprint the deployed code. Two backends running different checkouts will
// disagree here even if their databases happen to match.
foreach (['application/libraries/App.php',
          'application/core/AdminController.php',
          'modules/purchase/models/Purchase_model.php',
          'modules/purchase/controllers/Purchase.php'] as $rel) {
    $p = $appRoot . '/' . $rel;
    kv('md5 ' . $rel, is_file($p)
        ? substr(md5_file($p), 0, 12) . '  (' . date('Y-m-d H:i', filemtime($p)) . ')'
        : '(missing)');
}

if (!defined('BASEPATH')) {
    define('BASEPATH', $appRoot . '/system/');
}
require_once $configPath;

echo "\n";
kv('APP_DB_HOSTNAME', defined('APP_DB_HOSTNAME') ? APP_DB_HOSTNAME : '(undefined)');
kv('APP_DB_USERNAME', defined('APP_DB_USERNAME') ? APP_DB_USERNAME : '(undefined)');
kv('APP_DB_PASSWORD', defined('APP_DB_PASSWORD')
    ? (APP_DB_PASSWORD === ''
        ? '(empty)'
        : '(set, ' . strlen(APP_DB_PASSWORD) . ' chars, md5 ' . substr(md5(APP_DB_PASSWORD), 0, 8) . ')')
    : '(undefined)');
kv('APP_DB_NAME', defined('APP_DB_NAME') ? APP_DB_NAME : '(undefined)');
kv('APP_BASE_URL', defined('APP_BASE_URL') ? APP_BASE_URL : '(undefined)');

/* =========================================================================
 * 3. Which database server answered?
 * ====================================================================== */
section('3. DATABASE SERVER IDENTITY   <-- compare between the two browsers');

try {
    $pdo = new PDO(
        'mysql:host=' . APP_DB_HOSTNAME . ';dbname=' . APP_DB_NAME . ';charset=utf8mb4',
        APP_DB_USERNAME,
        APP_DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    echo "  CONNECT FAILED: " . $e->getMessage() . "\n";
    exit;
}

/**
 * First column of the first row, tolerating restricted server variables.
 */
function scalar(PDO $pdo, $sql, $col = 0)
{
    try {
        $row = $pdo->query($sql)->fetch(PDO::FETCH_NUM);

        return $row === false ? '(no rows)' : (string) $row[$col];
    } catch (Throwable $e) {
        return '(unavailable: ' . $e->getMessage() . ')';
    }
}

kv('@@hostname', scalar($pdo, 'SELECT @@hostname'));
kv('DATABASE()', scalar($pdo, 'SELECT DATABASE()'));
kv('VERSION()', scalar($pdo, 'SELECT VERSION()'));
kv('@@port', scalar($pdo, 'SELECT @@port'));
kv('@@server_id', scalar($pdo, 'SELECT @@server_id'));
kv('CONNECTION_ID()', scalar($pdo, 'SELECT CONNECTION_ID()'));
kv('CURRENT_USER()', scalar($pdo, 'SELECT CURRENT_USER()'));
kv('NOW()', scalar($pdo, 'SELECT NOW()'));

// Uptime matters: InnoDB rebuilds an AUTO_INCREMENT counter as MAX(id)+1 on
// restart, so a recent restart makes a deleted top id available for reuse.
$uptime = scalar($pdo, "SHOW GLOBAL STATUS LIKE 'Uptime'", 1);
kv('uptime seconds', $uptime);
if (ctype_digit((string) $uptime)) {
    kv('  started at (approx)', date('Y-m-d H:i:s', time() - (int) $uptime));
    kv('  uptime days', round($uptime / 86400, 2));
}

/* =========================================================================
 * 4. The disputed rows
 * ====================================================================== */
section('4. DISPUTED PURCHASE ORDER ROWS (ids 87, 88, 89)   <-- the decider');

echo "  Chrome saw id 89 = PO00000092 / MITCO / Approved / 88,184.25\n";
echo "  Edge   saw id 89 = PO00000093 / A2Y Construction / Draft / 113,032.50\n";
echo "  Below is what THIS backend holds right now.\n";
echo "  approve_status: 1 = draft/not sent, 2 = approved (see section 9 note)\n";

try {
    $rows = $pdo->query(
        'SELECT o.id,
                o.pur_order_number,
                o.number,
                o.vendor,
                v.company        AS vendor_name,
                o.type,
                o.department,
                o.approve_status,
                o.status,
                o.subtotal,
                o.total_tax,
                o.total,
                o.currency,
                o.ledger_account,
                o.cost_center,
                o.pur_request,
                o.order_date,
                o.datecreated,
                o.addedfrom,
                o.is_deleted
           FROM tblpur_orders o
           LEFT JOIN tblpur_vendor v ON v.userid = o.vendor
          WHERE o.id IN (87, 88, 89)
          ORDER BY o.id'
    )->fetchAll();

    if (!$rows) {
        echo "\n  !! No rows with id 87-89 in this database.\n";
    }

    foreach ($rows as $row) {
        echo "\n  --- tblpur_orders.id = " . $row['id'] . " ---\n";
        foreach ($row as $col => $val) {
            if ($col === 'id') {
                continue;
            }
            if (is_string($val) && strlen($val) > 120) {
                $val = substr($val, 0, 120) . '...';
            }
            kv($col, $val);
        }
    }
} catch (Throwable $e) {
    echo "  QUERY FAILED: " . $e->getMessage() . "\n";
}

/* =========================================================================
 * 5. Does PO00000093 exist anywhere?
 * ====================================================================== */
section('5. PO NUMBER LOOKUPS   <-- if PO00000093 exists here, it is two backends');

echo "  Deleted rows are included, so a soft deleted PO still shows up.\n\n";

foreach (['PO00000090', 'PO00000091', 'PO00000092', 'PO00000093', 'PO00000094'] as $num) {
    try {
        $stmt = $pdo->prepare(
            'SELECT o.id, o.pur_order_number, o.number, o.vendor, v.company AS vendor_name,
                    o.approve_status, o.total, o.is_deleted, o.datecreated
               FROM tblpur_orders o
               LEFT JOIN tblpur_vendor v ON v.userid = o.vendor
              WHERE o.pur_order_number = ?
              ORDER BY o.id'
        );
        $stmt->execute([$num]);
        $found = $stmt->fetchAll();

        if (!$found) {
            kv($num, 'NOT PRESENT');
            continue;
        }
        foreach ($found as $f) {
            kv($num, 'id=' . $f['id']
                . ' vendor=' . $f['vendor'] . ' (' . $f['vendor_name'] . ')'
                . ' approve_status=' . $f['approve_status']
                . ' total=' . $f['total']
                . ' is_deleted=' . $f['is_deleted']
                . ' created=' . $f['datecreated']);
        }
    } catch (Throwable $e) {
        kv($num, 'query failed: ' . $e->getMessage());
    }
}

echo "\n  Sequence state:\n";
kv('MAX(id)', scalar($pdo, 'SELECT MAX(id) FROM tblpur_orders'));
kv('COUNT(*)', scalar($pdo, 'SELECT COUNT(*) FROM tblpur_orders'));
kv('MAX(number)', scalar($pdo, 'SELECT MAX(number) FROM tblpur_orders'));
kv('MAX(pur_order_number)', scalar($pdo, 'SELECT MAX(pur_order_number) FROM tblpur_orders'));
kv('missing ids (MAX - COUNT)', scalar($pdo, 'SELECT MAX(id) - COUNT(*) FROM tblpur_orders')
    . '  (>0 means rows were hard deleted)');
kv('next AUTO_INCREMENT', scalar($pdo, "SHOW TABLE STATUS LIKE 'tblpur_orders'", 10));

echo "\n  If MAX(number) is 92 then PO00000093 has never been issued here, and\n";
echo "  93 is the number the next new purchase order will take.\n";

/* =========================================================================
 * 6. Audit trail for the disputed rows
 * ====================================================================== */
section('6. AUDIT TRAIL FOR ids 87-89');

echo "  One 'created' entry per row and no gap in the log id sequence means no\n";
echo "  purchase order was deleted between them - so nothing vanished, and the\n";
echo "  older looking view was simply an older render.\n\n";

try {
    $rows = $pdo->query(
        "SELECT l.id, l.rel_id, l.rel_type, l.staffid, l.date, LEFT(l.note, 150) AS note
           FROM tblpur_activity_log l
          WHERE l.rel_type LIKE '%order%' AND l.rel_id IN (87, 88, 89)
          ORDER BY l.id"
    )->fetchAll();

    if (!$rows) {
        echo "  (no entries)\n";
    }
    foreach ($rows as $r) {
        printf("  log#%-6s rel_id=%-4s staff=%-4s %s  %s\n",
            $r['id'], $r['rel_id'], $r['staffid'], $r['date'], $r['note']);
    }

    if ($rows) {
        $ids     = array_map('intval', array_column($rows, 'id'));
        $lo      = min($ids);
        $hi      = max($ids);
        $present = $pdo->query('SELECT COUNT(*) FROM tblpur_activity_log
                                 WHERE id BETWEEN ' . $lo . ' AND ' . $hi)
                       ->fetch(PDO::FETCH_NUM)[0];
        echo "\n";
        kv('log id range', $lo . ' .. ' . $hi);
        kv('rows present in range', $present . ' of ' . ($hi - $lo + 1) . ' possible');
        kv('verdict', $present == ($hi - $lo + 1)
            ? 'CONTIGUOUS - no deleted log rows in this window'
            : 'GAPS - something was deleted in this window');
    }
} catch (Throwable $e) {
    echo "  lookup failed: " . $e->getMessage() . "\n";
}

echo "\n  Any log entry anywhere mentioning 93 or A2Y:\n";
foreach ([
    'tblpur_activity_log' => "SELECT id, rel_id, rel_type, date, LEFT(note,150) AS txt
                                FROM tblpur_activity_log
                               WHERE note LIKE '%00000093%' OR note LIKE '%A2Y%'
                               ORDER BY date",
    'tblactivity_log'     => "SELECT id, 0 AS rel_id, '' AS rel_type, date, LEFT(description,150) AS txt
                                FROM tblactivity_log
                               WHERE description LIKE '%00000093%' OR description LIKE '%A2Y%'
                               ORDER BY date",
] as $tbl => $sql) {
    try {
        $hits = $pdo->query($sql)->fetchAll();
        if (!$hits) {
            kv($tbl, 'no matches');
            continue;
        }
        foreach ($hits as $h) {
            printf("  %-22s #%-6s %s  %s\n", $tbl, $h['id'], $h['date'], $h['txt']);
        }
    } catch (Throwable $e) {
        kv($tbl, 'lookup failed: ' . $e->getMessage());
    }
}

/* =========================================================================
 * 7. Dataset fingerprint
 * ====================================================================== */
section('7. DATASET FINGERPRINT');

echo "  Two backends diverged from a common ancestor will differ on several of\n";
echo "  these. One backend gives identical numbers in both browsers.\n\n";

foreach ([
    'tblpur_orders',
    'tblpur_request',
    'tblpur_invoices',
    'tblpur_vendor',
    'tblgoods_receipt',
    'tblinv_shipping_charges',
    'tblacc_account_history',
    'tblstaff',
    'tblsessions',
    'tblnotifications',
    'tblpur_approval_setting',
    'tblpur_approval_details',
    'tblactivity_log',
    'tblpur_activity_log',
] as $t) {
    try {
        $count = $pdo->query('SELECT COUNT(*) FROM ' . $t)->fetch(PDO::FETCH_NUM)[0];
        $extra = '';
        try {
            $maxId = $pdo->query('SELECT MAX(id) FROM ' . $t)->fetch(PDO::FETCH_NUM)[0];
            if (is_numeric($maxId)) {
                $extra = ' | max(id)=' . $maxId;
            }
        } catch (Throwable $e) {
            $extra = '';
        }
        kv($t, 'rows=' . $count . $extra);
    } catch (Throwable $e) {
        kv($t, 'MISSING or unreadable');
    }
}

/* =========================================================================
 * 8. Has the pending schema work landed on THIS database?
 * ====================================================================== */
section('8. SCHEMA DRIFT PROBES');

echo "  Zero row SELECT per recently added column. PRESENT means that migration\n";
echo "  is already applied on this node.\n\n";

foreach ([
    'tblinv_shipping_charges.cost_center'             => 'SELECT cost_center FROM tblinv_shipping_charges LIMIT 0',
    'tblinv_shipping_charges.shipping_invoice_number' => 'SELECT shipping_invoice_number FROM tblinv_shipping_charges LIMIT 0',
    'tblinv_shipping_charges.shipping_invoice_date'   => 'SELECT shipping_invoice_date FROM tblinv_shipping_charges LIMIT 0',
    'tblinv_shipping_charges.adminnote'               => 'SELECT adminnote FROM tblinv_shipping_charges LIMIT 0',
    'tblinv_shipping_charges.vendor_note'             => 'SELECT vendor_note FROM tblinv_shipping_charges LIMIT 0',
    'tblinv_shipping_charges.terms'                   => 'SELECT terms FROM tblinv_shipping_charges LIMIT 0',
    'tblfe_asset_creation.asset_class'                => 'SELECT asset_class FROM tblfe_asset_creation LIMIT 0',
    'tblfe_asset_creation.capitalization_date'        => 'SELECT capitalization_date FROM tblfe_asset_creation LIMIT 0',
    'tblpur_orders.cost_center'                       => 'SELECT cost_center FROM tblpur_orders LIMIT 0',
    'tblpur_orders.ledger_account'                    => 'SELECT ledger_account FROM tblpur_orders LIMIT 0',
] as $label => $sql) {
    try {
        $pdo->query($sql);
        kv($label, 'PRESENT');
    } catch (Throwable $e) {
        kv($label, 'MISSING');
    }
}

echo "\n  Unique index on tblpur_orders.pur_order_number (the duplicate PO guard):\n";
try {
    $found = false;
    foreach ($pdo->query('SHOW INDEX FROM tblpur_orders')->fetchAll() as $i) {
        if (isset($i['Column_name'], $i['Non_unique'])
            && $i['Column_name'] === 'pur_order_number'
            && (string) $i['Non_unique'] === '0') {
            kv('unique index', 'PRESENT (' . $i['Key_name'] . ')');
            $found = true;
        }
    }
    if (!$found) {
        kv('unique index', 'MISSING - duplicate PO numbers are still possible here');
    }
} catch (Throwable $e) {
    kv('unique index', 'check failed: ' . $e->getMessage());
}

/* =========================================================================
 * 9. Approval chains
 * ====================================================================== */
section('9. APPROVAL CHAINS');

echo "  A chain with department NULL or 0 acts as the catch all. Without one,\n";
echo "  documents whose department resolves to 0 fall through to an arbitrary\n";
echo "  chain, which is what made approval routing look random.\n\n";

try {
    $chains = $pdo->query('SELECT id, name, related, department, approval_type
                             FROM tblpur_approval_setting
                            ORDER BY id')->fetchAll();
    if (!$chains) {
        echo "  (none configured)\n";
    }

    // The catch all has to exist per document type. A pur_order catch all does
    // nothing for a pur_request whose department resolves to 0.
    $catchAll = [];
    $seenPair = [];
    $dupes    = [];

    foreach ($chains as $c) {
        $related = $c['related'];
        $dept    = $c['department'] === null ? 'NULL' : (string) $c['department'];

        if ($dept === 'NULL' || $dept === '0') {
            $catchAll[$related] = true;
        }

        $pair = $related . '|' . $dept;
        if (isset($seenPair[$pair])) {
            $dupes[$pair][] = $c['id'];
        } else {
            $seenPair[$pair] = $c['id'];
        }

        kv('chain ' . $c['id'], 'related=' . $related
            . ' dept=' . $dept
            . ' type=' . $c['approval_type']
            . ' name=' . $c['name']);
    }

    echo "\n  Catch all (department 0 or NULL) per document type:\n";
    foreach (array_unique(array_column($chains, 'related')) as $related) {
        kv($related, isset($catchAll[$related])
            ? 'yes'
            : 'NO - a document of this type whose department resolves to 0 has no chain');
    }

    if ($dupes) {
        echo "\n  More than one chain for the same document type + department.\n";
        echo "  These are the rows that made routing look random: the lookup used\n";
        echo "  to take whichever one the optimiser returned first.\n";
        foreach ($dupes as $pair => $ids) {
            [$related, $dept] = explode('|', $pair);
            kv($related . ' dept=' . $dept,
                'chain ids ' . $seenPair[$pair] . ', ' . implode(', ', $ids)
                . ' - ORDER BY id now always picks ' . $seenPair[$pair]);
        }
    } else {
        echo "\n  No duplicate document type + department pairs.\n";
    }
} catch (Throwable $e) {
    echo "  lookup failed: " . $e->getMessage() . "\n";
}

/* =========================================================================
 * 10. Reconciliation inventory
 * ====================================================================== */
section('10. PURCHASE ORDER INVENTORY - diff this section between the two nodes');

echo "  One line per purchase order, oldest first, in a fixed format so the two\n";
echo "  node outputs can be compared with a plain text diff. Any line that\n";
echo "  appears on only one node is data that exists nowhere else.\n\n";
echo "  id    | number     | ven | st | dept | total           | created             | del\n";
echo "  ------+------------+-----+----+------+-----------------+---------------------+----\n";

try {
    $rows = $pdo->query(
        'SELECT id, pur_order_number, vendor, approve_status, department,
                total, datecreated, is_deleted
           FROM tblpur_orders
          ORDER BY id'
    )->fetchAll();

    foreach ($rows as $r) {
        printf("  %-5s | %-10s | %-3s | %-2s | %-4s | %15s | %-19s | %s\n",
            $r['id'],
            $r['pur_order_number'],
            $r['vendor'],
            $r['approve_status'],
            $r['department'] === null ? '-' : $r['department'],
            number_format((float) $r['total'], 2),
            $r['datecreated'],
            $r['is_deleted']);
    }

    echo "\n";
    kv('purchase orders listed', count($rows));
} catch (Throwable $e) {
    echo "  inventory failed: " . $e->getMessage() . "\n";
}

// The same divergence will exist in the other document tables. Max ids and
// counts are enough to spot it without dumping every row.
echo "\n  Highest identifier per document type - a difference here means that\n";
echo "  document type has diverged too:\n";

foreach ([
    'purchase requests'  => 'SELECT COUNT(*), MAX(id), MAX(pur_rq_code) FROM tblpur_request',
    'goods receipts'     => 'SELECT COUNT(*), MAX(id), MAX(goods_receipt_code) FROM tblgoods_receipt',
    'purchase invoices'  => 'SELECT COUNT(*), MAX(id), MAX(id) FROM tblpur_invoices',
    'shipping charges'   => 'SELECT COUNT(*), MAX(id), MAX(shipping_number) FROM tblinv_shipping_charges',
    'gl entries'         => 'SELECT COUNT(*), MAX(id), MAX(id) FROM tblacc_account_history',
    'approval details'   => 'SELECT COUNT(*), MAX(id), MAX(id) FROM tblpur_approval_details',
    'notifications'      => 'SELECT COUNT(*), MAX(id), MAX(id) FROM tblnotifications',
] as $label => $sql) {
    try {
        $r = $pdo->query($sql)->fetch(PDO::FETCH_NUM);
        kv($label, 'rows=' . $r[0] . ' max_id=' . ($r[1] ?? '-') . ' latest_code=' . ($r[2] ?? '-'));
    } catch (Throwable $e) {
        kv($label, 'unreadable');
    }
}

// Sessions are stored in the database, which is why the logouts happen.
echo "\n  Session store - this is the table behind the automatic logouts:\n";
try {
    $sess = $pdo->query('SELECT COUNT(*) FROM tblsessions')->fetch(PDO::FETCH_NUM)[0];
    kv('tblsessions rows', $sess);
    kv('note', 'SESS_DRIVER is database, so a session created on the other node'
        . ' does not exist here and that request is treated as logged out');
} catch (Throwable $e) {
    kv('tblsessions', 'unreadable');
}

/* =========================================================================
 * How to read it
 * ====================================================================== */
section('HOW TO READ THIS');

echo <<<TXT
  The split is already confirmed from two datatable payloads. This script is for
  telling the nodes apart and inventorying them.

  IDENTIFY WHICH NODE YOU LANDED ON
      section 1  gethostname / SERVER_ADDR    - which web server
      section 3  @@hostname / @@server_id     - which database server
      section 5  MAX(number) and COUNT(*)     - which dataset

    Node A signature : 88 purchase orders, PO00000092 present, 93 and 94 absent
    Node B signature : 89 purchase orders, PO00000093 and PO00000094 present,
                       PO00000092 absent

    Reload until you have captured both. Logging out and back in, or switching
    browser, tends to flip you to the other node.

    Third discriminator - PO00000089, id 86, vendor 40, 25,515.00, created
    2026-08-27 16:42:01. It is present in the local development copy but did NOT
    appear in either live payload, in a position where the sort would have put
    it. So the local database is not an exact copy of either live node. Treat it
    as a THIRD copy and inventory it too before deciding what the correct data
    is - do not assume any one of the three is authoritative.

  THEN, IN THIS ORDER
    1. Have whoever manages the FortiGate confirm the virtual server on port
       5225 has more than one real server behind it, and take one out of the
       pool so every request reaches a single host. Until that is done, each new
       purchase order, approval and goods receipt lands in whichever database
       the connection happened to reach, and the gap keeps widening.

    2. Diff section 10 between the two node outputs. Every line unique to the
       node you are about to retire has to be re-entered on the surviving node,
       or copied across at the database level. Approval state matters as much as
       the documents - the same order is Approved on one node and Sent for
       Approval on the other.

    3. Only after the two are reconciled, apply sql/LIVE_DEPLOY.md. Applying it
       to one of two diverged databases makes the reconciliation harder.

  WHY THE LOGOUTS HAPPEN
    Sessions are rows in tblsessions, inside the database. Log in on one node
    and the session row is written to that node's database. When the next
    request is routed to the other node, it looks your session id up in a
    database that has never seen it, is_staff_logged_in() returns false and
    AdminController redirects to /admin/authentication. Nothing timed out.
    Fixing the routing fixes the logouts.

  *** DELETE THIS FILE FROM THE SERVER WHEN YOU ARE DONE. ***

TXT;
