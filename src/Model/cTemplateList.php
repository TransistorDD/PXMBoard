<?php

require_once(SRCDIR . '/Model/cTemplate.php');
/**
 * handles the templates of the system
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cTemplateList
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * get all templates
     *
     * @return array templates
     */
    public function getList(): array
    {


        $arrTemplates = [];

        if ($objResultSet = cDBFactory::getInstance()->executeQuery('SELECT te_id,te_message,te_name,te_description FROM pxm_template ORDER BY te_id ASC')) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objTemplate = new cTemplate();
                $objTemplate->setId($objResultRow->te_id);
                $objTemplate->setMessage($objResultRow->te_message);
                $objTemplate->setName($objResultRow->te_name);
                $objTemplate->setDescription($objResultRow->te_description);

                $arrTemplates[intval($objResultRow->te_id)] = $objTemplate;
            }
            $objResultSet->freeResult();
        }
        return $arrTemplates;
    }
}
