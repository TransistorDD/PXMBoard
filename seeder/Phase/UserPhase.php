<?php

declare(strict_types=1);

/**
 * Phase 1: Generates synthetic users in pxm_user.
 *
 * - Usernames: adjective + noun + number (unique per user ID)
 * - All users share the password "test1234" (one bcrypt hash computed once)
 * - u_status = 1 (active), posting permissions enabled
 */
class UserPhase extends AbstractPhase
{
    public function getName(): string
    {
        return 'users';
    }

    protected function execute(bool $resume): void
    {
        $userCount   = $this->config['seed']['user_count'];
        $flushSize   = $this->config['batch']['flush_size'];
        $startFrom   = $resume ? (int) $this->getCheckpointValue('last_user_id', 0) : 0;

        // Starting u_id: append after existing users
        $maxExisting = (int) $this->db->scalar('SELECT COALESCE(MAX(u_id), 0) FROM pxm_user');
        $startId     = max($maxExisting + 1, $startFrom + 1);

        if ($startFrom > 0) {
            $this->log("Resuming from user ID {$startId}");
        }

        $passwordHash = DataGenerator::passwordHash();
        $now          = time();
        $columns      = [
            'u_id', 'u_username', 'u_password', 'u_passwordkey',
            'u_firstname', 'u_lastname', 'u_city',
            'u_publicmail', 'u_privatemail', 'u_registrationmail',
            'u_registrationtstmp', 'u_msgquantity', 'u_lastonlinetstmp',
            'u_profilechangedtstmp', 'u_imgfile', 'u_signature',
            'u_profile_url', 'u_profile_hobby',
            'u_highlight', 'u_status', 'u_post', 'u_edit',
            'u_admin', 'u_visible', 'u_skinid', 'u_threadlistsort',
            'u_timeoffset', 'u_embed_external', 'u_privatenotification',
            'u_notification_unread_count', 'u_priv_message_unread_count',
        ];

        $batch      = [];
        $totalDone  = 0;
        $endId      = $startId + $userCount - 1;

        $this->progress->start('Users', $userCount);
        $this->db->beginTransaction();

        for ($userId = $startId; $userId <= $endId; $userId++) {
            $regTs  = DataGenerator::randomTimestamp(
                $this->config['seed']['start_timestamp'] ?: strtotime('-5 years'),
                $now
            );

            $batch[] = [
                $userId,
                DataGenerator::username($userId),
                $passwordHash,
                DataGenerator::passwordKey($userId),
                '',                                 // u_firstname
                '',                                 // u_lastname
                '',                                 // u_city
                '',                                 // u_publicmail
                DataGenerator::email($userId),      // u_privatemail
                DataGenerator::email($userId),      // u_registrationmail
                $regTs,                             // u_registrationtstmp
                0,                                  // u_msgquantity (updated in phase 4)
                $regTs,                             // u_lastonlinetstmp
                $regTs,                             // u_profilechangedtstmp
                '',                                 // u_imgfile
                '',                                 // u_signature
                '',                                 // u_profile_url
                '',                                 // u_profile_hobby
                0,                                  // u_highlight
                1,                                  // u_status = ACTIVE
                1,                                  // u_post
                1,                                  // u_edit
                0,                                  // u_admin
                1,                                  // u_visible
                1,                                  // u_skinid
                '',                                 // u_threadlistsort
                0,                                  // u_timeoffset
                0,                                  // u_embed_external
                0,                                  // u_privatenotification
                0,                                  // u_notification_unread_count
                0,                                  // u_priv_message_unread_count
            ];

            $totalDone++;

            if (count($batch) >= $flushSize) {
                $this->db->bulkFlush('pxm_user', $columns, $batch);
                $batch = [];
                $this->saveCheckpointValue('last_user_id', $userId);
                $this->progress->update($totalDone);
            }
        }

        if (!empty($batch)) {
            $this->db->bulkFlush('pxm_user', $columns, $batch);
        }

        $this->db->commit();
        $this->progress->finish();

        $this->log("Total: {$totalDone} users (ID {$startId}–{$endId})");
        $this->saveCheckpointValue('start_id', $startId);
        $this->saveCheckpointValue('end_id', $endId);
    }
}
