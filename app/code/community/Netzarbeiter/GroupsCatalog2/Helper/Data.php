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
 * @copyright  Copyright (c) 2014 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Netzarbeiter_GroupsCatalog2_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MODE_HIDE_BY_DEFAULT = 'hide';
    const MODE_SHOW_BY_DEFAULT = 'show';
    const USE_DEFAULT = -2;
    const USE_NONE = -1;
    const LABEL_DEFAULT = '[ USE DEFAULT ]';
    const LABEL_NONE = '[ NONE ]';

    const XML_CONFIG_PRODUCT_MODE = 'netzarbeiter_groupscatalog2/general/product_mode';
    const XML_CONFIG_CATEGORY_MODE = 'netzarbeiter_groupscatalog2/general/category_mode';
    const XML_CONFIG_PRODUCT_DEFAULT_PREFIX = 'netzarbeiter_groupscatalog2/general/product_default_';
    const XML_CONFIG_CATEGORY_DEFAULT_PREFIX = 'netzarbeiter_groupscatalog2/general/category_default_';
    const XML_CONFIG_DISABLED_ROUTES = 'global/netzarbeiter_groupscatalog2/disabled_on_routes';

    const HIDE_GROUPS_ATTRIBUTE = 'groupscatalog2_groups';
    const HIDE_GROUPS_ATTRIBUTE_STATE_CACHE = 'groupscatalog2_groups_state_cache';
    const CUSTOMER_GROUP_CACHE_TAG = 'groupscatalog2_customer_group';

    /**
     * Customer group collection instance
     *
     * @var $_groups Mage_Customer_Model_Resource_Group_Collection
     */
    protected $_groups;

    /**
     * Array of all customer groups
     *
     * @var $_groupIds array
     */
    protected $_groupIds;

    /**
     * Cache the groups that may see an entity type by store
     *
     * @var $_visibilityByStore array
     */
    protected $_visibilityByStore = array();

    /**
     * If set to false groupscatalog2 filtering is skipped
     *
     * @var bool|null
     */
    protected $_moduleActive = null;

    /**
     * On these routes the module is inactive.
     * This is important for IPN notifications and API requests to succeed
     *
     * @var array
     */
    protected $_disabledOnRoutes;

    /**
     * Return a configuration setting from within the netzarbeiter_groupscatalog2/general section.
     *
     * Just in case I decide to change the configuration path in future, this file should be the
     * only one where the config path is hardcoded.
     *
     * @param string $field
     * @param int|string|Mage_Core_Model_Store $store
     * @return mixed
     */
    public function getConfig($field, $store = null)
    {
        return Mage::getStoreConfig('netzarbeiter_groupscatalog2/general/' . $field, $store);
    }

    /**
     * Return all groups including the NOT_LOGGED_IN group that is normally hidden.
     *
     * @return Mage_Customer_Model_Resource_Group_Collection
     */
    public function getGroups()
    {
        if (is_null($this->_groups)) {
            $this->_groups = Mage::getResourceModel('customer/group_collection')->load();
        }
        return $this->_groups;
    }

    /**
     * Return the ids of all customer groups
     *
     * @return array
     */
    public function getCustomerGroupIds()
    {
        if (is_null($this->_groupIds)) {
            $this->_groupIds = array_keys($this->getGroups()->getItems());
        }
        return $this->_groupIds;
    }

    /**
     * Return if the module is active for the current store view
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @param bool $checkAdmin If false don't return false just because the specified store is the admin view
     * @return bool
     */
    public function isModuleActive($store = null, $checkAdmin = true)
    {
        $store = Mage::app()->getStore($store);
        if ($checkAdmin && $store->isAdmin()) {
            return false;
        }

        // Temporary setting has higher priority then system config setting
        if (null !== $this->getModuleActiveFlag()) {
            return $this->getModuleActiveFlag();
        }

        $setting = $this->getConfig('is_active', $store);
        return (bool)$setting;
    }

    public function isEntityVisible(Mage_Catalog_Model_Abstract $entity, $customerGroupId = null)
    {
        // if the module is deactivated or a store view all entities are visible
        if (!$this->isModuleActive($entity->getStoreId())) {
            return true;
        }
        // Fix for infinity redirect loop
        if (!$entity->getId()) {
            return true;
        }
        
        $cachedState = $entity->getData(self::HIDE_GROUPS_ATTRIBUTE_STATE_CACHE);
        if (! is_null($cachedState)) {
            return $cachedState;
        }


        // Default to the current customer group id
        if (is_null($customerGroupId)) {
            $customerGroupId = $this->getCustomerGroupId();
        }

        $groupIds = $entity->getData(self::HIDE_GROUPS_ATTRIBUTE);
        if (is_string($groupIds)) {
            if ('' === $groupIds) {
                // This case will not happen in production:
                // at least USE_DEFAULT or USE_NONE should be in the value array.
                // Just your average paranoia...
                $groupIds = array(self::USE_NONE);
            } else {
                $groupIds = explode(',', $groupIds);
            }
        }
        // When the entity's attribute isn't set, fall back on [USE DEFAULT]
        if ($groupIds === null) {
            $groupIds = array(self::USE_DEFAULT);
        } elseif (!in_array($groupIds[0], array(self::USE_NONE, self::USE_DEFAULT))) {
            // Quick querying the db index table
            $visibility = Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
                ->isEntityVisible($entity, $customerGroupId);
            $entity->setData(self::HIDE_GROUPS_ATTRIBUTE_STATE_CACHE, $visibility);
            return $visibility;
        }

        /* @var $entityType string The entity type code for the specified entity */
        $entityType = $this->getEntityTypeCodeFromEntity($entity);

        if (in_array(self::USE_NONE, $groupIds)) {
            $groupIds = array();
        } elseif (in_array(self::USE_DEFAULT, $groupIds)) {
            // Get the default settings for this entity type without applying the mode settings
            $groupIds = $this->getEntityVisibleDefaultGroupIds($entityType, $entity->getStore(), false);
        }

        // If the configured mode is 'show' the list of group ids must be inverse
        $groupIds = $this->applyConfigModeSettingByStore($groupIds, $entityType, $entity->getStore());

        $visibility = in_array($customerGroupId, $groupIds);
        $entity->setData(self::HIDE_GROUPS_ATTRIBUTE_STATE_CACHE, $visibility);
        return $visibility;
    }

    /**
     * Get the index table-id for the specified entity type
     *
     * @param string|int|Mage_Eav_Model_Entity_Type $entityType
     * @return string
     */
    public function getIndexTableByEntityType($entityType)
    {
        $entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);
        switch ($entityType->getEntityTypeCode()) {
            default:
            case Mage_Catalog_Model_Product::ENTITY:
                $table = 'netzarbeiter_groupscatalog2/product_index';
                break;

            case Mage_Catalog_Model_Category::ENTITY:
                $table = 'netzarbeiter_groupscatalog2/category_index';
                break;
        }
        return $table;
    }

    /**
     * Return the customer id of the current customer
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        return Mage::getSingleton('customer/session')->getCustomerGroupId();
    }

    /**
     * Return the entity type code from a catalog entity
     *
     * @param Mage_Catalog_Model_Abstract $entity
     * @return string
     */
    public function getEntityTypeCodeFromEntity(Mage_Catalog_Model_Abstract $entity)
    {
        // $entity::ENTITY is only possible from PHP 5.3.0, but Magento requires only 5.2.13
        return constant(get_class($entity) . '::ENTITY');
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
        $entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);
        switch ($entityType->getEntityTypeCode()) {
            default:
            case Mage_Catalog_Model_Product::ENTITY:
                $path = self::XML_CONFIG_PRODUCT_MODE;
                break;

            case Mage_Catalog_Model_Category::ENTITY:
                $path = self::XML_CONFIG_CATEGORY_MODE;
                break;
        }

        return (string)Mage::getStoreConfig($path, $store);
    }

    /**
     * Return the customer groups selected as the default in the
     * system config for the specified entity type.
     * The setting is cached in an array property $_visibilityByStore.
     *
     * @param string|int|Mage_Eav_Model_Entity_Type $entityType
     * @param null|int|string|Mage_Core_Model_Store $store
     * @param bool $applyMode
     * @return array
     */
    public function getEntityVisibleDefaultGroupIds($entityType, $store = null, $applyMode = true)
    {
        $store = Mage::app()->getStore($store);
        $entityType = Mage::getSingleton('eav/config')->getEntityType($entityType);

        $storeId = $store->getId();
        $entityTypeCode = $entityType->getEntityTypeCode();

        if (!array_key_exists($entityTypeCode, $this->_visibilityByStore)) {
            $this->_visibilityByStore[$entityTypeCode] = array();
        }

        if (!array_key_exists($storeId, $this->_visibilityByStore[$entityTypeCode])) {
            $this->_visibilityByStore[$entityTypeCode][$storeId] =
                $this->_getEntityVisibleDefaultGroupIds($entityType, $store);
        }
        $groupIds = $this->_visibilityByStore[$entityTypeCode][$storeId];
        if ($applyMode) {
            $mode = $this->getModeSettingByEntityType($entityType, $store);
            $groupIds = $this->applyConfigModeSetting($groupIds, $mode);
        }
        return $groupIds;
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
        $prefix = $this->_getEntityVisibilityDefaultsPathPrefixByEntityType($entityType);
        $mode = $this->_getEntityVisibilityDefaultsPathPostfixByMode(
            $this->getModeSettingByEntityType($entityType, $store)
        );
        $groupIds = Mage::getStoreConfig($prefix . $mode, $store);

        if (null === $groupIds) {
            $groupIds = array();
        } else {
            $groupIds = explode(',', (string)$groupIds);

            // USE_NONE is a pseudo group id for "none selected"
            if (in_array(self::USE_NONE, $groupIds)) {
                $groupIds = array();
            }
        }

        return $groupIds;
    }

    /**
     * Diff the group id array against an array with all group ids if the
     * specified store configuration mode is 'show by default'.
     *
     * @param array $groupIds
     * @param string $mode hide|show
     * @return array
     */
    public function applyConfigModeSetting(array $groupIds, $mode)
    {
        if (self::MODE_SHOW_BY_DEFAULT === $mode) {
            // Because by default the specified entity is visible, the configured groups should NOT
            // see the entities. Because of this the array needs to be "inverted".
            $groupIds = array_values(array_diff($this->getCustomerGroupIds(), $groupIds));
            return $groupIds;
        }
        return array_values($groupIds);
    }

    /**
     * Fetch the mode setting for the specified store and apply the mode setting if applicable
     *
     * @param array $groupIds group ids
     * @param string|int|Mage_Eav_Model_Entity_Type $entityType
     * @param null|int|string|Mage_Core_Model_Store $store
     * @return array
     * @see self::applyConfigModeSetting()
     */
    public function applyConfigModeSettingByStore(array $groupIds, $entityType, $store = null)
    {
        $mode = $this->getModeSettingByEntityType($entityType, $store);
        return $this->applyConfigModeSetting($groupIds, $mode);
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
        switch ($entityType->getEntityTypeCode()) {
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

    /**
     * Return the opposite mode flag, because that is where the default configuration
     * setting for the default groups is stored.
     * If the default is to show all categories, the default categories are under "hide" and
     * vica versa.
     *
     * @param string $mode
     * @return string
     */
    protected function _getEntityVisibilityDefaultsPathPostfixByMode($mode)
    {
        if ($mode == self::MODE_HIDE_BY_DEFAULT) {
            $mode = self::MODE_SHOW_BY_DEFAULT;
        } else {
            $mode = self::MODE_HIDE_BY_DEFAULT;
        }
        return $mode;
    }

    /**
     * Provide ability to (de)activate the extension on the fly
     *
     * @param bool $state
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    public function setModuleActive($state = true)
    {
        $this->_moduleActive = $state;
        return $this;
    }

    /**
     * Reset the module to use the system configuration activation state
     *
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    public function resetActivationState()
    {
        $this->_moduleActive = null;
        return $this;
    }

    /**
     * Return the value of the _moduleActive flag
     *
     * @return bool
     */
    public function getModuleActiveFlag()
    {
        return $this->_moduleActive;
    }

    /**
     * Return the route names on which the module should be inactive
     *
     * @return array
     */
    public function getDisabledOnRoutes()
    {
        if (null == $this->_disabledOnRoutes) {
            $this->_disabledOnRoutes = array_keys(
                Mage::getConfig()->getNode(self::XML_CONFIG_DISABLED_ROUTES)->asArray()
            );
        }
        return $this->_disabledOnRoutes;
    }

    /**
     * Return true if the request is made via the api or one of the other disabled routes
     *
     * @return boolean
     */
    public function isDisabledOnCurrentRoute()
    {
        $currentRoute = Mage::app()->getRequest()->getModuleName();
        return $currentRoute && in_array($currentRoute, $this->getDisabledOnRoutes());
    }

    /**
     * Return the groupscatalog attribute model
     * 
     * @param string $entityType
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getGroupsCatalogAttribute($entityType)
    {
        return Mage::getSingleton('eav/config')->getAttribute($entityType, self::HIDE_GROUPS_ATTRIBUTE);
    }

    /**
     * Return a string of comma separated customer group names.
     * Used when rendering of multiselect fields in the admin interface is turned off.
     * 
     * @param array $value List of customer group ids
     * @return string
     */
    public function getGroupNamesAsString(array $value)
    {
        $list = array();

        $key = array_search(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT, $value);
        if (false !== $key) {
            $list[] = $this->__(Netzarbeiter_GroupsCatalog2_Helper_Data::LABEL_DEFAULT);
            unset($value[$key]);
        }
        $key = array_search(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE, $value);
        if (false !== $key) {
            $list[] = $this->__(Netzarbeiter_GroupsCatalog2_Helper_Data::LABEL_NONE);
            unset($value[$key]);
        }
        if (count($value)) {
            /** @var Mage_Customer_Model_Resource_Group_Collection $groups */
            $groups = Mage::getResourceModel('customer/group_collection');
            $groups->addFieldToFilter('customer_group_id', array('in' => $value));
            $groups->initCache(Mage::app()->getCache(), 'groupscatalog2', array(
                self::CUSTOMER_GROUP_CACHE_TAG
            ));
            foreach ($groups as $group) {
                $list[] = $group->getCustomerGroupCode();
            }
        }
        return implode(', ', $list);
    }
}
