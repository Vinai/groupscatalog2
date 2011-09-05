<?php

class Netzarbeiter_GroupsCatalog2_Helper_Data extends Mage_Core_Helper_Abstract
{
	const MODE_HIDE_BY_DEFAULT = 'hide';
	const MODE_SHOW_BY_DEFAULT = 'show';
	const USE_DEFAULT = -2;
	const USE_NONE = -1;

	const XML_CONFIG_PRODUCT_MODE = 'netzarbeiter_groupscatalog2/general/product_mode';
	const XML_CONFIG_CATEGORY_MODE = 'netzarbeiter_groupscatalog2/general/category_mode';
	const XML_CONFIG_PRODUCT_DEFAULT_PREFIX = 'netzarbeiter_groupscatalog2/general/product_default_';
	const XML_CONFIG_CATEGORY_DEFAULT_PREFIX = 'netzarbeiter_groupscatalog2/general/category_default_';

	const HIDE_GROUPS_ATTRIBUTE = 'groupscatalog2_groups';

	/* @var $_groups Mage_Customer_Model_Resource_Group_Collection */
	protected $_groups;

	protected $_visibilityByStore = array();

	/**
	 * Return all groups including the NOT_LOGGED_IN group that is normally hidden.
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

	/**
	 * Return true if the entity should be visible for the specified customer group id.
	 * If no customer group id is specified, use the customer group id from the current customer session.
	 *
	 * @param Mage_Catalog_Model_Abstract $entity
	 * @param int|null $customerGroupId
	 * @return bool
	 */
	public function isEntityVisible(Mage_Catalog_Model_Abstract $entity, $customerGroupId = null)
	{
		if (is_null($customerGroupId))
		{
			$customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
		}
		$visibleGroups = $this->getEntityVisibleGroups($entity);
		return in_array($customerGroupId, $visibleGroups);
	}

	/**
	 * Return an array with all customer groups the specified catalog entity should be visible to
	 *
	 * @param Mage_Catalog_Model_Abstract $entity
	 * @return array
	 */
	public function getEntityVisibleGroups(Mage_Catalog_Model_Abstract $entity)
	{
		$entitySettings = (array) $entity->getData(self::HIDE_GROUPS_ATTRIBUTE);
		$entityType = $entity->getResource()->getEntityType();
		$store = $entity->getStore();

		if (in_array(self::USE_DEFAULT, $entitySettings))
		{
			return $this->getEntityVisibleDefaultGroupIds($entityType, $store);
		}
		
		$mode = $this->getModeSettingByEntityType($entityType, $store);
		return $this->_applyExtensionModeSetting($entitySettings, $mode);
	}

	/**
	 * Return the extension mode ('hide' or 'show') for the specified entity type and store.
	 *
	 * @param string $entityType catalog_category|catalog_product
	 * @param int|string|Mage_Core_Model_Store $store
	 * @return string
	 */
	public function getModeSettingByEntityType($entityType, $store = null)
	{
		$path = $this->_getModeSettingPathByEntityType($entityType);
		return (string) Mage::getStoreConfig($path, $store);
	}

	/**
	 * Return the customer groups selected as the default in the
	 * system config for the specified entity type.
	 * The setting is cached in an array property $_visibilityByStore.
	 *
	 * @param string|int|Mage_Eav_Model_Entity_Type $entityType
	 * @param null|int|string|Mage_Core_Model_Store $store
	 * @return array
	 */
	public function getEntityVisibleDefaultGroupIds($entityType, $store = null)
	{
		$store = Mage::app()->getStore($store);
		$entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);

		$storeId = $store->getId();
		$entityTypeCode = $entityType->getEntityTypeCode();

		if (! array_key_exists($entityTypeCode, $this->_visibilityByStore))
		{
			$this->_visibilityByStore[$entityTypeCode] = array();
		}

		if (! array_key_exists($storeId, $this->_visibilityByStore[$entityTypeCode]))
		{
			$this->_visibilityByStore[$entityTypeCode][$storeId] = $this->_getEntityVisibleDefaultGroupIds($entityType, $store);
		}
		return $this->_visibilityByStore[$entityTypeCode][$storeId];
	}

	/**
	 * Return the xml path to the mode setting for the specified entity type.
	 * 
	 * @param string|int|Mage_Eav_Model_Entity_Type $entityType
	 * @return string
	 */
	protected function _getModeSettingPathByEntityType($entityType)
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

	/**
	 * See self::getEntityVisibleDefaultGroupIds() for a detailed description.
	 * 
	 * @param string|int|Mage_Eav_Model_Entity_Type $entityType
	 * @param null|int|string|Mage_Core_Model_Store $store
	 * @return array
	 */
	protected function _getEntityVisibleDefaultGroupIds($entityType, $store = null)
	{
		$path = $this->_getEntityVisibilityDefaultsPathByEntityType($entityType, $store);
		$groupIds = Mage::getStoreConfig($path, $store);
		if (null === $groupIds)
		{
			$groupIds = array();
		}
		else
		{
			$groupIds = explode(',', (string) $groupIds);

			// USE_NONE is a pseudo group id for "none selected"
			if (in_array(self::USE_NONE, $groupIds))
			{
				$groupIds = array();
			}
		}
		$mode = $this->getModeSettingByEntityType($entityType, $store);
		return $this->_applyExtensionModeSetting($groupIds, $mode);
	}

	/**
	 * Diff the group id array against an array with all group ids if the
	 * extension mode is 'show' by default.
	 *
	 * @param array $groupIds
	 * @param string $mode hide|show
	 * @return array
	 */
	protected function _applyExtensionModeSetting(array $groupIds, $mode)
	{
		if (self::MODE_SHOW_BY_DEFAULT === $mode)
		{
			// Because by default the specified entity is visible, the configured groups should NOT
			// see the entities. Because of this the array needs to be "inverted".
			$allGroupIds = array_keys($this->getGroups()->getItems());
			$groupIds = array_diff($allGroupIds, $groupIds);
			return $groupIds;
		}
		return $groupIds;
	}

	/**
	 * Return the xpath to the config setting for the customer groups selected as
	 * default in the system config for the specified entity type.
	 *
	 * @param string|int|Mage_Eav_Model_Entity_Type $entityType
	 * @param null|int|string|Mage_Core_Model_Store $store
	 * @return string
	 */
	protected function _getEntityVisibilityDefaultsPathByEntityType($entityType, $store = null)
	{
		$prefix = $this->_getEntityVisibilityDefaultsPathPrefixByEntityType($entityType);
		$mode = $this->getModeSettingByEntityType($entityType, $store);
		return $prefix . $mode;
	}

	/**
	 * Return the xpath prefix to the config setting for the customer groups
	 * selected as default in the system config for the specified entity type.
	 *
	 * @param string|int|Mage_Eav_Model_Entity_Type $entityType
	 * @return string
	 */
	protected function _getEntityVisibilityDefaultsPathPrefixByEntityType($entityType)
	{
		$entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);
		$path = '';
		switch ($entityType->getEntityTypeCode())
		{
			default:
			case Mage_Catalog_Model_Product::ENTITY:
				$path = self::XML_CONFIG_PRODUCT_DEFAULT_PREFIX;
			break;

			case Mage_Catalog_Model_Category::ENTITY:
				$path = self::XML_CONFIG_CATEGORY_DEFAULT_PREFIX;
			break;
		}
		return $path;
	}
}