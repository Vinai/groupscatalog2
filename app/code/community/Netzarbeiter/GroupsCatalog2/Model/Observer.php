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
