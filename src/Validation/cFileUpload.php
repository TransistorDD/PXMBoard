<?php

namespace PXMBoard\Validation;

/**
 * handles file uploads
 *
 * @link      https://github.com/TransistorDD/PXMBoard
 * @author    Torsten Rentsch <forum@torsten-rentsch.de>
 * @copyright 2001-2026 Torsten Rentsch
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */
class cFileUpload{

	protected string $m_sFileVarName = "";

	/**
	 * Constructor
	 * 
	 * @param string $sFileVarName name of the file variable
	 * @return void
	 */
	public function __construct($sFileVarName){
		$this->m_sFileVarName = $sFileVarName;
	}

	/**
	 * was this file uploaded via HTTP POST?
	 * 
	 * @return boolean is / is not an uploaded file
	 */
	public function isUploadedFile(){
		if(isset($_FILES[$this->m_sFileVarName])){
			return is_uploaded_file($_FILES[$this->m_sFileVarName]["tmp_name"]);
		}
		else return false;
	}

	/**
	 * get the original name of the file
	 * 
	 * @return string original name of the file
	 */
	public function getFileName(){
		return $_FILES[$this->m_sFileVarName]["name"];
	}

	/**
	 * get the mime type of the file
	 * 
	 * @return string mime type of the file
	 */
	public function getFileType(){
		return $_FILES[$this->m_sFileVarName]["type"];
	}

	/**
	 * get the size of the file
	 * 
	 * @return string size of the file
	 */
	public function getFileSize(){
		return $_FILES[$this->m_sFileVarName]["size"];
	}

	/**
	 * get the temporary filename of the file
	 * 
	 * @return string temporary filename of the file
	 */
	public function getFileTmpName(){
		return $_FILES[$this->m_sFileVarName]["tmp_name"];
	}
}
?>