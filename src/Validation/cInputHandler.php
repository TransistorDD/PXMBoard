<?php

declare(strict_types=1);
require_once(SRCDIR . '/Validation/cStringValidations.php');
/**
 * Handles the input from the web
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cInputHandler
{
    /**
     * get a string variable from the web
     *
     * @param string $sVarName name of the variable
     * @param string $sValidName name of the validation that should be used
     * @param bool $bSearchPost search post vars for this variable
     * @param bool $bSearchGet search get vars for this variable
     * @param string $sAddFunction name of an additional function that should be called (e.g. trim)
     * @return string value of the variable
     */
    public function getStringFormVar(string $sVarName, string $sValidName, bool $bSearchPost, bool $bSearchGet, string $sAddFunction = ''): string
    {

        $sValue = '';
        if (($bSearchPost) && isset($_POST[$sVarName])) {
            $sValue = $_POST[$sVarName];
        } elseif (($bSearchGet) && isset($_GET[$sVarName])) {
            $sValue = $_GET[$sVarName];
        }

        $sValue = str_replace("\r", "\n", str_replace("\r\n", "\n", $sValue));

        // Strip control characters; \n (0x0A) is intentionally preserved
        $sValue = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/u', '', $sValue);

        if (strlen($sAddFunction) > 0) {
            $sValue = @$sAddFunction($sValue);
        }

        if (!empty($sValidName)) {
            $sValidNameLower = strtolower($sValidName);
            $sValue = cStringValidations::truncate($sValue, $sValidNameLower);
            if ($sValidNameLower === 'type' && !cStringValidations::isAlpha($sValue)) {
                $sValue = '';
            }
        }
        return $sValue;
    }

    /**
     * get a integer variable from the web
     *
     * @param string $sVarName name of the variable
     * @param bool $bSearchPost search post vars for this variable
     * @param bool $bSearchGet search get vars for this variable
     * @param bool $bForcePositive set negative numbers to 0
     * @return int value of the variable
     */
    public function getIntFormVar(string $sVarName, bool $bSearchPost, bool $bSearchGet, bool $bForcePositive = false): int
    {
        $iValue = 0;
        if (($bSearchPost) && isset($_POST[$sVarName])) {
            $iValue = intval($_POST[$sVarName]);
        } elseif (($bSearchGet) && isset($_GET[$sVarName])) {
            $iValue = intval($_GET[$sVarName]);
        }
        if (($iValue < 0) && ($bForcePositive)) {
            $iValue = 0;
        }
        return $iValue;
    }

    /**
     * get a array variable from the web
     *
     * @param string $sVarName name of the variable
     * @param bool $bSearchPost search post vars for this variable
     * @param bool $bSearchGet search get vars for this variable
     * @param bool $bForceUnique make the array elements unique
     * @param string $sAddFunction name of an additional function that should be called (e.g. trim)
     * @param string $sValidName name of the validation that should be used
     * @return array value of the variable
     */
    public function getArrFormVar(string $sVarName, bool $bSearchPost, bool $bSearchGet, bool $bForceUnique = false, string $sAddFunction = '', string $sValidName = ''): array
    {
        $sValidNameLower = !empty($sValidName) ? strtolower($sValidName) : '';

        $arrValues = [];
        if (($bSearchPost) && isset($_POST[$sVarName])) {
            $arrValues = $_POST[$sVarName];
        } elseif (($bSearchGet) && isset($_GET[$sVarName])) {
            $arrValues = $_GET[$sVarName];
        }

        if ($sAddFunction || $sValidNameLower) {
            foreach ($arrValues as $mKey => $mVal) {
                $val = $mVal;
                if ($sAddFunction) {
                    $val = @$sAddFunction($val);
                }
                if ($sValidNameLower) {
                    $val = cStringValidations::truncate($val, $sValidNameLower);
                }
                $arrValues[$mKey] = $val;
            }
        }
        if ($bForceUnique) {
            $arrValues = array_unique($arrValues);
        }
        reset($arrValues);
        return $arrValues;
    }

    /**
     * get a file upload object
     *
     * @param string $sVarName name of the file variable
     * @return object file upload object
     */
    public function getFileFormObject(string $sVarName): object
    {
        require_once(SRCDIR . '/Validation/cFileUpload.php');
        $objFileUpload = new cFileUpload($sVarName);
        return $objFileUpload;
    }

    /**
     * get the size of an input type
     *
     * @param string $sVaidatorType type of the variable
     * @return int|null size of an input type, or null if unknown
     */
    public function getInputSize(string $sVaidatorType): ?int
    {
        return cStringValidations::getLength($sVaidatorType);
    }

    /**
     * get all input size mappings
     *
     * @return array<string,int>
     */
    public function getInputSizes(): array
    {
        return cStringValidations::getAllLimits();
    }
}
