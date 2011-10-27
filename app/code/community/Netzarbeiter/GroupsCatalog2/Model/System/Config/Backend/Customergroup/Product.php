<?php
 
class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Product
	extends Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Abstract
{
	/**
	 * Return the indexer code
	 *
	 * @return string
	 * @see Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Abstract::_afterSave()
	 */
	protected function _getIndexerCode()
	{
		return 'groupscatalog2_product';
	}
}
