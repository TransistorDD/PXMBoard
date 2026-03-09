<?php

declare(strict_types=1);

/**
 * Phase 4: Updates metadata in pxm_thread and pxm_user.
 *
 * Sets after message seeding:
 *   - t_lastmsgid      → ID of the most recent message per thread
 *   - t_lastmsgtstmp   → Timestamp of the most recent message per thread
 *   - t_msgquantity    → Number of messages per thread
 *   - u_msgquantity    → Number of messages per user
 *
 * Also updates b_lastmsgtstmp in pxm_board.
 */
class ThreadUpdatePhase extends AbstractPhase
{
    public function getName(): string
    {
        return 'update';
    }

    protected function execute(bool $resume): void
    {
        $this->log('Updating pxm_thread (lastmsgid, lastmsgtstmp, msgquantity)...');
        $this->db->exec('
            UPDATE pxm_thread t
            INNER JOIN (
                SELECT
                    m_threadid,
                    MAX(m_id)    AS last_id,
                    MAX(m_tstmp) AS last_ts,
                    COUNT(*)     AS qty
                FROM pxm_message
                WHERE m_status = 1
                GROUP BY m_threadid
            ) agg ON t.t_id = agg.m_threadid
            SET
                t.t_lastmsgid    = agg.last_id,
                t.t_lastmsgtstmp = agg.last_ts,
                t.t_msgquantity  = agg.qty
        ');
        $this->log('pxm_thread updated.');

        $this->log('Updating pxm_user (msgquantity)...');
        $this->db->exec('
            UPDATE pxm_user u
            INNER JOIN (
                SELECT m_userid, COUNT(*) AS cnt
                FROM pxm_message
                WHERE m_status = 1
                GROUP BY m_userid
            ) agg ON u.u_id = agg.m_userid
            SET u.u_msgquantity = agg.cnt
        ');
        $this->log('pxm_user updated.');

        $this->log('Updating pxm_board (lastmsgtstmp)...');
        $this->db->exec('
            UPDATE pxm_board b
            INNER JOIN (
                SELECT t_boardid, MAX(t_lastmsgtstmp) AS last_ts
                FROM pxm_thread
                GROUP BY t_boardid
            ) agg ON b.b_id = agg.t_boardid
            SET b.b_lastmsgtstmp = agg.last_ts
        ');
        $this->log('pxm_board updated.');

        // Print statistics
        $threadCount  = $this->db->scalar('SELECT COUNT(*) FROM pxm_thread WHERE t_msgquantity > 0');
        $messageCount = $this->db->scalar('SELECT COUNT(*) FROM pxm_message WHERE m_status = 1');
        $userCount    = $this->db->scalar('SELECT COUNT(*) FROM pxm_user WHERE u_msgquantity > 0');

        $this->log("Threads with messages: {$threadCount}");
        $this->log("Total messages:        {$messageCount}");
        $this->log("Users with posts:      {$userCount}");
    }
}
