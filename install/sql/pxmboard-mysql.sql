CREATE TABLE `pxm_badword` (
  `bw_name` char(20) NOT NULL default '',
  `bw_replacement` char(20) NOT NULL default '',
  PRIMARY KEY  (`bw_name`)
) ENGINE=InnoDB;

INSERT INTO `pxm_badword` (`bw_name`, `bw_replacement`) VALUES ('fuck', '****');
# --------------------------------------------------------

CREATE TABLE `pxm_board` (
  `b_id` int(10) unsigned NOT NULL auto_increment,
  `b_name` varchar(100) NOT NULL default '',
  `b_description` varchar(255) NOT NULL default '',
  `b_position` int(10) unsigned NOT NULL default '0',
  `b_status` tinyint(3) unsigned NOT NULL default '1' COMMENT '1=PUBLIC, 2=MEMBERS_ONLY, 3=READONLY_PUBLIC, 4=READONLY_MEMBERS, 5=CLOSED',
  `b_lastmsgtstmp` int(10) unsigned NOT NULL default '0',
  `b_skinid` smallint(5) unsigned NOT NULL default '1',
  `b_timespan` smallint(5) unsigned NOT NULL default '100',
  `b_threadlistsort` varchar(20) NOT NULL default '',
  `b_embed_external` BOOLEAN NOT NULL default TRUE COMMENT 'Einbettung externer Inhalte (Bilder, YouTube, Twitch)',
  `b_replacetext` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`b_id`)
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_configuration` (
  `c_id` int(10) unsigned NOT NULL auto_increment,
  `c_quickpost` tinyint(3) unsigned NOT NULL default '0',
  `c_directregistration` tinyint(3) unsigned NOT NULL default '0',
  `c_uniquemail` tinyint(3) unsigned NOT NULL default '0',
  `c_dateformat` varchar(30) NOT NULL default '',
  `c_timeoffset` tinyint(3) unsigned NOT NULL default '0',
  `c_onlinetime` smallint(5) unsigned NOT NULL default '0',
  `c_closethreads` smallint(5) unsigned NOT NULL default '0',
  `c_usrperpage` mediumint(8) unsigned NOT NULL default '0',
  `c_msgperpage` mediumint(8) unsigned NOT NULL default '0',
  `c_msgheaderperpage` mediumint(8) unsigned NOT NULL default '0',
  `c_privatemsgperpage` mediumint(8) unsigned NOT NULL default '0',
  `c_thrdperpage` mediumint(8) unsigned NOT NULL default '0',
  `c_mailwebmaster` varchar(100) NOT NULL default '',
  `c_maxprofilepicsize` mediumint(8) unsigned NOT NULL default '0',
  `c_maxprofilepicwidth` smallint(5) unsigned NOT NULL default '0',
  `c_maxprofilepicheight` smallint(5) unsigned NOT NULL default '0',
  `c_profileimgdir` varchar(100) NOT NULL default '',
  `c_usesignatures` tinyint(3) unsigned NOT NULL default '0',
  `c_skinid` smallint(5) unsigned NOT NULL default '1',
  `c_quotesubject` varchar(10) NOT NULL default 'Re:',
  `c_skindir` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`c_id`)
) ENGINE=InnoDB;

INSERT INTO `pxm_configuration` (`c_id`, `c_quickpost`, `c_directregistration`, `c_uniquemail`, `c_dateformat`, `c_timeoffset`, `c_onlinetime`, `c_closethreads`, `c_usrperpage`, `c_msgperpage`, `c_msgheaderperpage`, `c_privatemsgperpage`, `c_thrdperpage`, `c_mailwebmaster`, `c_maxprofilepicsize`, `c_maxprofilepicwidth`, `c_maxprofilepicheight`, `c_profileimgdir`, `c_usesignatures`, `c_skinid`, `c_quotesubject`, `c_skindir`) VALUES (1, 1, 1, 1, 'j.m.Y H:i', 0, 300, 0, 10, 10, 50, 10, 20, '', 50000, 200, 250, 'images/profile/', 1, 1, 'Re:', 'skins/');
# --------------------------------------------------------

CREATE TABLE `pxm_forbiddenmail` (
  `fm_adress` char(100) NOT NULL default '',
  PRIMARY KEY  (`fm_adress`)
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_message` (
  `m_id` int(10) unsigned NOT NULL auto_increment,
  `m_threadid` int(10) unsigned NOT NULL default '0',
  `m_parentid` int(10) unsigned NOT NULL default '0',
  `m_userid` int(10) unsigned NOT NULL default '0',
  `m_username` varchar(30) NOT NULL default '',
  `m_usermail` varchar(100) NOT NULL default '',
  `m_userhighlight` tinyint(3) unsigned NOT NULL default '0',
  `m_subject` varchar(100) NOT NULL default '',
  `m_body` mediumtext NOT NULL,
  `m_tstmp` int(10) unsigned NOT NULL default '0',
  `m_ip` varchar(50) NOT NULL default '',
  `m_notify_on_reply` tinyint(3) unsigned NOT NULL default '0',
  `m_status` tinyint(3) unsigned NOT NULL default '1' COMMENT '0=draft, 1=published, 2=archived, 3=deleted',
  PRIMARY KEY  (`m_id`),
  KEY `m_tstmp` (`m_tstmp`),
  KEY `m_thread` (`m_threadid`,`m_tstmp`),
  KEY `m_thread_parent` (`m_threadid`,`m_parentid`),
  KEY `m_parentid` (`m_parentid`),
  KEY `m_username` (`m_username`,`m_tstmp`),
  KEY `m_status` (`m_status`),
  FULLTEXT KEY `m_search` (`m_subject`,`m_body`)
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_message_read` (
  `mr_userid` int(10) unsigned NOT NULL,
  `mr_messageid` int(10) unsigned NOT NULL,
  `mr_timestamp` int(10) unsigned NOT NULL,
  PRIMARY KEY (`mr_userid`,`mr_messageid`),
  KEY `idx_user_timestamp` (`mr_userid`,`mr_timestamp`),
  KEY `idx_messageid` (`mr_messageid`)
) ENGINE=InnoDB COMMENT='Tracks read messages per user (no foreign keys for performance). PRIMARY KEY is optimal for LEFT JOIN queries in cThreadList.';
# --------------------------------------------------------

CREATE TABLE `pxm_message_notification` (
  `mn_messageid` int(10) unsigned NOT NULL,
  `mn_userid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`mn_messageid`,`mn_userid`),
  KEY `mn_messageid` (`mn_messageid`),
  KEY `mn_userid` (`mn_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Per-message notification subscriptions. Unsubscribing deletes the entry (no soft-delete)';
# --------------------------------------------------------

CREATE TABLE `pxm_moderator` (
  `mod_userid` int(10) unsigned NOT NULL default '0',
  `mod_boardid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`mod_userid`,`mod_boardid`),
  KEY `mod_boardid` (`mod_boardid`)
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_template` (
  `te_id` mediumint(8) unsigned NOT NULL auto_increment,
  `te_message` text NOT NULL,
  `te_name` varchar(50) NOT NULL default '',
  `te_description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`te_id`)
) ENGINE=InnoDB COMMENT='Text templates for emails and application messages';

INSERT INTO `pxm_template` (`te_id`, `te_message`, `te_name`, `te_description`) VALUES (1, 'PXMBoard Registrierung', 'registration mail subject', 'subject of the registration mail'),
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
# --------------------------------------------------------

CREATE TABLE `pxm_priv_message` (
  `p_id` int(10) unsigned NOT NULL auto_increment,
  `p_touserid` int(10) unsigned NOT NULL default '0',
  `p_fromuserid` int(10) unsigned NOT NULL default '0',
  `p_subject` varchar(100) NOT NULL default '',
  `p_body` mediumtext NOT NULL,
  `p_tstmp` int(10) unsigned NOT NULL default '0',
  `p_ip` varchar(50) NOT NULL default '',
  `p_tostate` tinyint(3) unsigned NOT NULL default '1',
  `p_fromstate` tinyint(3) unsigned NOT NULL default '2',
  PRIMARY KEY  (`p_id`),
  KEY `p_tstmp` (`p_tstmp`),
  KEY `p_inbox` ( `p_fromuserid` , `p_touserid` , `p_tostate` , `p_tstmp` ),
  KEY `p_outbox` ( `p_touserid` , `p_fromuserid` , `p_fromstate` , `p_tstmp` )
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_profile_accept` (
  `pa_name` char(15) NOT NULL default '',
  `pa_type` enum('s','a','i') NOT NULL default 's',
  `pa_length` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pa_name`)
) ENGINE=InnoDB;

INSERT INTO `pxm_profile_accept` (`pa_name`, `pa_type`, `pa_length`) VALUES ('url', 's', 100),
('hobby', 's', 100);
# --------------------------------------------------------

CREATE TABLE `pxm_search` (
`se_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`se_userid` INT UNSIGNED NOT NULL ,
`se_message` VARCHAR( 255 ) NOT NULL ,
`se_username` VARCHAR( 30 ) NOT NULL ,
`se_days` INT UNSIGNED NOT NULL ,
`se_boardids` VARCHAR( 255 ) NOT NULL ,
`se_tstmp` INT UNSIGNED NOT NULL ,
`se_ipaddress` VARCHAR( 45 ) NOT NULL ,
PRIMARY KEY ( `se_id` ) ,
INDEX ( `se_tstmp` ) ,
INDEX `idx_ratelimit` ( `se_ipaddress`, `se_tstmp` )
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_skin` (
  `s_id` int(10) unsigned NOT NULL default '0',
  `s_fieldname` varchar(15) NOT NULL default '',
  `s_fieldvalue` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`s_id`,`s_fieldname`)
) ENGINE=InnoDB;

INSERT INTO `pxm_skin` (`s_id`, `s_fieldname`, `s_fieldvalue`) VALUES
(1, 'name', 'PXM Skin'),
(1, 'dir', 'pxm'),
(1, 'type', 'Smarty'),
(1, 'tgfx_lastc', '<img src="images/lc.gif" width="8" height="22" border="0"/>'),
(1, 'tgfx_midc', '<img src="images/mc.gif" width="8" height="22" border="0"/>'),
(1, 'tgfx_noc', '<img src="images/nc.gif" width="8" height="22" border="0"/>'),
(1, 'tgfx_empty', '<img src="images/empty.gif" width="8" height="22" border="0"/>');
# --------------------------------------------------------

CREATE TABLE `pxm_textreplacement` (
  `tr_name` char(20) NOT NULL default '',
  `tr_replacement` char(255) NOT NULL default '',
  PRIMARY KEY  (`tr_name`)
) ENGINE=InnoDB;

INSERT INTO `pxm_textreplacement` (`tr_name`, `tr_replacement`) VALUES (':-)', '<img src="images/smiley.gif"/>');
# --------------------------------------------------------

CREATE TABLE `pxm_thread` (
  `t_id` int(10) unsigned NOT NULL auto_increment,
  `t_boardid` int(10) unsigned NOT NULL default '0',
  `t_active` tinyint(3) unsigned NOT NULL default '0',
  `t_fixed` tinyint(3) unsigned NOT NULL default '0',
  `t_lastmsgtstmp` int(10) unsigned NOT NULL default '0',
  `t_lastmsgid` int(10) unsigned NOT NULL default '0',
  `t_msgquantity` int(10) unsigned NOT NULL default '0',
  `t_views` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`t_id`),
  KEY `threadlist_lastmsgtstmp` ( `t_boardid` , `t_fixed` , `t_lastmsgtstmp` ),
  KEY `t_lastmsgtstmp` (`t_lastmsgtstmp`)
) ENGINE=InnoDB;
# --------------------------------------------------------

CREATE TABLE `pxm_user` (
  `u_id` int(10) unsigned NOT NULL auto_increment,
  `u_username` varchar(30) NOT NULL default '',
  `u_password` varchar(255) NOT NULL default '',
  `u_passwordkey` char(32) NOT NULL default '',
  `u_firstname` varchar(30) NOT NULL default '',
  `u_lastname` varchar(30) NOT NULL default '',
  `u_city` varchar(30) NOT NULL default '',
  `u_publicmail` varchar(100) NOT NULL default '',
  `u_privatemail` varchar(100) NOT NULL default '',
  `u_registrationmail` varchar(100) NOT NULL default '',
  `u_registrationtstmp` int(10) unsigned NOT NULL default '0',
  `u_msgquantity` int(10) unsigned NOT NULL default '0',
  `u_lastonlinetstmp` int(10) unsigned NOT NULL default '0',
  `u_profilechangedtstmp` int(10) unsigned NOT NULL default '0',
  `u_imgfile` varchar(20) NOT NULL default '',
  `u_signature` varchar(100) NOT NULL default '',
  `u_profile_url` varchar(100) NOT NULL default '',
  `u_profile_hobby` varchar(100) NOT NULL default '',
  `u_highlight` BOOLEAN NOT NULL default FALSE,
  `u_status` tinyint(3) unsigned NOT NULL default '0' COMMENT '1=ACTIVE, 2=NOT_ACTIVATED, 3=DISABLED',
  `u_post` BOOLEAN NOT NULL default TRUE,
  `u_edit` BOOLEAN NOT NULL default TRUE,
  `u_admin` BOOLEAN NOT NULL default FALSE,
  `u_visible` BOOLEAN NOT NULL default TRUE,
  `u_skinid` smallint(5) unsigned NOT NULL default '1',
  `u_threadlistsort` varchar(20) NOT NULL default '',
  `u_timeoffset` smallint(5) unsigned NOT NULL default '0',
  `u_embed_external` BOOLEAN NOT NULL default FALSE COMMENT 'Einbettung externer Inhalte (Bilder, YouTube, Twitch)',
  `u_privatenotification` BOOLEAN NOT NULL default FALSE,
  `u_notification_unread_count` int(10) unsigned NOT NULL default '0',
  `u_priv_message_unread_count` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`u_id`),
  UNIQUE KEY `u_username` (`u_username`),
  UNIQUE KEY `u_passwordkey` (`u_passwordkey`),
  KEY `u_lastonlinetstmp` (`u_lastonlinetstmp`)
) ENGINE=InnoDB;

# --------------------------------------------------------

#
# Table structure for table `pxm_user_login_ticket`
#

CREATE TABLE `pxm_user_login_ticket` (
  `ult_id` int(10) unsigned NOT NULL auto_increment,
  `ult_userid` int(10) unsigned NOT NULL,
  `ult_token` varchar(32) NOT NULL,
  `ult_useragent` varchar(255) NOT NULL default '',
  `ult_ipaddress` varchar(45) NOT NULL default '',
  `ult_created_timestamp` int(10) unsigned NOT NULL,
  `ult_last_used_timestamp` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ult_id`),
  UNIQUE KEY `ult_token` (`ult_token`),
  KEY `idx_userid` (`ult_userid`),
  KEY `idx_last_used` (`ult_last_used_timestamp`),
  CONSTRAINT `fk_login_ticket_user` FOREIGN KEY (`ult_userid`) REFERENCES `pxm_user` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

# --------------------------------------------------------

#
# Table structure for table `pxm_notification`
#

CREATE TABLE `pxm_notification` (
  `n_id` int(10) unsigned NOT NULL auto_increment,
  `n_userid` int(10) unsigned NOT NULL,
  `n_type` varchar(50) NOT NULL,
  `n_status` enum('unread','read') NOT NULL default 'unread',
  `n_title` varchar(255) NOT NULL,
  `n_message` text NOT NULL,
  `n_link` varchar(255) NOT NULL default '',
  `n_related_userid` int(10) unsigned default NULL,
  `n_related_messageid` int(10) unsigned default NULL,
  `n_related_threadid` int(10) unsigned default NULL,
  `n_related_pmid` int(10) unsigned default NULL,
  `n_created_timestamp` int(10) unsigned NOT NULL,
  `n_read_timestamp` int(10) unsigned default NULL,
  PRIMARY KEY  (`n_id`),
  KEY `idx_userid_status` (`n_userid`,`n_status`),
  KEY `idx_created` (`n_created_timestamp`),
  KEY `idx_type` (`n_type`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`n_userid`) REFERENCES `pxm_user` (`u_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

