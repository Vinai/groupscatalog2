<?php

class Netzarbeiter_GroupsCatalog2_Helper_Data extends Mage_Core_Helper_Abstract
{
	const MODE_HIDE_BY_DEFAULT = 'hide';
	const MODE_SHOW_BY_DEFAULT = 'show';
	const USE_DEFAULT = -2;
	const USE_NONE = -1;

	const XML_CONFIG_PRODUCT_MODE = 'netzarbeiter_groupscatalog2/general/product_mode';
	const XML_CONFIG_CATEGORY_MODE = 'netzarbeiter_groupscatalog2/general/category_mode';

	/* @var $_groups Mage_Customer_Model_Resource_Group_Collection */
	protected $_groups;

	/**
	 * Return all groups including the NOT_LOGGED_IN group that is normally hidden
	 *
	 * @return Mage_Customer_Model_Resource_Group_Collection
	 */
	public function getGroups()
	{
		if (is_null($this->_groups))
		{
			$this->_groups = Mage::getResourceModel('customer/group_collection')->load();
		}
		return $this->_groups;
	}

	public function getModeSettingForEntityType($entityType, $store)
	{
		$path = $this->getModeSettingPathForEntityType($entityType);
		return Mage::getStoreConfig($path, $store);
	}

	public function getModeSettingPathForEntityType($entityType)
	{
		$entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);
		$path = '';
		switch ($entityType->getEntityTypeCode())
		{
			default:
			case Mage_Catalog_Model_Product::ENTITY:
				$path = self::XML_CONFIG_PRODUCT_MODE;
			break;

			case Mage_Catalog_Model_Category::ENTITY:
				$path = self::XML_CONFIG_PRODUCT_MODE;
			break;
		}
		return $path;
	}
}
