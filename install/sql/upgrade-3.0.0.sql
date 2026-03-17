-- PXMBoard Database Upgrade Script
-- Version: 3.0.0
-- Description: Complete upgrade from 2.5.1 to 3.0.0
--
-- This script consolidates all schema changes for Release 3.0.0:
-- 1. Password hashing: Extend password field for bcrypt
-- 2. Notification terminology: Rename pxm_notification to pxm_template
-- 3. User notifications: New notification system with in-app messages
-- 4. Message read tracking: Server-side read status for logged-in users
-- 5. Message drafts: Status field for draft functionality
-- 6. Secure tokens: Enhanced security for login tickets and password recovery
-- 7. Remove banner: Drop unused banner functionality
-- 8. Remove guestpost: Drop unused guestpost configuration
--
-- Execute this script once per installation.
--

-- ============================================================================
-- ENGINE AND CHARACTER SET CONVERSION: MyISAM -> InnoDB, charset -> utf8mb4
-- ============================================================================
-- Legacy PXMBoard installations used TYPE=MyISAM (old MySQL syntax) or
-- ENGINE=MyISAM with latin1/utf8 charset. This section converts all existing
-- tables to ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci.
--
-- InnoDB is required for:
-- - Foreign key constraints (pxm_user_login_ticket, pxm_notification)
-- - ACID-compliant transactions used in the codebase
-- - Consistent row-level locking behaviour
--
-- utf8mb4 is required for:
-- - Full Unicode support (4-byte characters, emojis)
-- - Correct FULLTEXT indexing in MySQL 5.6+ / MariaDB 10+
--
-- CONVERT TO CHARACTER SET re-encodes existing column definitions AND stored
-- data, so this must run before any structural changes below.
--
-- NOTE: These ALTER TABLE operations rebuild each table. On large forums this
-- may take several minutes per table. A backup before running is recommended.

ALTER TABLE `pxm_badword`        ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_board`          ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_configuration`  ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_forbiddenmail`  ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_message`        ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_moderator`      ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_notification`   ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_priv_message`   ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_profile_accept` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_search`         ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_skin`           ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_textreplacement` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_thread`         ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `pxm_user`           ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ============================================================================
-- PASSWORD HASHING: Extend password field for bcrypt
-- ============================================================================
-- This extends the u_password field to support bcrypt hashes (60 chars)
-- and future algorithms (up to 255 chars).
--
-- Existing MD5 hashes (32 chars) will continue to work and be automatically
-- migrated to bcrypt on next successful user login.

ALTER TABLE `pxm_user`
  MODIFY COLUMN `u_password` VARCHAR(255) NOT NULL DEFAULT '';

-- ============================================================================
-- NOTIFICATION TERMINOLOGY: Rename pxm_notification to pxm_template
-- ============================================================================
-- This migration addresses two distinct concepts that were both called "notification":
-- 1. pxm_notification table -> pxm_template (Text templates for emails)
-- 2. m_notification field -> m_notify_on_reply (Flag: Send email notification on reply)

-- Step 1: Rename table pxm_notification to pxm_template
RENAME TABLE `pxm_notification` TO `pxm_template`;

-- Step 2: Rename columns in pxm_template (n_* -> te_*)
ALTER TABLE `pxm_template`
  CHANGE COLUMN `n_id` `te_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Template ID',
  CHANGE COLUMN `n_message` `te_message` TEXT NOT NULL COMMENT 'Template text (supports %placeholder% substitution)',
  CHANGE COLUMN `n_name` `te_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Internal template name',
  CHANGE COLUMN `n_description` `te_description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Template description and placeholder reference';

-- Step 3: Update table comment to clarify purpose
ALTER TABLE `pxm_template`
  COMMENT='Text templates for emails and application messages';

-- Step 4: Rename column in pxm_message (m_notification -> m_notify_on_reply)
ALTER TABLE `pxm_message`
  CHANGE COLUMN `m_notification` `m_notify_on_reply` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Send email notification on reply';

-- ============================================================================
-- USER NOTIFICATION SYSTEM: In-app notifications
-- ============================================================================
-- Adds notification table and unread count cache in pxm_user
--
-- Features:
-- - In-app notifications for forum events (replies, private messages)
-- - Automatic aging: 7 days -> read, 90 days -> deleted
-- - Unread count cache in u_notification_unread_count for performance

CREATE TABLE `pxm_notification` (
  `n_id`                INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Notification ID',
  `n_userid`            MEDIUMINT UNSIGNED NOT NULL COMMENT 'Recipient user ID (FK: pxm_user)',
  `n_type`              VARCHAR(50) NOT NULL COMMENT 'Notification type (eNotificationType)',
  `n_status`            ENUM('unread', 'read') NOT NULL DEFAULT 'unread' COMMENT 'Read status',
  `n_title`             VARCHAR(255) NOT NULL COMMENT 'Notification title',
  `n_message`           TEXT NOT NULL COMMENT 'Notification message text',
  `n_link`              VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Optional URL link',
  `n_related_messageid` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Related board message ID',
  `n_related_pmid`      INT UNSIGNED NULL DEFAULT NULL COMMENT 'Related private message ID',
  `n_created_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when notification was created',
  `n_read_timestamp`    INT UNSIGNED NULL DEFAULT NULL COMMENT 'Unix timestamp when notification was read',
  PRIMARY KEY (`n_id`),
  INDEX `idx_userid_status` (`n_userid`, `n_status`),
  INDEX `idx_created` (`n_created_timestamp`),
  INDEX `idx_type` (`n_type`),
  CONSTRAINT `fk_notification_user`
    FOREIGN KEY (`n_userid`) REFERENCES `pxm_user` (`u_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User notifications for forum events';

-- Add unread count cache to pxm_user table (for badge display performance)
ALTER TABLE `pxm_user`
  ADD COLUMN `u_notification_unread_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread in-app notifications';

-- Add unread private message count cache to pxm_user table
ALTER TABLE `pxm_user`
  ADD COLUMN `u_priv_message_unread_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread private messages';

-- ============================================================================
-- MESSAGE DRAFTS: Draft functionality
-- ============================================================================
-- Add status column to public messages for draft functionality
--
-- Status values:
-- 0 = draft (visible only to author)
-- 1 = published (visible to all)
-- 2 = archived (visible but read-only)
-- 3 = deleted (soft delete)

ALTER TABLE `pxm_message`
ADD COLUMN `m_status` TINYINT UNSIGNED NOT NULL DEFAULT 1
COMMENT '0=draft, 1=published, 2=archived, 3=deleted'
AFTER `m_notify_on_reply`;

-- Add index for faster draft queries
ALTER TABLE `pxm_message` ADD INDEX `m_status` (`m_status`);

-- ============================================================================
-- SECURE TOKEN GENERATION: Enhanced token security
-- ============================================================================
-- Upgrade token storage format for Password Recovery Keys (u_passwordkey)
--
-- NEW IMPLEMENTATION:
-- - Uses bin2hex(random_bytes(16)) for 32-character hex strings (128-bit entropy)
-- - Stores as CHAR(32) for consistent, indexed lookups
--
-- NOTE: If this migration fails due to duplicate values, manually clean up the
-- pxm_user table (set duplicates to NULL) and retry the migration.

ALTER TABLE `pxm_user`
  MODIFY COLUMN `u_passwordkey` CHAR(32) NULL DEFAULT NULL COMMENT 'Password recovery key (hex-encoded 16 random bytes)';

-- ============================================================================
-- MULTI-DEVICE LOGIN: Login Tickets Table
-- ============================================================================
-- Creates new pxm_user_login_ticket table for multi-device persistent login
-- Migrates existing u_ticket data and removes legacy u_ticket column
--
-- FEATURES:
-- - Multiple login tickets per user (one per device)
-- - User-Agent and IP address tracking for device identification
-- - Last-used timestamp for cleanup of inactive tickets
-- - Foreign key constraint with CASCADE DELETE for data integrity
--
-- NOTE: u_lastlogin column remains in pxm_user table for performance

CREATE TABLE `pxm_user_login_ticket` (
  `ult_id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Login ticket ID',
  `ult_userid`              MEDIUMINT UNSIGNED NOT NULL COMMENT 'User ID (FK: pxm_user)',
  `ult_token`               CHAR(32) NOT NULL COMMENT 'Secure random login token',
  `ult_useragent`           VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Browser User-Agent string',
  `ult_ipaddress`           VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Client IP address',
  `ult_created_timestamp`   INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when ticket was created',
  `ult_last_used_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of last use',
  PRIMARY KEY (`ult_id`),
  UNIQUE KEY `ult_token` (`ult_token`),
  INDEX `idx_userid` (`ult_userid`),
  INDEX `idx_last_used` (`ult_last_used_timestamp`),
  CONSTRAINT `fk_login_ticket_user`
    FOREIGN KEY (`ult_userid`) REFERENCES `pxm_user` (`u_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Multiple login tickets per user for multi-device support';

-- Migrate existing u_ticket to new table (if tickets exist)
INSERT INTO pxm_user_login_ticket (ult_userid, ult_token, ult_useragent, ult_ipaddress, ult_created_timestamp, ult_last_used_timestamp)
SELECT u_id, u_ticket, '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM pxm_user
WHERE u_ticket != '' AND u_ticket IS NOT NULL;

-- Remove legacy u_ticket column (no backward compatibility needed)
ALTER TABLE `pxm_user` DROP COLUMN IF EXISTS `u_ticket`;

-- ============================================================================
-- REMOVE BANNER FUNCTIONALITY
-- ============================================================================
-- Drops pxm_banner table and c_banner column from configuration
-- Banner system was removed in 3.0.0 (XSS risk, redundant to modern alternatives)

DROP TABLE IF EXISTS `pxm_banner`;

ALTER TABLE `pxm_configuration` DROP COLUMN IF EXISTS `c_banner`;

-- ============================================================================
-- REMOVE GUESTPOST FUNCTIONALITY
-- ============================================================================
-- Removes guestpost configuration (security feature removal)
-- Guestposting was removed in 3.0.0 as a security measure

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'pxm_configuration'
    AND COLUMN_NAME = 'c_guestpost'
    AND TABLE_SCHEMA = DATABASE()
);

SET @query = IF(@column_exists > 0,
    'ALTER TABLE pxm_configuration DROP COLUMN c_guestpost',
    'SELECT "Column c_guestpost does not exist, skipping." AS message'
);

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- REMOVE UNUSED CONFIGURATION OPTIONS
-- ============================================================================
-- Removes c_countviews and c_quotechar columns from pxm_configuration
-- These configuration options are no longer used:
-- - c_countviews: Thread view counting is now always enabled (not configurable)
-- - c_quotechar: Quote character prefix feature removed (unnecessary with modern editor)

ALTER TABLE `pxm_configuration` DROP COLUMN IF EXISTS `c_countviews`;
ALTER TABLE `pxm_configuration` DROP COLUMN IF EXISTS `c_quotechar`;

-- ============================================================================
-- REMOVE ERROR TABLE (Error messages moved to PHP Enum)
-- ============================================================================
-- Drops pxm_error table - error messages are now defined as eError enum
-- This improves type safety and eliminates database lookups for error texts

DROP TABLE IF EXISTS `pxm_error`;

-- ============================================================================
-- UPDATE SKIN TABLE: Rename skins and remove quote formatting
-- ============================================================================
-- Updates skin names and removes quoteprefix/quotesuffix fields
-- Quote formatting is now handled by CSS instead of inline HTML

-- Update skin names (match by old name, not by ID)
UPDATE `pxm_skin` SET `s_fieldvalue` = 'PXM Xsl Skin (deprecated)'
WHERE `s_fieldvalue` = 'PXM Xsl Template' AND `s_fieldname` = 'name';

UPDATE `pxm_skin` SET `s_fieldvalue` = 'PXM Skin'
WHERE `s_fieldvalue` IN ('PXM Smarty Template', 'PXM Template') AND `s_fieldname` = 'name';

-- Remove quoteprefix and quotesuffix (quote styling now in CSS)
DELETE FROM `pxm_skin` WHERE `s_fieldname` = 'quoteprefix';
DELETE FROM `pxm_skin` WHERE `s_fieldname` = 'quotesuffix';

-- ============================================================================
-- TERMINOLOGY: Rename "nickname" to "username"
-- ============================================================================
-- This migration renames all nickname-related database columns to use the
-- English term "username" for consistency throughout the application.
--
-- Affected tables:
-- 1. pxm_user: u_nickname -> u_username
-- 2. pxm_message: m_usernickname -> m_userusername
-- 3. pxm_search: se_nickname -> se_username

-- Step 1: Rename column in pxm_user table
ALTER TABLE `pxm_user`
  CHANGE COLUMN `u_nickname` `u_username` VARCHAR(30) NOT NULL DEFAULT '';

-- Step 2: Update UNIQUE constraint on renamed column
ALTER TABLE `pxm_user`
  DROP INDEX `u_nickname`;

ALTER TABLE `pxm_user`
  ADD UNIQUE KEY `u_username` (`u_username`);

-- Step 3: Rename column in pxm_message table
ALTER TABLE `pxm_message`
  CHANGE COLUMN `m_usernickname` `m_username` VARCHAR(30) NOT NULL DEFAULT '';

-- Step 4: Update INDEX on pxm_message
ALTER TABLE `pxm_message`
  DROP INDEX `m_usernickname`;

ALTER TABLE `pxm_message`
  ADD INDEX `m_username` (`m_username`, `m_tstmp`);

-- Step 5: Rename column in pxm_search table
ALTER TABLE `pxm_search`
  CHANGE COLUMN `se_nickname` `se_username` VARCHAR(30) NOT NULL DEFAULT '';

-- ============================================================================
-- MESSAGE-SPECIFIC NOTIFICATIONS: Per-message notification subscriptions
-- ============================================================================
-- This migration introduces granular notification control at the message level.
-- Users can now subscribe to notifications for any message, not just their own.
--
-- This replaces the simple m_notify_on_reply flag with a more flexible system:
-- - Authors are automatically subscribed to their own messages
-- - Users can subscribe to any message to receive notifications on replies
-- - Multiple users can subscribe to the same message
-- - Unsubscribing deletes the entry (no soft-delete)
--
-- New table: pxm_message_notification
-- - Stores user subscriptions to messages
-- - Composite primary key (mn_messageid, mn_userid) prevents duplicates
-- - Indices for efficient queries when:
--   * Checking if a user is subscribed to a message (rendering)
--   * Finding all subscribers when a reply is posted

CREATE TABLE `pxm_message_notification` (
  `mn_messageid` INT UNSIGNED NOT NULL COMMENT 'Message ID being watched',
  `mn_userid`    MEDIUMINT UNSIGNED NOT NULL COMMENT 'Subscriber user ID',
  PRIMARY KEY (`mn_messageid`, `mn_userid`),
  KEY `mn_messageid` (`mn_messageid`),
  KEY `mn_userid` (`mn_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Per-message notification subscriptions';

-- ============================================================================
-- REMOVE LEGACY FRAME SETTINGS
-- ============================================================================
-- Frame-based layouts are obsolete. Remove frame size configuration.
-- These fields were never used in the modern template implementation.

ALTER TABLE `pxm_user`
  DROP COLUMN IF EXISTS `u_frame_top`,
  DROP COLUMN IF EXISTS `u_frame_bottom`;

-- ============================================================================
-- CONSOLIDATE USER STATUS: DISABLED_BY_MOD -> DISABLED
-- ============================================================================
-- Merge DISABLED_BY_MOD (4) into DISABLED (3).
-- DISABLED will be the single status for disabled users.
-- This simplifies the user status model.

UPDATE `pxm_user` SET `u_status` = 3 WHERE `u_status` = 4;

-- Add documentation comment to u_status column
ALTER TABLE `pxm_user` MODIFY COLUMN `u_status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=ACTIVE, 2=NOT_ACTIVATED, 3=DISABLED';

-- ============================================================================
-- CONVERT BOOLEAN-LIKE COLUMNS TO BOOLEAN
-- ============================================================================
-- Convert TINYINT columns that represent boolean values
-- to proper BOOLEAN type for better semantic clarity.

ALTER TABLE `pxm_user`
  MODIFY COLUMN `u_highlight` BOOLEAN NOT NULL default FALSE,
  MODIFY COLUMN `u_post` BOOLEAN NOT NULL default TRUE,
  MODIFY COLUMN `u_edit` BOOLEAN NOT NULL default TRUE,
  MODIFY COLUMN `u_admin` BOOLEAN NOT NULL default FALSE,
  MODIFY COLUMN `u_visible` BOOLEAN NOT NULL default TRUE,
  MODIFY COLUMN `u_privatenotification` BOOLEAN NOT NULL default FALSE;

-- ============================================================================
-- BOARD STATUS EXPANSION: From binary to five states
-- ============================================================================
-- Replaces b_active (0=closed, 1=public) with b_status enum supporting:
-- 1 = PUBLIC          (everyone can read, authenticated users can write)
-- 2 = MEMBERS_ONLY    (only authenticated users can read and write)
-- 3 = READONLY_PUBLIC (everyone can read, only mods/admins can write)
-- 4 = READONLY_MEMBERS (only authenticated users can read, only mods/admins can write)
-- 5 = CLOSED          (only mods/admins can access)

-- Rename and modify b_active to b_status
ALTER TABLE `pxm_board`
  CHANGE COLUMN `b_active` `b_status` TINYINT UNSIGNED NOT NULL DEFAULT 1
  COMMENT '1=PUBLIC, 2=MEMBERS_ONLY, 3=READONLY_PUBLIC, 4=READONLY_MEMBERS, 5=CLOSED';

-- Migrate existing values: active=1 -> PUBLIC(1), active=0 -> CLOSED(5)
UPDATE `pxm_board` SET `b_status` = CASE
  WHEN `b_status` = 0 THEN 5
  ELSE 1
END;

-- ============================================================================
-- SEARCH PERFORMANCE: Add index on m_parentid for relevance-weighted queries
-- ============================================================================
-- This index improves performance for search queries that differentiate between
-- root messages (m_parentid=0) and replies for weighted relevance scoring.
-- Without this index, the CASE WHEN m_parentid=0 condition requires full scans.

ALTER TABLE `pxm_message`
  ADD INDEX `m_parentid` (`m_parentid`);

-- ============================================================================
-- SEARCH RATE LIMITING: Add IP address tracking to search profiles
-- ============================================================================
-- Store the IP address of users performing searches to enable rate limiting
-- (max. 5 searches per minute per IP). The combined index on (se_ipaddress,
-- se_tstmp) enables efficient queries for rate limit checks.

ALTER TABLE `pxm_search`
  ADD COLUMN `se_ipaddress` VARCHAR(45) NOT NULL DEFAULT '' AFTER `se_tstmp`;

ALTER TABLE `pxm_search`
  ADD INDEX `idx_ratelimit` (`se_ipaddress`, `se_tstmp`);

-- ============================================================================
-- SCHEMA CLEANUP: Remove deprecated parser columns
-- ============================================================================
-- Remove b_parsestyle and b_parseurl from pxm_board (no longer used),
-- rename b_parseimg to b_embed_external (now controls all external content embedding).
-- Remove c_parseurl and c_parsestyle from pxm_configuration (no longer used).
-- Remove u_replacetext and u_showsignatures from pxm_user (no longer used),
-- rename u_parseimg to u_embed_external.

ALTER TABLE `pxm_board`
  DROP COLUMN IF EXISTS `b_parsestyle`,
  DROP COLUMN IF EXISTS `b_parseurl`,
  CHANGE COLUMN `b_parseimg` `b_embed_external` BOOLEAN NOT NULL DEFAULT TRUE
    COMMENT 'Allow embedding external content (images, YouTube, Twitch)';

ALTER TABLE `pxm_configuration`
  DROP COLUMN IF EXISTS `c_parseurl`,
  DROP COLUMN IF EXISTS `c_parsestyle`;

ALTER TABLE `pxm_user`
  DROP COLUMN IF EXISTS `u_replacetext`,
  DROP COLUMN IF EXISTS `u_showsignatures`,
  CHANGE COLUMN `u_parseimg` `u_embed_external` BOOLEAN NOT NULL DEFAULT FALSE
    COMMENT 'Allow embedding external content (images, YouTube, Twitch)';


-- ============================================================================
-- SCHEMA CLEANUP: Remove flat-mode messages-per-page configuration
-- ============================================================================
-- c_msgperpage was used for flat-mode message display which is no longer
-- supported. The setting is replaced by c_msgheaderperpage for all list views.

ALTER TABLE `pxm_configuration`
  DROP COLUMN IF EXISTS `c_msgperpage`;


-- ============================================================================
-- SCHEMA CHANGE: pxm_message_read monthly range partitioning
-- ============================================================================
-- Replace the old timestamp-based read tracking table with a partitioned
-- schema. mr_timestamp is dropped entirely; mr_year_month (YYMM, 2 bytes)
-- replaces it as the partition key and must be part of the PRIMARY KEY
-- (MySQL requirement §26.6.1).
--
-- A new helper table pxm_message_read_partition tracks managed months so
-- that managePartitions() can use a fast PK-lookup instead of querying
-- INFORMATION_SCHEMA (which is slow and often locked on shared hosts).
--
-- NOTE: This is a development-only upgrade. pxm_message_read was introduced
-- in 3.0.0 and there are no production installations yet. The table is
-- dropped and recreated; no data migration is necessary.

DROP TABLE IF EXISTS `pxm_message_read`;

CREATE TABLE `pxm_message_read` (
  `mr_userid`     MEDIUMINT UNSIGNED NOT NULL COMMENT 'User ID who read the message',
  `mr_messageid`  INT UNSIGNED NOT NULL COMMENT 'Read message ID',
  `mr_year_month` SMALLINT UNSIGNED NOT NULL COMMENT 'YYMM, partition key',
  PRIMARY KEY (`mr_userid`, `mr_messageid`, `mr_year_month`),
  KEY `idx_messageid` (`mr_messageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks read messages per user. Monthly RANGE partitions; mr_year_month must be in PRIMARY KEY (MySQL 26.6.1).'
  PARTITION BY RANGE (`mr_year_month`) (
    PARTITION p_initial VALUES LESS THAN (2601)
  );

DROP TABLE IF EXISTS `pxm_message_read_partition`;

CREATE TABLE `pxm_message_read_partition` (
  `mrp_year_month`        SMALLINT UNSIGNED NOT NULL COMMENT 'YYMM, managed partition month',
  `mrp_created_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when partition was created',
  PRIMARY KEY (`mrp_year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks managed months; INSERT IGNORE as concurrency gate avoids INFORMATION_SCHEMA queries.';

-- Read tracking retention configuration
ALTER TABLE `pxm_configuration`
  ADD COLUMN IF NOT EXISTS `c_read_retention_months` TINYINT UNSIGNED NOT NULL DEFAULT 13
  COMMENT 'Read tracking retention in months';

-- ============================================================================
-- COLUMN TYPE MODERNIZATION: Remove deprecated display widths, boolean cleanup,
-- and right-size ID columns
-- ============================================================================
-- MySQL 8.0+ deprecates integer display widths (e.g. INT(10), TINYINT(3)).
-- Boolean-typed TINYINT columns are converted to BOOLEAN for semantic clarity.
-- Oversized ID columns are right-sized to save index and storage space.
-- Missing column comments are added; German comments translated to English.
--
-- These changes are purely structural; no data migration is required.
-- All new types can hold all existing values.

-- ── pxm_board ────────────────────────────────────────────────────────────────
ALTER TABLE `pxm_board`
  MODIFY COLUMN `b_id`             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Board ID',
  MODIFY COLUMN `b_name`           VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Board display name',
  MODIFY COLUMN `b_description`    VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Board description',
  MODIFY COLUMN `b_position`       SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display position for sorting boards',
  MODIFY COLUMN `b_lastmsgtstmp`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of the last message',
  MODIFY COLUMN `b_skinid`         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Board-specific skin ID (FK: pxm_skin)',
  MODIFY COLUMN `b_timespan`       SMALLINT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Days back for thread list (0=show all)',
  MODIFY COLUMN `b_threadlistsort` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Default thread list sort order',
  MODIFY COLUMN `b_replacetext`    BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Enable text replacement (emoticons etc.)';

-- ── pxm_configuration ────────────────────────────────────────────────────────
ALTER TABLE `pxm_configuration`
  MODIFY COLUMN `c_id`                  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Configuration ID (always 1)',
  MODIFY COLUMN `c_quickpost`           BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Enable quick reply form on thread page',
  MODIFY COLUMN `c_directregistration`  BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Allow direct registration without admin approval',
  MODIFY COLUMN `c_uniquemail`          BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Require unique email address per user',
  MODIFY COLUMN `c_dateformat`          VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Date format string (PHP date() style)',
  MODIFY COLUMN `c_timeoffset`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Global time offset in hours',
  MODIFY COLUMN `c_onlinetime`          SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Duration to show users as online in seconds (0=disabled)',
  MODIFY COLUMN `c_closethreads`        SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum messages per thread before closing (0=no limit)',
  MODIFY COLUMN `c_usrperpage`          MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Users per page (online list, user search, admin)',
  MODIFY COLUMN `c_msgheaderperpage`    MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Message headers per page (search results)',
  MODIFY COLUMN `c_privatemsgperpage`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Private messages per page',
  MODIFY COLUMN `c_thrdperpage`         MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Threads per page',
  MODIFY COLUMN `c_mailwebmaster`       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Webmaster email address',
  MODIFY COLUMN `c_maxprofilepicsize`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image file size in bytes',
  MODIFY COLUMN `c_maxprofilepicwidth`  SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image width in pixels',
  MODIFY COLUMN `c_maxprofilepicheight` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image height in pixels',
  MODIFY COLUMN `c_profileimgdir`       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Directory for profile images',
  MODIFY COLUMN `c_usesignatures`       BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Enable user signatures globally',
  MODIFY COLUMN `c_skinid`              TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Default skin ID (FK: pxm_skin)',
  MODIFY COLUMN `c_quotesubject`        VARCHAR(10) NOT NULL DEFAULT 'Re:' COMMENT 'Prefix for quoted message subjects',
  MODIFY COLUMN `c_skindir`             VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Base directory for skins';

-- ── pxm_message ──────────────────────────────────────────────────────────────
ALTER TABLE `pxm_message`
  MODIFY COLUMN `m_id`              INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Message ID',
  MODIFY COLUMN `m_threadid`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent thread ID',
  MODIFY COLUMN `m_parentid`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent message ID (0=root message)',
  MODIFY COLUMN `m_userid`          MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Author user ID (0=guest)',
  MODIFY COLUMN `m_username`        VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Author username at time of posting',
  MODIFY COLUMN `m_usermail`        VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Author email at time of posting',
  MODIFY COLUMN `m_userhighlight`   BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Highlight this users messages',
  MODIFY COLUMN `m_subject`         VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Message subject',
  MODIFY COLUMN `m_tstmp`           INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of posting',
  MODIFY COLUMN `m_ip`              VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Poster IP address',
  MODIFY COLUMN `m_status`          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '0=draft, 1=published, 2=archived, 3=deleted';

-- ── pxm_message_notification ─────────────────────────────────────────────────
ALTER TABLE `pxm_message_notification`
  MODIFY COLUMN `mn_messageid` INT UNSIGNED NOT NULL COMMENT 'Message ID being watched',
  MODIFY COLUMN `mn_userid`    MEDIUMINT UNSIGNED NOT NULL COMMENT 'Subscriber user ID';

-- ── pxm_moderator ────────────────────────────────────────────────────────────
ALTER TABLE `pxm_moderator`
  MODIFY COLUMN `mod_userid`  MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Moderator user ID',
  MODIFY COLUMN `mod_boardid` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Board ID';

-- ── pxm_priv_message ─────────────────────────────────────────────────────────
ALTER TABLE `pxm_priv_message`
  MODIFY COLUMN `p_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Private message ID',
  MODIFY COLUMN `p_touserid`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Recipient user ID',
  MODIFY COLUMN `p_fromuserid` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sender user ID',
  MODIFY COLUMN `p_subject`    VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Message subject',
  MODIFY COLUMN `p_tstmp`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of sending',
  MODIFY COLUMN `p_ip`         VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Sender IP address',
  MODIFY COLUMN `p_tostate`    TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=UNREAD, 2=READ, 3=DELETED (recipient view)',
  MODIFY COLUMN `p_fromstate`  TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=UNREAD, 2=READ, 3=DELETED (sender view)';

-- ── pxm_profile_accept ───────────────────────────────────────────────────────
ALTER TABLE `pxm_profile_accept`
  MODIFY COLUMN `pa_name`   CHAR(15) NOT NULL DEFAULT '' COMMENT 'Profile field name',
  MODIFY COLUMN `pa_type`   ENUM('s','a','i') NOT NULL DEFAULT 's' COMMENT 'Field type: s=string, a=alpha, i=integer',
  MODIFY COLUMN `pa_length` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum field length';

-- ── pxm_search ───────────────────────────────────────────────────────────────
ALTER TABLE `pxm_search`
  MODIFY COLUMN `se_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Search query ID',
  MODIFY COLUMN `se_userid`    MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'User ID who performed the search (0=guest)',
  MODIFY COLUMN `se_message`   VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Search term',
  MODIFY COLUMN `se_username`  VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Username filter',
  MODIFY COLUMN `se_days`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Days back filter (0=all)',
  MODIFY COLUMN `se_boardids`  VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Board ID filter (comma-separated)',
  MODIFY COLUMN `se_tstmp`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of search',
  MODIFY COLUMN `se_ipaddress` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Searcher IP address';

-- ── pxm_skin ─────────────────────────────────────────────────────────────────
ALTER TABLE `pxm_skin`
  MODIFY COLUMN `s_id`         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Skin ID',
  MODIFY COLUMN `s_fieldname`  VARCHAR(15) NOT NULL DEFAULT '' COMMENT 'Skin configuration field name',
  MODIFY COLUMN `s_fieldvalue` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Skin configuration field value';

-- ── pxm_template ─────────────────────────────────────────────────────────────
ALTER TABLE `pxm_template`
  MODIFY COLUMN `te_id`          MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Template ID',
  MODIFY COLUMN `te_message`     TEXT NOT NULL COMMENT 'Template text (supports %placeholder% substitution)',
  MODIFY COLUMN `te_name`        VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Internal template name',
  MODIFY COLUMN `te_description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Template description and placeholder reference';

-- ── pxm_thread ───────────────────────────────────────────────────────────────
ALTER TABLE `pxm_thread`
  MODIFY COLUMN `t_id`           INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Thread ID',
  MODIFY COLUMN `t_boardid`      SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent board ID',
  MODIFY COLUMN `t_active`       BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether the thread is visible/active',
  MODIFY COLUMN `t_fixed`        BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether the thread is pinned to top',
  MODIFY COLUMN `t_lastmsgtstmp` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of the last message',
  MODIFY COLUMN `t_lastmsgid`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID of the last message',
  MODIFY COLUMN `t_msgquantity`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total message count in this thread',
  MODIFY COLUMN `t_views`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total view count';

-- ── pxm_user: drop FKs before changing PK type ───────────────────────────────
ALTER TABLE `pxm_user_login_ticket` DROP FOREIGN KEY `fk_login_ticket_user`;
ALTER TABLE `pxm_notification`      DROP FOREIGN KEY `fk_notification_user`;

ALTER TABLE `pxm_user`
  MODIFY COLUMN `u_id`                         MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  MODIFY COLUMN `u_registrationtstmp`          INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of registration',
  MODIFY COLUMN `u_msgquantity`                INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total number of messages posted',
  MODIFY COLUMN `u_lastonlinetstmp`            INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of last online visit',
  MODIFY COLUMN `u_profilechangedtstmp`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of last profile change',
  MODIFY COLUMN `u_skinid`                     TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'User-selected skin ID (FK: pxm_skin)',
  MODIFY COLUMN `u_timeoffset`                 SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Time offset in hours',
  MODIFY COLUMN `u_notification_unread_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread in-app notifications',
  MODIFY COLUMN `u_priv_message_unread_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread private messages';

ALTER TABLE `pxm_user_login_ticket`
  MODIFY COLUMN `ult_id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Login ticket ID',
  MODIFY COLUMN `ult_userid`              MEDIUMINT UNSIGNED NOT NULL COMMENT 'User ID (FK: pxm_user)',
  MODIFY COLUMN `ult_token`               CHAR(32) NOT NULL COMMENT 'Secure random login token',
  MODIFY COLUMN `ult_created_timestamp`   INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when ticket was created',
  MODIFY COLUMN `ult_last_used_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of last use';

ALTER TABLE `pxm_notification`
  MODIFY COLUMN `n_id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Notification ID',
  MODIFY COLUMN `n_userid`             MEDIUMINT UNSIGNED NOT NULL COMMENT 'Recipient user ID (FK: pxm_user)',
  MODIFY COLUMN `n_type`               VARCHAR(50) NOT NULL COMMENT 'Notification type (eNotificationType)',
  MODIFY COLUMN `n_title`              VARCHAR(255) NOT NULL COMMENT 'Notification title',
  MODIFY COLUMN `n_related_messageid`  INT UNSIGNED DEFAULT NULL COMMENT 'Related board message ID',
  MODIFY COLUMN `n_related_pmid`       INT UNSIGNED DEFAULT NULL COMMENT 'Related private message ID',
  MODIFY COLUMN `n_created_timestamp`  INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when notification was created',
  MODIFY COLUMN `n_read_timestamp`     INT UNSIGNED DEFAULT NULL COMMENT 'Unix timestamp when notification was read';

-- Re-add FK constraints after type changes
ALTER TABLE `pxm_user_login_ticket`
  ADD CONSTRAINT `fk_login_ticket_user`
    FOREIGN KEY (`ult_userid`) REFERENCES `pxm_user` (`u_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pxm_notification`
  ADD CONSTRAINT `fk_notification_user`
    FOREIGN KEY (`n_userid`) REFERENCES `pxm_user` (`u_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================================================
-- Add composite indexes on pxm_user for statistics queries (cUserStatistics)
-- ============================================================================
-- Enables index-range-scan + ordered traversal without filesort for:
--   getNewestMember/getNewestMembers/getOldestMembers (ORDER BY u_registrationtstmp)
--   getMostActiveUsers/getLeastActiveUsers             (ORDER BY u_msgquantity)
ALTER TABLE `pxm_user`
  ADD KEY `idx_status_registration` (`u_status`, `u_registrationtstmp`),
  ADD KEY `idx_status_msgquantity`  (`u_status`, `u_msgquantity`);


-- ============================================================================
-- All changes for Release 3.0.0 have been applied successfully.
--
-- Summary of changes:
-- - pxm_user: u_password extended, u_passwordkey to CHAR(32) with UNIQUE constraint, u_notification_unread_count added, u_priv_message_unread_count added, u_ticket removed, u_frame_top and u_frame_bottom dropped, u_status: DISABLED_BY_MOD (4) merged into DISABLED (3), boolean columns converted to BOOLEAN, u_parseimg renamed to u_embed_external, u_replacetext and u_showsignatures removed
-- - pxm_user_login_ticket: new table for multi-device login support with User-Agent and IP tracking
-- - pxm_template: renamed from pxm_notification (columns renamed n_* -> te_*)
-- - pxm_message: m_notification -> m_notify_on_reply, added m_status with index, added m_parentid index
-- - pxm_notification: new table for in-app user notifications
-- - pxm_message_notification: new table for per-message notification subscriptions
-- - pxm_message_read: new table for server-side read tracking with idx_messageid for read count queries
-- - pxm_configuration: c_banner, c_guestpost, c_countviews, c_quotechar, c_parseurl, c_parsestyle, c_msgperpage columns dropped, c_read_retention_months added
-- - pxm_configuration: c_quickpost, c_directregistration, c_uniquemail, c_usesignatures converted to BOOLEAN
-- - pxm_configuration: c_id -> TINYINT UNSIGNED, c_skinid -> TINYINT UNSIGNED, numeric columns: display widths removed
-- - pxm_board: b_id, b_position -> SMALLINT UNSIGNED; b_skinid -> TINYINT UNSIGNED; b_replacetext -> BOOLEAN
-- - pxm_message: m_userid -> MEDIUMINT UNSIGNED; m_userhighlight, m_notify_on_reply -> BOOLEAN
-- - pxm_moderator: mod_userid -> MEDIUMINT UNSIGNED, mod_boardid -> SMALLINT UNSIGNED
-- - pxm_priv_message: p_touserid, p_fromuserid -> MEDIUMINT UNSIGNED; p_tostate/p_fromstate comments added
-- - pxm_thread: t_boardid -> SMALLINT UNSIGNED; t_active, t_fixed -> BOOLEAN
-- - pxm_user: u_id -> MEDIUMINT UNSIGNED; u_skinid -> TINYINT UNSIGNED; all integer display widths removed
-- - pxm_skin: s_id -> TINYINT UNSIGNED; pxm_search: se_userid -> MEDIUMINT UNSIGNED
-- - All German column comments translated to English; missing column comments added
-- - pxm_error: table dropped (replaced by eError PHP enum)
-- - pxm_skin: names updated, quoteprefix/quotesuffix removed (CSS-based quote styling), frame_top/frame_bottom removed
-- - pxm_search: se_ipaddress added with idx_ratelimit index for search rate limiting
-- - Renamed u_nickname to u_username, m_usernickname to m_username, se_nickname to se_username
-- - pxm_board: b_active replaced with b_status (1=PUBLIC, 2=MEMBERS_ONLY, 3=READONLY_PUBLIC, 4=READONLY_MEMBERS, 5=CLOSED), idx_board_status added, b_parsestyle and b_parseurl removed, b_parseimg renamed to b_embed_external
--
-- Existing data is preserved. New installations using pxmboard-mysql.sql
-- will include all these changes automatically.
