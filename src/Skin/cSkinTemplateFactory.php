<?php

namespace PXMBoard\Skin;

/**
 * factory class for template abstraction
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cSkinTemplateFactory
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
     * instanciates and returns the selected template object
     *
     * @param  string $sTemplateType type of the templates
     * @param string $sSkinDir skin directory
     * @return cSkinTemplate|null template object
     */
    public static function getTemplateObject(string $sTemplateType, string $sSkinDir): ?cSkinTemplate
    {
        $objTemplate = null;
        if (preg_match('/^[a-zA-Z]+$/', $sTemplateType)) {
            $sTemplateType = __NAMESPACE__ . '\\cSkinTemplate' . $sTemplateType;
            $objTemplate = new $sTemplateType($sSkinDir);
        }
        return $objTemplate;
    }
}
