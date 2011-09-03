<?php

class Netzarbeiter_GroupsCatalog2_Model_Entity_Attribute_Frontend_Customergroups
	extends Mage_Eav_Model_Entity_Attribute_Frontend_Abstract
{
	public function getLabel()
	{
		$storeId = (int)Mage::app()->getRequest()->getParam('store', 0);
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		$setting = $helper->getModeSettingForEntityType($this->getAttribute()->getEntityTypeId(), $storeId);
		$label = '';
		switch ($setting)
		{
			case Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_HIDE_BY_DEFAULT:
				// Show products by default
				$label = $helper->__('Show to Groups');
				break;
			case Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_SHOW_BY_DEFAULT:
				$label = $helper->__('Hide from Groups');
				break;
		}

		return $label;
	}
}
