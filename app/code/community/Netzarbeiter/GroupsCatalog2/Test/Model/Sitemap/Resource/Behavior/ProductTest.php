<?php


class Netzarbeiter_GroupsCatalog2_Test_Model_Sitemap_Resource_Behavior_ProductTest
    extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapProduct';

    /**
     * @return Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapProduct
     */
    private function getInstance()
    {
        /** @var Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Behavior_FilterSitemapProduct $instance */
        $instance = new $this->class;
        return $instance;
    }
    
    private function getStubHelper($moduleActive = true)
    {
        $stubHelper = $this->getMock('Netzarbeiter_GroupsCatalog2_Helper_Data');
        $stubHelper->expects($this->any())
            ->method('isModuleActive')
            ->will($this->returnValue($moduleActive));
        return $stubHelper;
    }
    
    private function getMockFilter()
    {
        $mockFilter = $this->getMockBuilder('Netzarbeiter_GroupsCatalog2_Model_Resource_Filter')
            ->disableOriginalConstructor()
            ->getMock();
        
        return $mockFilter;
    }
    
    public function testItExists()
    {
        $this->assertTrue(class_exists($this->class, true), "Class {$this->class} does not exist or can't be found by the autoloader");
    }
    
    public function testItAddsTheSelectFilterWhenLoadingEntitiesIfModuleActive()
    {
        $instance = $this->getInstance();
        
        $storeId = $this->app()->getDefaultStoreView()->getId();
        
        $instance->setGroupsCatalogHelper($this->getStubHelper());
        $filter = $this->getMockFilter();
        $filter->expects($this->exactly(1))
            ->method('addGroupsCatalogProductFilterToSelect')
            ->with(
                $this->isInstanceOf('Varien_Db_Select'),
                Mage_Customer_Model_Group::NOT_LOGGED_IN_ID,
                $storeId
            );
        $instance->setGroupsCatalogResourceFilter($filter);
        
        $stubSelect = $this->getMockBuilder('Varien_Db_Select')
            ->disableOriginalConstructor()
            ->getMock();

        $instance->setStoreId($storeId);
        $instance->addNotLoggedInGroupFilter($stubSelect);
    }
} 