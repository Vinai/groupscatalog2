<?php
/**
 * Netzarbeiter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog2
 * @copyright  Copyright (c) 2011 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
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
