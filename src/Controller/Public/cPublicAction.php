<?php

declare(strict_types=1);

require_once(SRCDIR . '/Controller/cBaseAction.php');
require_once(SRCDIR . '/Exception/SkinInitializationException.php');
require_once(SRCDIR . '/Model/cSkin.php');
require_once(SRCDIR . '/Parser/cPxmParser.php');
require_once(SRCDIR . '/Skin/cSkinTemplateFactory.php');

/**
 * Abstract base class for public web actions (template-based output).
 *
 * Extends cBaseAction with template engine initialization, context building,
 * PXM parser setup, and HTML output via the skin template system.
 *
 * @author Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright Torsten Rentsch 2001 - 2026
 */
abstract class cPublicAction extends cBaseAction
{
    protected mixed $m_objTemplate = null;

    /**
     * Constructor - calls parent, then initializes the skin for this public action.
     *
     * @param cConfig $objConfig configuration data of the board
     * @param int $iUserId user id from session (0 = guest)
     * @param int $iBoardId board id from request (0 = no board)
     * @throws SkinInitializationException if skin cannot be initialized
     */
    public function __construct(cConfig $objConfig, int $iUserId = 0, int $iBoardId = 0)
    {
        parent::__construct($objConfig, $iUserId, $iBoardId);
        if (!$this->initSkin()) {
            throw new SkinInitializationException('Could not initialize skin. Check configuration.');
        }
    }

    /**
     * Handle permission error by rendering the error template.
     *
     * @param eError $error the error that caused the permission failure
     * @return void
     */
    protected function _handlePermissionError(eError $error): void
    {
        $this->m_objTemplate = $this->_getErrorTemplateObject($error);
    }

    /**
     * Initialize the skin for output with user or default preferences.
     *
     * @return bool true on success, false on failure
     */
    public function initSkin(): bool
    {
        $bReturn = true;

        if (is_object($this->m_objActiveUser) && $this->m_objActiveUser->getSkinId() > 0) {
            $iSkinId = $this->m_objActiveUser->getSkinId();
        } else {
            $iSkinId = $this->m_objConfig->getDefaultSkinId();
        }

        $this->m_objActiveSkin = new cSkin();
        $arrValidTemplateEngines = [];

        if (!$this->m_objActiveSkin->loadDataById($iSkinId)
            || !($arrValidTemplateEngines = array_intersect(
                $this->m_objConfig->getAvailableTemplateEngines(),
                $this->m_objActiveSkin->getSupportedTemplateEngines()
            ))
        ) {
            if ($iSkinId == $this->m_objConfig->getDefaultSkinId()
                || !$this->m_objActiveSkin->loadDataById($this->m_objConfig->getDefaultSkinId())
                || !($arrValidTemplateEngines = array_intersect(
                    $this->m_objConfig->getAvailableTemplateEngines(),
                    $this->m_objActiveSkin->getSupportedTemplateEngines()
                ))
            ) {
                $bReturn = false;
            }
        }

        if ($bReturn) {
            reset($arrValidTemplateEngines);
            $sActiveTemplateEngine = current($arrValidTemplateEngines);
            $this->m_objConfig->setActiveTemplateEngine($sActiveTemplateEngine);
        }

        return $bReturn;
    }

    /**
     * Get the output of this action (rendered template).
     *
     * @return string rendered HTML output
     */
    public function getOutput(): string
    {
        if (is_object($this->m_objTemplate)) {
            return $this->m_objTemplate->getOutput();
        }
        return "Overwrite getOutput() for actions that don't use templates";
    }

    /**
     * Get the template object for the given template name.
     *
     * @param string $sTemplateName name of the template
     * @return cSkinTemplate template object
     */
    protected function _getTemplateObject(string $sTemplateName): cSkinTemplate
    {
        $objTemplate = cSkinTemplateFactory::getTemplateObject(
            $this->m_objConfig->getActiveTemplateEngine(),
            $this->m_objConfig->getSkinDirectory() . $this->m_objActiveSkin->getDirectory()
        );
        $objTemplate->setTemplateName($sTemplateName);
        return $objTemplate;
    }

    /**
     * Get the error template object for the given error enum.
     *
     * @param eError $error error enum
     * @return cSkinTemplate error template object
     */
    protected function _getErrorTemplateObject(eError $error): cSkinTemplate
    {
        $objTemplate = cSkinTemplateFactory::getTemplateObject(
            $this->m_objConfig->getActiveTemplateEngine(),
            $this->m_objConfig->getSkinDirectory() . $this->m_objActiveSkin->getDirectory()
        );
        $sTemplateName = 'error-' . strtolower(get_class($this));
        if (!$objTemplate->isTemplateValid($sTemplateName)) {
            $sTemplateName = 'error';
        }
        $objTemplate->setTemplateName($sTemplateName);
        $objTemplate->addData($this->getContextDataArray());
        $objTemplate->addData(['error' => ['text' => $error->value]]);
        return $objTemplate;
    }

    /**
     * Get context data array for templates.
     *
     * @param array<string, mixed> $arrAdditionalData additional data to merge
     * @return array<string, mixed> context data
     */
    protected function getContextDataArray(array $arrAdditionalData = []): array
    {
        $arrContext = [
            'logedin'   => is_object($this->m_objActiveUser) ? '1' : '0',
            'admin'     => '0',
            'moderator' => '0',
            'timespan'  => '0',
        ];

        if (is_object($this->m_objActiveBoard)) {
            $arrContext['board'] = [
                'id'   => $this->m_objActiveBoard->getId(),
                'name' => $this->m_objActiveBoard->getName(),
            ];
            $arrContext['timespan'] = $this->m_objActiveBoard->getThreadListTimeSpan();
        }

        if (is_object($this->m_objActiveUser)) {
            $arrContext['admin'] = $this->m_objActiveUser->isAdmin() ? '1' : '0';
            if (is_object($this->m_objActiveBoard)) {
                $arrContext['moderator'] = $this->m_objActiveUser->isModerator($this->m_objActiveBoard->getId()) ? '1' : '0';
            }
            $arrContext['user'] = [
                'id'                        => $this->m_objActiveUser->getId(),
                'username'                  => $this->m_objActiveUser->getUserName(),
                'imgfile'                   => $this->m_objActiveUser->getImageFileName(),
                'notification_unread_count' => $this->m_objActiveUser->getUnreadNotificationCount(),
                'priv_message_unread_count' => $this->m_objActiveUser->getUnreadPrivMessageCount(),
            ];
        }

        if (is_object($this->m_objActiveSkin)) {
            $arrContext['skin'] = $this->m_objActiveSkin->getDataArray();
        }

        $arrContext['input_sizes'] = $this->m_objInputHandler->getInputSizes();
        $arrContext['csrf_token'] = $this->m_sCsrfToken ?? '';

        return [
            'config' => array_merge_recursive(
                $this->m_objConfig->getDataArray()['config'] ?? [],
                $arrContext,
                $arrAdditionalData
            ),
        ];
    }

    /**
     * Get a predefined PXM parser object.
     *
     * @param bool $bDoTextReplacements should text replacements be applied?
     * @param bool $bDoQuote should the data be enclosed in quotes?
     * @return cPxmParser parser object
     */
    protected function _getPredefinedPxmParser(bool $bDoTextReplacements = false, bool $bDoQuote = false): cPxmParser
    {
        $objPxmParser = new cPxmParser();
        $objPxmParser->setIsLoggedIn($this->m_objActiveUser !== null);
        $objPxmParser->setQuoteTag($this->m_objConfig->getQuoteTag());
        $objPxmParser->setEmbedExternal($this->embedExternal());
        $objPxmParser->setDoQuote($bDoQuote);
        $objPxmParser->setHttpHost($this->m_objServerHandler->getHttpHost());
        if ($bDoTextReplacements) {
            require_once(SRCDIR . '/Model/cTextreplacementList.php');
            $objTextreplacementList = new cTextreplacementList();
            $objPxmParser->setReplacements($objTextreplacementList->getList());
        }
        return $objPxmParser;
    }

    /**
     * Should external content be embedded? (images, YouTube, Twitch)
     *
     * @return bool true if external content should be embedded
     */
    public function embedExternal(): bool
    {
        $bEmbedExternal = false;
        if (is_object($this->m_objActiveUser)) {
            $bEmbedExternal = $this->m_objActiveUser->embedExternal();
        } elseif (is_object($this->m_objActiveBoard)) {
            $bEmbedExternal = $this->m_objActiveBoard->embedExternal();
        }
        return $bEmbedExternal;
    }

    /**
     * Should text replacements (smilies etc.) be applied?
     *
     * @return bool true if text replacements should be applied
     */
    public function doTextReplacements(): bool
    {
        $bDoTextReplacements = false;
        if (is_object($this->m_objActiveBoard)) {
            $bDoTextReplacements = $this->m_objActiveBoard->doTextReplacements();
        }
        return $bDoTextReplacements;
    }
}
