<?php
 
class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup extends Mage_Core_Model_Config_Data
{
	/**
	 * Sanitize settings
	 * 
	 * @return void
	 */
	protected function _beforeSave()
	{
		$value = $this->getValue();
		if (is_array($value) && 1 < count($value))
		{
			// if USE_NONE is selected remove all other selected groups
			if (in_array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE, $value))
			{
				$value = array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE);
				$this->setValue($value);
			}
		}
		return parent::_beforeSave();
	}
}
