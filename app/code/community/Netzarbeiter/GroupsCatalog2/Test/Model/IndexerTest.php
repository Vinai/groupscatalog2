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
 * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract
 * @see Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Abstract
 *
 * @doNotIndexAll
 */
class Netzarbeiter_GroupsCatalog2_Test_Model_IndexerTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @test
     * @dataProvider indexerRunWithNoEntitiesProvider
     */
    public function indexerRunWithNoEntities($entityType, $indexerSuffix)
    {
        // Non-existant, dummy values
        $entityModelMock = new Varien_Object(array(
            'id' => -1
        ));
        $entityModelMock->setData(Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE, 1);

        $event = Mage::getModel('index/event')
            ->setEntity($entityType)
            ->setType(Mage_Index_Model_Event::TYPE_SAVE)
            ->setDataObject($entityModelMock)
            ->setEntityPk($entityModelMock->getId());

        Mage::getModel('netzarbeiter_groupscatalog2/indexer_' . $indexerSuffix)
            ->register($event)
            ->processEvent($event);

        // No exception is what we are asserting
    }

    /**
     * DataProvider for indexerRunWithNoEntities test
     *
     * @return array
     */
    public function indexerRunWithNoEntitiesProvider()
    {
        return array(
            array(Mage_Catalog_Model_Product::ENTITY, 'product'),
            array(Mage_Catalog_Model_Category::ENTITY, 'category'),
        );
    }
}