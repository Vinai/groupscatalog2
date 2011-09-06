<?php
 
class Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
{
	/**
	 * Inner join the groupscatalog index table to hide entities not visible to the specified customer group id
	 *
	 * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
	 * @param int $groupId The customer group id
	 * @return void
	 */
	public function addGroupsCatalogFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection, $groupId)
	{
		/**
		 * This is slightly complicated but it works with products and
		 * categories whether the flat tables enabled or not
		 * 
		 * @var $entityType Mage_Eav_Model_Entity_Type
		 */
		$entityType = $collection->getNewEmptyItem()->getResource()->getEntityType();

		$collection->joinTable(
			Mage::helper('netzarbeiter_groupscatalog2')->getIndexTableByEntityType($entityType), // table
			"entity_id=entity_id", // primary bind
			array('group_id' => 'group_id', 'store_id' => 'store_id'), // alias to field mappings for the bind cond.
			array( // additional bind conditions (see mappings above)
				'group_id' => $groupId,
				'store_id' => $collection->getStoreId(),
			),
			'inner' // join type
		);
	}
}
