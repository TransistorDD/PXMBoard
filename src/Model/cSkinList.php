<?php

require_once(SRCDIR . '/Model/cSkin.php');
/**
 * handles the skins
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSkinList
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
     * get all skin ids and names
     *
     * @return array skins
     */
    public function getList(): array
    {

        $arrSkins = [];


        if ($objResultSet = cDBFactory::getInstance()->executeQuery("SELECT s1.s_id,s1.s_fieldvalue AS s_name,s2.s_fieldvalue AS s_type FROM pxm_skin s1,pxm_skin s2 WHERE s1.s_id=s2.s_id AND s1.s_fieldname='name' AND s2.s_fieldname='type' ORDER BY s1.s_id")) {
            while ($objResultRow = $objResultSet->getNextResultRowObject()) {

                $objSkin = new cSkin();
                $objSkin->setId($objResultRow->s_id);
                $objSkin->setName($objResultRow->s_name);
                $objSkin->setSupportedTemplateEngines(explode(',', $objResultRow->s_type));

                $arrSkins[] = $objSkin;
            }
            $objResultSet->freeResult();
        }
        return $arrSkins;
    }
}
