<?php

require_once(SRCDIR . '/Parser/cParser.php');
/**
 * Plain text to HTML parser (escapes HTML entities)
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cPlainTextParser extends cParser
{
    /**
     * parse the given text
     *
     * @param string $sText text to be parsed
     * @return string parsed text
     */
    public function parse(string $sText): string
    {
        $sReturnText = htmlspecialchars($sText);
        if (!empty($this->m_sQuoteTag)) {
            $sReturnText = '<'.$this->m_sQuoteTag.'>'.$sReturnText.'</'.$this->m_sQuoteTag.'>';
        }
        return $sReturnText;
    }
}
