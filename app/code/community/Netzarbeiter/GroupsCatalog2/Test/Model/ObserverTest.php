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
 * @see Netzarbeiter_GroupsCatalog2_Model_Observer
 *
 * @loadSharedFixture global.yaml
 * @doNotIndexAll
 */
class Netzarbeiter_GroupsCatalog2_Test_Model_ObserverTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Prepare grouscatalog2 index tables
     */
    public static function setUpBeforeClass()
    {
        // Fix SET @SQL_MODE='NO_AUTO_VALUE_ON_ZERO' bugs from shared fixture files
        // With the merge of https://github.com/IvanChepurnyi/EcomDev_PHPUnit/pull/93 this hack isn't required any more
        /** @var $con Varien_Db_Adapter_Interface */
        $con = Mage::getSingleton('core/resource')->getConnection('customer_write');
        $con->update(
            Mage::getSingleton('core/resource')->getTableName('customer/customer_group'),
            array('customer_group_id' => 0),
            "customer_group_code='NOT LOGGED IN'"
        );

        // Rebuild GroupsCatalog2 product index
        Mage::getModel('index/indexer')->getProcessByCode('groupscatalog2_product')->reindexEverything();
    }

    /**
     * Mock customer session singleton and enable frontend events
     */
    protected function setUp()
    {
        // Mock customer session
        $mockSession = $this->getModelMockBuilder('customer/session')
                ->disableOriginalConstructor()
                ->getMock();

        $this->replaceByMock('singleton', 'customer/session', $mockSession);

        $this->app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
    }

    /**
     * Revert to admin scope
     */
    protected function tearDown()
    {
        $this->setCurrentStore('admin');
    }

    /**
     * This will test the event observers triggered by loading a product collection
     * and applying test the groupscatalog filters are applied correctly.
     *
     * @test
     * @param string $storeCode
     * @param int $customerGroupId
     * @dataProvider dataProvider
     */
    public function loadEavProductCollection($storeCode, $customerGroupId)
    {
        // In test fixture setup
        $this->setCurrentStore($storeCode);

        /* @var $session PHPUnit_Framework_MockObject_MockObject Stub */
        $mockSession = Mage::getSingleton('customer/session');
        $mockSession->expects($this->atLeastOnce())
                ->method('getCustomerGroupId')
                ->will($this->returnValue($customerGroupId));

        // Instantiate and load collection
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getModel('catalog/product')->getCollection()->load();

        $expected = $this->expected("%s-%s", $storeCode, $customerGroupId)->getProductsPresent();
        $actual = array_keys($collection->getItems());
        sort($actual);

        $message = sprintf(
            "Product(s) to be present for group %d in store %s: [%s]. Products found: [%s]",
            $customerGroupId, $storeCode, implode(', ', $expected), implode(', ', $actual)
        );
        $this->assertEquals($expected, $actual, $message);
    }
}
