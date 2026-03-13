-- PXMBoard E2E Test Seed
-- This file is imported by tests/E2E/fixtures/reset-db.js before every test run.
-- It establishes a clean, deterministic starting state for all E2E tests.
--
-- Test credentials:
--   Admin :  username=Webmaster  password=test1234
--   User  :  username=Tester     password=test5678

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Schema (kept in sync with install/sql/pxmboard-mysql.sql)
-- ============================================================

DROP TABLE IF EXISTS `pxm_user_login_ticket`;
DROP TABLE IF EXISTS `pxm_notification`;
DROP TABLE IF EXISTS `pxm_message_read_partition`;
DROP TABLE IF EXISTS `pxm_message_read`;
DROP TABLE IF EXISTS `pxm_message_notification`;
DROP TABLE IF EXISTS `pxm_message`;
DROP TABLE IF EXISTS `pxm_thread`;
DROP TABLE IF EXISTS `pxm_moderator`;
DROP TABLE IF EXISTS `pxm_priv_message`;
DROP TABLE IF EXISTS `pxm_search`;
DROP TABLE IF EXISTS `pxm_user`;
DROP TABLE IF EXISTS `pxm_board`;
DROP TABLE IF EXISTS `pxm_configuration`;
DROP TABLE IF EXISTS `pxm_skin`;
DROP TABLE IF EXISTS `pxm_template`;
DROP TABLE IF EXISTS `pxm_textreplacement`;
DROP TABLE IF EXISTS `pxm_badword`;
DROP TABLE IF EXISTS `pxm_forbiddenmail`;
DROP TABLE IF EXISTS `pxm_profile_accept`;

CREATE TABLE `pxm_badword` (
  `bw_name` char(20) NOT NULL default '',
  `bw_replacement` char(20) NOT NULL default '',
  PRIMARY KEY  (`bw_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_board` (
  `b_id`            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Board ID',
  `b_name`          VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Board display name',
  `b_description`   VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Board description',
  `b_position`      SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Display position for sorting boards',
  `b_status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=PUBLIC, 2=MEMBERS_ONLY, 3=READONLY_PUBLIC, 4=READONLY_MEMBERS, 5=CLOSED',
  `b_lastmsgtstmp`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of the last message',
  `b_skinid`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Board-specific skin ID (FK: pxm_skin)',
  `b_timespan`      SMALLINT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Days back for thread list (0=show all)',
  `b_threadlistsort` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Default thread list sort order',
  `b_embed_external` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Allow embedding external content (images, YouTube, Twitch)',
  `b_replacetext`   BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Enable text replacement (emoticons etc.)',
  PRIMARY KEY  (`b_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_configuration` (
  `c_id`                  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Configuration ID (always 1)',
  `c_quickpost`           BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Enable quick reply form on thread page',
  `c_directregistration`  BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Allow direct registration without admin approval',
  `c_uniquemail`          BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Require unique email address per user',
  `c_dateformat`          VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Date format string (PHP date() style)',
  `c_timeoffset`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Global time offset in hours',
  `c_onlinetime`          SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Duration to show users as online in seconds (0=disabled)',
  `c_closethreads`        SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum messages per thread before closing (0=no limit)',
  `c_usrperpage`          MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Users per page (online list, user search, admin)',
  `c_msgheaderperpage`    MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Message headers per page (search results)',
  `c_privatemsgperpage`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Private messages per page',
  `c_thrdperpage`         MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Threads per page',
  `c_mailwebmaster`       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Webmaster email address',
  `c_maxprofilepicsize`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image file size in bytes',
  `c_maxprofilepicwidth`  SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image width in pixels',
  `c_maxprofilepicheight` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum profile image height in pixels',
  `c_profileimgdir`       VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Directory for profile images',
  `c_usesignatures`       BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Enable user signatures globally',
  `c_skinid`              TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Default skin ID (FK: pxm_skin)',
  `c_quotesubject`        VARCHAR(10) NOT NULL DEFAULT 'Re:' COMMENT 'Prefix for quoted message subjects',
  `c_skindir`             VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Base directory for skins',
  `c_read_retention_months` TINYINT UNSIGNED NOT NULL DEFAULT 13 COMMENT 'Read tracking retention in months',
  PRIMARY KEY  (`c_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_forbiddenmail` (
  `fm_adress` char(100) NOT NULL default '',
  PRIMARY KEY  (`fm_adress`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_message` (
  `m_id`              INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Message ID',
  `m_threadid`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent thread ID',
  `m_parentid`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent message ID (0=root message)',
  `m_userid`          MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Author user ID (0=guest)',
  `m_username`        VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Author username at time of posting',
  `m_usermail`        VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Author email at time of posting',
  `m_userhighlight`   BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Highlight this users messages',
  `m_subject`         VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Message subject',
  `m_body`            MEDIUMTEXT NOT NULL COMMENT 'Message body (PXM markup)',
  `m_tstmp`           INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of posting',
  `m_ip`              VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Poster IP address',
  `m_notify_on_reply` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Send email notification on reply',
  `m_status`          TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '0=draft, 1=published, 2=archived, 3=deleted',
  PRIMARY KEY  (`m_id`),
  KEY `m_tstmp` (`m_tstmp`),
  KEY `m_thread` (`m_threadid`,`m_tstmp`),
  KEY `m_thread_parent` (`m_threadid`,`m_parentid`),
  KEY `m_parentid` (`m_parentid`),
  KEY `m_username` (`m_username`,`m_tstmp`),
  KEY `m_status` (`m_status`),
  FULLTEXT KEY `m_search` (`m_subject`,`m_body`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `pxm_message_read_partition` (
  `mrp_year_month`        SMALLINT UNSIGNED NOT NULL COMMENT 'YYMM, managed partition month',
  `mrp_created_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when partition was created',
  PRIMARY KEY (`mrp_year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks managed months; INSERT IGNORE as concurrency gate avoids INFORMATION_SCHEMA queries.';

CREATE TABLE `pxm_message_notification` (
  `mn_messageid` INT UNSIGNED NOT NULL COMMENT 'Message ID being watched',
  `mn_userid`    MEDIUMINT UNSIGNED NOT NULL COMMENT 'Subscriber user ID',
  PRIMARY KEY (`mn_messageid`,`mn_userid`),
  KEY `mn_messageid` (`mn_messageid`),
  KEY `mn_userid` (`mn_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-message notification subscriptions. Unsubscribing deletes the entry (no soft-delete)';

CREATE TABLE `pxm_moderator` (
  `mod_userid`  MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Moderator user ID',
  `mod_boardid` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Board ID',
  PRIMARY KEY  (`mod_userid`,`mod_boardid`),
  KEY `mod_boardid` (`mod_boardid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Moderator-board assignments';

CREATE TABLE `pxm_template` (
  `te_id`          MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Template ID',
  `te_message`     TEXT NOT NULL COMMENT 'Template text (supports %placeholder% substitution)',
  `te_name`        VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Internal template name',
  `te_description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Template description and placeholder reference',
  PRIMARY KEY  (`te_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Text templates for emails and application messages';

CREATE TABLE `pxm_priv_message` (
  `p_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Private message ID',
  `p_touserid`   MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Recipient user ID',
  `p_fromuserid` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sender user ID',
  `p_subject`    VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Message subject',
  `p_body`       MEDIUMTEXT NOT NULL COMMENT 'Message body (PXM markup)',
  `p_tstmp`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of sending',
  `p_ip`         VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Sender IP address',
  `p_tostate`    TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=UNREAD, 2=READ, 3=DELETED (recipient view)',
  `p_fromstate`  TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1=UNREAD, 2=READ, 3=DELETED (sender view)',
  PRIMARY KEY  (`p_id`),
  KEY `p_tstmp` (`p_tstmp`),
  KEY `p_inbox`  (`p_fromuserid`, `p_touserid`, `p_tostate`, `p_tstmp`),
  KEY `p_outbox` (`p_touserid`, `p_fromuserid`, `p_fromstate`, `p_tstmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_profile_accept` (
  `pa_name`   CHAR(15) NOT NULL DEFAULT '' COMMENT 'Profile field name',
  `pa_type`   ENUM('s','a','i') NOT NULL DEFAULT 's' COMMENT 'Field type: s=string, a=alpha, i=integer',
  `pa_length` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Maximum field length',
  PRIMARY KEY  (`pa_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Accepted user profile fields';

CREATE TABLE `pxm_search` (
  `se_id`        INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Search query ID',
  `se_userid`    MEDIUMINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'User ID who performed the search (0=guest)',
  `se_message`   VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Search term',
  `se_username`  VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Username filter',
  `se_days`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Days back filter (0=all)',
  `se_boardids`  VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Board ID filter (comma-separated)',
  `se_tstmp`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of search',
  `se_ipaddress` VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Searcher IP address',
  PRIMARY KEY (`se_id`),
  KEY `idx_tstmp` (`se_tstmp`),
  KEY `idx_ratelimit` (`se_ipaddress`, `se_tstmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Saved search queries for result caching and rate limiting';

CREATE TABLE `pxm_skin` (
  `s_id`         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Skin ID',
  `s_fieldname`  VARCHAR(15) NOT NULL DEFAULT '' COMMENT 'Skin configuration field name',
  `s_fieldvalue` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Skin configuration field value',
  PRIMARY KEY  (`s_id`,`s_fieldname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Skin configuration key-value pairs';

CREATE TABLE `pxm_textreplacement` (
  `tr_name` char(20) NOT NULL default '',
  `tr_replacement` char(255) NOT NULL default '',
  PRIMARY KEY  (`tr_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_thread` (
  `t_id`           INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Thread ID',
  `t_boardid`      SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Parent board ID',
  `t_active`       BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether the thread is visible/active',
  `t_fixed`        BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether the thread is pinned to top',
  `t_lastmsgtstmp` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of the last message',
  `t_lastmsgid`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID of the last message',
  `t_msgquantity`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total message count in this thread',
  `t_views`        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total view count',
  PRIMARY KEY  (`t_id`),
  KEY `threadlist_lastmsgtstmp` (`t_boardid`, `t_fixed`, `t_lastmsgtstmp`),
  KEY `t_lastmsgtstmp` (`t_lastmsgtstmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_user` (
  `u_id`                        MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'User ID',
  `u_username`                  VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Unique username',
  `u_password`                  VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'bcrypt password hash',
  `u_passwordkey`               CHAR(32) NULL DEFAULT NULL COMMENT 'Password recovery key (hex-encoded 16 random bytes)',
  `u_firstname`                 VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'First name',
  `u_lastname`                  VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'Last name',
  `u_city`                      VARCHAR(30) NOT NULL DEFAULT '' COMMENT 'City',
  `u_publicmail`                VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Publicly displayed email',
  `u_privatemail`               VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Private contact email',
  `u_registrationmail`          VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Email used at registration',
  `u_registrationtstmp`         INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of registration',
  `u_msgquantity`               INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total number of messages posted',
  `u_lastonlinetstmp`           INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of last online visit',
  `u_profilechangedtstmp`       INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Unix timestamp of last profile change',
  `u_imgfile`                   VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Profile image filename',
  `u_signature`                 VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'User signature text',
  `u_profile_url`               VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Custom profile field: URL',
  `u_profile_hobby`             VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Custom profile field: hobby',
  `u_highlight`                 BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Highlight this user\'s posts',
  `u_status`                    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '1=ACTIVE, 2=NOT_ACTIVATED, 3=DISABLED',
  `u_post`                      BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Permission to post messages',
  `u_edit`                      BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Permission to edit own messages',
  `u_admin`                     BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Administrator flag',
  `u_visible`                   BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Visible in online list',
  `u_skinid`                    TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'User-selected skin ID (FK: pxm_skin)',
  `u_threadlistsort`            VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'User\'s preferred thread list sort order',
  `u_timeoffset`                SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Time offset in hours',
  `u_embed_external`            BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Allow embedding external content (images, YouTube, Twitch)',
  `u_privatenotification`       BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Email notification for private messages',
  `u_notification_unread_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread in-app notifications',
  `u_priv_message_unread_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cached count of unread private messages',
  PRIMARY KEY  (`u_id`),
  UNIQUE KEY `u_username` (`u_username`),
  UNIQUE KEY `u_passwordkey` (`u_passwordkey`),
  KEY `u_lastonlinetstmp` (`u_lastonlinetstmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_user_login_ticket` (
  `ult_id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Login ticket ID',
  `ult_userid`              MEDIUMINT UNSIGNED NOT NULL COMMENT 'User ID (FK: pxm_user)',
  `ult_token`               CHAR(32) NOT NULL COMMENT 'Secure random login token',
  `ult_useragent`           VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Browser User-Agent string',
  `ult_ipaddress`           VARCHAR(45) NOT NULL DEFAULT '' COMMENT 'Client IP address',
  `ult_created_timestamp`   INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when ticket was created',
  `ult_last_used_timestamp` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of last use',
  PRIMARY KEY  (`ult_id`),
  UNIQUE KEY `ult_token` (`ult_token`),
  KEY `idx_userid` (`ult_userid`),
  KEY `idx_last_used` (`ult_last_used_timestamp`),
  CONSTRAINT `fk_login_ticket_user` FOREIGN KEY (`ult_userid`) REFERENCES `pxm_user` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pxm_notification` (
  `n_id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Notification ID',
  `n_userid`             MEDIUMINT UNSIGNED NOT NULL COMMENT 'Recipient user ID (FK: pxm_user)',
  `n_type`               VARCHAR(50) NOT NULL COMMENT 'Notification type (eNotificationType)',
  `n_status`             ENUM('unread','read') NOT NULL DEFAULT 'unread' COMMENT 'Read status',
  `n_title`              VARCHAR(255) NOT NULL COMMENT 'Notification title',
  `n_message`            TEXT NOT NULL COMMENT 'Notification message text',
  `n_link`               VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Optional URL link',
  `n_related_messageid`  INT UNSIGNED DEFAULT NULL COMMENT 'Related board message ID',
  `n_related_pmid`       INT UNSIGNED DEFAULT NULL COMMENT 'Related private message ID',
  `n_created_timestamp`  INT UNSIGNED NOT NULL COMMENT 'Unix timestamp when notification was created',
  `n_read_timestamp`     INT UNSIGNED DEFAULT NULL COMMENT 'Unix timestamp when notification was read',
  PRIMARY KEY  (`n_id`),
  KEY `idx_userid_status` (`n_userid`,`n_status`),
  KEY `idx_created` (`n_created_timestamp`),
  KEY `idx_type` (`n_type`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`n_userid`) REFERENCES `pxm_user` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Static data (identical to install/sql/pxmboard-mysql.sql)
-- ============================================================

INSERT INTO `pxm_badword` (`bw_name`, `bw_replacement`) VALUES ('fuck', '****');

INSERT INTO `pxm_configuration` (`c_id`, `c_quickpost`, `c_directregistration`, `c_uniquemail`, `c_dateformat`, `c_timeoffset`, `c_onlinetime`, `c_closethreads`, `c_usrperpage`, `c_msgheaderperpage`, `c_privatemsgperpage`, `c_thrdperpage`, `c_mailwebmaster`, `c_maxprofilepicsize`, `c_maxprofilepicwidth`, `c_maxprofilepicheight`, `c_profileimgdir`, `c_usesignatures`, `c_skinid`, `c_quotesubject`, `c_skindir`, `c_read_retention_months`) VALUES (1, 1, 1, 1, 'j.m.Y H:i', 0, 300, 0, 10, 50, 10, 20, 'webmaster@example.com', 50000, 200, 250, 'images/profile/', 1, 1, 'Re:', 'skins/', 13);

INSERT INTO `pxm_template` (`te_id`, `te_message`, `te_name`, `te_description`) VALUES
(1, 'PXMBoard Registrierung', 'registration mail subject', 'subject of the registration mail'),
(2, 'Sie wurden registriert.\nIhr Nickname lautet: %nickname%\nIhr Passwort lautet: %password%', 'registration mail body', 'body of the registration mail\navailable placeholders: %password%,%nickname%'),
(3, 'PXMBoard Registrierung', 'registration declined mail subject', 'subject of the registration declined mail'),
(4, 'Sie wurden nich registriert.\nGrund: %reason%', 'registration declined mail body', 'body of the registration declined mail\navailable placeholders: %nickname%,%reason%'),
(5, 'Doppelanmeldungen sind unzulässig!', 'registration declined reason', 'default reason for a declined registration'),
(6, 'Anforderung eines neuen Passwortes', 'password request mail subject', 'subject of the password request mail'),
(7, 'Rufen sie folgenden Link auf wenn sie ein neues Passwort benötigen http://localhost/pxmboard/pxmboard.php?mode=usersendpwd&key=%key%', 'password request mail body', 'body of the password request mail\navailable placeholders: %nickname%, %key%'),
(8, 'PXMBoard', 'lost password mail subject', 'subject of the lost password mail'),
(9, 'Ihr Passwort lautet %password%', 'lost password mail body', 'body of the lost password mail\navailable placeholders: %nickname%, %password%'),
(10, 'Wurde editiert', 'edit note', 'edit note for messages\navailable placeholders: %nickname%, %date%'),
(11, 'PXMBoard private Nachricht', 'private message mail subject', 'subject of the new private message notification'),
(12, 'Sie habe eine neue private Nachricht erhalten', 'private message mail body', 'body of the new private message notification\navailable placeholders: %nickname%'),
(13, 'PXMBoard neue Antwort', 'reply notification mail subject', 'subject of the reply notification'),
(14, 'Der Nutzer %nickname% hat auf ihren Beitrag %subject% geantwortet.\npxmboard.php?mode=board&brdid=%boardid%&thrdid=%threadid%&msgid=%replyid%', 'reply notification mail body', 'body of the reply notification\navailable placeholders: %nickname%,%subject%,%id%,%replysubject%,%replyid%,%boardid%,%threadid%');

INSERT INTO `pxm_profile_accept` (`pa_name`, `pa_type`, `pa_length`) VALUES ('url', 's', 100), ('hobby', 's', 100);

INSERT INTO `pxm_skin` (`s_id`, `s_fieldname`, `s_fieldvalue`) VALUES
(1, 'name', 'PXM Skin'),
(1, 'dir', 'pxm'),
(1, 'type', 'Smarty');

INSERT INTO `pxm_textreplacement` (`tr_name`, `tr_replacement`) VALUES (':-)', '<img src="images/smiley.gif"/>');

-- ============================================================
-- Seed data for E2E tests
-- ============================================================

-- Users
-- Admin:      username=Webmaster   password=test1234
-- User:       username=Tester      password=test5678
-- ReadTester: username=ReadTester  password=read5678
--
-- ReadTester is a dedicated user for spec 08 (read-tracking tests).
-- No other spec logs in as ReadTester, so u_lastonlinetstmp is never updated
-- by other tests.  This guarantees a stable last-login context:
--
--   u_lastonlinetstmp = UNIX_TIMESTAMP() - 86400  (yesterday / 24 h ago)
--
-- At login time pxmboard.php freezes this value as last_login_tstmp in the
-- session (getLastOnlineTimestamp() returns the in-memory value loaded from
-- the DB before updateLastOnlineTimestamp() is called, so the session always
-- sees the seed value, not the freshly-written one).
--
-- Message timestamps in the seed:
--   m_id=1,2,3: UNIX_TIMESTAMP() - 172800  (2 days ago) → is_new = 0
--   m_id=5:     UNIX_TIMESTAMP()            (now / seed-load time) → is_new = 1
INSERT INTO `pxm_user`
  (`u_id`, `u_username`, `u_password`, `u_passwordkey`, `u_privatemail`,
   `u_registrationmail`, `u_registrationtstmp`, `u_lastonlinetstmp`,
   `u_status`, `u_admin`, `u_post`, `u_edit`, `u_visible`, `u_skinid`)
VALUES
  (1, 'Webmaster',
   '$2y$12$YBhtXvFqa1E/JqFmbtQFROWcsqvNRVQ3vJvO.TQH6/.Z8ztQotUDG',
   'e2e0000000000000000000000000001',
   'webmaster@example.com', 'webmaster@example.com',
   UNIX_TIMESTAMP(), 0, 1, TRUE, TRUE, TRUE, TRUE, 1),
  (2, 'Tester',
   '$2y$12$.vdRfkVtESu7aFw7F7CpI.YPmcqTNct0pvoHWwBu.sYW7/FPQkm.a',
   'e2e0000000000000000000000000002',
   'tester@example.com', 'tester@example.com',
   UNIX_TIMESTAMP(), UNIX_TIMESTAMP() - 1800,
   1, FALSE, TRUE, TRUE, TRUE, 1),
  (3, 'ReadTester',
   '$2y$12$QJfgMpk72sztSIHC4lPYWO8QbxEOoWxfrq.jmE1TxIlkQrMe/tqMq',
   'e2e0000000000000000000000000003',
   'readtester@example.com', 'readtester@example.com',
   UNIX_TIMESTAMP(), UNIX_TIMESTAMP() - 86400,
   1, FALSE, TRUE, TRUE, TRUE, 1);

-- Boards
INSERT INTO `pxm_board`
  (`b_id`, `b_name`, `b_description`, `b_position`, `b_status`, `b_skinid`)
VALUES
  (1, 'Test', 'E2E-Test-Board', 1, 1, 1),
  (2, 'Test2', 'Zweites E2E-Test-Board', 2, 1, 1);

-- Thread + 4 messages in board 1
-- Root message: m_id=1, t_id=1 (root message shares ID with thread)
--
-- Timestamps chosen relative to ReadTester's u_lastonlinetstmp (yesterday):
--   m_id=1,2,3: 2 days ago → is_new=0 for ReadTester
--   m_id=5:     UNIX_TIMESTAMP() + 86400 (tomorrow) → always is_new=1 for ReadTester
--               Using a future timestamp guarantees is_new=1 regardless of how many
--               times updateLastOnlineTimestamp() runs within this test suite run.
INSERT INTO `pxm_thread` (`t_id`, `t_boardid`, `t_active`, `t_fixed`, `t_lastmsgtstmp`, `t_lastmsgid`, `t_msgquantity`, `t_views`)
VALUES (1, 1, 1, 0, UNIX_TIMESTAMP() - 172800, 3, 3, 42);

INSERT INTO `pxm_message` (`m_id`, `m_threadid`, `m_parentid`, `m_userid`, `m_username`, `m_subject`, `m_body`, `m_tstmp`, `m_ip`, `m_status`)
VALUES
  (1, 1, 0, 1, 'Webmaster', 'E2E Testthread', 'Dies ist der Startbeitrag fuer die E2E-Tests.', UNIX_TIMESTAMP() - 172800, '127.0.0.1', 1),
  (2, 1, 1, 2, 'Tester', 'Re: E2E Testthread', 'Antwort vom Tester-Account.', UNIX_TIMESTAMP() - 172700, '127.0.0.1', 1),
  (3, 1, 1, 1, 'Webmaster', 'Re: E2E Testthread', 'Zweite Antwort vom Webmaster.', UNIX_TIMESTAMP() - 172600, '127.0.0.1', 1),
  -- m_id=5: posted TOMORROW (seed-load time + 24h) – always AFTER any login timestamp
  --          that updateLastOnlineTimestamp() could write during this spec run → is_new=1
  (5, 1, 2, 1, 'Webmaster', 'Neue Antwortnachricht', 'Diese Nachricht wurde nach dem letzten Login von ReadTester erstellt.', UNIX_TIMESTAMP() + 86400, '127.0.0.1', 1);

-- Update board lastmsgtstmp (matches m_id=5's future timestamp so lastnew is computed correctly)
UPDATE `pxm_board` SET `b_lastmsgtstmp` = UNIX_TIMESTAMP() + 86400 WHERE `b_id` = 1;

-- Update thread 1 stats to reflect the new message (m_id=5)
-- t_lastmsgtstmp = tomorrow so lastnew=1 for ReadTester even after updateLastOnlineTimestamp() runs
UPDATE `pxm_thread` SET `t_lastmsgtstmp` = UNIX_TIMESTAMP() + 86400, `t_lastmsgid` = 5, `t_msgquantity` = 4 WHERE `t_id` = 1;

-- Thread 2: pinned thread for navigation tests
INSERT INTO `pxm_thread` (`t_id`, `t_boardid`, `t_active`, `t_fixed`, `t_lastmsgtstmp`, `t_lastmsgid`, `t_msgquantity`, `t_views`)
VALUES (4, 1, 1, 1, UNIX_TIMESTAMP() - 100, 4, 1, 5);

INSERT INTO `pxm_message` (`m_id`, `m_threadid`, `m_parentid`, `m_userid`, `m_username`, `m_subject`, `m_body`, `m_tstmp`, `m_ip`, `m_status`)
VALUES (4, 4, 0, 1, 'Webmaster', 'Angepinnter Thread', 'Dieser Thread ist angepinnt.', UNIX_TIMESTAMP() - 100, '127.0.0.1', 1);

-- Private message from Webmaster to Tester
INSERT INTO `pxm_priv_message`
  (`p_id`, `p_touserid`, `p_fromuserid`, `p_subject`, `p_body`, `p_tstmp`, `p_ip`, `p_tostate`, `p_fromstate`)
VALUES
  (1, 2, 1, 'Willkommen beim E2E-Test', 'Hallo Tester, das ist eine Testnachricht.', UNIX_TIMESTAMP() - 3600, '127.0.0.1', 1, 2);

-- Update Tester's unread PM count
UPDATE `pxm_user` SET `u_priv_message_unread_count` = 1 WHERE `u_id` = 2;
