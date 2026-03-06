<?php

namespace PXMBoard\Parser;

use PXMBoard\Database\cDB;

/**
 * PXM markup to HTML parser
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPxmParser extends cParser
{
    /** @var array<string, array<string>> */
    protected array $m_arrReplacements;			// textreplacements (array[search];array[replace])
    protected bool $m_bEmbedExternal;			// embed external content (images, YouTube, Twitch)?
    protected bool $m_bIsLoggedIn;				// is current user logged in?
    /** @var array<int, string> */
    protected array $m_arrMentionCache;			// cached user usernames for mentions
    private string $m_sHttpHost;				// HTTP Host header value (for Twitch embed parent parameter)

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {

        parent::__construct();

        $this->m_arrReplacements = ['search' => [],'replace' => []];
        $this->m_bEmbedExternal = false;
        $this->m_bIsLoggedIn = false;
        $this->m_arrMentionCache = [];
        $this->m_sHttpHost = 'localhost';
    }

    /**
     * parse the given text
     *
     * Converts PXM markup (stack-based tag notation) to HTML for display.
     *
     * IMPORTANT: The PXM format must be kept in sync with tiptapToPxm() in build/main.js.
     * Changes to the PXM format (new tags, changed syntax) must be reflected in both converters.
     *
     * Workflow:
     * - Editor:  Tiptap → tiptapToPxm() → PXM → Database
     * - Display: PXM → parse() → HTML → Browser
     *
     * Supported tags:
     * - [b:] Bold, [i:] Italic, [u:] Underline, [s:] Strike
     * - [h:] Hidden/Spoiler (visible to logged-in users only)
     * - [m:] Member-only content (shown only to logged-in users, placeholder otherwise)
     * - [q:] Quote/Blockquote
     * - [user:id] User mentions (with autocomplete)
     * - [http(s)://...], [ftp(s)://...], [www...], [mailto:...] URLs
     * - [img:http(s)://....jpg|gif|png|jpeg] Images
     * - [yt:videoId or URL] YouTube videos
     * - [ttv:URL] Twitch videos/clips/channels
     *
     * @see build/main.js::tiptapToPxm() - client-side Tiptap → PXM conversion
     * @param string $sText text to be parsed
     * @return string parsed text
     */
    public function parse(string $sText): string
    {

        // Pre-load all mentioned users for batch performance
        $this->_preloadMentions($sText);

        $sReturnText = '';
        if (($iBodyLength = strlen($sText)) > 0) {
            $iCharPointer = 0;
            $arrStyleStack = [];
            $bIsInQuote = false;
            $bMemberSkip = false; // Track whether we're skipping member-only content (not logged in)

            if ($this->m_sDoQuote && !empty($this->m_sQuoteTag)) {
                $sReturnText = '<'.$this->m_sQuoteTag.'>';
                array_push($arrStyleStack, $this->m_sQuoteTag);
                $bIsInQuote = true;
            }

            while ($iCharPointer < $iBodyLength) {

                // skip regular text without special characters (unless in member-skip mode)
                $iCharPointerTextLength = strcspn($sText, "[]\n", $iCharPointer);
                if ($iCharPointerTextLength > 0 && !$bMemberSkip) { // process normal text only if not skipping
                    $sReturnText .= str_replace($this->m_arrReplacements['search'], $this->m_arrReplacements['replace'], htmlspecialchars(substr($sText, $iCharPointer, $iCharPointerTextLength)));
                }
                $iCharPointer += $iCharPointerTextLength;

                // deal with special characters if end of text not reached
                if ($iCharPointer < $iBodyLength) {
                    $sMessagePart = substr($sText, $iCharPointer);

                    switch ($sMessagePart[0]) { // special character handling
                        case "\n":	// line break
                            if (!$bMemberSkip) {
                                $sReturnText .= "<br />\n";
                            }
                            break;
                        case '[':	// start of pxm code tag
                            // tags
                            if (preg_match('/^\[([biushqm]):/iu', $sMessagePart, $arrStyleMatch)) {
                                $cStyleChar = strtolower($arrStyleMatch[1]);
                                $sStyleTag = '';
                                switch ($cStyleChar) {
                                    case 'h':	// hidden/spoiler tag realized with html span area
                                        $sStyleTag = 'span';
                                        break;
                                    case 'm':	// member-only content
                                        $sStyleTag = 'span';
                                        break;
                                    case 'q':	// quote
                                        $sStyleTag = $this->m_sQuoteTag;
                                        break;
                                    default:
                                        $sStyleTag = $cStyleChar;
                                }
                                if ((!empty($sStyleTag) && $sStyleTag === $this->m_sQuoteTag) || !in_array($sStyleTag, $arrStyleStack)) {	// if this kind of styling is not already open, blockquote can be nested
                                    switch ($cStyleChar) {
                                        case 'h':	// hidden/spoiler tag with emoji toggle
                                            if (!$bMemberSkip) { // Only render if not in member-skip mode
                                                $sReturnText .= '<button type="button" class="spoiler-button" onclick="spoiler(this)"><span class="spoiler-emoji">🤫</span><span class="spoiler-label">Spoiler anzeigen</span></button><span class="spoiler hidden ml-2">';
                                            }
                                            break;
                                        case 'm':	// member-only content
                                            if ($this->m_bIsLoggedIn) {
                                                // User is logged in - show content normally
                                                $sReturnText .= '<span class="member-content"><span class="member-icon" title="Nur für Mitglieder">🔐</span><span class="member-content-text">';
                                            } elseif (!$bMemberSkip) {
                                                // User not logged in - show placeholder and skip content
                                                $sReturnText .= '<span class="member-locked"><span class="member-icon">🔐</span><span class="member-locked-text">Dieser Inhalt ist nur für eingeloggte Mitglieder sichtbar</span></span>';
                                                $bMemberSkip = true; // Start skipping content
                                            }
                                            break;
                                        case 'q':	// quote
                                            if (!$bMemberSkip) {
                                                $sReturnText .= '<' . $sStyleTag . '>';
                                                //$bIsInQuote = true;
                                            }
                                            break;
                                        default:
                                            if (!$bMemberSkip) {
                                                $sReturnText .= '<' . $sStyleTag . '>';
                                            }
                                    }
                                    array_push($arrStyleStack, $sStyleTag);
                                } else {										// otherwhise it is not needed so we pass it through
                                    if (!$bMemberSkip) {
                                        $sReturnText .= '[' . $cStyleChar . ':';
                                    }
                                }
                                $iCharPointer += 2;
                                // links
                            } elseif (preg_match('/^\[((https?|ftps?|www|mailto:)([^\] ]+))/iu', $sMessagePart, $arrLinkMatch)) {
                                if (!$bMemberSkip) {
                                    if (strcasecmp($arrLinkMatch[2], 'www') === 0) {
                                        $sReturnText .= '[<a href="https://' . htmlspecialchars($arrLinkMatch[1]) . '" class="pxm-link" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($arrLinkMatch[1]) . '</a>]';
                                    } elseif (strcasecmp($arrLinkMatch[2], 'mailto:') === 0) {
                                        $sReturnText .= '[<a href="' . htmlspecialchars($arrLinkMatch[1]) . '" class="pxm-link">' . htmlspecialchars($arrLinkMatch[3]) . '</a>]';
                                    } else {
                                        $sReturnText .= '[<a href="' . htmlspecialchars($arrLinkMatch[1]) . '" class="pxm-link" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($arrLinkMatch[1]) . '</a>]';
                                    }
                                }
                                $iCharPointer += strlen($arrLinkMatch[1]) + 1;
                                // images
                            } elseif (preg_match('/^\[img:((https?[^\] ]+)\.(?:jpg|gif|png|jpeg))/iu', $sMessagePart, $arrImgMatch)) {
                                if (!$bMemberSkip) {
                                    if ($this->m_bEmbedExternal /* && !$bIsInQuote*/) {
                                        $sReturnText .= '<img src="' . htmlspecialchars($arrImgMatch[1]) . '" class="pxm-img"/>';
                                    } else {
                                        $sReturnText .= '[<a href="' . htmlspecialchars($arrImgMatch[1]) . '" class="pxm-link" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($arrImgMatch[1]) . '</a>]';
                                    }
                                }
                                $iCharPointer += strlen($arrImgMatch[1]) + 5;
                                // user mentions: [user:123]
                            } elseif (preg_match('/^\[user:(\d+)\]/iu', substr($sText, $iCharPointer), $arrMentionMatch)) {
                                if (!$bMemberSkip) {
                                    $iUserId = (int) $arrMentionMatch[1];
                                    if (isset($this->m_arrMentionCache[$iUserId])) {
                                        $sUsername = htmlspecialchars($this->m_arrMentionCache[$iUserId]);
                                        $sProfileUrl = 'pxmboard.php?mode=userprofile&usrid='.$iUserId;
                                        $sReturnText .= '<a href="'.htmlspecialchars($sProfileUrl).'" class="mention" data-user-id="'.$iUserId.'">@'.$sUsername.'</a>';
                                    } else {
                                        $sReturnText .= '<span class="mention mention-deleted">[Gel&ouml;schter Nutzer]</span>';
                                    }
                                }
                                $iCharPointer += strlen($arrMentionMatch[0]) - 1;
                                // YouTube videos: [yt:videoId or URL]
                            } elseif (preg_match('/^\[yt:([^\]]+)\]/iu', substr($sText, $iCharPointer), $arrYtMatch)) {
                                if (!$bMemberSkip) {
                                    $sVideoId = $this->_extractYouTubeId($arrYtMatch[1]);
                                    if ($sVideoId) {
                                        if ($this->m_bEmbedExternal) {
                                            $sReturnText .= '<div class="youtube-embed"><iframe width="640" height="480" src="https://www.youtube.com/embed/'.htmlspecialchars($sVideoId).'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
                                        } else {
                                            $sYtUrl = 'https://www.youtube.com/watch?v='.htmlspecialchars($sVideoId);
                                            $sReturnText .= '<a href="'.$sYtUrl.'" class="video-link video-link--yt" target="_blank" rel="noopener noreferrer">YouTube</a>';
                                        }
                                    }
                                }
                                $iCharPointer += strlen($arrYtMatch[0]) - 1;
                                // Twitch videos/clips/channels: [ttv:URL]
                            } elseif (preg_match('/^\[ttv:([^\]]+)\]/iu', substr($sText, $iCharPointer), $arrTtvMatch)) {
                                if (!$bMemberSkip) {
                                    $arrTwitchData = $this->_extractTwitchData($arrTtvMatch[1]);
                                    if ($arrTwitchData) {
                                        if ($this->m_bEmbedExternal) {
                                            $sReturnText .= $this->_getTwitchIframe($arrTwitchData);
                                        } else {
                                            $sTtvUrl = htmlspecialchars($arrTtvMatch[1]);
                                            $sReturnText .= '<a href="'.$sTtvUrl.'" class="video-link video-link--twitch" target="_blank" rel="noopener noreferrer">Twitch</a>';
                                        }
                                    }
                                }
                                $iCharPointer += strlen($arrTtvMatch[0]) - 1;
                                // default/not supported
                            } else {
                                if (!$bMemberSkip) {
                                    $sReturnText .= $sText[$iCharPointer];
                                }
                            }
                            break;
                        case ']':
                            if (!empty($arrStyleStack)) {
                                $sStyleTag = array_pop($arrStyleStack);

                                // Check if we're closing a member-skip tag
                                if ($sStyleTag == 'span' && $bMemberSkip && !$this->m_bIsLoggedIn) {
                                    // We're closing a [m:] tag while in skip mode
                                    $bMemberSkip = false;
                                }

                                if (!$bMemberSkip) {
                                    $sReturnText .= '</' . $sStyleTag . '>';
                                }

                                if ($sStyleTag === $this->m_sQuoteTag) {
                                    $bIsInQuote = false;
                                }
                            } else {
                                if (!$bMemberSkip) {
                                    $sReturnText .= ']';
                                }
                            }
                            break;
                    }
                }
                ++$iCharPointer;
            }
            while (sizeof($arrStyleStack) > 0) {
                $sReturnText .= '</' . array_pop($arrStyleStack) . '>';
            }
            if ($bIsInQuote) {
                $sReturnText .= '</'.$this->m_sQuoteTag.'>';
            }
            $sReturnText = str_replace('  ', ' &nbsp;', $sReturnText);
        }
        return $sReturnText;
    }

    /**
     * get replacements
     *
     * @return array<string, array<string>> replacements
     */
    public function getReplacements(): array
    {
        return $this->m_arrReplacements;
    }

    /**
     * set replacements
     *
     * @param array<string, array<string>> $arrReplacements replacements
     * @return void
     */
    public function setReplacements(array $arrReplacements): void
    {
        $this->m_arrReplacements = $arrReplacements;
    }

    /**
     * embed external content? (images, YouTube, Twitch)
     *
     * @return bool embed external content?
     */
    public function embedExternal(): bool
    {
        return $this->m_bEmbedExternal;
    }

    /**
     * set embed external content flag
     *
     * @param bool $bEmbedExternal embed external content?
     * @return void
     */
    public function setEmbedExternal(bool $bEmbedExternal): void
    {
        $this->m_bEmbedExternal = $bEmbedExternal ? true : false;
    }

    /**
     * set logged in status (for member-only content)
     *
     * @param bool $bIsLoggedIn is user logged in?
     * @return void
     */
    public function setIsLoggedIn(bool $bIsLoggedIn): void
    {
        $this->m_bIsLoggedIn = $bIsLoggedIn ? true : false;
    }

    /**
     * Set the HTTP Host header value for Twitch embed parent parameter.
     * Must be called by the Action before using the parser for embed rendering.
     *
     * @param string $sHttpHost HTTP_HOST value (e.g. 'example.com' or 'example.com:8080')
     * @return void
     */
    public function setHttpHost(string $sHttpHost): void
    {
        $this->m_sHttpHost = $sHttpHost;
    }

    /**
     * Pre-load all mentioned users from database (batch operation)
     * Prevents N+1 queries by loading all mentions at once
     *
     * @param string $sText text to scan for mentions
     * @return void
     */
    protected function _preloadMentions(string $sText): void
    {
        // Extract all user IDs from [user:id] tags
        if (preg_match_all('/\[user:(\d+)\]/', $sText, $arrMatches)) {
            $arrUserIds = array_unique(array_map('intval', $arrMatches[1]));

            // Limit to max 10 mentions per message for performance
            $arrUserIds = array_slice($arrUserIds, 0, 10);

            if (!empty($arrUserIds)) {
                $objDb = cDB::getInstance();

                $sIds = implode(',', $arrUserIds);
                $sQuery = 'SELECT u_id, u_username FROM pxm_user WHERE u_id IN ('.$sIds.')';
                $objResultSet = $objDb->executeQuery($sQuery);

                while ($objRow = $objResultSet->getNextResultRowObject()) {
                    $this->m_arrMentionCache[(int) $objRow->u_id] = $objRow->u_username;
                }
                $objResultSet->freeResult();
            }
        }
    }

    /**
     * Extract YouTube video ID from URL
     * Only accepts full URLs (youtube.com/watch?v= or youtu.be/)
     * Video IDs (11 characters) are no longer supported
     *
     * @param string $sInput YouTube URL
     * @return string|null video ID or null if invalid
     */
    private function _extractYouTubeId(string $sInput): ?string
    {
        $sInput = trim($sInput);

        // youtube.com/watch?v=VIDEO_ID
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/', $sInput, $arrMatch)) {
            return $arrMatch[1];
        }

        // youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $sInput, $arrMatch)) {
            return $arrMatch[1];
        }

        return null;
    }

    /**
     * Extract Twitch embed data from URL
     *
     * @param string $sUrl Twitch URL
     * @return array<string, mixed>|null ['type' => 'video'|'clip'|'channel', 'id' => string] or null
     */
    private function _extractTwitchData(string $sUrl): ?array
    {
        $sUrl = trim($sUrl);

        // Videos: twitch.tv/videos/123456
        if (preg_match('/twitch\.tv\/videos\/(\d+)/', $sUrl, $arrMatch)) {
            return ['type' => 'video', 'id' => $arrMatch[1]];
        }

        // Clips: twitch.tv/channel/clip/ClipName or clips.twitch.tv/ClipName
        if (preg_match('/clips\.twitch\.tv\/([a-zA-Z0-9_-]+)/', $sUrl, $arrMatch)) {
            return ['type' => 'clip', 'id' => $arrMatch[1]];
        }
        if (preg_match('/twitch\.tv\/[^\/]+\/clip\/([a-zA-Z0-9_-]+)/', $sUrl, $arrMatch)) {
            return ['type' => 'clip', 'id' => $arrMatch[1]];
        }

        // Channel: twitch.tv/channelname (not videos or clips)
        if (preg_match('/twitch\.tv\/([a-zA-Z0-9_]+)$/', $sUrl, $arrMatch)) {
            return ['type' => 'channel', 'id' => $arrMatch[1]];
        }

        return null;
    }

    /**
     * Generate Twitch iframe embed HTML
     *
     * @param array<string, mixed> $arrData ['type' => 'video'|'clip'|'channel', 'id' => string]
     * @return string iframe HTML
     */
    private function _getTwitchIframe(array $arrData): string
    {
        $sType = $arrData['type'];
        $sId = htmlspecialchars($arrData['id']);

        // Extract hostname without port (Twitch parent parameter doesn't allow ports)
        $sHost = $this->m_sHttpHost;
        $sParent = htmlspecialchars(explode(':', $sHost)[0]);

        switch ($sType) {
            case 'video':
                $sSrc = "https://player.twitch.tv/?video={$sId}&parent={$sParent}";
                break;
            case 'clip':
                $sSrc = "https://clips.twitch.tv/embed?clip={$sId}&parent={$sParent}";
                break;
            case 'channel':
                $sSrc = "https://player.twitch.tv/?channel={$sId}&parent={$sParent}";
                break;
            default:
                return '';
        }

        return '<div class="twitch-embed"><iframe src="'.$sSrc.'" width="640" height="480" frameborder="0" scrolling="no" allowfullscreen></iframe></div>';
    }
}
