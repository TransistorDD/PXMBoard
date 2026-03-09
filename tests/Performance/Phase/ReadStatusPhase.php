<?php

declare(strict_types=1);

/**
 * Phase 5: Generates read status entries in pxm_message_read.
 *
 * Read pattern (from config read_pattern):
 *
 *   Heavy readers (30 %):
 *     Reads N complete threads (all message IDs in range).
 *     → Produces contiguous ID blocks in the index (high index pressure).
 *
 *   Moderate (50 %):
 *     Reads M complete threads + E random individual messages.
 *     → Mix of contiguous blocks and scatter.
 *
 *   Lurker (20 %):
 *     Reads L random messages from the entire ID range.
 *     → Maximum scatter across the full index.
 *
 * Requires: thread_ranges.csv from phase 3 (MessagePhase).
 */
class ReadStatusPhase extends AbstractPhase
{
    /** @var array<int, array{first: int, last: int}> thread_id → message ID range */
    private array $threadRanges = [];

    private int $minMsgId = PHP_INT_MAX;
    private int $maxMsgId = 0;

    public function getName(): string
    {
        return 'readstatus';
    }

    protected function execute(bool $resume): void
    {
        $this->loadThreadRanges();

        if (empty($this->threadRanges)) {
            $this->log('thread_ranges.csv is empty. Run the messages phase first.');
            return;
        }

        $threadIds    = array_keys($this->threadRanges);
        $threadCount  = count($threadIds);

        $this->log("Thread ranges loaded: {$threadCount} threads");
        $this->log("Message ID range: {$this->minMsgId} – {$this->maxMsgId}");

        // User ID range
        $minUserId = (int) $this->db->scalar('SELECT MIN(u_id) FROM pxm_user');
        $maxUserId = (int) $this->db->scalar('SELECT MAX(u_id) FROM pxm_user');
        $userCount = $maxUserId - $minUserId + 1;

        if ($userCount <= 0) {
            $this->log('No users found.');
            return;
        }

        // Read pattern configuration
        $cfg         = $this->config['read_pattern'];
        $heavyPct    = (int) $cfg['heavy_pct'];
        $moderatePct = (int) $cfg['moderate_pct'];
        $flushSize   = $this->config['batch']['flush_size'];
        $now         = time();

        $heavyUntil    = $minUserId + (int) ($userCount * $heavyPct / 100) - 1;
        $moderateUntil = $heavyUntil + (int) ($userCount * $moderatePct / 100);

        // Estimated total read entries
        $estHeavy    = (int) ($userCount * $heavyPct / 100)
            * $cfg['heavy_thread_count']
            * (int) (($this->maxMsgId - $this->minMsgId + 1) / max($threadCount, 1));
        $estModerate = (int) ($userCount * $moderatePct / 100)
            * ($cfg['moderate_thread_count'] * (int) (($this->maxMsgId - $this->minMsgId + 1) / max($threadCount, 1)) + $cfg['moderate_extra_messages']);
        $estLurker   = (int) ($userCount * (100 - $heavyPct - $moderatePct) / 100)
            * $cfg['lurker_message_count'];
        $estTotal    = $estHeavy + $estModerate + $estLurker;

        $this->log("Estimated read entries: ~" . number_format($estTotal, 0, ',', '.'));

        $lastDoneUserId = $resume ? (int) $this->getCheckpointValue('last_done_user_id', 0) : 0;

        $columns     = ['mr_userid', 'mr_messageid', 'mr_timestamp'];
        $batch       = [];
        $totalRows   = 0;

        $this->progress->start('ReadStatus', $estTotal);
        $this->db->beginTransaction();

        for ($userId = $minUserId; $userId <= $maxUserId; $userId++) {

            if ($userId <= $lastDoneUserId) {
                continue;
            }

            if ($userId <= $heavyUntil) {
                $entries = $this->generateHeavyEntries($userId, $threadIds, $cfg, $now);
            } elseif ($userId <= $moderateUntil) {
                $entries = $this->generateModerateEntries($userId, $threadIds, $cfg, $now);
            } else {
                $entries = $this->generateLurkerEntries($userId, $cfg, $now);
            }

            foreach ($entries as $entry) {
                $batch[] = $entry;
                $totalRows++;

                if (count($batch) >= $flushSize) {
                    $this->db->bulkFlush('pxm_message_read', $columns, $batch);
                    $batch = [];
                    $this->saveCheckpointValue('last_done_user_id', $userId);
                    $this->progress->update($totalRows);
                }
            }
        }

        if (!empty($batch)) {
            $this->db->bulkFlush('pxm_message_read', $columns, $batch);
        }

        $this->db->commit();
        $this->progress->finish();

        $this->log("Total: {$totalRows} read entries");
    }

    // -------------------------------------------------------------------------
    // Generators per user group
    // -------------------------------------------------------------------------

    /**
     * Heavy reader: reads N complete threads (all messages in range).
     * Produces contiguous message ID blocks in the index.
     *
     * @param int[] $threadIds
     * @return array[]
     */
    private function generateHeavyEntries(int $userId, array $threadIds, array $cfg, int $now): array
    {
        $count      = min($cfg['heavy_thread_count'], count($threadIds));
        $picked     = $this->pickRandomThreads($threadIds, $count);
        $entries    = [];

        foreach ($picked as $tid) {
            $range = $this->threadRanges[$tid];
            $ts    = mt_rand($range['first_ts'], $now);

            for ($msgId = $range['first']; $msgId <= $range['last']; $msgId++) {
                $entries[] = [$userId, $msgId, $ts];
            }
        }

        return $entries;
    }

    /**
     * Moderate: reads M complete threads + E random individual messages.
     *
     * @param int[] $threadIds
     * @return array[]
     */
    private function generateModerateEntries(int $userId, array $threadIds, array $cfg, int $now): array
    {
        $count   = min($cfg['moderate_thread_count'], count($threadIds));
        $picked  = $this->pickRandomThreads($threadIds, $count);
        $entries = [];

        foreach ($picked as $tid) {
            $range = $this->threadRanges[$tid];
            $ts    = mt_rand($range['first_ts'], $now);

            for ($msgId = $range['first']; $msgId <= $range['last']; $msgId++) {
                $entries[] = [$userId, $msgId, $ts];
            }
        }

        // Additional random individual messages (scatter across full ID range)
        $extra = $cfg['moderate_extra_messages'];
        for ($i = 0; $i < $extra; $i++) {
            $msgId     = mt_rand($this->minMsgId, $this->maxMsgId);
            $entries[] = [$userId, $msgId, mt_rand($this->minMsgId, $now)];
        }

        return $entries;
    }

    /**
     * Lurker: reads L random messages from the entire ID range.
     * Maximum scatter → maximum index pressure for point lookups.
     *
     * @return array[]
     */
    private function generateLurkerEntries(int $userId, array $cfg, int $now): array
    {
        $entries = [];
        $count   = $cfg['lurker_message_count'];

        for ($i = 0; $i < $count; $i++) {
            $msgId     = mt_rand($this->minMsgId, $this->maxMsgId);
            $entries[] = [$userId, $msgId, mt_rand($this->minMsgId, $now)];
        }

        return $entries;
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Loads thread_ranges.csv into $this->threadRanges.
     * Format: thread_id, first_msg_id, last_msg_id
     */
    private function loadThreadRanges(): void
    {
        $file = $this->config['thread_ranges_file'];

        if (!file_exists($file)) {
            return;
        }

        $fh = fopen($file, 'r');
        if (!$fh) {
            throw new \RuntimeException("Cannot read thread_ranges.csv: {$file}");
        }

        while (($row = fgetcsv($fh)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            $threadId  = (int) $row[0];
            $firstId   = (int) $row[1];
            $lastId    = (int) $row[2];

            $this->threadRanges[$threadId] = [
                'first'    => $firstId,
                'last'     => $lastId,
                'first_ts' => 0,    // Timestamp unknown, will be approximated
            ];

            if ($firstId < $this->minMsgId) {
                $this->minMsgId = $firstId;
            }
            if ($lastId > $this->maxMsgId) {
                $this->maxMsgId = $lastId;
            }
        }

        fclose($fh);

        // Load approximate timestamp from DB (min only, used as lower bound)
        if (!empty($this->threadRanges)) {
            $minTs = (int) $this->db->scalar('SELECT MIN(m_tstmp) FROM pxm_message');
            foreach ($this->threadRanges as &$range) {
                $range['first_ts'] = $minTs;
            }
            unset($range);
        }
    }

    /**
     * Picks $count random thread IDs from $threadIds without duplicates.
     *
     * @param int[] $threadIds
     * @return int[]
     */
    private function pickRandomThreads(array $threadIds, int $count): array
    {
        if ($count >= count($threadIds)) {
            return $threadIds;
        }

        $keys   = array_rand($threadIds, $count);
        $result = [];

        foreach ((array) $keys as $k) {
            $result[] = $threadIds[$k];
        }

        return $result;
    }
}
