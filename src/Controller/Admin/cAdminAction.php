<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Controller\cBaseAction;
use PXMBoard\Database\cDBFactory;
use PXMBoard\Enum\eErrorKeys;
use PXMBoard\Model\cConfig;

/**
 * base class for the board admin actions
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
abstract class cAdminAction extends cBaseAction
{
    protected string $m_sOutput;
    private bool $m_bShowSidebar = true;

    /**
     * Constructor
     *
     * @param cConfig $objConfig configuration data of the board
     * @param int $iUserId user id from session (0 = guest)
     * @param int $iBoardId board id from request (0 = no board)
     * @return void
     */
    public function __construct(cConfig $objConfig, int $iUserId = 0, int $iBoardId = 0)
    {
        parent::__construct($objConfig, $iUserId, $iBoardId);
        $this->m_sOutput = '';
    }

    /**
     * Handle permission error by rendering an HTML error page.
     *
     * @param eErrorKeys $error the error that caused the permission failure
     * @return void
     */
    protected function _handlePermissionError(eErrorKeys $error): void
    {
        $this->m_sOutput = $this->_getHead()
            . $this->_getAlert(htmlspecialchars($error->t()))
            . $this->_getFooter();
    }

    /**
     * Get a hidden CSRF token form field.
     *
     * @return string hidden input HTML
     */
    protected function _getHiddenCsrfField(): string
    {
        return $this->_getHiddenField('csrf_token', $this->m_sCsrfToken ?? '');
    }

    /**
     * get the output of this action
     *
     * @return string output of this action
     */
    public function getOutput(): string
    {
        return $this->m_sOutput;
    }

    /**
     * get the head for the output
     *
     * @param bool $bShowSidebar show the sidebar navigation layout?
     * @return string head
     */
    protected function _getHead(bool $bShowSidebar = true): string
    {
        $this->m_bShowSidebar = $bShowSidebar;
        $sReturn  = "<!DOCTYPE html>\n";
        $sReturn .= "<html lang=\"en\">\n";
        $sReturn .= "<head>\n";
        $sReturn .= "<meta charset=\"UTF-8\">\n";
        $sReturn .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $sReturn .= "<title>PXMBoard Admin</title>\n";
        $sReturn .= "<link rel=\"stylesheet\" href=\"css/pxm_admin.css\">\n";
        $sReturn .= "</head>\n";
        $sReturn .= "<body>\n";
        $sReturn .= "<header class=\"pxm-admin-header\">\n";
        $sReturn .= "<a href=\"pxmboard.php?mode=admintro\"><img src=\"images/pxmboard_logo.png\" alt=\"PXMBoard\"></a>\n";
        $sReturn .= "</header>\n";

        if ($bShowSidebar) {
            $sReturn .= "<div class=\"pxm-admin-layout\">\n";
            $sReturn .= $this->_getSidebar();
            $sReturn .= "<main class=\"pxm-admin-main\">\n";
        } else {
            $sReturn .= "<div class=\"pxm-admin-container\">\n";
        }

        return $sReturn;
    }

    /**
     * get the footer for the output
     *
     * @return string footer
     */
    protected function _getFooter(): string
    {
        if ($this->m_bShowSidebar) {
            return "</main>\n</div>\n</body>\n</html>\n";
        }
        return "</div>\n</body>\n</html>\n";
    }

    /**
     * get the sidebar navigation
     *
     * @return string sidebar HTML
     */
    protected function _getSidebar(): string
    {
        $bIsMySql = strcasecmp(cDBFactory::getInstance()->getDBType(), 'MySQL') === 0;

        $s  = "<nav class=\"pxm-admin-sidebar\">\n";
        $s .= "<ul class=\"pxm-admin-nav\">\n";
        $s .= "<li><span class=\"pxm-admin-nav__label\">Configuration</span></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admconfigform\">General</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admreplacementform\">Text replacements</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admbadwordform\">Badwords</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admforbiddenmailform\">Forbidden mails</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admprofileform\">Profile fields</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admtemplatelist\">Templates</a></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admskinlist\">Skins</a></li>\n";

        $s .= "<li><span class=\"pxm-admin-nav__label\">Boards</span></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admboardform\">Add board</a></li>\n";

        $s .= "<li><span class=\"pxm-admin-nav__label\">Users</span></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admuserlist\">Overview</a></li>\n";
        if (!$this->m_objConfig->useDirectRegistration()) {
            $s .= "<li><a href=\"pxmboard.php?mode=admactivateusersform\">Activation</a></li>\n";
        }

        $s .= "<li><span class=\"pxm-admin-nav__label\">Tools</span></li>\n";
        $s .= "<li><a href=\"pxmboard.php?mode=admmessageform\">Message tool</a></li>\n";
        if ($bIsMySql) {
            $s .= "<li><a href=\"pxmboard.php?mode=admdbcleanform\">Clean database</a></li>\n";
        }

        $s .= "</ul>\n";
        $s .= "<div class=\"pxm-admin-sidebar__footer\">&copy; 1998&ndash;2026 Torsten Rentsch</div>\n";
        $s .= "</nav>\n";

        return $s;
    }

    /**
     * get an alert box
     *
     * @param string $sMessage message to display
     * @param string $sType alert type: 'error', 'success', or 'warning'
     * @return string alert HTML
     */
    protected function _getAlert(string $sMessage, string $sType = 'error'): string
    {
        return '<div class="pxm-alert pxm-alert--' . htmlspecialchars($sType) . '">'
            . htmlspecialchars($sMessage)
            . "</div>\n";
    }

    /**
     * get a text input formfield
     *
     * When $sDesc is provided, outputs a complete .pxm-form-group row.
     * Without $sDesc, outputs the bare input element for inline use.
     *
     * @param string $sName name of the formfield
     * @param int $iMaxLength maximum length of the formfield
     * @param string $sValue value of the formfield
     * @param string $sDesc description/label of the formfield
     * @return string html formfield
     */
    protected function _getTextField(string $sName, int $iMaxLength, string $sValue, string $sDesc = ''): string
    {
        $sInput = '<input type="text" id="' . htmlspecialchars($sName) . '" name="' . htmlspecialchars($sName) . '"'
            . ' value="' . htmlspecialchars($sValue) . '"'
            . " maxlength=\"$iMaxLength\">\n";

        if (!empty($sDesc)) {
            return "<div class=\"pxm-form-group\">\n"
                . '<label for="' . htmlspecialchars($sName) . '">' . htmlspecialchars($sDesc) . "</label>\n"
                . '<div class="pxm-field">' . $sInput . "</div>\n"
                . "</div>\n";
        }
        return $sInput;
    }

    /**
     * get a password input formfield
     *
     * When $sDesc is provided, outputs a complete .pxm-form-group row.
     * Without $sDesc, outputs the bare input element for inline use.
     *
     * @param string $sName name of the formfield
     * @param int $iMaxLength maximum length of the formfield
     * @param string $sDesc description/label of the formfield
     * @return string html formfield
     */
    protected function _getPasswordField(string $sName, int $iMaxLength, string $sDesc = ''): string
    {
        $sInput = '<input type="password" id="' . htmlspecialchars($sName) . '" name="' . htmlspecialchars($sName) . '"'
            . " maxlength=\"$iMaxLength\" autocomplete=\"current-password\">\n";

        if (!empty($sDesc)) {
            return "<div class=\"pxm-form-group\">\n"
                . '<label for="' . htmlspecialchars($sName) . '">' . htmlspecialchars($sDesc) . "</label>\n"
                . '<div class="pxm-field">' . $sInput . "</div>\n"
                . "</div>\n";
        }
        return $sInput;
    }

    /**
     * get a hidden formfield
     *
     * @param string $sName name of the formfield
     * @param string $sValue value of the formfield
     * @return string html formfield
     */
    protected function _getHiddenField(string $sName, string $sValue): string
    {
        return '<input type="hidden" name="' . htmlspecialchars($sName) . '" value="' . htmlspecialchars($sValue) . "\">\n";
    }

    /**
     * get a checkbox formfield
     *
     * When $sDesc is provided, outputs a complete .pxm-form-group row.
     * Without $sDesc, outputs the bare input element for inline use.
     *
     * @param string $sName name of the formfield
     * @param string $sValue value of the formfield
     * @param string $sDesc description/label of the formfield
     * @param bool $bIsChecked is the formfield checked?
     * @param string $sAdditionalHtml additional html attributes (onclick etc)
     * @return string html formfield
     */
    protected function _getCheckboxField(string $sName, string $sValue, string $sDesc = '', bool $bIsChecked = false, string $sAdditionalHtml = ''): string
    {
        $sChecked = $bIsChecked ? ' checked' : '';
        $sInput = '<input type="checkbox" id="' . htmlspecialchars($sName) . '" name="' . htmlspecialchars($sName) . '"'
            . ' value="' . htmlspecialchars($sValue) . "\"$sChecked"
            . (empty($sAdditionalHtml) ? '' : " $sAdditionalHtml") . ">\n";

        if (!empty($sDesc)) {
            return "<div class=\"pxm-form-group\">\n"
                . '<label for="' . htmlspecialchars($sName) . '">' . htmlspecialchars($sDesc) . "</label>\n"
                . '<div class="pxm-field">' . $sInput . "</div>\n"
                . "</div>\n";
        }
        return $sInput;
    }

    /**
     * get a radio formfield
     *
     * When $sDesc is provided, outputs a complete .pxm-form-group row.
     * Without $sDesc, outputs the bare input element for inline use.
     *
     * @param string $sName name of the formfield
     * @param string $sValue value of the formfield
     * @param string $sDesc description/label of the formfield
     * @param bool $bIsChecked is the formfield checked?
     * @return string html formfield
     */
    protected function _getRadioField(string $sName, string $sValue, string $sDesc = '', bool $bIsChecked = false): string
    {
        $sId = htmlspecialchars($sName) . '_' . htmlspecialchars($sValue);
        $sChecked = $bIsChecked ? ' checked' : '';
        $sInput = "<input type=\"radio\" id=\"$sId\" name=\"" . htmlspecialchars($sName) . '"'
            . ' value="' . htmlspecialchars($sValue) . "\"$sChecked>\n";

        if (!empty($sDesc)) {
            return "<div class=\"pxm-form-group\">\n"
                . "<label for=\"$sId\">" . htmlspecialchars($sDesc) . "</label>\n"
                . '<div class="pxm-field">' . $sInput . "</div>\n"
                . "</div>\n";
        }
        return $sInput;
    }
}
