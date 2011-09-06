<?php
 
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
}
