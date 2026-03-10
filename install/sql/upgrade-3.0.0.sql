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
  CHANGE COLUMN `n_id` `te_id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  CHANGE COLUMN `n_message` `te_message` TEXT NOT NULL,
  CHANGE COLUMN `n_name` `te_name` VARCHAR(50) NOT NULL DEFAULT '',
  CHANGE COLUMN `n_description` `te_description` VARCHAR(255) NOT NULL DEFAULT '';

-- Step 3: Update table comment to clarify purpose
ALTER TABLE `pxm_template`
  COMMENT='Text templates for emails and application messages';

-- Step 4: Rename column in pxm_message (m_notification -> m_notify_on_reply)
ALTER TABLE `pxm_message`
  CHANGE COLUMN `m_notification` `m_notify_on_reply` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0;

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
  `n_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `n_userid` INT(10) UNSIGNED NOT NULL,
  `n_type` VARCHAR(50) NOT NULL,
  `n_status` ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
  `n_title` VARCHAR(255) NOT NULL,
  `n_message` TEXT NOT NULL,
  `n_link` VARCHAR(255) NOT NULL DEFAULT '',
  `n_related_messageid` INT(10) UNSIGNED NULL DEFAULT NULL,
  `n_related_pmid` INT(10) UNSIGNED NULL DEFAULT NULL,
  `n_created_timestamp` INT(10) UNSIGNED NOT NULL,
  `n_read_timestamp` INT(10) UNSIGNED NULL DEFAULT NULL,
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
  ADD COLUMN `u_notification_unread_count` INT(10) UNSIGNED NOT NULL DEFAULT 0;

-- Add unread private message count cache to pxm_user table
ALTER TABLE `pxm_user`
  ADD COLUMN `u_priv_message_unread_count` INT(10) UNSIGNED NOT NULL DEFAULT 0;

-- ============================================================================
-- MESSAGE READ TRACKING: Server-side read status
-- ============================================================================
-- Tracks read messages per user for cross-device synchronization
--
-- This table stores which messages each logged-in user has viewed.
-- Guests use browser-side tracking, logged-in users use this table.
-- No foreign keys for performance on large forums.
--
-- INDEX STRATEGY:
-- - PRIMARY KEY (mr_userid, mr_messageid): Optimal for LEFT JOIN queries in cThreadList
--   WHERE mr_userid=? AND mr_messageid=? (covers both columns efficiently)
-- - idx_user_timestamp: For cleanup operations (delete old entries per user)

CREATE TABLE IF NOT EXISTS `pxm_message_read` (
  `mr_userid` INT(10) UNSIGNED NOT NULL,
  `mr_messageid` INT(10) UNSIGNED NOT NULL,
  `mr_timestamp` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`mr_userid`, `mr_messageid`),
  INDEX `idx_user_timestamp` (`mr_userid`, `mr_timestamp`),
  INDEX `idx_messageid` (`mr_messageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks read messages per user (no foreign keys for performance). PRIMARY KEY optimal for LEFT JOIN queries.';

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
  `ult_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ult_userid` INT(10) UNSIGNED NOT NULL,
  `ult_token` CHAR(32) NOT NULL COMMENT '32 chars hex from bin2hex(random_bytes(16))',
  `ult_useragent` VARCHAR(255) NOT NULL DEFAULT '',
  `ult_ipaddress` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'IPv4 or IPv6 address',
  `ult_created_timestamp` INT(10) UNSIGNED NOT NULL,
  `ult_last_used_timestamp` INT(10) UNSIGNED NOT NULL,
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
  `mn_messageid` int unsigned NOT NULL,
  `mn_userid` int unsigned NOT NULL,
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
ALTER TABLE `pxm_user` MODIFY COLUMN `u_status` tinyint(3) unsigned NOT NULL default '0' COMMENT '1=ACTIVE, 2=NOT_ACTIVATED, 3=DISABLED';

-- ============================================================================
-- CONVERT BOOLEAN-LIKE COLUMNS TO BOOLEAN
-- ============================================================================
-- Convert tinyint(3) unsigned columns that represent boolean values
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
  CHANGE COLUMN `b_active` `b_status` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1
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
    COMMENT 'Einbettung externer Inhalte (Bilder, YouTube, Twitch)';

ALTER TABLE `pxm_configuration`
  DROP COLUMN IF EXISTS `c_parseurl`,
  DROP COLUMN IF EXISTS `c_parsestyle`;

ALTER TABLE `pxm_user`
  DROP COLUMN IF EXISTS `u_replacetext`,
  DROP COLUMN IF EXISTS `u_showsignatures`,
  CHANGE COLUMN `u_parseimg` `u_embed_external` BOOLEAN NOT NULL DEFAULT FALSE
    COMMENT 'Einbettung externer Inhalte (Bilder, YouTube, Twitch)';


-- ============================================================================
-- SCHEMA CLEANUP: Remove flat-mode messages-per-page configuration
-- ============================================================================
-- c_msgperpage was used for flat-mode message display which is no longer
-- supported. The setting is replaced by c_msgheaderperpage for all list views.

ALTER TABLE `pxm_configuration`
  DROP COLUMN IF EXISTS `c_msgperpage`;


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
-- - pxm_configuration: c_banner, c_guestpost, c_countviews, c_quotechar, c_parseurl, c_parsestyle, c_msgperpage columns dropped
-- - pxm_error: table dropped (replaced by eError PHP enum)
-- - pxm_skin: names updated, quoteprefix/quotesuffix removed (CSS-based quote styling), frame_top/frame_bottom removed
-- - pxm_search: se_ipaddress added with idx_ratelimit index for search rate limiting
-- - Renamed u_nickname to u_username, m_usernickname to m_username, se_nickname to se_username
-- - pxm_board: b_active replaced with b_status (1=PUBLIC, 2=MEMBERS_ONLY, 3=READONLY_PUBLIC, 4=READONLY_MEMBERS, 5=CLOSED), idx_board_status added, b_parsestyle and b_parseurl removed, b_parseimg renamed to b_embed_external
--
-- Existing data is preserved. New installations using pxmboard-mysql.sql
-- will include all these changes automatically.
