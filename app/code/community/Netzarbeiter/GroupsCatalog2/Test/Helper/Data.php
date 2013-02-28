<?php

class Netzarbeiter_GroupsCatalog2_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @return Mage_Core_Model_Store
     * @throwException Exception
     */
    protected function getFrontendStore()
    {
        foreach (Mage::app()->getStores() as $store) {
            if (! $store->isAdmin()) return $store;
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

    public function testGetConfig()
    {
        $store = $this->getFrontendStore();
        $store->setConfig('netzarbeiter_groupscatalog2/general/test', 256);
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $this->assertEquals($helper->getConfig('test', $store), 256);
    }

    public function testGetGroups()
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $groups = $helper->getGroups();

        $this->assertInstanceOf('Mage_Customer_Model_Resource_Group_Collection', $groups);
    }

    public function testGetGroupsContainsNotLoggedIn()
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $group = $helper->getGroups()->getItemByColumnValue('customer_group_code', 'NOT LOGGED IN');
        $this->assertInstanceOf('Mage_Customer_Model_Group', $group);
    }

    public function testIsModuleActiveFrontend()
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $store = $this->getFrontendStore();

        $store->setConfig('netzarbeiter_groupscatalog2/general/is_active', 1);
        $this->assertEquals(true, $helper->isModuleActive($store), 'Store config active');

        $helper->setModuleActive(false);
        $this->assertEquals(false, $helper->isModuleActive($store), 'ModuleActive Flag should override store config');

        $helper->resetActivationState();
        $this->assertEquals(true, $helper->isModuleActive($store), 'resetActivationState() should revert to store config');

        $store->setConfig('netzarbeiter_groupscatalog2/general/is_active', 0);
        $this->assertEquals(false, $helper->isModuleActive($store), 'Store config inactive');
    }

    public function testIsModuleActiveAdmin()
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $store = $this->getAdminStore();

        $store->setConfig('netzarbeiter_groupscatalog2/general/is_active', 1);
        $this->assertEquals(false, $helper->isModuleActive($store), 'Admin store is always inactive by default');
        $this->assertEquals(true, $helper->isModuleActive($store, false), 'Admin check disabled should return store setting');

        $store->setConfig('netzarbeiter_groupscatalog2/general/is_active', 0);
        $helper->setModuleActive(true);
        $this->assertEquals(false, $helper->isModuleActive($store), 'Admin scope should ignore module state flag');
        $this->assertEquals(true, $helper->isModuleActive($store, false), 'Admin check disabled should return module state flag');

        $helper->resetActivationState();
    }

    public function testIsEntityVisible()
    {
        $this->markTestIncomplete();
    }

    public function testGetEntityVisibleDefaultGroupIds()
    {
        $this->markTestIncomplete();
    }

    public function testGetModeSettingByEntityType()
    {
        $this->markTestIncomplete();
    }

    public function testApplyConfigModeSetting()
    {
        $this->markTestIncomplete();
    }
}
