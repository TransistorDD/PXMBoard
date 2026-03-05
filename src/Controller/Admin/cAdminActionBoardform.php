<?php

namespace PXMBoard\Controller\Admin;

use PXMBoard\Enum\eBoardStatus;
use PXMBoard\Model\cBoard;

/**
 * displays the board edit form
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cAdminActionBoardform extends cAdminAction
{
    /**
     * Validate permissions - requires admin rights.
     *
     * @return bool true if user is admin, false otherwise
     */
    public function validateBasePermissionsAndConditions(): bool
    {
        return $this->_requireAdminPermission();
    }

    /**
     * perform the action
     *
     * @return void
     */
    public function performAction(): void
    {

        $this->m_sOutput .= $this->_getHead();

        $iBoardId = $this->m_objInputHandler->getIntFormVar('id', true, true, true);

        $objBoard = new cBoard();

        $sCardTitle = 'add new board';
        if ($iBoardId > 0) {
            if ($objBoard->loadDataById($iBoardId)) {

                $objBoard->updatePosition($this->m_objInputHandler->getIntFormVar('position', true, true, true));

                $objBoard->loadModData();
                $this->m_sOutput .= "<script language=\"JavaScript\">\n";
                $this->m_sOutput .= "  function delbrd()\n  {\n";
                $this->m_sOutput .= "  	result = confirm(\"Remove board $iBoardId?\");\n";
                $this->m_sOutput .= "  	if(result == true) location.href=\"pxmboard.php?mode=admboarddelete&id=$iBoardId\"\n";
                $this->m_sOutput .= "  }\n</script>\n";
                $sCardTitle = 'edit board configuration';
            } else {
                $iBoardId = 0;
            }
        }

        $this->m_sOutput .= "<form action=\"pxmboard.php\" method=\"post\" onsubmit=\"return confirm('update configuration?')\">".$this->_getHiddenField('mode', 'admboardsave');
        $this->m_sOutput .= $this->_getHiddenCsrfField();

        $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">$sCardTitle</div><div class=\"pxm-admin-card__body\">\n";
        $this->m_sOutput .= "<div class=\"pxm-form-group\"><label>ID</label><div class=\"pxm-field\">$iBoardId".$this->_getHiddenField('id', $iBoardId)."</div></div>\n";
        $this->m_sOutput .= $this->_getTextField('name', $this->m_objInputHandler->getInputSize('boardname'), $objBoard->getName(), 'boardname');
        $this->m_sOutput .= $this->_getTextField('desc', $this->m_objInputHandler->getInputSize('boarddescription'), $objBoard->getDescription(), 'description');
        $this->m_sOutput .= '<div class="pxm-form-group"><label>last message</label><div class="pxm-field">'.(($objBoard->getLastMessageTimestamp() > 0) ? date($this->m_objConfig->getDateFormat(), ($objBoard->getLastMessageTimestamp() + $this->m_objConfig->getTimeOffset() * 3600)) : 0)."</div></div>\n";
        // Board status dropdown
        $eStatus = $objBoard->getStatus();
        $this->m_sOutput .= '<div class="pxm-form-group"><label>board status</label><div class="pxm-field"><select name="status" size="1">';
        $this->m_sOutput .= '<option value="1"'.($eStatus === eBoardStatus::PUBLIC ? ' selected' : '').'>Public</option>';
        $this->m_sOutput .= '<option value="2"'.($eStatus === eBoardStatus::MEMBERS_ONLY ? ' selected' : '').'>Members only</option>';
        $this->m_sOutput .= '<option value="3"'.($eStatus === eBoardStatus::READONLY_PUBLIC ? ' selected' : '').'>Read only (public)</option>';
        $this->m_sOutput .= '<option value="4"'.($eStatus === eBoardStatus::READONLY_MEMBERS ? ' selected' : '').'>Read only (members)</option>';
        $this->m_sOutput .= '<option value="5"'.($eStatus === eBoardStatus::CLOSED ? ' selected' : '').'>Closed</option>';
        $this->m_sOutput .= "</select></div></div>\n";
        $this->m_sOutput .= $this->_getCheckboxField('embed_external', '1', 'Embed external content (images, YouTube, Twitch)?', $objBoard->embedExternal());
        $this->m_sOutput .= $this->_getCheckboxField('repl', '1', 'do textreplacements?', $objBoard->doTextReplacements());
        $this->m_sOutput .= $this->_getTextField('date', 5, (string)$objBoard->getThreadListTimeSpan(), 'timespan (days)');

        $sSortMode = $objBoard->getThreadListSortMode();
        $this->m_sOutput .= '<div class="pxm-form-group"><label>sortmode</label><div class="pxm-field"><select name="sort" size="1">';
        $this->m_sOutput .= '<option value="thread"'.((strcasecmp($sSortMode, 'thread') == 0) ? ' selected' : '').'>thread</option>';
        $this->m_sOutput .= '<option value="last"'.((strcasecmp($sSortMode, 'last') == 0) ? ' selected' : '').'>last reply</option>';
        $this->m_sOutput .= '<option value="username"'.((strcasecmp($sSortMode, 'username') == 0) ? ' selected' : '').'>username</option>';
        $this->m_sOutput .= '<option value="subject"'.((strcasecmp($sSortMode, 'subject') == 0) ? ' selected' : '').'>subject</option>';
        $this->m_sOutput .= '<option value="replies"'.((strcasecmp($sSortMode, 'replies') == 0) ? ' selected' : '').'>replies</option>';
        $this->m_sOutput .= '<option value="views"'.((strcasecmp($sSortMode, 'views') == 0) ? ' selected' : '').'>views</option>';
        $this->m_sOutput .= "</select></div></div>\n";
        $this->m_sOutput .= "</div></div>\n";

        $this->m_sOutput .= "<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">moderators</div><div class=\"pxm-admin-card__body\">\n";
        $this->m_sOutput .= '<div class="pxm-form-group"><label>moderators</label><div class="pxm-field"><textarea cols="20" rows="10" name="mod">';

        foreach ($objBoard->getModerators() as $objModerator) {
            $this->m_sOutput .= htmlspecialchars($objModerator->getUserName())."\n";
        }

        $this->m_sOutput .= "</textarea></div></div>\n";
        $this->m_sOutput .= "</div></div>\n";

        $this->m_sOutput .= '<div class="pxm-btn-row">';
        if ($iBoardId > 0) {
            $this->m_sOutput .= '<button type="button" class="pxm-btn pxm-btn--danger" ondblclick="delbrd()">delete board</button> <small>doubleclick to delete this board and corresponding threads</small> ';
        }
        $this->m_sOutput .= '<button type="submit" class="pxm-btn pxm-btn--primary">update data</button> <button type="reset" class="pxm-btn">reset data</button></div>';
        $this->m_sOutput .= '</form>';

        if ($iBoardId > 0) {
            $this->m_sOutput .= "<br>\n<div class=\"pxm-admin-card\"><div class=\"pxm-admin-card__header\">change board position</div><div class=\"pxm-admin-card__body\">\n";
            $this->m_sOutput .= "<p class=\"pxm-hint\">Click a board name to swap its position with the current board.</p>\n";
            $this->m_sOutput .= "<table class=\"pxm-table\"><tbody>\n";

            foreach ($this->_getBoardListArray() as $arrBoard) {
                if ($arrBoard['id'] == $iBoardId) {
                    $this->m_sOutput .= '<tr><td><b>'.htmlspecialchars($arrBoard['name']).'</b></td></tr>';
                } else {
                    $this->m_sOutput .= "<tr><td><a href=\"pxmboard.php?mode=admboardform&id=$iBoardId&position=".$arrBoard['position'].'">'.htmlspecialchars($arrBoard['name']).'</a></td></tr>';
                }
            }
            $this->m_sOutput .= "</tbody></table>\n";
            $this->m_sOutput .= '</div></div>';
        }

        $this->m_sOutput .= $this->_getFooter();
    }
}
