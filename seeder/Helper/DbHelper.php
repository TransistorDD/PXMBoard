<?php

declare(strict_types=1);

/**
 * MySQLi wrapper for the seeder.
 *
 * Supports two bulk-insert modes:
 *   1. LOAD DATA LOCAL INFILE (fast, requires local_infile=ON on the server)
 *   2. Multi-row INSERT (fallback, portable)
 *
 * The mode is auto-detected and can be queried via supportsLocalInfile().
 */
class DbHelper
{
    private \mysqli $mysqli;
    private bool $localInfile;
    private int $flushCount = 0;
    private int $commitEvery;

    public function __construct(array $dbConfig, int $commitEvery = 5)
    {
        $this->commitEvery = $commitEvery;

        $this->mysqli = mysqli_init();
        $this->mysqli->options(MYSQLI_OPT_LOCAL_INFILE, 1);
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        $connected = @$this->mysqli->real_connect(
            $dbConfig['host'],
            $dbConfig['user'],
            $dbConfig['pass'],
            $dbConfig['name'],
            $dbConfig['port']
        );

        if (!$connected || $this->mysqli->connect_errno) {
            throw new \RuntimeException(
                'DB connection failed: ' . $this->mysqli->connect_error
            );
        }

        $this->mysqli->set_charset('utf8mb4');
        $this->localInfile = $this->detectLocalInfile();
    }

    // -------------------------------------------------------------------------
    // Basic database operations
    // -------------------------------------------------------------------------

    public function exec(string $sql): void
    {
        if (!$this->mysqli->query($sql)) {
            throw new \RuntimeException("SQL error: {$this->mysqli->error}\nQuery: " . substr($sql, 0, 200));
        }
    }

    public function scalar(string $sql): mixed
    {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("SQL error: {$this->mysqli->error}");
        }
        $row = $result->fetch_row();
        $result->free();
        return $row ? $row[0] : null;
    }

    public function fetchAll(string $sql): array
    {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("SQL error: {$this->mysqli->error}");
        }
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }

    public function fetchColumn(string $sql): array
    {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new \RuntimeException("SQL error: {$this->mysqli->error}");
        }
        $values = [];
        while ($row = $result->fetch_row()) {
            $values[] = $row[0];
        }
        $result->free();
        return $values;
    }

    // -------------------------------------------------------------------------
    // Transaction management
    // -------------------------------------------------------------------------

    public function beginTransaction(): void
    {
        $this->exec('START TRANSACTION');
        $this->flushCount = 0;
    }

    public function commit(): void
    {
        $this->exec('COMMIT');
        $this->flushCount = 0;
    }

    // -------------------------------------------------------------------------
    // Bulk insert (automatic mode)
    // -------------------------------------------------------------------------

    /**
     * Inserts $rows into $table. Automatically selects LOAD DATA LOCAL INFILE or
     * multi-row INSERT. Commits after $commitEvery calls.
     *
     * @param string   $table   Table name (without backticks)
     * @param string[] $columns Column list
     * @param array[]  $rows    Row data (numeric arrays, same order as $columns)
     * @return int Number of inserted rows
     */
    public function bulkFlush(string $table, array $columns, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $count = $this->localInfile
            ? $this->loadViaInfile($table, $columns, $rows)
            : $this->loadViaBatchInsert($table, $columns, $rows);

        $this->flushCount++;
        if ($this->flushCount >= $this->commitEvery) {
            $this->commit();
            $this->beginTransaction();
        }

        return $count;
    }

    // -------------------------------------------------------------------------
    // Optimisations for bulk seeding
    // -------------------------------------------------------------------------

    public function disableChecks(): void
    {
        $this->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->exec('SET UNIQUE_CHECKS = 0');
        $this->exec('SET autocommit = 0');
    }

    public function enableChecks(): void
    {
        $this->exec('COMMIT');
        $this->exec('SET FOREIGN_KEY_CHECKS = 1');
        $this->exec('SET UNIQUE_CHECKS = 1');
        $this->exec('SET autocommit = 1');
    }

    public function dropFulltextIndex(): void
    {
        $exists = $this->scalar(
            "SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE()
               AND table_name = 'pxm_message'
               AND index_name = 'm_search'"
        );
        if ((int) $exists > 0) {
            $this->exec('ALTER TABLE pxm_message DROP INDEX m_search');
        }
    }

    public function rebuildFulltextIndex(): void
    {
        $this->exec('ALTER TABLE pxm_message ADD FULLTEXT KEY m_search (m_subject, m_body)');
    }

    // -------------------------------------------------------------------------
    // Preflight check
    // -------------------------------------------------------------------------

    public function preflightCheck(array $boardIds): void
    {
        echo "Preflight check...\n";

        // Verify boards
        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));
        $stmt = $this->mysqli->prepare(
            "SELECT COUNT(*) FROM pxm_board WHERE b_id IN ({$placeholders})"
        );
        $types = str_repeat('i', count($boardIds));
        $stmt->bind_param($types, ...$boardIds);
        $stmt->execute();
        $stmt->bind_result($found);
        $stmt->fetch();
        $stmt->close();

        if ($found < count($boardIds)) {
            throw new \RuntimeException(
                "Not all board_ids found. Please create at least one board in the admin panel."
            );
        }

        $mode = $this->localInfile ? 'LOAD DATA LOCAL INFILE' : 'Multi-row INSERT (fallback)';
        echo "  Connection OK | Bulk mode: {$mode}\n";

        if (!$this->localInfile) {
            echo "  Note: For faster seeding set local_infile=ON in my.cnf\n";
            echo "        and restart MySQL, or run: SET GLOBAL local_infile=1;\n";
        }
    }

    public function supportsLocalInfile(): bool
    {
        return $this->localInfile;
    }

    // -------------------------------------------------------------------------
    // Internal implementations
    // -------------------------------------------------------------------------

    private function detectLocalInfile(): bool
    {
        try {
            $result = $this->mysqli->query("SHOW VARIABLES LIKE 'local_infile'");
            if ($row = $result->fetch_assoc()) {
                return strtoupper($row['Value']) === 'ON';
            }
        } catch (\Throwable) {
        }
        return false;
    }

    private function loadViaInfile(string $table, array $columns, array $rows): int
    {
        $file = sys_get_temp_dir() . '/pxm_seed_' . getmypid() . '.csv';
        $fh   = fopen($file, 'w');

        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);

        $colList = implode(',', $columns);
        $sql     = "LOAD DATA LOCAL INFILE '{$file}' IGNORE INTO TABLE `{$table}` "
            . "CHARACTER SET utf8mb4 "
            . "FIELDS TERMINATED BY ',' ENCLOSED BY '\"' ESCAPED BY '\"' "
            . "LINES TERMINATED BY '\\n' "
            . "({$colList})";

        $ok = $this->mysqli->query($sql);
        @unlink($file);

        if (!$ok) {
            throw new \RuntimeException("LOAD DATA failed: {$this->mysqli->error}");
        }

        return $this->mysqli->affected_rows;
    }

    private function loadViaBatchInsert(string $table, array $columns, array $rows): int
    {
        $colList = implode(',', $columns);
        $total   = 0;

        foreach (array_chunk($rows, 1_000) as $chunk) {
            $valueSets = [];
            foreach ($chunk as $row) {
                $escaped = array_map(function (mixed $v): string {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return "'" . $this->mysqli->real_escape_string((string) $v) . "'";
                }, $row);
                $valueSets[] = '(' . implode(',', $escaped) . ')';
            }

            $sql = "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES " . implode(',', $valueSets);
            $this->exec($sql);
            $total += $this->mysqli->affected_rows;
        }

        return $total;
    }
}
