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

class Netzarbeiter_GroupsCatalog2_Model_Observer
{
    /**
     * Change rewrite depending on Magento version
     * 
     * In Magento 1.8 the method signature changed for 
     * Mage_Catalog_Model_Resource_Category_Flat::_loadNodes()
     * 
     * @param Varien_Event_Observer $observer
     */
    public function controllerFrontInitBefore(Varien_Event_Observer $observer)
    {
        if (version_compare(Mage::getVersion(), '1.8', '<')) {
            Mage::getConfig()->setNode(
                'global/models/catalog_resource/rewrite/category_flat',
                'Netzarbeiter_GroupsCatalog2_Model_Catalog_Resource_Category_Flat17'
            );
        }
    }
    
    /**
     * Add the groupscatalog filter sql to product collections
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductCollectionLoadBefore(Varien_Event_Observer $observer)
    {
        $collection = $observer->getCollection();
        $this->_addGroupsCatalogFilterToCollection($collection);
    }

    /**
     * Add the groupscatalog filter sql to category collections
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogCategoryCollectionLoadBefore(Varien_Event_Observer $observer)
    {
        $collection = $observer->getCategoryCollection();
        $this->_addGroupsCatalogFilterToCollection($collection);
    }

    /**
     * "Unload" a loaded category if the customer is not allowed to view it
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogCategoryLoadAfter(Varien_Event_Observer $observer)
    {
        $category = $observer->getCategory();
        $this->_applyGroupsCatalogSettingsToEntity($category);
        if ($category->getData('forbidden_by_groupscatalog2')) {
            $this->_applyHiddenEntityHandling(Mage_Catalog_Model_Category::ENTITY);
        }
    }

    /**
     * "Unload" a loaded product if the customer is not allowed to view it
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductLoadAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getProduct();
        $this->_applyGroupsCatalogSettingsToEntity($product);
        if ($product->getData('forbidden_by_groupscatalog2')) {
            $this->_applyHiddenEntityHandling(Mage_Catalog_Model_Product::ENTITY);
        }
    }

    /**
     * Apply the message display and redirect if configured.
     *
     * @param string $entityTypeCode
     */
    protected function _applyHiddenEntityHandling($entityTypeCode)
    {
        $helper = $this->_getHelper();
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            // Do not apply redirects and messages to customer module (order history and dashboard for example).
            // Otherwise products that where previously purchased by the customer and now are hidden from him
            // would make the customer account inaccessible.
            if (Mage::app()->getRequest()->getModuleName() !== 'customer') {
                Mage::helper('netzarbeiter_groupscatalog2/hidden')->applyHiddenEntityHandling($entityTypeCode);
            }
        }
    }


    /**
     * Set the html block cache to disabled/invalid when groupscatalog visibility settings
     * have changed because the top menu is cached.
     *
     * This might be better suited to go into the indexer, but that seems very unclean to mix
     * the indexer logic with the cache logic. So for now I'll put it here.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogCategorySaveAfter(Varien_Event_Observer $observer)
    {
        /* @var $category Mage_Catalog_Model_Category */
        $category = $observer->getCategory();
        $helper = $this->_getHelper();

        // If the module isn't disabled on a global scale
        if ($helper->isModuleActive($category->getStore(), false) && !$helper->isDisabledOnCurrentRoute()) {
            if ($category->dataHasChangedFor(Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE)) {
                if ($helper->getConfig('auto_refresh_block_cache')) {
                    // Only refresh the category block cache: Mage_Catalog_Model_Category::CACHE_TAG
                    Mage::app()->cleanCache(array(Mage_Catalog_Model_Category::CACHE_TAG));
                } else {
                    Mage::app()->getCacheInstance()->invalidateType(Mage_Core_Block_Abstract::CACHE_GROUP);
                }
            }
        }
    }

    /**
     * Clear the group collection cache.
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerGroupSaveAfter(Varien_Event_Observer $observer)
    {
        // Clean the collection cache used when the input type of the attributes
        // is switched to label via the system configuration.
        Mage::app()->cleanCache(array(
            Netzarbeiter_GroupsCatalog2_Helper_Data::CUSTOMER_GROUP_CACHE_TAG
        ));
    }

    /**
     * Add the groupscatalog filter to the wishlist item collection.
     *
     * This event only exists because of the rewrite of the wishlist item collection. The event
     * prefix and object properties are not set in the core. A contribution patch is on its way, though.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function wishlistItemCollectionLoadBefore(Varien_Event_Observer $observer)
    {
        /* @var $collection Mage_Wishlist_Model_Resource_Item_Collection */
        $collection = $observer->getCollection();
        $helper = $this->_getHelper();
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            $customerGroupId = $helper->getCustomerGroupId();

            $storeId = Mage::app()->getStore()->getId();
            $this->_getResource()
                    ->addGroupsCatalogFilterToWishlistItemCollection($collection, $customerGroupId, $storeId);
        }
    }

    /**
     * Add the groupscatalog filter to the product collection count so the numbers
     * beside the categories in the sidebar navigation are correct.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductCollectionBeforeAddCountToCategories(Varien_Event_Observer $observer)
    {
        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = $observer->getCollection();
        $helper = $this->_getHelper();
        if ($helper->isModuleActive($collection->getStoreId()) && !$helper->isDisabledOnCurrentRoute()) {
            $customerGroupId = $helper->getCustomerGroupId();
            $this->_getResource()
                    ->addGroupsCatalogFilterToProductCollectionCountSelect($collection, $customerGroupId);
        }
    }

    /**
     * Remove products that are in the cart that where not hidden while logged out
     * but are hidden to the customer once logged in.
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteMergeBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote $guestQuote */
        $guestQuote = $observer->getSource();

        // If a hidden product is loaded, it's entity_id is set to null.
        // So all we need to do here is set the deleted property to true,
        // and then they will not be merged into the customer quote.
        foreach ($guestQuote->getItemsCollection() as $quoteItem) {
            if (! $quoteItem->getProductId()) {
                $quoteItem->isDeleted(true);
            }
        }
    }

    /**
     * Switch groupscatalog attribute input to display only if configured to avoid loading
     * the customer group option list.
     * This makes sense for stores with a large number of customer groups who manage the
     * assignment via an product import mechanism.
     * Prohibit loading of the customer groups using this hackish approach and not in the
     * attribute source model, because that is also used during importing and it needs to
     * always return the full list of options, regardless of the "show_multiselect_field"
     * setting.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatchAdminhtmlCatalogProductEdit(Varien_Event_Observer $observer)
    {
        $helper = $this->_getHelper();
        if (! $helper->getConfig('show_multiselect_field')) {
            $entityType = Mage_Catalog_Model_Product::ENTITY;
            $attribute = $helper->getGroupsCatalogAttribute($entityType);
            $attribute->setFrontendInput('label');
        }
    }

    /**
     * Switch groupscatalog attribute input to display only if configured
     * 
     * @see self::controllerActionPredispatchAdminhtmlCatalogProductEdit
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatchAdminhtmlCatalogCategoryEdit(Varien_Event_Observer $observer)
    {
        $helper = $this->_getHelper();
        if (! $helper->getConfig('show_multiselect_field')) {
            $entityType = Mage_Catalog_Model_Category::ENTITY;
            $attribute = $helper->getGroupsCatalogAttribute($entityType);
            $attribute->setFrontendInput('label');
        }
    }

    /**
     * "Unload" the specified catalog entity if the groupscatalog settings specify so
     *
     * @param Mage_Catalog_Model_Abstract $entity
     * @return void
     */
    protected function _applyGroupsCatalogSettingsToEntity(Mage_Catalog_Model_Abstract $entity)
    {
        $helper = $this->_getHelper();
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            if (!$helper->isEntityVisible($entity)) {
                $entity->setData(null)->setId(null);
                // Set flag to make it easier to implement a redirect if needed (or debug)
                $entity->setData('forbidden_by_groupscatalog2', true);
            }
        }
    }

    /**
     * Add the groupscatalog filter sql to catalog collections using the groupscatalog filter resource model
     *
     * @param Varien_Data_Collection_Db (Mage_Catalog_Model_Resource_Category_Flat_Collection) $collection
     * @return void
     */
    protected function _addGroupsCatalogFilterToCollection(Varien_Data_Collection_Db $collection)
    {
        $helper = $this->_getHelper();
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            $customerGroupId = $helper->getCustomerGroupId();

            $this->_getResource()
                    ->addGroupsCatalogFilterToCollection($collection, $customerGroupId);
        }
    }

    /**
     * Helper convenience method
     *
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('netzarbeiter_groupscatalog2');
    }

    /**
     * Filter resource convenience method
     *
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
     */
    protected function _getResource()
    {
        return Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter');
    }
}
