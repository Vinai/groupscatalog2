<?php


class Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapCategory
    extends Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract
{
    /**
     * Add the NOT LOGGED IN group filter to the sitemap product collection load select instance
     *
     * @param Varien_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     */
    protected function _addFilter(Varien_Db_Select $select, $groupId, $storeId)
    {
        $this->_getGroupsCatalogFilter()
            ->addGroupsCatalogCategoryFilterToSelect($select, $groupId, $storeId);
    }
}