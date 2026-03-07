<?php

declare(strict_types=1);

/**
 * Phase 3: Generates messages in pxm_message.
 *
 * Critical path — largest data volume.
 *
 * Notes:
 *   - Message IDs are assigned explicitly (required for m_parentid)
 *   - FULLTEXT index is dropped before seeding and rebuilt afterwards
 *   - Thread ranges (thread_id, first_msg_id, last_msg_id) are written to
 *     thread_ranges.csv for phase 5 (ReadStatusPhase)
 *   - Checkpoint enables resuming from the last fully processed thread
 */
class MessagePhase extends AbstractPhase
{
    public function getName(): string
    {
        return 'messages';
    }

    protected function execute(bool $resume): void
    {
        $minMsgs   = $this->config['seed']['messages_per_thread_min'];
        $maxMsgs   = $this->config['seed']['messages_per_thread_max'];
        $flushSize = $this->config['batch']['flush_size'];
        $startTs   = $this->config['seed']['start_timestamp'] ?: strtotime('-5 years');
        $now       = time();

        // Load thread IDs
        $lastDoneThreadId = $resume ? (int) $this->getCheckpointValue('last_done_thread_id', 0) : 0;
        $threadRows       = $this->db->fetchAll(
            'SELECT t_id FROM pxm_thread'
            . ($lastDoneThreadId > 0 ? " WHERE t_id > {$lastDoneThreadId}" : '')
            . ' ORDER BY t_id'
        );

        if (empty($threadRows)) {
            $this->log('No threads found. Run the threads phase first.');
            return;
        }

        $totalThreads = count($threadRows);
        $this->log("Processing {$totalThreads} threads");

        // User ID range
        $minUserId = (int) $this->db->scalar('SELECT MIN(u_id) FROM pxm_user');
        $maxUserId = (int) $this->db->scalar('SELECT MAX(u_id) FROM pxm_user');

        if ($minUserId === 0) {
            $this->log('No users found. Run the users phase first.');
            return;
        }

        // Determine next message ID
        $nextMsgId = $resume
            ? (int) $this->getCheckpointValue('next_msg_id', 1)
            : (int) $this->db->scalar('SELECT COALESCE(MAX(m_id), 0) FROM pxm_message') + 1;

        // Drop FULLTEXT index (major performance benefit)
        $this->log('Dropping FULLTEXT index (m_search)...');
        $this->db->dropFulltextIndex();

        // Open thread ranges file
        $rangesFile   = $this->config['thread_ranges_file'];
        $rangesFh     = fopen($rangesFile, $resume ? 'a' : 'w');
        if (!$rangesFh) {
            throw new \RuntimeException("Cannot open thread_ranges.csv: {$rangesFile}");
        }

        $columns = [
            'm_id', 'm_threadid', 'm_parentid', 'm_userid', 'm_username',
            'm_usermail', 'm_userhighlight', 'm_subject', 'm_body',
            'm_tstmp', 'm_ip', 'm_notify_on_reply', 'm_status',
        ];

        $batch         = [];
        $totalMessages = 0;
        $threadsDone   = 0;
        $flushCount    = 0;

        // Estimate total message count for progress display
        $estimatedTotal = (int) ($totalThreads * ($minMsgs + $maxMsgs) / 2);
        $this->progress->start('Messages', $estimatedTotal);
        $this->db->beginTransaction();

        foreach ($threadRows as $threadRow) {
            $threadId  = (int) $threadRow['t_id'];
            $msgCount  = mt_rand($minMsgs, $maxMsgs);

            // Thread timespan: random start date, endpoint = now
            $threadStart = DataGenerator::randomTimestamp($startTs, $now - 86_400);
            $timestamps  = DataGenerator::ascendingTimestamps($msgCount, $threadStart, $now);

            $firstMsgId = $nextMsgId;

            for ($i = 0; $i < $msgCount; $i++) {
                $isFirstMsg = ($i === 0);
                $userId     = mt_rand($minUserId, $maxUserId);

                $batch[] = [
                    $nextMsgId,
                    $threadId,
                    $isFirstMsg ? 0 : $firstMsgId,  // m_parentid
                    $userId,
                    DataGenerator::username($userId),
                    '',                              // m_usermail (empty)
                    0,                               // m_userhighlight
                    DataGenerator::subject($threadId, !$isFirstMsg),
                    DataGenerator::messageBody(),
                    $timestamps[$i],
                    '127.0.0.1',
                    0,                               // m_notify_on_reply
                    1,                               // m_status = published
                ];

                $nextMsgId++;
                $totalMessages++;
            }

            // Save thread range for ReadStatusPhase
            fputcsv($rangesFh, [$threadId, $firstMsgId, $nextMsgId - 1]);
            $threadsDone++;

            if (count($batch) >= $flushSize) {
                $this->db->bulkFlush('pxm_message', $columns, $batch);
                $batch = [];
                $flushCount++;

                $this->saveCheckpointValue('last_done_thread_id', $threadId);
                $this->saveCheckpointValue('next_msg_id', $nextMsgId);
                $this->progress->update($totalMessages);
            }
        }

        // Write final batch
        if (!empty($batch)) {
            $this->db->bulkFlush('pxm_message', $columns, $batch);
        }

        fclose($rangesFh);
        $this->db->commit();
        $this->progress->finish();

        $this->log("Total: {$totalMessages} messages in {$threadsDone} threads");
        $this->log("Next available message ID: {$nextMsgId}");
        $this->saveCheckpointValue('next_msg_id', $nextMsgId);
        $this->saveCheckpointValue('total_messages', $totalMessages);

        // Rebuild FULLTEXT index (takes minutes — progress shown in MySQL)
        $this->log('Rebuilding FULLTEXT index (may take several minutes)...');
        $this->db->rebuildFulltextIndex();
        $this->log('FULLTEXT index rebuilt.');
    }
}
