<?php


class Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Category
    extends Mage_Sitemap_Model_Resource_Catalog_Category
{
    /**
     * @var Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
     */
    protected $_groupsCatalogFilter;

    /**
     * @var Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected $_groupsCatalogHelper;

    /**
     * @var int The store id specified during the last call to getCollection()
     */
    protected $_storeId;

    /**
     * DI getter
     *
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
     */
    protected function _getGroupsCatalogFilter()
    {
        if (! $this->_groupsCatalogFilter) {
            $this->_groupsCatalogFilter = Mage::getResourceModel('netzarbeiter_groupscatalog2/filter');
        }
        return $this->_groupsCatalogFilter;
    }

    /**
     * DI getter
     *
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected function _getGroupsCatalogHelper()
    {
        if (! $this->_groupsCatalogHelper) {
            $this->_groupsCatalogHelper = Mage::helper('netzarbeiter_groupscatalog2');
        }
        return $this->_groupsCatalogHelper;
    }

    /**
     * Setter Dependency Injection Method
     *
     * @param Netzarbeiter_GroupsCatalog2_Model_Resource_Filter $filter
     * @return $this
     */
    public function setGroupsCatalogResourceFilter(Netzarbeiter_GroupsCatalog2_Model_Resource_Filter $filter)
    {
        $this->_groupsCatalogFilter = $filter;
        return $this;
    }

    /**
     * Setter Dependency Injection Method
     *
     * @param Netzarbeiter_GroupsCatalog2_Helper_Data $helper
     * @return $this
     */
    public function setGroupsCatalogHelper(Netzarbeiter_GroupsCatalog2_Helper_Data $helper)
    {
        $this->_groupsCatalogHelper = $helper;
        return $this;
    }

    /**
     * Set the store Id
     *
     * @param int $storeId
     * @return array
     */
    public function getCollection($storeId)
    {
        $this->_storeId = $storeId;
        return parent::getCollection($storeId);
    }
    
    /**
     * Filter results to only contain NOT LOGGED IN visible entities
     *
     * @return array
     */
    protected function _loadEntities()
    {
        if ($this->_getGroupsCatalogHelper()->isModuleActive($this->_storeId)) {
            $select = $this->_select;
            $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            $this->_getGroupsCatalogFilter()
                ->addGroupsCatalogCategoryFilterToSelect($select, $groupId, $this->_storeId);
        }
        return parent::_loadEntities();
    }
} 