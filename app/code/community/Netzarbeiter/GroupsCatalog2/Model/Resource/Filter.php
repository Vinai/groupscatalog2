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
class Netzarbeiter_GroupsCatalog2_Model_Resource_Filter
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Implement method required by abstract
     */
    protected function _construct()
    {
    }

    /**
     * Return the collection entity table alias.
     *
     * This is ugly, but so far I haven't come up with a better way. On the other hand
     * the alias haven't changed since Magento 1.0 so I guess it's kinda safe.
     *
     * @param Varien_Data_Collection_Db $collection
     * @return string
     */
    protected function _getCollectionTableAlias(Varien_Data_Collection_Db $collection)
    {
        $tableAlias = 'main_table';
        if ($collection instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
            $tableAlias = 'e';
        }
        return $tableAlias;
    }

    /**
     * Check if the index table has been created yet.
     *
     * This method can only be executed *after* _init() has been called.
     *
     * @return bool
     */
    protected function _doesIndexExists()
    {
        return $this->_getReadAdapter()->isTableExists($this->getMainTable());
    }

    /**
     * Inner join the groupscatalog index table to hide entities not visible to the specified customer group id
     *
     * @param Varien_Data_Collection_Db $collection
     * @param int $groupId The customer group id
     * @return void
     */
    public function addGroupsCatalogFilterToCollection(Varien_Data_Collection_Db $collection, $groupId)
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');

        /**
         * This is slightly complicated but it works with products and
         * categories whether the flat tables enabled or not
         *
         * @var $entityType string
         * @var $entity Mage_Catalog_Model_Abstract
         */
        $entity = $collection->getNewEmptyItem();
        $entityType = $helper->getEntityTypeCodeFromEntity($entity);

        $this->_init($helper->getIndexTableByEntityType($entityType), 'id');

        if ($this->_doesIndexExists()) {
            $filterTable = $collection->getResource()->getTable($helper->getIndexTableByEntityType($entityType));
            $entityIdField = "{$this->_getCollectionTableAlias($collection)}.entity_id";

            $this->_addGroupsCatalogFilterToSelect(
                $collection->getSelect(), $filterTable, $groupId, $collection->getStoreId(), $entityIdField
            );
        }
    }

    /**
     * Inner join the groupscatalog index table to hide wishlist items whose
     * products are not visible to the specified customer group id
     *
     * @param Mage_Wishlist_Model_Resource_Item_Collection $collection
     * @param int $groupId
     * @param int $storeId
     * @return void
     */
    public function addGroupsCatalogFilterToWishlistItemCollection(
        Mage_Wishlist_Model_Resource_Item_Collection $collection, $groupId, $storeId
    )
    {
        $select = $collection->getSelect();
        $entityField = 'main_table.product_id';
        $this->addGroupsCatalogProductFilterToSelect($select, $groupId, $storeId, $entityField);
    }

    /**
     * Checks the given entities visibility against the groupscatalog index
     *
     * @param Mage_Catalog_Model_Abstract $entity
     * @param int $groupId
     * @return bool
     */
    public function isEntityVisible(Mage_Catalog_Model_Abstract $entity, $groupId)
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        $entityType = $helper->getEntityTypeCodeFromEntity($entity);

        // Switch index table depending on the specified entity
        $this->_init($helper->getIndexTableByEntityType($entityType), 'id');

        if (!$this->_doesIndexExists()) {
            // If the index hasn't been created yet. Default to entity 
            // is visible to minimize the number of support requests.
            return true;
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'catalog_entity_id')
            ->where('catalog_entity_id=?', $entity->getId())
            ->where('group_id=?', $groupId)
            ->where('store_id=?', $entity->getStoreId());

        // If a matching record is found the entity is visible
        return (bool)$this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Checks the list of ids against the groupscatalog index table and returns those that are visible
     *
     * @param string $entityTypeCode
     * @param array $ids
     * @param int $storeId
     * @param int $groupId
     * @return array
     */
    public function getVisibleIdsFromEntityIdList($entityTypeCode, array $ids, $storeId, $groupId)
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');

        // Dummy entry with invalid entity id so the select doesn't fail with an empty list
        $ids[] = 0;

        // Switch index table depending on the specified entity
        $this->_init($helper->getIndexTableByEntityType($entityTypeCode), 'id');

        if (!$this->_doesIndexExists()) {
            // If the index hasn't been created yet, default to all entities
            // are visible to minimize the number of support requests
            return $ids;
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), 'catalog_entity_id')
            ->where('catalog_entity_id IN(?)', $ids)
            ->where('group_id=?', $groupId)
            ->where('store_id=?', $storeId);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Inner join the groupscatalog index table to not count products
     * not visible to the specified customer group id
     *
     * @param Mage_Catalog_Model_Resource_Product_Collection $collection
     * @param int $groupId
     * @return void
     */
    public function addGroupsCatalogFilterToProductCollectionCountSelect(
        Mage_Catalog_Model_Resource_Product_Collection $collection, $groupId
    )
    {
        $select = $collection->getProductCountSelect();
        $storeId = $collection->getStoreId();
        $this->addGroupsCatalogProductFilterToSelect($select, $groupId, $storeId);
    }

    /**
     * Add the groupscatalog filter to a select instance returned by a product collection. More specifically
     * this is used to correct the number of results in the pager of the search results.
     *
     * @param Zend_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     * @return void
     */
    public function addGroupsCatalogFilterToSelectCountSql(Zend_Db_Select $select, $groupId, $storeId)
    {
        $this->addGroupsCatalogProductFilterToSelect($select, $groupId, $storeId);
    }

    /**
     * Add the groupscatalog filter to a product collection select instance.
     *
     * @param Zend_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     * @param string $entityField This is passed straight through to _addGroupsCatalogFilterToSelect()
     * @return void
     */
    public function addGroupsCatalogProductFilterToSelect(
        Zend_Db_Select $select, $groupId, $storeId, $entityField = null
    )
    {
        $this->_addGroupsCatalogEntityFilterToSelect(
            Mage_Catalog_Model_Product::ENTITY, $select, $groupId, $storeId, $entityField
        );
    }

    /**
     * Add the groupscatalog filter to a category collection select instance.
     *
     * @param Zend_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     * @param string $entityField This is passed straight through to _addGroupsCatalogFilterToSelect()
     * @return void
     */
    public function addGroupsCatalogCategoryFilterToSelect(
        Zend_Db_Select $select, $groupId, $storeId, $entityField = null
    )
    {
        $this->_addGroupsCatalogEntityFilterToSelect(
            Mage_Catalog_Model_Category::ENTITY, $select, $groupId, $storeId, $entityField
        );
    }

    /**
     * Add the groupscatalog filter to a product or category collection select instance.
     *
     * @param string $entityType
     * @param Zend_Db_Select $select
     * @param int $groupId
     * @param int $storeId
     * @param string $entityField This is passed straight through to _addGroupsCatalogFilterToSelect()
     * @return void
     */
    protected function _addGroupsCatalogEntityFilterToSelect(
        $entityType, Zend_Db_Select $select, $groupId, $storeId, $entityField = null
    )
    {
        /* @var $helper Netzarbeiter_GroupsCatalog2_Helper_Data */
        $helper = Mage::helper('netzarbeiter_groupscatalog2');

        // Switch index table depending on the specified entity
        $this->_init($helper->getIndexTableByEntityType($entityType), 'id');

        if ($this->_doesIndexExists()) {
            $table = $this->getTable($helper->getIndexTableByEntityType($entityType));
            $this->_addGroupsCatalogFilterToSelect($select, $table, $groupId, $storeId, $entityField);
        }
    }

    /**
     * Join the specified groupscatalog index table to the passed select instance
     *
     * @param Zend_Db_Select $select
     * @param string $table The groupscatalog index table
     * @param int $groupId
     * @param int $storeId
     * @param null $entityField The entity table column where the product or category id is stored
     * @return void
     */
    protected function _addGroupsCatalogFilterToSelect(
        Zend_Db_Select $select, $table, $groupId, $storeId, $entityField = null
    )
    {
        if (!$entityField) {
            $mainTableSqlAlias = $this->_getMainTableAliasFromSelect($select);
            $entityField =  $mainTableSqlAlias . '.entity_id';
        }

        // NOTE to self:
        // Using joinTable() seems to trigger an exception for some users that I can't reproduce (so far).
        // It is related to the flat catalog (Mage_Catalog_Model_Resource_Category_Flat_Collection missing
        // joinTable()). Using getSelect()->joinInner() to work around this issue.

        /*
        $collection->joinTable(
            $helper->getIndexTableByEntityType($entityType), // table
            "catalog_entity_id=entity_id", // primary bind
            array('group_id' => 'group_id', 'store_id' => 'store_id'), // alias to field mappings for the bind cond.
            array( // additional bind conditions (see mappings above)
                'group_id' => $groupId,
                'store_id' => $collection->getStoreId(),
            ),
            'inner' // join type
        );
        */

        // Avoid double joins for the wishlist collection.
        // They clone and clear() the collection each time, but the joins on the
        // collections select objects persist. This is more reliable then setting
        // a flag on the collection object.
        foreach ($select->getPart(Zend_Db_Select::FROM) as $joinedTable) {
            if ($joinedTable['tableName'] == $table) {
                // filter join already applied
                return;
            }
        }

        $select->joinInner(
            $table,
            "{$table}.catalog_entity_id={$entityField} AND " .
            $this->_getReadAdapter()->quoteInto("{$table}.group_id=? AND ", $groupId) .
            $this->_getReadAdapter()->quoteInto("{$table}.store_id=?", $storeId),
            array()
        );
    }

    /**
     * @param Zend_Db_Select $select
     * @return string
     */
    protected function _getMainTableAliasFromSelect(Zend_Db_Select $select)
    {
        $tables = $select->getPart(Zend_Db_Select::FROM);
        return array_key_exists('main_table', $tables) ? 'main_table' : 'e';
    }
}
