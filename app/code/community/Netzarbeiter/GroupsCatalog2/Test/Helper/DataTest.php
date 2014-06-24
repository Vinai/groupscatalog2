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
 * @loadSharedFixture global.yaml
 * @doNotIndexAll
 */
class Netzarbeiter_GroupsCatalog2_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
    protected $_configSection = 'netzarbeiter_groupscatalog2';
    protected $_configGroup = 'general';
    /** @var Netzarbeiter_GroupsCatalog2_Helper_Data */
    protected $_helper;

    public static function setUpBeforeClass()
    {
        // Fix SET @SQL_MODE='NO_AUTO_VALUE_ON_ZERO' bugs from shared fixture files
        /** @var $con Varien_Db_Adapter_Interface */

        // With the merge of https://github.com/IvanChepurnyi/EcomDev_PHPUnit/pull/93 this hack isn't required any more
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
     * @var string $code
     * @return Mage_Core_Model_Store
     * @throwException Exception
     */
    protected function getFrontendStore($code = null)
    {
        /** @var $store Mage_Core_Model_Store */
        foreach (Mage::app()->getStores() as $store) {
            if (null === $code) {
                if (! $store->isAdmin()) {
                    return $store;
                }
            } else {
                if ($store->getCode() == $code) {
                    return $store;
                }
            }
        }
        $this->throwException(new Exception('Unable to find frontend store'));
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getAdminStore()
    {
        return Mage::app()->getStore('admin');
    }

    /**
     * @return string
     */
    protected function getConfigPrefix()
    {
        return $this->_configSection . '/' . $this->_configGroup .'/';
    }

    protected function setUp()
    {
        $this->_helper = Mage::helper('netzarbeiter_groupscatalog2');

        // Mock customer session
        $mockSession = $this->getModelMockBuilder('customer/session')
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->replaceByMock('singleton', 'customer/session', $mockSession);
    }


    // Tests #######

    public function testGetConfig()
    {
        $store = $this->getFrontendStore('germany');
        $store->setConfig($this->getConfigPrefix() . 'test', 256);
        $this->assertEquals($this->_helper->getConfig('test', $store), 256);
    }

    public function testGetGroups()
    {
        $groups = $this->_helper->getGroups();

        $this->assertInstanceOf('Mage_Customer_Model_Resource_Group_Collection', $groups);
    }

    public function testGetGroupsContainsNotLoggedIn()
    {
        $group = $this->_helper->getGroups()->getItemByColumnValue('customer_group_code', 'NOT LOGGED IN');
        $this->assertInstanceOf('Mage_Customer_Model_Group', $group);
    }

    public function testIsModuleActiveFrontend()
    {
        $store = $this->getFrontendStore();

        $store->setConfig($this->getConfigPrefix() . 'is_active', 1);
        $this->assertEquals(true, $this->_helper->isModuleActive($store), 'Store config active');

        $this->_helper->setModuleActive(false);
        $this->assertEquals(
            false, $this->_helper->isModuleActive($store), 'ModuleActive Flag should override store config'
        );

        $this->_helper->resetActivationState();
        $this->assertEquals(
            true, $this->_helper->isModuleActive($store), 'resetActivationState() should revert to store config'
        );

        $store->setConfig($this->getConfigPrefix() . 'is_active', 0);
        $this->assertEquals(false, $this->_helper->isModuleActive($store), 'Store config inactive');
    }

    public function testIsModuleActiveAdmin()
    {
        $store = $this->getAdminStore();

        $store->setConfig($this->getConfigPrefix() . 'is_active', 1);
        $this->assertEquals(false, $this->_helper->isModuleActive($store), 'Admin store is always inactive by default');
        $this->assertEquals(
            true, $this->_helper->isModuleActive($store, false), 'Admin check disabled should return store setting'
        );

        $store->setConfig($this->getConfigPrefix() . 'is_active', 0);
        $this->_helper->setModuleActive(true);
        $this->assertEquals(
            false, $this->_helper->isModuleActive($store), 'Admin scope should ignore module state flag'
        );
        $this->assertEquals(
            true, $this->_helper->isModuleActive($store, false), 'Admin check disabled should return module state flag'
        );

        $this->_helper->resetActivationState();
    }

    /**
     * @param string $storeCode
     * @param int $customerGroupId
     * @dataProvider dataProvider
     */
    public function testIsProductVisible($storeCode, $customerGroupId)
    {
        // Complete mock of customer session
        /* @var $session PHPUnit_Framework_MockObject_MockObject Stub */
        $mockSession = Mage::getSingleton('customer/session');
        $mockSession->expects($this->any()) // Will be only called if current store is deactivated
            ->method('getCustomerGroupId')
            ->will($this->returnValue($customerGroupId));

        $this->setCurrentStore($storeCode);
        foreach (array(1, 2, 3) as $productId) {
            $product = $this->_getProduct($productId);
            $expected = $this->expected('%s-%s-%s', $storeCode, $customerGroupId, $productId)->getIsVisible();
            $visible = $this->_helper->isEntityVisible($product, $customerGroupId);

            $message = sprintf(
                "Visibility for product %d, store %s, customer group %s (%d) is expected to be %d but found to be %d",
                $productId, $storeCode,
                $this->_helper->getGroups()->getItemById($customerGroupId)->getCustomerGroupCode(),
                $customerGroupId, $expected, $visible
            );
            $this->assertEquals($expected, $visible, $message);
        }
    }

    /**
     * Cosmetic wrapper so phpcs doesn't spit out an ERROR for using load in a foreach loop.
     * 
     * @param int $productId
     * @return Mage_Catalog_Model_Product
     */
    protected function _getProduct($productId)
    {
        return Mage::getModel('catalog/product')->load($productId);
    }

    /**
     * @param string $entityTypeCode
     * @param int|string|Mage_Core_Model_Store $store
     * @dataProvider dataProvider
     */
    public function testGetEntityVisibleDefaultGroupIds($entityTypeCode, $store)
    {
        $store = Mage::app()->getStore($store);
        $expected = $this->expected('%s-%s', $entityTypeCode, $store->getCode());
        $groups = $this->_helper->getEntityVisibleDefaultGroupIds($entityTypeCode, $store);
        $message = sprintf(
            'Default visible to groups for store %s "%s" not matching expected list "%s"',
            $store->getCode(), implode(',', $groups), implode(',', $expected->getVisibleToGroups())
        );
        $this->assertEquals($expected->getVisibleToGroups(), $groups, $message);
    }

    /**
     * @param string $entityTypeCode
     * @param int|string|Mage_Core_Model_Store $store
     * @dataProvider dataProvider
     */
    public function testGetModeSettingByEntityType($entityTypeCode, $store)
    {
        $store = Mage::app()->getStore($store);
        $expected = $this->expected('%s-%s', $entityTypeCode, $store->getCode())->getMode();
        $mode = $this->_helper->getModeSettingByEntityType($entityTypeCode, $store);
        $message = sprintf(
            'Mode setting for %s in store %s is "%s"',
            $entityTypeCode, $store->getCode(), $mode
        );
        $this->assertEquals($expected, $mode, $message);
    }

    /**
     * @param array $groupIds
     * @param string $mode show | hide
     * @dataProvider dataProvider
     */
    public function testApplyConfigModeSetting($groupIds, $mode)
    {
        $expected = $this->expected('%s-%s', $mode, implode('', $groupIds))->getGroupIds();
        $result = $this->_helper->applyConfigModeSetting($groupIds, $mode);
        $message = sprintf(
            'Apply mode "%s" to group ids "%s" is expected to result in "%s" but was "%s"',
            $mode, implode(',', $groupIds), implode(',', $expected), implode(',', $result)
        );
        $this->assertEquals($expected, $result, $message);
    }
}
