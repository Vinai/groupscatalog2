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
 * @copyright  Copyright (c) 2011 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
	extends Mage_Core_Model_Resource_Db_Abstract
{
	protected function _construct()
	{
	}

	/**
	 * Inner join the groupscatalog index table to hide entities not visible to the specified customer group id
	 *
	 * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
	 * @param int $groupId The customer group id
	 * @return void
	 */
	public function addGroupsCatalogFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection, $groupId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		/**
		 * This is slightly complicated but it works with products and
		 * categories whether the flat tables enabled or not
		 *
		 * @var $entityType string
		 */
		$entity = $collection->getNewEmptyItem();
		$entityType = $helper->getEntityTypeCodeFromEntity($entity);

		$collection->joinTable(
			$helper->getIndexTableByEntityType($entityType), // table
			"entity_id=entity_id", // primary bind
			array('group_id' => 'group_id', 'store_id' => 'store_id'), // alias to field mappings for the bind cond.
			array( // additional bind conditions (see mappings above)
				'group_id' => $groupId,
				'store_id' => $collection->getStoreId(),
			),
			'inner' // join type
		);
	}

	/**
	 * Inner join the groupscatalog index table to hide wishlist items whose
	 * products are not visible to the specified customer group id
	 *
	 * @param Mage_Wishlist_Model_Resource_Item_Collection $collection
	 * @param int $groupId
	 * @param int $storeId
	 * @return void
	 */
	public function addGroupsCatalogFilterToWishlistItemCollection(Mage_Wishlist_Model_Resource_Item_Collection $collection, $groupId, $storeId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		// Switch index table depending on the specified entity
		$this->_init($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY), 'id');

		$table = $this->getTable($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY));
		$this->_addGroupsCatalogFilterToSelect(
			$collection->getSelect(), $table, $groupId, $storeId, 'main_table.product_id'
		);
	}

	/**
	 * Checks the given entities visibility against the groupscatalog index
	 *
	 * @param Mage_Catalog_Model_Abstract $entity
	 * @param int $groupId
	 * @return bool
	 */
	public function isEntityVisible(Mage_Catalog_Model_Abstract $entity, $groupId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		$entityType = $helper->getEntityTypeCodeFromEntity($entity);

		// Switch index table depending on the specified entity
		$this->_init($helper->getIndexTableByEntityType($entityType), 'id');

		$select = $this->_getReadAdapter()->select()
				->from($this->getMainTable(), 'id')
				->where('entity_id=?', $entity->getId())
				->where('group_id=?', $groupId)
				->where('store_id=?', $entity->getStoreId());

		// If a matching record is found the entity is visible
		return (bool) $this->_getReadAdapter()->fetchOne($select);
	}

	/**
	 * Checks the list of ids against the groupscatalog index table and returns those that are visible
	 *
	 * @param string $entityTypeCode
	 * @param array $ids
	 * @param int $storeId
	 * @param int $groupId
	 * @return array
	 */
	public function getVisibleIdsFromEntityIdList($entityTypeCode, array $ids, $storeId, $groupId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		
		// Dummy entry with invalid entity id so the select doesn't fail with an empty list
		$ids[] = 0;

		// Switch index table depending on the specified entity
		$this->_init($helper->getIndexTableByEntityType($entityTypeCode), 'id');

		$select = $this->_getReadAdapter()->select()
				->from($this->getMainTable(), 'entity_id')
				->where('entity_id IN(?)', $ids)
				->where('group_id=?', $groupId)
				->where('store_id=?', $storeId);
		
		return $this->_getReadAdapter()->fetchCol($select);
	}
	
	/**
	 * Inner join the groupscatalog index table to not count products
	 * not visible to the specified customer group id
	 *
	 * @param Mage_Catalog_Model_Resource_Product_Collection $collection
	 * @param int $groupId
	 * @return void
	 */
	public function addGroupsCatalogFilterToProductCollectionCountSelect(Mage_Catalog_Model_Resource_Product_Collection $collection, $groupId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		// Switch index table depending on the specified entity
		$this->_init($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY), 'id');

		$table = $this->getTable($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY));
		$this->_addGroupsCatalogFilterToSelect($collection->getProductCountSelect(), $table, $groupId, $collection->getStoreId());
	}

	/**
	 * Add the groupscatalog filter to a select instance returned by a product collection. More specifically
	 * this is used to correct the number of results in the pager of the search results.
	 *
	 * @param Zend_Db_Select $select
	 * @param int $groupId
	 * @param int $storeId
	 * @return void
	 */
	public function addGroupsCatalogFilterToSelectCountSql(Zend_Db_Select $select, $groupId, $storeId)
	{
		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');

		// Switch index table depending on the specified entity
		$this->_init($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY), 'id');
		$table = $this->getTable($helper->getIndexTableByEntityType(Mage_Catalog_Model_Product::ENTITY));

		$this->_addGroupsCatalogFilterToSelect($select, $table, $groupId, $storeId);
	}

	/**
	 * Join the specified groupscatalog index table to the passed select instance
	 *
	 * @param Zend_Db_Select $select
	 * @param string $table
	 * @param int $groupId
	 * @param int $storeId
	 * @param string $entityField The table column where the product or category id is stored
	 * @return void
	 */
	protected function _addGroupsCatalogFilterToSelect(Zend_Db_Select $select, $table, $groupId, $storeId, $entityField='e.entity_id')
	{
		$select->joinInner(
			$table,
			"{$table}.entity_id={$entityField} AND " .
				$this->_getReadAdapter()->quoteInto("{$table}.group_id=? AND ", $groupId) .
				$this->_getReadAdapter()->quoteInto("{$table}.store_id=?", $storeId),
			''
		);
	}
}
