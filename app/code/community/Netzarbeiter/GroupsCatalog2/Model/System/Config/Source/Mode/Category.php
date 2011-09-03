<?php

class Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_Mode_Category
{
	/**
	 * Return the mode options for the category configuration
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		return array(
			array(
				'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_SHOW_BY_DEFAULT,
				'label' => $helper->__('Show categories by default')
			),
			array(
				'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_HIDE_BY_DEFAULT,
				'label' => $helper->__('Hide categories by default')
			),
		);
	}
}
