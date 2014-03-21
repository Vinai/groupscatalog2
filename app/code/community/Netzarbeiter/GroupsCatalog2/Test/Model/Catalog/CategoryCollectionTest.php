<?php
/**
 * Netzarbeiter
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog2
 * @copyright  Copyright (c) 2014 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Use global fixtures so available store views are known.
 *
 * This tests issue https://github.com/Vinai/groupscatalog2/pull/43
 *
 *
 * @loadSharedFixture global.yaml
 */
class Netzarbeiter_GroupsCatalog2_Test_Model_Catalog_CategoryCollectionTest
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Prepare category flat index table
     */
    public static function setUpBeforeClass()
    {
        Mage::getModel('index/indexer')->getProcessByCode('catalog_category_flat')->reindexEverything();
    }

    /**
     * Mock customer session singleton and enable frontend events
     */
    protected function setUp()
    {
        // Activate frontend store so the flat table name is built correctly
        $this->setCurrentStore('usa');

        // Mock customer session
        $mockSession = $this->getModelMockBuilder('customer/session')
                ->disableOriginalConstructor()
                ->getMock();
        $this->replaceByMock('singleton', 'customer/session', $mockSession);

        $this->app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
    }

    /**
     * Clean up mocked customer session and revert to admin scope
     */
    protected function tearDown()
    {
        $this->setCurrentStore('admin');
    }

    /**
     * @test
     */
    public function eavCategoryCollectionAddIdFilter()
    {
        /* @var $collection Mage_Catalog_Model_Resource_Category_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $this->assertInstanceOf('Mage_Catalog_Model_Resource_Category_Collection', $collection);

        // Dummy value, the important point is that the generated SQL still is valid
        $collection->addIdFilter(1)->load();
        $this->assertTrue($collection->isLoaded());
    }

    /**
     * @test
     */
    public function flatCategoryCollectionAddIdFilter()
    {
        /* @var $collection Mage_Catalog_Model_Resource_Category_Flat_Collection */
        $collection = Mage::getResourceModel('catalog/category_flat_collection');
        $this->assertInstanceOf('Mage_Catalog_Model_Resource_Category_Flat_Collection', $collection);

        // Dummy value, the important point is that the generated SQL still is valid
        $collection->addIdFilter(1)->load();
        $this->assertTrue($collection->isLoaded());
    }
}
