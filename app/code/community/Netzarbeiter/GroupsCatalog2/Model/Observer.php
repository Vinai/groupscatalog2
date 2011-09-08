<?php
 
class Netzarbeiter_GroupsCatalog2_Model_Observer
{
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
		$this->_applyGroupsCatalogSettingsToEntity($observer->getCategory());
	}

	/**
	 * "Unload" a loaded product if the customer is not allowed to view it
	 *
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function catalogProductLoadAfter(Varien_Event_Observer $observer)
	{
		$this->_applyGroupsCatalogSettingsToEntity($observer->getProduct());
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
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		// If the module isn't disabled on a global scale
		if ($helper->isModuleActive($category->getStore(), false))
		{
			if ($category->dataHasChangedFor(Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE))
			{
				if ($helper->getConfig('auto_refresh_block_cache'))
				{
					// Only refresh the category block cache: Mage_Catalog_Model_Category::CACHE_TAG
					Mage::app()->cleanCache(array(Mage_Catalog_Model_Category::CACHE_TAG));
				}
				else
				{
					Mage::app()->getCacheInstance()->invalidateType(Mage_Core_Block_Abstract::CACHE_GROUP);
				}
			}
		}
	}

	/**
	 * This fixes a bug in the wishlist module that counts items even
	 * though they have the is_deleted flag set.
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return void
	 */
	public function wishlistItemsRenewed(Varien_Event_Observer $observer)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		if ($helper->isModuleActive())
		{
			/* @var $wishlistHelper Mage_Wishlist_Helper_Data */
			$wishlistHelper = Mage::helper('wishlist');

			$collection = $wishlistHelper->getWishlistItemCollection();
			$session = Mage::getSingleton('customer/session');
			$count = 0;

			foreach ($collection as $item)
			{
				if (! $item->isDeleted())
				{
					$count++;
				}
			}
			$session->setWishlistItemCount($count);
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
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		if ($helper->isModuleActive($collection->getStoreId()))
		{
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
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		/*
		 * This is an excerpt from Mage_Sales_Model_Quote::collectTotals(). We don't need to
		 * recalculate all totals here, we just need to make sure the item quantities are correct.
		 */
		if ($helper->isModuleActive($quote->getStore()) && $quote->getItemsQty() > 0)
		{
			$itemsCount = $itemsQty = $virtualItemsQty = 0;
			foreach ($quote->getAllVisibleItems() as $item) {
				if ($item->getParentItem()) {
					continue;
				}
				$children = $item->getChildren();
				if ($children && $item->isShipSeparately()) {
					foreach ($children as $child) {
						if ($child->getProduct()->getIsVirtual()) {
							$virtualItemsQty += $child->getQty()*$item->getQty();
						}
					}
				}
				if ($item->getProduct()->getIsVirtual()) {
					$virtualItemsQty += $item->getQty();
				}
				$itemsCount += 1;
				$itemsQty += (float) $item->getQty();
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
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		if ($helper->isModuleActive() && ! $this->_isApiRequest())
		{
			if (! $helper->isEntityVisible($entity))
			{
				$entity->setData(null)->setId(null);
				// Set flag to make it easier to implement a redirect if needed (or debug)
				$entity->setData('forbidden_by_groupscatalog2', true);
				$entity->setData('forbidden_by_groupscatalog2_debug', array(
					'method' => __METHOD__, 'file' => __FILE__, 'line' => (__LINE__ -4)
				));
			}
		}
	}

	/**
	 * Add the groupscatalog filter sql to catalog collections using the groupscatalog filter resource model
	 *
	 * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
	 * @return void
	 */
	protected function _addGroupsCatalogFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection)
	{
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		if ($helper->isModuleActive() && ! $this->_isApiRequest())
		{
			$customerGroupId = $helper->getCustomerGroupId();
			Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
				->addGroupsCatalogFilterToCollection($collection, $customerGroupId);
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
}
