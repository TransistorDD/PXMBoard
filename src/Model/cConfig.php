<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDBFactory;

/**
 * configuration handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cConfig
{
    /** @var array<string> */
    protected array $m_arrAvailableTemplateEngines;			// available template engines
    protected string $m_sActiveTemplateEngine	= '';		// active template engine, depending on installed engines and skin configuration

    protected int $m_iAccessTimestamp;						// current timestamp

    protected int $m_iDefaultSkinId = 0;					// default skin id
    protected string $m_sSkinDir = 'skins/';				// skin directory
    protected bool $m_bUseQuickPost = false;				// activate quickpost?
    protected bool $m_bUseDirectRegistration = false;		// activate direct registratiom?
    protected bool $m_bUniqueRegistrationMails = false;	    // unique registration mail?
    protected bool $m_bUseSignatures = false;				// use usersignatures?
    protected string $m_sDateFormat = 'j.m.Y H:i';			// string for php date function
    protected int $m_iTimeOffset = 0;						// date & time offset in hours
    protected int $m_iOnlineTime = 300;						// time that a user will be visible in onlinelist in seconds

    protected int $m_iThreadSizeLimit = 500;				// close threads with at least x messages
    protected int $m_iUserPerPage = 20;						// display x user per page
    protected int $m_iMessageHeaderPerPage = 50;			// display x messages per page (search)
    protected int $m_iPrivateMessagesPerPage = 20;			// display x private messages per page
    protected int $m_iThreadsPerPage = 50;					// display x threads per page

    protected string $m_sQuoteSubject = 'Re:';				// prefix for quoted subjects
    protected string $m_sQuoteTag = 'blockquote';			// HTML tag for quoted text (blockquote)

    protected string $m_sMailWebmaster	= '';				// mail of webmaster

    protected int $m_iMaxProfileImgSize = 512000;			// size of profile images in bytes
    protected int $m_iMaxProfileImgWidth = 200;				// width of profile images
    protected int $m_iMaxProfileImgHeight = 400;			// height of profile images
    protected string $m_sProfileImgDir	= '';				// profile images directory
    protected int $m_iProfileImgSplitDir = 100;				// one directory for x profile images
    /** @var array<string> */
    protected array $m_arrProfileImgTypes = ['image/jpeg' => 'jpg','image/pjpeg' => 'jpg','image/gif' => 'gif','image/png' => 'png'];					// accepted filetypes for profile images

    /**
     * Constructor
     *
     * @param array<string> $arrTemplateEngines available template engine ordered by priority
     * @return void
     */
    public function __construct(array $arrTemplateEngines)
    {
        $this->m_arrAvailableTemplateEngines = $arrTemplateEngines;

        // defaults
        $this->m_iAccessTimestamp = time();

        // load general configuration from database
        $this->_loadData();
    }

    /**
     * get data from database
     *
     * @return bool success / failure
     */
    private function _loadData(): bool
    {
        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT c_skinid,'.
                                                        'c_quickpost,'.
                                                        'c_directregistration,'.
                                                        'c_uniquemail,'.
                                                        'c_usesignatures,'.
                                                        'c_dateformat,'.
                                                        'c_timeoffset,'.
                                                        'c_onlinetime,'.
                                                        'c_closethreads,'.
                                                        'c_usrperpage,'.
                                                        'c_msgheaderperpage,'.
                                                        'c_privatemsgperpage,'.
                                                        'c_thrdperpage,'.
                                                        'c_quotesubject,'.
                                                        'c_mailwebmaster,'.
                                                        'c_skindir,'.
                                                        'c_maxprofilepicsize,'.
                                                        'c_maxprofilepicwidth,'.
                                                        'c_maxprofilepicheight,'.
                                                        'c_profileimgdir'.
                                                    ' FROM pxm_configuration')) {
            if ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objResultSet->freeResult();
                unset($objResultSet);

                $this->m_iDefaultSkinId = (int) $objResultRow->c_skinid;

                $this->m_bUseQuickPost = (bool) $objResultRow->c_quickpost;
                $this->m_bUseDirectRegistration = (bool) $objResultRow->c_directregistration;
                $this->m_bUniqueRegistrationMails = (bool) $objResultRow->c_uniquemail;
                $this->m_bUseSignatures = (bool) $objResultRow->c_usesignatures;
                $this->m_sDateFormat = $objResultRow->c_dateformat;
                $this->m_iTimeOffset = (int) $objResultRow->c_timeoffset;
                $this->m_iOnlineTime = (int) $objResultRow->c_onlinetime;

                $this->m_iThreadSizeLimit = (int) $objResultRow->c_closethreads;
                $this->m_iUserPerPage = (int) $objResultRow->c_usrperpage;
                $this->m_iMessageHeaderPerPage = (int) $objResultRow->c_msgheaderperpage;
                $this->m_iPrivateMessagesPerPage = (int) $objResultRow->c_privatemsgperpage;
                $this->m_iThreadsPerPage = (int) $objResultRow->c_thrdperpage;

                $this->m_sQuoteSubject = $objResultRow->c_quotesubject;

                $this->m_sMailWebmaster	= $objResultRow->c_mailwebmaster;

                $this->m_sSkinDir = $objResultRow->c_skindir;
                $this->m_iMaxProfileImgSize = (int) $objResultRow->c_maxprofilepicsize;
                $this->m_iMaxProfileImgWidth = (int) $objResultRow->c_maxprofilepicwidth;
                $this->m_iMaxProfileImgHeight = (int) $objResultRow->c_maxprofilepicheight;
                $this->m_sProfileImgDir = $objResultRow->c_profileimgdir;

                unset($objResultRow);

                return true;
            }
        }
        return false;
    }

    /**
     * update data in database
     *
     * @return bool success / failure
     */
    public function updateData(): bool
    {
        if (cDBFactory::getInstance()->executeQuery("UPDATE pxm_configuration SET c_skinid=$this->m_iDefaultSkinId,".
                                                                        'c_quickpost='.intval($this->m_bUseQuickPost).','.
                                                                        'c_directregistration='.intval($this->m_bUseDirectRegistration).','.
                                                                        'c_uniquemail='.intval($this->m_bUniqueRegistrationMails).','.
                                                                        'c_usesignatures='.intval($this->m_bUseSignatures).','.
                                                                        'c_dateformat='.cDBFactory::getInstance()->quote($this->m_sDateFormat).','.
                                                                        "c_timeoffset=$this->m_iTimeOffset,".
                                                                        "c_onlinetime=$this->m_iOnlineTime,".
                                                                        "c_closethreads=$this->m_iThreadSizeLimit,".
                                                                        "c_usrperpage=$this->m_iUserPerPage,".
                                                                        "c_msgheaderperpage=$this->m_iMessageHeaderPerPage,".
                                                                        "c_privatemsgperpage=$this->m_iPrivateMessagesPerPage,".
                                                                        "c_thrdperpage=$this->m_iThreadsPerPage,".
                                                                        'c_quotesubject='.cDBFactory::getInstance()->quote($this->m_sQuoteSubject).','.
                                                                        'c_mailwebmaster='.cDBFactory::getInstance()->quote($this->m_sMailWebmaster).','.
                                                                        'c_skindir='.cDBFactory::getInstance()->quote($this->m_sSkinDir).','.
                                                                        "c_maxprofilepicsize=$this->m_iMaxProfileImgSize,".
                                                                        "c_maxprofilepicwidth=$this->m_iMaxProfileImgWidth,".
                                                                        "c_maxprofilepicheight=$this->m_iMaxProfileImgHeight,".
                                                                        'c_profileimgdir='.cDBFactory::getInstance()->quote($this->m_sProfileImgDir))) {
            return true;
        }
        return false;
    }

    /**
     * get available template engines
     *
     * @return array<string> available template engines
     */
    public function getAvailableTemplateEngines(): array
    {
        return $this->m_arrAvailableTemplateEngines;
    }

    /**
     * get active template engine
     *
     * @return string active template engine
     */
    public function getActiveTemplateEngine(): string
    {
        return $this->m_sActiveTemplateEngine;
    }

    /**
     * set active template engine
     *
     * @param string $sActiveTemplateEngine active template engine
     * @return void
     */
    public function setActiveTemplateEngine(string $sActiveTemplateEngine): void
    {
        $this->m_sActiveTemplateEngine = $sActiveTemplateEngine;
    }

    /**
     * get default skin id
     *
     * @return int default skin id
     */
    public function getDefaultSkinId(): int
    {
        return $this->m_iDefaultSkinId;
    }

    /**
     * set default skin id
     *
     * @param int $iDefaultSkinId default skin id
     * @return void
     */
    public function setDefaultSkinId(int $iDefaultSkinId): void
    {
        $this->m_iDefaultSkinId = $iDefaultSkinId;
    }

    /**
     * get access timestamp
     *
     * @return int access timestamp
     */
    public function getAccessTimestamp(): int
    {
        return $this->m_iAccessTimestamp;
    }

    /**
     * use quickpost?
     *
     * @return bool use quickpost?
     */
    public function useQuickPost(): bool
    {
        return $this->m_bUseQuickPost;
    }

    /**
     * set use quickpost
     *
     * @param bool $bUseQuickPost use quickpost?
     * @return void
     */
    public function setUseQuickPost(bool $bUseQuickPost): void
    {
        $this->m_bUseQuickPost = $bUseQuickPost ? true : false;
    }

    /**
     * use signatures?
     *
     * @return bool use signatures?
     */
    public function useSignatures(): bool
    {
        return $this->m_bUseSignatures;
    }

    /**
     * set use signatures
     *
     * @param bool $bUseSignatures use signatures?
     * @return void
     */
    public function setUseSignatures(bool $bUseSignatures): void
    {
        $this->m_bUseSignatures = $bUseSignatures ? true : false;
    }

    /**
     * use direct registration?
     *
     * @return bool use direct registration?
     */
    public function useDirectRegistration(): bool
    {
        return $this->m_bUseDirectRegistration;
    }

    /**
     * set use direct registration
     *
     * @param bool $bUseDirectRegistration use direct registration?
     * @return void
     */
    public function setUseDirectRegistration(bool $bUseDirectRegistration): void
    {
        $this->m_bUseDirectRegistration = $bUseDirectRegistration ? true : false;
    }

    /**
     * are the private mail adresses unique?
     *
     * @return bool registration mail adresses unique?
     */
    public function uniqueRegistrationMails(): bool
    {
        return $this->m_bUniqueRegistrationMails;
    }

    /**
     * set private mail adresses unique
     *
     * @param bool $bUniqueRegistrationMails registration mail adresses unique?
     * @return void
     */
    public function setUniqueRegistrationMails(bool $bUniqueRegistrationMails): void
    {
        $this->m_bUniqueRegistrationMails = $bUniqueRegistrationMails ? true : false;
    }

    /**
     * get date format
     *
     * @return string date format
     */
    public function getDateFormat(): string
    {
        return $this->m_sDateFormat;
    }

    /**
     * set date format
     *
     * @param string $sDateFormat date format
     * @return void
     */
    public function setDateFormat(string $sDateFormat): void
    {
        $this->m_sDateFormat = $sDateFormat;
    }

    /**
     * get time offset
     *
     * @return int time offset
     */
    public function getTimeOffset(): int
    {
        return $this->m_iTimeOffset;
    }

    /**
     * set time offset
     *
     * @param int $iTimeOffset time offset
     * @return void
     */
    public function setTimeOffset(int $iTimeOffset): void
    {
        if ($iTimeOffset < 13 && $iTimeOffset > -13) {
            $this->m_iTimeOffset = $iTimeOffset;
        }
    }

    /**
     * get online time
     *
     * @return int online time (seconds)
     */
    public function getOnlineTime(): int
    {
        return $this->m_iOnlineTime;
    }

    /**
     * set online time
     *
     * @param int $iOnlineTime online time (seconds)
     * @return void
     */
    public function setOnlineTime(int $iOnlineTime): void
    {
        $this->m_iOnlineTime = $iOnlineTime;
    }

    /**
     * get thread size limit
     *
     * @return int thread size limit (0 = no limit)
     */
    public function getThreadSizeLimit(): int
    {
        return $this->m_iThreadSizeLimit;
    }

    /**
     * set thread size limit
     *
     * @param int $iThreadSizeLimit thread size limit (0 = no limit)
     * @return void
     */
    public function setThreadSizeLimit(int $iThreadSizeLimit): void
    {
        $this->m_iThreadSizeLimit = $iThreadSizeLimit;
    }

    /**
     * get user per page
     *
     * @return int user per page
     */
    public function getUserPerPage(): int
    {
        return $this->m_iUserPerPage;
    }

    /**
     * set user per page
     *
     * @param int $iUserPerPage user per page
     * @return void
     */
    public function setUserPerPage(int $iUserPerPage): void
    {
        $this->m_iUserPerPage = $iUserPerPage;
    }

    /**
     * get message header per page (search)
     *
     * @return int message header per page
     */
    public function getMessageHeaderPerPage(): int
    {
        return $this->m_iMessageHeaderPerPage;
    }

    /**
     * set message header per page (search)
     *
     * @param int $iMessageHeaderPerPage message header per page
     * @return void
     */
    public function setMessageHeaderPerPage(int $iMessageHeaderPerPage): void
    {
        $this->m_iMessageHeaderPerPage = $iMessageHeaderPerPage;
    }

    /**
     * get private messages per page
     *
     * @return int private messages per page
     */
    public function getPrivateMessagesPerPage(): int
    {
        return $this->m_iPrivateMessagesPerPage;
    }

    /**
     * set private messages per page
     *
     * @param int $iPrivateMessagesPerPage private messages per page
     * @return void
     */
    public function setPrivateMessagesPerPage(int $iPrivateMessagesPerPage): void
    {
        $this->m_iPrivateMessagesPerPage = $iPrivateMessagesPerPage;
    }

    /**
     * get threads per page
     *
     * @return int threads per page
     */
    public function getThreadsPerPage(): int
    {
        return $this->m_iThreadsPerPage;
    }

    /**
     * set threads per page
     *
     * @param int $iThreadsPerPage threads per page
     * @return void
     */
    public function setThreadsPerPage(int $iThreadsPerPage): void
    {
        $this->m_iThreadsPerPage = $iThreadsPerPage;
    }

    /**
     * get webmaster mail adress
     *
     * @return string webmaster mail adress
     */
    public function getMailWebmaster(): string
    {
        return $this->m_sMailWebmaster;
    }

    /**
     * set webmaster mail adress
     *
     * @param string $sMailWebmaster webmaster mail adress
     * @return void
     */
    public function setMailWebmaster(string $sMailWebmaster): void
    {
        $this->m_sMailWebmaster = $sMailWebmaster;
    }

    /**
     * get quote subject
     *
     * @return string quote subject
     */
    public function getQuoteSubject(): string
    {
        return $this->m_sQuoteSubject;
    }

    /**
     * set quote subject
     *
     * @param string $sQuoteSubject quote subject
     * @return void
     */
    public function setQuoteSubject(string $sQuoteSubject): void
    {
        $this->m_sQuoteSubject = $sQuoteSubject;
    }

    /**
     * get quote tag
     *
     * @return string quote tag (HTML element name)
     */
    public function getQuoteTag(): string
    {
        return $this->m_sQuoteTag;
    }

    /**
     * get skin directory
     *
     * @return string skin directory
     */
    public function getSkinDirectory(): string
    {
        // Resolve relative paths to absolute using BASEDIR when available.
        // Skins reside outside public/, so relative paths must be anchored to the project root.
        if ($this->m_sSkinDir !== '' && $this->m_sSkinDir[0] !== '/' && defined('BASEDIR')) {
            return BASEDIR . '/' . $this->m_sSkinDir;
        }
        return $this->m_sSkinDir;
    }

    /**
     * set skin directory
     *
     * @param string $sSkinDir skin directory
     * @return void
     */
    public function setSkinDirectory(string $sSkinDir): void
    {
        $this->m_sSkinDir = $sSkinDir.(((strlen($sSkinDir) > 0) && ($sSkinDir[strlen($sSkinDir) - 1] != '/')) ? '/' : '');
    }

    /**
     * get max profile img size
     *
     * @return int max profile img size (byte)
     */
    public function getMaxProfileImgSize(): int
    {
        return $this->m_iMaxProfileImgSize;
    }

    /**
     * set max profile img size
     *
     * @param int $iMaxProfileImgSize max profile img size (byte)
     * @return void
     */
    public function setMaxProfileImgSize(int $iMaxProfileImgSize): void
    {
        $this->m_iMaxProfileImgSize = $iMaxProfileImgSize;
    }

    /**
     * get max profile img width
     *
     * @return int max profile img width (pixel)
     */
    public function getMaxProfileImgWidth(): int
    {
        return $this->m_iMaxProfileImgWidth;
    }

    /**
     * set max profile img width
     *
     * @param int $iMaxProfileImgWidth max profile img width (pixel)
     * @return void
     */
    public function setMaxProfileImgWidth(int $iMaxProfileImgWidth): void
    {
        $this->m_iMaxProfileImgWidth = $iMaxProfileImgWidth;
    }

    /**
     * get max profile img height
     *
     * @return int max profile img height (pixel)
     */
    public function getMaxProfileImgHeight(): int
    {
        return $this->m_iMaxProfileImgHeight;
    }

    /**
     * set max profile img height
     *
     * @param int $iMaxProfileImgHeight max profile img height (pixel)
     * @return void
     */
    public function setMaxProfileImgHeight(int $iMaxProfileImgHeight): void
    {
        $this->m_iMaxProfileImgHeight = $iMaxProfileImgHeight;
    }

    /**
     * get profile img directory (web-relative path for use in templates)
     *
     * @return string profile img directory (web-relative)
     */
    public function getProfileImgDirectory(): string
    {
        return $this->m_sProfileImgDir;
    }

    /**
     * get profile img directory as absolute filesystem path (for file operations)
     * Resolves relative paths against PUBLICDIR when available.
     *
     * @return string absolute filesystem path to profile img directory
     */
    public function getProfileImgFsDirectory(): string
    {
        if ($this->m_sProfileImgDir !== '' && $this->m_sProfileImgDir[0] !== '/' && defined('PUBLICDIR')) {
            return PUBLICDIR . '/' . $this->m_sProfileImgDir;
        }
        return $this->m_sProfileImgDir;
    }

    /**
     * set profile img directory
     *
     * @param string $sProfileImgDir profile img directory
     * @return void
     */
    public function setProfileImgDirectory(string $sProfileImgDir): void
    {
        $this->m_sProfileImgDir = $sProfileImgDir.(((strlen($sProfileImgDir) > 0) && ($sProfileImgDir[strlen($sProfileImgDir) - 1] != '/')) ? '/' : '');
    }

    /**
     * get profile img types
     *
     * @return array<string> profile img types
     */
    public function getProfileImgTypes(): array
    {
        return $this->m_arrProfileImgTypes;
    }

    /**
     * get profile img directory split
     *
     * @return int profile img directory split
     */
    public function getProfileImgDirectorySplit(): int
    {
        return $this->m_iProfileImgSplitDir;
    }

    /**
     * get membervariables as array
     *
     * @param array<string, mixed>  $arrAdditionalConfig additional configuration
     * @return array<mixed> member variables
     */
    public function getDataArray(array $arrAdditionalConfig = []): array
    {
        $arrGeneralConfiguration = [
            'webmaster'			=> $this->m_sMailWebmaster,
            'usesignatures'		=> (int) $this->m_bUseSignatures,
            'profile_img_dir'	=> $this->m_sProfileImgDir
        ];
        //TODO: additonalConfig entfernen
        return ['config' => $arrGeneralConfiguration, $arrAdditionalConfig];
    }
}
