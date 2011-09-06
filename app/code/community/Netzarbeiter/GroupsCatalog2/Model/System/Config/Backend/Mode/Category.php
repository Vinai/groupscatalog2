<?php
 
class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Mode_Category
	extends Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Mode_Abstract
{
	/**
	 * Return the indexer code
	 *
	 * @return string
	 * @see Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Mode_Abstract::_afterSave()
	 */
	protected function _getIndexerCode()
	{
		return 'groupscatalog2_category';
	}
}
