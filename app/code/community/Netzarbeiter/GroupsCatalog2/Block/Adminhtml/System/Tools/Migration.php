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

/**
 * Migration Steps:
 *
 *  1) Remove any attribute models for the old GroupsCatalog extension
 *  2) Deactivate old GroupsCatalog extension
 *  3) Migrate system configuration settings
 *  4) Migrate product settings
 *  5) Migrate category settings
 *  6) Update GroupsCatalog2 index
 *  7) Remove attributes of old extension
 *  8) Remove configuration settings of old extension
 *  9) Remove app/etc/modules/Netzarbeiter_GroupsCatalog.xml
 * 10) Optional: Now it's save to remove all other files of the old module
 *
 * Note: leave records in core_resource from old extension to prohibit accidental reinstall
 */
class Netzarbeiter_GroupsCatalog2_Block_Adminhtml_System_Tools_Migration
    extends Mage_Adminhtml_Block_Template
{
    const STATUS_INSTALLED = 1;
    const STATUS_INSTALLED_ACTIVE = 2;

    protected $_attributeModels = array();

    /**
     * Cache the installation status of the old GroupsCatalog module
     *
     * @var int $_groupsCatalogInstallationStatus
     */
    protected $_groupsCatalogInstallationStatus;

    /**
     * Return true if the old GroupsCatalog module is installed
     *
     * @return bool
     */
    public function isGroupsCatalogInstalled()
    {
        return (bool)($this->getGroupsCatalogInstallationStatus() & self::STATUS_INSTALLED);
    }

    /**
     * Return true if the old GroupsCatalog module is installed and active
     *
     * @return bool
     */
    public function isGroupsCatalogActive()
    {
        return (bool)($this->getGroupsCatalogInstallationStatus() & self::STATUS_INSTALLED_ACTIVE);
    }

    /**
     * Return the old GroupsCatalog module installation status as a bitmask.
     * 1 = installed
     * 2 = installed and active
     *
     * @return int
     */
    public function getGroupsCatalogInstallationStatus()
    {
        if (is_null($this->_groupsCatalogInstallationStatus)) {
            $this->_groupsCatalogInstallationStatus = 0;
            $config = Mage::getConfig()->getNode('modules/Netzarbeiter_GroupsCatalog');
            if ($config) {
                $this->_groupsCatalogInstallationStatus |= self::STATUS_INSTALLED;
                if ($config->active && in_array((string) $config->active, array('1', 'true'), true)) {
                    $this->_groupsCatalogInstallationStatus |= self::STATUS_INSTALLED_ACTIVE;
                }
            }
        }
        return $this->_groupsCatalogInstallationStatus;
    }

    /**
     * Return true if system configuration options for the old GroupsCatalog extension are loaded
     *
     * @return bool
     */
    public function isGroupsCatalogConfigurationAvailable()
    {
        return (bool)Mage::getStoreConfig('catalog/groupscatalog', Mage_Core_Model_Store::ADMIN_CODE);
    }

    /**
     * Return the product GroupsCatalog attribute model
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     */
    protected function _getProductGroupsCatalogAttribute()
    {
        return $this->_getGroupsCatalogAttribute(Mage_Catalog_Model_Product::ENTITY);
    }

    /**
     * Return the category GroupsCatalog attribute model
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     */
    protected function _getCategoryGroupsCatalogAttribute()
    {
        return $this->_getGroupsCatalogAttribute(Mage_Catalog_Model_Category::ENTITY);
    }

    /**
     * Return the GroupsCatalog attribute model for the specified entity type
     *
     * @param $entityCode
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     */
    protected function _getGroupsCatalogAttribute($entityCode)
    {
        if (!isset($this->_attributeModels[$entityCode])) {
            $this->_attributeModels[$entityCode] = Mage::getSingleton('eav/config')
                    ->getAttribute(
                        $entityCode, Netzarbeiter_GroupsCatalog2_Helper_Migration::GROUPSCATALOG1_ATTRIBUTE_CODE
                    );
        }
        return $this->_attributeModels[$entityCode];
    }

    /**
     * Return true if either the product or the category attribute for the old GroupsCatalog attribute is available
     *
     * @return bool
     */
    public function isGroupsCatalogAttributeAvailable()
    {
        $attribute = $this->_getProductGroupsCatalogAttribute();
        if (!$attribute->getAttributeId()) {
            $attribute = $this->_getCategoryGroupsCatalogAttribute();
        }
        return (bool)$attribute->getAttributeId();
    }

    /**
     * Return true if one of the product or category GroupsCatalog attributes has a backend or source model specified
     *
     * @return bool
     */
    public function areAttributeModelsSpecified()
    {
        // Check product attribute first
        $attribute = $this->_getProductGroupsCatalogAttribute();
        if ($attribute->getAttributeId() && ($attribute->getBackendModel() || $attribute->getSourceModel())) {
            return true;
        }
        // Product attribute is either not present or has no models specified, check category attribute
        $attribute = $this->_getCategoryGroupsCatalogAttribute();
        return (bool)$attribute->getAttributeId() && ($attribute->getBackendModel() || $attribute->getSourceModel());
    }

    /**
     * Return true if a migration might be possible
     *
     * @return bool
     */
    public function isMigrationAvailable()
    {
        if ($this->isGroupsCatalogInstalled() &&
                !$this->isGroupsCatalogActive() &&
                $this->isGroupsCatalogConfigurationAvailable() &&
                $this->isGroupsCatalogAttributeAvailable()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Return true if an entry for the old GroupsCatalog extension is present in the core_resource table
     *
     * @return bool
     */
    public function isGroupsCatalogInstallResourcePresent()
    {
        return 0 < Mage::getResourceSingleton('core/resource')->getDbVersion('groupscatalog_setup');
    }
}
