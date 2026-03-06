<?php

namespace PXMBoard\Model;

use PXMBoard\Database\cDBFactory;
use PXMBoard\Enum\ePrivateMessageStatus;

/**
 * private message outbox handling
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPrivateOutboxList extends cPrivateMessageList
{
    /**
     * get the query
     *
     * @return string query
     */
    protected function _getQuery(): string
    {
        $sQuery = 'SELECT p_id,p_subject,p_tstmp,u_id,u_username,u_highlight,p_fromstate FROM pxm_priv_message,pxm_user WHERE ';
        $sQuery .= "p_touserid=u_id AND p_fromuserid=$this->m_iUserId AND p_fromstate!=".ePrivateMessageStatus::DELETED->value;
        $sQuery .= ' ORDER BY p_tstmp DESC';
        return $sQuery;
    }

    /**
     * initalize the member variables with the resultrow from the db
     *
     * @param object $objResultRow resultrow from db query
     * @return bool success / failure
     */
    protected function _setDataFromDb(object $objResultRow): bool
    {
        $this->m_arrResultList[] = ['id'		=> $objResultRow->p_id,
                                    'subject'	=> $objResultRow->p_subject,
                                    'date'		=> date($this->m_sDateFormat, ($objResultRow->p_tstmp + $this->m_iTimeOffset)),
                                    'read'		=> ($objResultRow->p_fromstate == ePrivateMessageStatus::READ->value ? '1' : '0'),
                                    'user'		=> ['id'		=> $objResultRow->u_id,
                                                    'username'	=> $objResultRow->u_username,
                                                    'highlight'	=> $objResultRow->u_highlight]];
        return true;
    }

    /**
     * delete data from database
     *
     * @return bool success / failure
     */
    public function deleteData(): bool
    {
        // set the message to deleted if we are the author
        cDBFactory::getInstance()->executeQuery('UPDATE pxm_priv_message SET p_fromstate='.ePrivateMessageStatus::DELETED->value." WHERE p_fromuserid=$this->m_iUserId");

        // remove all deleted messages from db
        cDBFactory::getInstance()->executeQuery('DELETE FROM pxm_priv_message WHERE p_tostate='.ePrivateMessageStatus::DELETED->value.' AND p_fromstate='.ePrivateMessageStatus::DELETED->value);

        return true;
    }
}
