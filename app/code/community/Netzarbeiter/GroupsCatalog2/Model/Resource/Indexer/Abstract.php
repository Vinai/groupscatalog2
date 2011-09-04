<?php
 
abstract class Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Abstract extends Mage_Index_Model_Resource_Abstract
{
	/**
	 * Return the groupscatalog index table id for this indexers entity
	 * 
	 * @abstract
	 * @return string
	 */
	abstract protected function _getIndexTable();

	/**
	 * Handle reindex all calls
	 *
	 * @return void
	 */
	public function reindexAll()
	{
		$this->_reindexEntity();
	}

	/**
	 * Update or rebuild the index.
	 *
	 * @param Mage_Index_Model_Event $event
	 * @return void
	 */
	protected function _reindexEntity($event = null)
	{
		$attribute = Mage::getSingleton('eav/config')->getAttribute(
			$event->getEntity(), Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE
		);
		$select = $this->_getReadAdapter()->select()
			->from($attribute->getBackendTable(), 'entity_id')
			->where('attribute_id = ?', $attribute->getId());

		if (is_null($event))
		{
			$this->_getWriteAdapter()->truncateTable($this->_getIndexTable());
		}
		else
		{
			$entityIds = $event->getData('entity_ids');
			$select->where('entity_id IN (?)', $entityIds);
			$this->_getWriteAdapter()->delete($this->_getIndexTable(), array('entity_id IN (?)' => $entityIds));
		}

		//$sql = $select->insertIgnoreFromSelect($this->_getIndexTable(), array('entity_id', 'group_id', 'store_id'));
		//$this->insertFromSelect($select, $this->_getIndexTable(), array('entity_id', 'group_id', 'store_id'));

	}
}
