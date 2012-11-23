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
 * @copyright  Copyright (c) 2012 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Netzarbeiter_GroupsCatalog2_Model_Observer
{
    /**
     * List of routes that have to match the current request
     * for the configured message top be displayed.
     *
     * @var array
     */
    protected $_displayMessageRoutes = array();

    /**
     * Avoid adding the configured message more then once if more then one hidden entity is loaded,
     * e.g. the main product and a related product or a product in a banner in the footer block.
     * Set to true when the message has been added to the session.
     *
     * @var bool
     */
    protected $_messageAdded = false;

    /**
     * Initialize the _displayMessageRoutes property.
     */
    public function __construct()
    {
        $this->_displayMessageRoutes = array(
            'catalog_product_view' => Mage_Catalog_Model_Product::ENTITY,
            'catalog_category_view' => Mage_Catalog_Model_Category::ENTITY
        );
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
        if ($helper->isModuleActive() && !$this->_isApiRequest()) {
            if ($this->_applyHiddenEntityRedirect($entityTypeCode)) {
                $this->_applyHiddenEntityMsg($entityTypeCode);
            }
        }
    }

    /**
     * Apply the configured splash message to display if a hidden entity is accessed.
     *
     * @param string $entityTypeCode
     */
    protected function _applyHiddenEntityMsg($entityTypeCode)
    {
        if ($this->_shouldDisplayMessage($entityTypeCode)) {
            $this->_messageAdded = true;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $message = $this->_getHelper()->getConfig('entity_hidden_msg_customer');
            } else {
                $message = $this->_getHelper()->getConfig('entity_hidden_msg_guest');
            }
            if (mb_strlen($message, 'UTF-8') > 0) {
                Mage::getSingleton('core/session')->addError($message);
            }
        }
    }

    /**
     * Check if a configured message should be shown.
     *
     * @param string $entityTypeCode
     * @return bool
     */
    protected function _shouldDisplayMessage($entityTypeCode)
    {
        // Avoid double messages if two hidden entities are loaded
        if (!$this->_messageAdded) {
            if ($action = Mage::app()->getFrontController()->getAction()) {
                $fullActionName = $action->getFullActionName();
                if (isset($this->_displayMessageRoutes[$fullActionName])) {
                    if ($this->_displayMessageRoutes[$fullActionName] == $entityTypeCode) {
                        if ($this->_getHelper()->getConfig('display_entity_hidden_msg')) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Apply redirects for hidden entity page requests if configured.
     *
     * @param string $entityTypeCode
     * @return bool true if redirect was set
     */
    protected function _applyHiddenEntityRedirect($entityTypeCode)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $handlingTypeSetting = 'entity_hidden_behaviour_customer';
            $targetRouteSetting = 'entity_hidden_redirect_customer';
        } else {
            $handlingTypeSetting = 'entity_hidden_behaviour_guest';
            $targetRouteSetting = 'entity_hidden_redirect_guest';
        }
        $type = $this->_getHelper()->getConfig($handlingTypeSetting);
        switch ($type) {
            case Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_HiddenEntityHandling::HIDDEN_ENTITY_HANDLING_REDIRECT:
                return $this->_applyEntityRedirectTargetRoute($targetRouteSetting);
                break;

            case Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_HiddenEntityHandling::HIDDEN_ENTITY_HANDLING_REDIRECT_PARENT:
                return $this->_applyEntityRedirectParentDirectory($targetRouteSetting);
                break;

            default:
                return false;
        }
    }

    /**
     * Redirect customer to configured target route.
     *
     * If the current request is to the configured target route, don't
     * redirect the customer at all.
     *
     * @param $targetRouteSetting
     * @return bool Display configured message if true is returned
     */
    protected function _applyEntityRedirectTargetRoute($targetRouteSetting)
    {
        $targetRoute = $this->_getHelper()->getConfig($targetRouteSetting);
        if (!$this->_isCurrentRequest($targetRoute)) {
            if ('customer/account/login' == $targetRoute) {
                // Special case, set after_auth_url in session for redirects to the login page
                $currentUrl = $this->_getCurrentUrl();
                Mage::getSingleton('customer/session')->setAfterAuthUrl($currentUrl);
            }

            $this->_sendRedirectHeaders($targetRoute);
            return true;
        }
        return false;
    }

    /**
     * Redirect customer to parent directory of current request.
     *
     * If the current redirect is to the top level, don't do a redirect.
     *
     * @param $targetRouteSetting
     * @return bool Display configured message if true is returned
     *
     * @todo Don't display message twice in case of chained redirects to parent (e.g. product and containing category are hidden)
     */
    protected function _applyEntityRedirectParentDirectory($targetRouteSetting)
    {
        $url = $this->_getCurrentUrl();
        // Cut off query string
        if ($p = strpos($url, '?')) {
            $url = substr($url, 0, $p);
        }
        // Only apply dirname() if there is a parent directory
        if (preg_match('#^https?://[^/]+/[^/]+#i', $url)) {
            $url = dirname($url);

            // Append configured category file suffix if this still isn't a top level request
            // TODO: Don't assume Magento is the top level application
            if (preg_match('#^https?://[^/]+/[^/]+#i', $url)) {
                $url .= Mage::helper('catalog/category')->getCategoryUrlSuffix();
            }
            $this->_sendRedirectHeaders($url, false);
        }
        // Always show message if configured
        // TODO: figure out a way to return false in case of chained redirects... maybe exit?
        return true;
    }

    /**
     * Return the URL of the current request
     *
     * @return string
     */
    protected function _getCurrentUrl()
    {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = Mage::getSingleton('core/url')->sessionUrlVar($currentUrl);
        return $currentUrl;
    }

    /**
     * Set redirect to passed target route or URL on response and send headers.
     *
     * @param string $url
     * @param bool $isMagentoRoute If set to true pass through Mage::getUrl()
     * @return Netzarbeiter_GroupsCatalog2_Model_Observer
     */
    protected function _sendRedirectHeaders($url, $isMagentoRoute = true)
    {
        if ($isMagentoRoute) {
            $url = Mage::getSingleton('core/url')->sessionUrlVar(Mage::getUrl($url));
        }
        Mage::app()->getResponse()
                ->setRedirect($url, 307)
                ->sendHeaders();
        Mage::app()->getRequest()->setDispatched(true);
        return $this;
    }

    /**
     * Check if the current request matches the passed route
     *
     * @param string $targetRoute
     */
    protected function _isCurrentRequest($targetRoute)
    {
        // Ignore parameters for now
        $targetRoute = array_slice(explode('/', $targetRoute), 0, 3);
        $front = Mage::app()->getFrontController();
        if (!isset($targetRoute[1])) {
            $targetRoute[1] = $front->getDefault('controller');
        }
        if (!isset($targetRoute[2])) {
            $targetRoute[2] = $front->getDefault('action');
        }
        $req = Mage::app()->getRequest();
        $current = array(
            $req->getModuleName(),
            $req->getControllerName(),
            $req->getActionName()
        );
        return $targetRoute === $current;
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
        if ($helper->isModuleActive($category->getStore(), false) && !$this->_isApiRequest()) {
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
     * Add a notice to rebuild the groupscatalog indexer whenever a new customer group is created.
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerGroupSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var $group Mage_Customer_Model_Group */
        $group = $observer->getObject();
        if ($group->isObjectNew()) {
            foreach (array('groupscatalog2_category', 'groupscatalog2_product') as $indexerCode) {
                $process = Mage::getModel('index/indexer')->getProcessByCode($indexerCode);
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
            }
        }
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
        $store = Mage::app()->getStore();
        if ($helper->isModuleActive($store) && !$this->_isApiRequest()) {
            $customerGroupId = $helper->getCustomerGroupId();
            // Avoid double join on frontend sitemap
            $flag = 'groupscatalog2_filter_applied';
            if (!$collection->getFlag($flag)) {
                Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
                        ->addGroupsCatalogFilterToWishlistItemCollection($collection, $customerGroupId, $store->getId());
                $collection->setFlag($flag, true);
            }
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
        if ($helper->isModuleActive($collection->getStoreId()) && !$this->_isApiRequest()) {
            $customerGroupId = $helper->getCustomerGroupId();
            Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
                    ->addGroupsCatalogFilterToProductCollectionCountSelect($collection, $customerGroupId);
        }
    }

    /**
     * Update the quote items quantities, in case one of the quote item products has been hidden.
     * If we don't do that the sidebar cart item qty might be wrong.
     *
     * There might be a better way to do this but so far this is the best way I could think of.
     * This is a very rare case that probably won't come into effect at all, only when a product
     * that a customer has in the cart becomes hidden (may be a customer group change or a product
     * setting change). Maybe this can go if the overhead is too large. Leave for now.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     * @see Mage_Sales_Model_Quote::collectTotals()
     */
    public function salesQuoteLoadAfter(Varien_Event_Observer $observer)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getQuote();

        /*
         * This is an excerpt from Mage_Sales_Model_Quote::collectTotals(). We don't need to
         * recalculate all totals here, we just need to make sure the item quantities are correct.
         */
        if ($this->_getHelper()->isModuleActive($quote->getStore()) &&
                $quote->getItemsQty() > 0 && !$this->_isApiRequest()
        ) {
            $itemsCount = $itemsQty = $virtualItemsQty = 0;
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $children = $item->getChildren();
                if ($children && $item->isShipSeparately()) {
                    foreach ($children as $child) {
                        if ($child->getProduct()->getIsVirtual()) {
                            $virtualItemsQty += $child->getQty() * $item->getQty();
                        }
                    }
                }
                if ($item->getProduct()->getIsVirtual()) {
                    $virtualItemsQty += $item->getQty();
                }
                $itemsCount += 1;
                $itemsQty += (float)$item->getQty();
            }
            $quote->setVirtualItemsQty($virtualItemsQty)
                    ->setItemsCount($itemsCount)
                    ->setItemsQty($itemsQty);
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
        if ($helper->isModuleActive() && !$this->_isApiRequest()) {
            if (!$helper->isEntityVisible($entity)) {
                $entity->setData(null)->setId(null);
                // Set flag to make it easier to implement a redirect if needed (or debug)
                $entity->setData('forbidden_by_groupscatalog2', true);
                $entity->setData('forbidden_by_groupscatalog2_debug', array(
                    'method' => __METHOD__, 'file' => __FILE__, 'line' => (__LINE__ - 4)
                ));
            }
        }
    }

    /**
     * Add the groupscatalog filter sql to catalog collections using the groupscatalog filter resource model
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract|Mage_Catalog_Model_Resource_Category_Flat_Collection $collection
     * @return void
     */
    protected function _addGroupsCatalogFilterToCollection(Varien_Data_Collection_Db $collection)
    {
        $helper = $this->_getHelper();
        if ($helper->isModuleActive() && !$this->_isApiRequest()) {
            $customerGroupId = $helper->getCustomerGroupId();

            // Avoid double join on frontend sitemap
            $flag = 'groupscatalog2_filter_applied';
            if (!$collection->getFlag($flag)) {
                Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
                        ->addGroupsCatalogFilterToCollection($collection, $customerGroupId);
                $collection->setFlag($flag, true);
            }
        }
    }

    /**
     * Return true if the request is made via the api
     *
     * @return boolean
     */
    protected function _isApiRequest()
    {
        return Mage::app()->getRequest()->getModuleName() === 'api';
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
}
