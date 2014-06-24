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

class Netzarbeiter_GroupsCatalog2_Helper_Migration
    extends Mage_Core_Helper_Abstract
{
    /**
     * @const The attribute code of the old groupscatalog module
     */
    const GROUPSCATALOG1_ATTRIBUTE_CODE = 'groupscatalog_hide_group';

    /**
     * @var Netzarbeiter_GroupsCatalog2_Model_Resource_Migration
     */
    protected $_resource;

    public function doStep($step)
    {
        switch ($step) {
            case 'unsetAttributeModels':
                $this->_unsetAttributeModels();
                break;
            case 'deactivateModule':
                $this->_deactivateModule();
                break;
            case 'migrateData':
                $this->_migrateData();
                break;
            case 'cleanupDb':
                $this->_cleanupDb();
                break;
            case 'removeFiles':
                $this->_removeFiles();
                break;
            default:
                Mage::throwException($this->__('Unknown migration step code: %s', $this->escapeHtml($step)));
        }
    }

    protected function _unsetAttributeModels()
    {
        /* @var $installer Mage_Catalog_Model_Resource_Setup */
        $installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
        foreach (array(Mage_Catalog_Model_Product::ENTITY, Mage_Catalog_Model_Category::ENTITY) as $entityCode) {
            $installer->updateAttribute($entityCode, self::GROUPSCATALOG1_ATTRIBUTE_CODE, 'backend_model', '');
            $installer->updateAttribute($entityCode, self::GROUPSCATALOG1_ATTRIBUTE_CODE, 'source_model', '');
        }
    }

    protected function _deactivateModule()
    {
        $file = Mage::getBaseDir('etc') . DS . 'modules' . DS . 'Netzarbeiter_GroupsCatalog.xml';
        $io = new Varien_Io_File();
        if (!$io->fileExists($file)) {
            $message = Mage::helper('netzarbeiter_groupscatalog2')->__(
                "The file app/etc/modules/Netzarbeiter_GroupsCatalog.xml doesn't exist."
            );
            Mage::throwException($message);
        }
        $xml = simplexml_load_file($file);
        if (in_array((string) $xml->modules->Netzarbeiter_GroupsCatalog->active, array('true', '1'), true)) {
            if (!$io->isWriteable($file)) {
                $message = Mage::helper('netzarbeiter_groupscatalog2')->__(
                    'The file app/etc/modules/Netzarbeiter_GroupsCatalog.xml is not writable.<br/>' .
                    'Please fix it and flush the configuration cache, or deactivate the module manually in that file.'
                );
                Mage::throwException($message);
            }
            $xml->modules->Netzarbeiter_GroupsCatalog->active = 'false';
            $xml->asXML($file);
            Mage::app()->cleanCache(Mage_Core_Model_Config::CACHE_TAG);
        }
    }

    protected function _migrateData()
    {
        $this->_migrateSystemConfig();
        $this->_migrateProductSettings();
        $this->_migrateCategorySettings();
        $this->_removeOldAttributes();
    }

    protected function _migrateSystemConfig()
    {
        /* @var $config Mage_Core_Model_Config */
        $config = Mage::getModel('core/config');

        $this->_setupConfigDefaultScope($config);

        $useNone = (string)Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE;
        $store = Mage::app()->getStore('admin');

        // Read defaults
        $defaults = array(
            'netzarbeiter_groupscatalog2/general/is_active' =>
            !(bool)$this->_getConfigValueWithDefault($store, 'catalog/groupscatalog/disable_ext', '0'),
            'netzarbeiter_groupscatalog2/general/product_default_hide' =>
            $this->_getConfigValueWithDefault($store, 'catalog/groupscatalog/default_product_groups', $useNone),
            'netzarbeiter_groupscatalog2/general/category_default_hide' =>
            $this->_getConfigValueWithDefault($store, 'catalog/groupscatalog/default_category_groups', $useNone),
        );

        foreach (Mage::app()->getStores(true) as $store) {
            $scope = $store->getId() == 0 ? 'default' : 'stores';
            $scopeId = $store->getId();
            $settings = array();

            $isActive = !(bool)$this->_getConfigValueWithDefault($store, 'catalog/groupscatalog/disable_ext', '0');
            $settings['netzarbeiter_groupscatalog2/general/is_active'] = $isActive;

            $hideFromGroups = $this->_getConfigValueWithDefault(
                $store, 'catalog/groupscatalog/default_product_groups', $useNone
            );
            $settings['netzarbeiter_groupscatalog2/general/product_default_hide'] = $hideFromGroups;

            $hideFromGroups = $this->_getConfigValueWithDefault(
                $store, 'catalog/groupscatalog/default_category_groups', $useNone
            );
            $settings['netzarbeiter_groupscatalog2/general/category_default_hide'] = $hideFromGroups;

            $this->_updateConfigStoreScope($config, $scope, $scopeId, $settings, $defaults);
        }
    }

    protected function _setupConfigDefaultScope(Mage_Core_Model_Config $config)
    {
        $mode = Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_SHOW_BY_DEFAULT;

        // Default settings
        $config->saveConfig('netzarbeiter_groupscatalog2/general/product_mode', $mode, 'default', 0);
        $config->saveConfig('netzarbeiter_groupscatalog2/general/category_mode', $mode, 'default', 0);
        $config->saveConfig('netzarbeiter_groupscatalog2/general/auto_refresh_block_cache', 1, 'default', 0);
        $config->deleteConfig('netzarbeiter_groupscatalog2/general/product_default_show', 'default', 0);
        $config->deleteConfig('netzarbeiter_groupscatalog2/general/category_default_show', 'default', 0);

        // Remove any website scope setting
        foreach (Mage::app()->getWebsites() as $website) {
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/product_mode', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/category_mode', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/product_default_show', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/category_default_show', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/product_default_hide', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/category_default_hide', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/product_mode', 'websites', $website->getId()
            );
            $config->deleteConfig(
                'netzarbeiter_groupscatalog2/general/category_mode', 'websites', $website->getId()
            );
        }
    }

    protected function _updateConfigStoreScope(Mage_Core_Model_Config $config, $scope, $scopeId, $settings, $defaults)
    {
        foreach ($settings as $path => $value) {
            // Only save if in default scope or value is different from default scope
            if ('default' === $scope || $value !== $defaults[$path]) {
                $config->saveConfig($path, $value, $scope, $scopeId);
            } else {
                $config->deleteConfig($path, $scope, $scopeId);
            }
        }

        // Delete store scope settings so the default scope value will take effect
        if ('default' !== $scope) {
            $config->deleteConfig('netzarbeiter_groupscatalog2/general/product_mode', $scope, $scopeId);
            $config->deleteConfig('netzarbeiter_groupscatalog2/general/category_mode', $scope, $scopeId);
            $config->deleteConfig('netzarbeiter_groupscatalog2/general/product_default_show', $scope, $scopeId);
            $config->deleteConfig('netzarbeiter_groupscatalog2/general/category_default_show', $scope, $scopeId);
        }
    }

    protected function _getConfigValueWithDefault(Mage_Core_Model_Store $store, $path, $default = null)
    {
        $value = $store->getConfig($path);
        if (is_null($value) || '' === $value) {
            $value = $default;
        }
        return $value;
    }

    /**
     * Return a resource model to get the DB work done
     *
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Migration
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('netzarbeiter_groupscatalog2/migration');
        }
        return $this->_resource;
    }

    protected function _migrateProductSettings()
    {
        $oldAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, self::GROUPSCATALOG1_ATTRIBUTE_CODE);
        $newAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(
                    Mage_Catalog_Model_Product::ENTITY, Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE
                );

        $productIds = $this->_getResource()->copyAttributeValues($oldAttribute, $newAttribute);

        // Update index
        $dataObj = new Varien_Object(array(
            'product_ids' => $productIds,
            'attributes_data' => array(Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE => true),
            //'store_id' => 0 // not used by GroupsCatalog2 indexer
        ));
        Mage::getSingleton('index/indexer')->processEntityAction(
            $dataObj, Mage_Catalog_Model_Product::ENTITY, Mage_Index_Model_Event::TYPE_MASS_ACTION
        );
    }

    protected function _migrateCategorySettings()
    {
        $oldAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Category::ENTITY, self::GROUPSCATALOG1_ATTRIBUTE_CODE);
        $newAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(
                    Mage_Catalog_Model_Category::ENTITY, Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE
                );

        $categoryIds = $this->_getResource()->copyAttributeValues($oldAttribute, $newAttribute);

        // Update index
        $dataObj = new Varien_Object(array(
            'category_ids' => $categoryIds,
            'attributes_data' => array(Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE => true),
            //'store_id' => 0 // not used by GroupsCatalog2 indexer
        ));
        Mage::getSingleton('index/indexer')->processEntityAction(
            $dataObj, Mage_Catalog_Model_Category::ENTITY, Mage_Index_Model_Event::TYPE_MASS_ACTION
        );
    }

    protected function _cleanupDb()
    {
        $this->_removeOldConfigTableSettings();
    }

    protected function _removeOldAttributes()
    {
        // Removing the attributes takes care of removing the attribute values, too
        $oldProductAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_product::ENTITY, self::GROUPSCATALOG1_ATTRIBUTE_CODE);
        $oldProductAttribute->delete();

        $oldCategoryAttribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Category::ENTITY, self::GROUPSCATALOG1_ATTRIBUTE_CODE);
        $oldCategoryAttribute->delete();

        Mage::app()->cleanCache(Mage_Eav_Model_Attribute::CACHE_TAG);
    }

    protected function _removeOldConfigTableSettings()
    {
        $this->_getResource()->deleteDbConfigSettingsByPath('catalog/groupscatalog/');
        Mage::app()->cleanCache(Mage_Core_Model_Config::CACHE_TAG);
    }

    protected function _removeFiles()
    {
        $message = $this->__('Remove the following files and directories to complete the uninstall:<br/>%s',
            implode('<br/>', array(
                'app/etc/modules/Netzarbeiter_GroupsCatalog.xml',
                'app/code/community/Netzarbeiter/GroupsCatalog/',
                'app/locale/de_DE/Netzarbeiter_GroupsCatalog.csv',
                'app/locale/en_US/Netzarbeiter_GroupsCatalog.csv',
                'app/locale/fr_FR/Netzarbeiter_GroupsCatalog.csv',
                'app/locale/nl_NL/Netzarbeiter_GroupsCatalog.csv'
            )
        ));
        Mage::throwException($message);
    }
}
