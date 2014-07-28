<?php


class Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Product
    extends Mage_Sitemap_Model_Resource_Catalog_Product
{
    /**
     * @var Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract
     */
    protected $_notLoggedInBehavior;

    /**
     * DI setter
     *
     * @param Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract $filter
     */
    public function setAddFilterBehavior(
        Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract $filter)
    {
        $this->_notLoggedInBehavior = $filter;
    }

    /**
     * DI getter
     *
     * @return Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract
     */
    private function _getNotLoggedInBehavior()
    {
        if (! $this->_notLoggedInBehavior) {
            $this->_notLoggedInBehavior = Mage::getResourceModel('netzarbeiter_groupscatalog2/sitemap_resource_catalog_behavior_filterSitemapProduct');
        }
        return $this->_notLoggedInBehavior;
    }

    /**
     * Set the store Id
     *
     * @param int $storeId
     * @return array
     */
    public function getCollection($storeId)
    {
        $this->_getNotLoggedInBehavior()->setStoreId($storeId);
        return parent::getCollection($storeId);
    }

    /**
     * Filter results to only contain NOT LOGGED IN visible entities
     *
     * @return array
     */
    protected function _loadEntities()
    {
        $this->_getNotLoggedInBehavior()->addNotLoggedInGroupFilter($this->_select);
        return parent::_loadEntities();
    }
} 