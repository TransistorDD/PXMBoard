<?php

/**
 * text parsing
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cParser
{
    protected string $m_sQuoteTag;				// tag for text quoting without <>
    protected bool $m_sDoQuote;					// should the text be quoted?

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->m_sQuoteTag = '';
        $this->m_sDoQuote = false;
    }

    /**
     * parse the given text
     *
     * @param string $sText text to be parsed
     * @return string parsed text
     */
    public function parse(string $sText): string
    {
        return $sText;
    }

    /**
     * set the tag for text quoting without <>
     *
     * @param string $sQuoteTag tag for text quoting without <>
     * @return void
     */
    public function setQuoteTag(string $sQuoteTag): void
    {
        $this->m_sQuoteTag = $sQuoteTag;
    }

    /**
     * should the text be quoted?
     *
     * @param bool $sDoQuote should the text be quoted?
     * @return void
     */
    public function setDoQuote(bool $sDoQuote): void
    {
        $this->m_sDoQuote = $sDoQuote;
    }
}
