<?php
 
class Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Category
	extends Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Abstract
{
	/**
	 * Initialize with table name and id field
	 * 
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('netzarbeiter_groupscatalog2/category_index', 'id');
	}

	/**
	 * Handle reindexing of single entity save events
	 *
	 * @param Mage_Index_Model_Event $event
	 * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Category
	 * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract::_processEvent()
	 */
	public function catalogCategorySave(Mage_Index_Model_Event $event)
	{
		$this->_reindexEntity($event);
		return $this;
	}

	/**
	 * Handle reindexing of entity mass action events
	 *
	 * @param Mage_Index_Model_Event $event
	 * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Category
	 * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract::_processEvent()
	 */
	public function catalogCategoryMassAction(Mage_Index_Model_Event $event)
	{
		$this->_reindexEntity($event);
		return $this;
	}

	/**
	 * Return the groupscatalog index table name for this indexers entity
	 * 
	 * @return string
	 */
	protected function _getIndexTable()
	{
		return $this->getTable('netzarbeiter_groupscatalog2/category_index');
	}
}
