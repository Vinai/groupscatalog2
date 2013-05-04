<?php


class Netzarbeiter_GroupsCatalog2_Test_Model_Resource_Indexer extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Run indexer with an event that will return zero entities.
     *
     * This test simulates running the indexer on an installation with
     * no products or categories.
     *
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
    }

    public function indexerRunWithNoEntitiesProvider()
    {
        return array(
            array(Mage_Catalog_Model_Product::ENTITY, 'product'),
            array(Mage_Catalog_Model_Category::ENTITY, 'category'),
        );
    }
}