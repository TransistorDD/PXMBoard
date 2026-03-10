<?php

declare(strict_types=1);

namespace PXMBoard\Tests\Performance\Phase;

/**
 * Phase 2: Generates thread skeletons in pxm_thread.
 *
 * t_lastmsgid, t_lastmsgtstmp and t_msgquantity are initialised to 0
 * and populated correctly by SQL in phase 4 (ThreadUpdatePhase).
 */
class ThreadPhase extends AbstractPhase
{
    public function getName(): string
    {
        return 'threads';
    }

    protected function execute(bool $resume): void
    {
        $threadCount = $this->config['seed']['thread_count'];
        $boardIds    = $this->config['seed']['board_ids'];
        $boardCount  = count($boardIds);
        $flushSize   = $this->config['batch']['flush_size'];
        $startTs     = $this->config['seed']['start_timestamp'] ?: strtotime('-5 years');
        $now         = time();

        $startFrom   = $resume ? (int) $this->getCheckpointValue('last_thread_id', 0) : 0;
        $maxExisting = (int) $this->db->scalar('SELECT COALESCE(MAX(t_id), 0) FROM pxm_thread');
        $startId     = max($maxExisting + 1, $startFrom + 1);

        if ($startFrom > 0) {
            $this->log("Resuming from thread ID {$startId}");
        }

        $columns = ['t_id', 't_boardid', 't_active', 't_fixed', 't_lastmsgtstmp', 't_lastmsgid', 't_msgquantity', 't_views'];
        $batch   = [];
        $endId   = $startId + $threadCount - 1;
        $done    = 0;

        $this->progress->start('Threads', $threadCount);
        $this->db->beginTransaction();

        for ($threadId = $startId; $threadId <= $endId; $threadId++) {
            $boardId = $boardIds[$threadId % $boardCount];
            $views   = mt_rand(0, 5_000);

            $batch[] = [
                $threadId,
                $boardId,
                1,          // t_active
                0,          // t_fixed
                0,          // t_lastmsgtstmp (updated in phase 4)
                0,          // t_lastmsgid    (updated in phase 4)
                0,          // t_msgquantity  (updated in phase 4)
                $views,
            ];

            $done++;

            if (count($batch) >= $flushSize) {
                $this->db->bulkFlush('pxm_thread', $columns, $batch);
                $batch = [];
                $this->saveCheckpointValue('last_thread_id', $threadId);
                $this->progress->update($done);
            }
        }

        if (!empty($batch)) {
            $this->db->bulkFlush('pxm_thread', $columns, $batch);
        }

        $this->db->commit();
        $this->progress->finish();

        $this->log("Total: {$done} threads (ID {$startId}–{$endId})");
        $this->saveCheckpointValue('start_id', $startId);
        $this->saveCheckpointValue('end_id', $endId);
    }
}
