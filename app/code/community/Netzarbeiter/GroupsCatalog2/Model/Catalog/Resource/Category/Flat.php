<?php
 
class Netzarbeiter_GroupsCatalog2_Model_Catalog_Resource_Category_Flat
	extends Mage_Catalog_Model_Resource_Category_Flat
{
	/**
	 * We need to rewrite this class to be able to filter hidden categories if the
	 * flat catalog category is enabled.
	 *
	 * @param null $parentNode
	 * @param int $recursionLevel
	 * @param int $storeId
	 * @return array|Mage_Catalog_Model_Resource_Category_Flat
	 */
	protected function _loadNodes($parentNode = null, $recursionLevel = 0, $storeId = 0)
	{
		$nodes = parent::_loadNodes($parentNode, $recursionLevel, $storeId);

		/* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
		$helper = Mage::helper('netzarbeiter_groupscatalog2');
		if ($helper->isModuleActive())
		{
			// Filter out hidden nodes
			if (count($nodes) > 0)
			{
				$nodeIds = array_keys($nodes);
				$visibleIds = Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
					->getVisibleIdsFromEntityIdList(
						Mage_Catalog_Model_Category::ENTITY, $nodeIds, $storeId, $helper->getCustomerGroupId()
					);
				$nodes = array_intersect_key($nodes, array_flip($visibleIds));
			}
		}
		return $nodes;
	}
}
