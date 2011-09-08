<?php
 
class Netzarbeiter_GroupsCatalog2_Model_CatalogSearch_Resource_Fulltext_Collection
	extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
	/**
	 * Add the groupscatalog filter to the select object so the number of search
	 * results on the pager is correct.
	 *
	 * @return Varien_Db_Select
	 */
	public function getSelectCountSql()
	{
		$select = parent::getSelectCountSql();
		$customerGroupId = Mage::helper('netzarbeiter_groupscatalog2')->getCustomerGroupId();
		Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
			->addGroupsCatalogFilterToSelectCountSql($select, $customerGroupId, $this->getStoreId());
		return $select;
	}
}
