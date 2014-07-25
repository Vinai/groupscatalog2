<?php


class Netzarbeiter_GroupsCatalog2_Test_Model_Sitemap_Resource_CategoryTest
    extends EcomDev_PHPUnit_Test_Case
{
    protected $class = 'Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Category';

    /**
     * @return Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Category
     */
    private function getInstance()
    {
        /** @var Netzarbeiter_GroupsCatalog2_Model_Sitemap_Resource_Catalog_Category $instance */
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
    
    public function testItIsRewritten()
    {
        $result = Mage::getConfig()->getResourceModelClassName('sitemap/catalog_category');
        $this->assertEquals($this->class, $result);
    }
    
    public function testItExists()
    {
        $this->assertTrue(class_exists($this->class, true), "Class {$this->class} does not exist or can't be found by the autoloader");
    }
    
    public function testItExtendsTheOriginalClass()
    {
        $instance = $this->getInstance();
        $this->assertInstanceOf($this->class, $instance);
        $this->assertInstanceOf('Mage_Sitemap_Model_Resource_Catalog_Category', $instance);
    }
    
    public function testItAddsTheSelectFilterWhenLoadingEntitiesIfModuleActive()
    {
        $instance = $this->getInstance();
        
        $storeId = $this->app()->getDefaultStoreView()->getId();
        
        $instance->setGroupsCatalogHelper($this->getStubHelper());
        $filter = $this->getMockFilter();
        $filter->expects($this->exactly(1))
            ->method('addGroupsCatalogCategoryFilterToSelect')
            ->with(
                $this->isInstanceOf('Varien_Db_Select'),
                Mage_Customer_Model_Group::NOT_LOGGED_IN_ID,
                $storeId
            );
        $instance->setGroupsCatalogResourceFilter($filter);
        
        $result = $instance->getCollection($storeId);
        
        $this->assertTrue(false !== $result, "Expected getCollection to return an array, received bool false");
    }
} 