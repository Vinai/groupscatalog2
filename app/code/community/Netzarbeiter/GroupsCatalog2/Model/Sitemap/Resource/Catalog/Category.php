<?php


class Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Category
    extends Mage_Sitemap_Model_Resource_Catalog_Category
{
    /**
     * @var Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapAbstract
     */
    protected $_notLoggedInBehavior;

    /**
     * @var string
     * @see self::_addFilter()
     */
    protected $_hookFilterAttributeName = 'is_active';

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
            $this->_notLoggedInBehavior = Mage::getModel('netzarbeiter_groupscatalog2/sitemap_resource_catalog_behavior_filterSitemapCategory');
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
     * This method is only overwritten for old versions of Magento that do not supply the _loadEntities method.
     * 
     * That would be EE versions <= 1.12 and CE versions <= 1.7.
     * 
     * @param int $storeId
     * @param string $attributeCode
     * @param mixed $value
     * @param string $type
     * @return Zend_Db_Select
     * @see self;;_loadEntities()
     */
    protected function _addFilter($storeId, $attributeCode, $value, $type = '=')
    {
        if (
            ! method_exists(get_parent_class($this), '_loadEntities') &&
            $this->_hookFilterAttributeName === $attributeCode) {
            $this->_addNotLoggedInGroupFilter();
        }
        return parent::_addFilter($storeId, $attributeCode, $value, $type);
    }

    /**
     * Filter results to only contain NOT LOGGED IN visible entities.
     * 
     * This method is only called in EE versions >= 1.13 and CE versions >= 1.8.
     * For older versions the filter is added by overloading the method _addFilter.
     * 
     * I'm aware I could simply omit the overloading of _loadEntities and use 
     * the _addFilter overload for any version, but I might drop support for older
     * versions in future and then I only need to clean up the _addFilter hack to
     * have a cleaner implementation again.
     *
     * @return array
     * @see self::_addFilter()
     */
    protected function _loadEntities()
    {
        $this->_addNotLoggedInGroupFilter();
        return parent::_loadEntities();
    }

    /**
     * Delegate to the strategy model to add the not logged in filter depending on the entity type.
     */
    private function _addNotLoggedInGroupFilter()
    {
        $this->_getNotLoggedInBehavior()->addNotLoggedInGroupFilter($this->_select);
    }
} 