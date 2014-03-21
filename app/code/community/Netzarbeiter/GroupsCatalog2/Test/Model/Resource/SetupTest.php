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
 * Class Netzarbeiter_GroupsCatalog2_Test_Model_Resource_SetupTest
 * 
 * @see Netzarbeiter_GroupsCatalog2_Model_Resource_Setup
 * @doNotIndexAll
 */
class Netzarbeiter_GroupsCatalog2_Test_Model_Resource_SetupTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Return a mock database connection model
     * 
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockDbConnection()
    {
        $mockDb = $this->getMockBuilder('Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->getMock();
        
        return $mockDb;
    }

    /**
     * Return a mock Varien_Db_Ddl_Table model
     * 
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockDdlTable()
    {
        $ddlTable = $this->getMock('Varien_Db_Ddl_Table');
        $ddlTable->expects($this->atLeastOnce())
            ->method('addColumn')
            ->will($this->returnSelf());
        $ddlTable->expects($this->any())
            ->method('addForeignKey')
            ->will($this->returnSelf());
        $ddlTable->expects($this->atLeastOnce())
            ->method('setComment')
            ->will($this->returnSelf());
        $ddlTable->expects($this->any())
            ->method('addIndex')
            ->will($this->returnSelf());

        return $ddlTable;
    }

    /**
     * Force connection setter injection on setup model using reflection
     * 
     * @param $setup
     * @param $connection
     */
    protected function _setConnectionOnInstaller($setup, $connection)
    {
        $prop = new ReflectionProperty($setup, '_conn');
        $prop->setAccessible(true);
        $prop->setValue($setup, $connection);
        $prop->setAccessible(false);
    }
    
    /**
     * @test
     * @param string $entityType
     * @param string $table
     * @dataProvider entityTypeProvider
     */
    public function dropIndexTable($entityType, $table)
    {
        $conn = $this->_getMockDbConnection();
        $conn->expects($this->once())
            ->method('isTableExists')
            ->with($this->equalTo($table))
            ->will($this->returnValue(true));

        $conn->expects($this->once())
            ->method('dropTable')
            ->with($this->equalTo($table))
            ->will($this->returnValue(true));
        
        $setup = Mage::getResourceModel('netzarbeiter_groupscatalog2/setup', 'netzarbeiter_groupscatalog2_setup');
        $this->_setConnectionOnInstaller($setup, $conn);
        $setup->dropIndexTable($entityType);
    }

    /**
     * @test
     * @param string $entityType
     * @param string $table
     * @dataProvider entityTypeProvider
     */
    public function createIndexTable($entityType, $table)
    {
        $conn = $this->_getMockDbConnection();
        $ddlTable = $this->_getMockDdlTable();
        
        $conn->expects($this->once())
            ->method('newTable')
            ->with($this->equalTo($table))
            ->will($this->returnValue($ddlTable));

        $conn->expects($this->once())
            ->method('createTable')
            ->with($this->equalTo($ddlTable));

        $setup = Mage::getResourceModel('netzarbeiter_groupscatalog2/setup', 'netzarbeiter_groupscatalog2_setup');
        $this->_setConnectionOnInstaller($setup, $conn);
        $setup->createIndexTable($entityType);
    }

    /**
     * Return entity type codes and the matching index table names
     * 
     * @return array
     */
    public function entityTypeProvider()
    {
        return array(
            array(Mage_Catalog_Model_Product::ENTITY, 'groupscatalog_product_idx'),
            array(Mage_Catalog_Model_Category::ENTITY, 'groupscatalog_category_idx'),
        );
    }
} 