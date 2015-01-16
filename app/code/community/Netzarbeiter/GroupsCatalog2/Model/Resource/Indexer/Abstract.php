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

abstract class Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Abstract extends Mage_Index_Model_Resource_Abstract
{
    /**
     * How many records to insert into the indexes with a single query
     */
    const INSERT_CHUNK_SIZE = 1000;

    /**
     * An array with store and group default visibility settings for this indexers entity
     *
     * array(
     *     storeId1 => array(
     *         group1-id,
     *         group2-id,
     *     ),
     *     ...
     * )
     *
     * Only groups that are allowed to see this indexers entities are included in the list.
     * This array is initialized for each store view as needed.
     *
     * @var array $_storeDefaults
     */
    protected $_storeDefaults = array();

    /**
     * Array of frontend store ids
     *
     * @var array $_frontendStoreIds
     */
    protected $_frontendStoreIds = array();

    /**
     * Array of all valid group ids plus Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE
     *
     * @var array $_groupIds
     */
    protected $_groupIds = array();

    /**
     * Module helper instance
     *
     * @var Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected $_helper;

    /**
     * Return the entity type code for this indexers entity
     *
     * @abstract
     * @return string
     */
    abstract protected function _getEntityTypeCode();

    /**
     * Initialize indexer
     */
    public function __construct()
    {
        parent::__construct();
        $this->_helper = Mage::helper('netzarbeiter_groupscatalog2');
        $this->_initStores();
        $this->_initGroupIds();
    }

    /**
     * Return the module data helper
     *
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected function _helper()
    {
        return $this->_helper;
    }

    /**
     * Initialize $_frontendStoreIds array.
     *
     * Do not initialize the $_storeDefaults, it will
     * be loaded by _getStoreDefaultGroups() if needed.
     * Don't include disabled stores in the frontend store ids array.
     * Don't include store ids where this module is disabled in
     * the array either.
     *
     * @return void
     */
    protected function _initStores()
    {
        foreach (Mage::app()->getStores() as $storeId => $store) {
            /** @var $store Mage_Core_Model_Store */
            if ($store->getIsActive() && $this->_helper()->isModuleActive($storeId, false)) {
                $this->_frontendStoreIds[] = $storeId;
            }
        }
    }

    /**
     * Initialize list of customer group ids
     *
     * @return void
     */
    protected function _initGroupIds()
    {
        $this->_groupIds = $this->_helper->getCustomerGroupIds();
        $this->_groupIds[] = Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE;
    }

    /**
     * Utility method to return identifier prefix for Varien_Profiler buckets
     *
     * @return string
     */
    protected function _getProfilerName()
    {
        return 'Netzarbeiter_GroupsCatalog2::' . $this->_getEntityTypeCode();
    }

    /**
     * Return the ids of the customer groups set in the system config that may see this entity
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return array
     */
    protected function _getStoreDefaultGroups($store)
    {
        $store = Mage::app()->getStore($store);
        if (!array_key_exists($store->getId(), $this->_storeDefaults)) {
            $this->_storeDefaults[$store->getId()] = $this->_helper()
                    ->getEntityVisibleDefaultGroupIds($this->_getEntityTypeCode(), $store);
        }
        return $this->_storeDefaults[$store->getId()];
    }

    /**
     * Return the groupscatalog index table name for this indexers entity
     *
     * @return string
     */
    protected function _getIndexTable()
    {
        $table = $this->_helper()->getIndexTableByEntityType($this->_getEntityTypeCode());
        return $this->getTable($table);
    }

    /**
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Setup
     */
    protected function _getSetupModel()
    {
        return Mage::getResourceModel(
            'netzarbeiter_groupscatalog2/setup',
            'netzarbeiter_groupscatalog2_setup'
        );
    }

    /**
     * If the index table does not exist, create it.
     */
    protected function _checkIndexTable()
    {
        static $tableChecked = false;
        if (! $tableChecked) {
            $tableChecked = true;
            if (! $this->_getReadAdapter()->isTableExists($this->_getIndexTable())) {
                $this->_getSetupModel()->createIndexTable($this->_getEntityTypeCode());
            }
        }
    }

    /**
     * If the EAV attribute does not exist, add it
     */
    protected function _checkAttribute()
    {
        static $attributeChecked = false;
        if (! $attributeChecked) {
            $attributeChecked = true;
            
            $select = $this->_getReadAdapter()->select()
                ->from($this->getTable('eav/attribute', 'attribute_id'))
                ->where('attribute_code=?', Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE);
            
            if (! $this->_getReadAdapter()->fetchOne($select)) {
                $this->_getSetupModel()->addGroupsCatalogAttribute($this->_getEntityTypeCode());
            }
        }
    }

    /**
     * Handle reindex all calls
     *
     * @return void
     */
    public function reindexAll()
    {
        $this->_reindexEntity();
    }

    /**
     * Only reindex records for the given customer group ids.
     * 
     * @param Mage_Index_Model_Event $event
     * @return $this
     */
    public function customerGroupSave(Mage_Index_Model_Event $event)
    {
        $limitToGroupIds = $event->getData('entity_ids');
        $event->unsetData('entity_ids');
        $this->_reindexEntity($event, $limitToGroupIds);
        return $this;
    }

    /**
     * Update or rebuild the index.
     *
     * @param Mage_Index_Model_Event $event
     * @param array $limitToGroupIds Only add the specified group ids to the index
     * @return void
     */
    protected function _reindexEntity($event = null, array $limitToGroupIds = null)
    {
        Varien_Profiler::start($this->_getProfilerName() . '::reindexEntity');
        
        $this->_checkIndexTable();
        $this->_checkAttribute();
        
        $entityType = Mage::getSingleton('eav/config')->getEntityType($this->_getEntityTypeCode());
        $attribute = Mage::getSingleton('eav/config')->getAttribute(
            $this->_getEntityTypeCode(), Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE
        );
        $select = $this->_getReadAdapter()->select()
                ->from(array('e' => $this->getTable(
                    $entityType->getEntityTable())), array('entity_id' => 'e.entity_id')
                )
                ->joinLeft(
                    array('a' => $attribute->getBackend()->getTable()),
                    $this->_getReadAdapter()->quoteInto(
                        'e.entity_id=a.entity_id AND a.attribute_id = ?', $attribute->getId()
                    ),
                    array('group_ids' => 'value', 'store_id' => 'store_id')
                )
                ->order('e.entity_id ASC')
                ->order('a.store_id ASC');

        $entityIds = array();
        if ($event && $event->hasData('entity_ids')) {
            $entityIds = $event->getData('entity_ids');
            $select->where('e.entity_id IN (?)', $entityIds);
        }
        
        $this->_clearRecordsForReindex($entityIds, $limitToGroupIds);

        $stmt = $this->_getReadAdapter()->query($select);
        $this->_insertIndexRecords($stmt, $limitToGroupIds);
        Varien_Profiler::stop($this->_getProfilerName() . '::reindexEntity');
    }

    /**
     * Remove all existing records from the index that will be effected by the reindex.
     * 
     * @param array $entityIds
     * @param array $groupIds
     */
    protected function _clearRecordsForReindex(
        array $entityIds = null, array $groupIds = null
    ) {
        if (is_null($entityIds) && is_null($groupIds)) {
            $this->_getWriteAdapter()->truncateTable($this->_getIndexTable());
        } else {
            $condition = array();
            if ($entityIds) {
                $condition['catalog_entity_id IN (?)'] = $entityIds;
            }
            if ($groupIds) {
                $condition['group_id IN (?)'] = $groupIds;
            }
            $this->_getWriteAdapter()->delete($this->_getIndexTable(), $condition);
        }
    }

    /**
     * Create the new index records for the indexer entity
     *
     * @param Zend_Db_Statement $stmt
     * @param array $limitToGroupIds Only add records for these group ids
     * @return void
     */
    protected function _insertIndexRecords(Zend_Db_Statement $stmt, array $limitToGroupIds = null)
    {
        Varien_Profiler::start($this->_getProfilerName() . '::reindexEntity::insert');
        $entityId = null;
        $data = $storesHandled = $entityDefaultGroupsWithoutMode = array();
        while ($row = $stmt->fetch()) {
            $this->_prepareRow($row);

            // A new entity is being handled
            if ($entityId !== $row['entity_id']) {
                // Add missing store id records to the insert data array for the previous entity id
                // That is why we skip this condition on the first iteration.
                // We need to do this last because then $storesHandled is set completely for the $entityId
                if (null !== $entityId) {
                    $this->_addMissingStoreRecords(
                        $data, $entityId, $entityDefaultGroupsWithoutMode, $storesHandled, $limitToGroupIds
                    );

                    // Insert INSERT_CHUNK_SIZE records at a time.
                    // If INSERT_CHUNK_SIZE records exist in $data then it is reset to an empty array afterwards
                    $this->_insertIndexRecordsIfMinChunkSizeReached($data, self::INSERT_CHUNK_SIZE);
                }

                // Set new entity as default
                $entityId = $row['entity_id'];

                // Set default groups for new entity (store id 0 is the first one for each entity in $result).
                // List of raw attribute value group ids without the store mode settings applied.
                $entityDefaultGroupsWithoutMode = $row['orig_group_ids'];

                // Reset stores handled for new entity to empty list
                $storesHandled = array();
                // We don't need an index entry for store id 0, simply use it as the default
                continue;
            }

            // Don't include stores in the index where the module is disabled anyway
            if ($this->_isModuleDisabledInStore($row['store_id'])) {
                continue;
            }

            // Add index record for each group id
            foreach ($row['group_ids'] as $groupId) {
                $this->_addDataRow($data, $row['entity_id'], $groupId, $row['store_id'], $limitToGroupIds);
                $storesHandled[] = $row['store_id'];
            }
        }

        // Check if at least one entity record was found. If not, $entityId will be null
        if ($entityId) {
            // The last iterations over $result will probably not have hit the >= INSERT_CHUNK_SIZE mark,
            // so we still need to insert these, too.

            // Add missing store id records to the insert data array for the last $entityId
            $this->_addMissingStoreRecords(
                $data, $entityId, $entityDefaultGroupsWithoutMode, $storesHandled, $limitToGroupIds
            );

            // Insert missing index records
            $this->_insertIndexRecordsIfMinChunkSizeReached($data, 1);
        }

        Varien_Profiler::stop($this->_getProfilerName() . '::reindexEntity::insert');
    }

    /**
     * Add a record to the data array if the group Id was not excluded by $limitToGroupIds
     * 
     * @param array $data Insert data
     * @param int $entityId Category or product id
     * @param int $groupId
     * @param int $storeId
     * @param array $limitToGroupIds
     */
    protected function _addDataRow(array &$data, $entityId, $groupId, $storeId, $limitToGroupIds)
    {
        if (! $limitToGroupIds || in_array($groupId, $limitToGroupIds)) {
            $data[] = array(
                'catalog_entity_id' => $entityId, 'group_id' => $groupId, 'store_id' => $storeId
            );;
        }
    }

    /**
     * Check if the specified store is part of the frontend store ids
     *
     * If the module is disabled on a store view, then that store is
     * not included in the _frontendStoreIds array.
     *
     * @param int $storeId
     * @return bool
     */
    protected function _isModuleDisabledInStore($storeId)
    {
        return !in_array($storeId, $this->_frontendStoreIds);
    }

    /**
     * Insert the records present in $data into the index table, if $minSize records are present.
     *
     * If $minSize records are present, then all records in $data are inserted into the index
     * table, INSERT_CHUNK_SIZE records at a time.
     *
     * If $minSize records are present, then also $data is reset to an empty array after the
     * records are inserted into the index table.
     *
     * @param array $data  The array is passed into the method by reference
     * @param int $minSize  Only insert the records if this number of entries are present in the $data array
     */
    protected function _insertIndexRecordsIfMinChunkSizeReached(&$data, $minSize)
    {
        if (count($data) >= $minSize) {
            // Since _addMissingStoreRecords() potentially adds many records, chunk this into sizes that are ok by MySQL
            foreach (array_chunk($data, self::INSERT_CHUNK_SIZE) as $chunk) {
                $this->_getWriteAdapter()->insertMultiple($this->_getIndexTable(), $chunk);
            }
            $data = array();
        }
    }

    /**
     * Prepare the record read from the database for the further indexing
     *
     * @param array $row
     * @return void
     */
    protected function _prepareRow(array &$row)
    {
        // Entities that don't have a value for the groupscatalog2_groups attribute
        if (null === $row['group_ids']) {
            $row['group_ids'] = Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT;
            $row['store_id'] = Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getId();
        }

        if (Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT == $row['group_ids']) {
            // This is needed for the additional missing store record handling
            // We need to know if it is USE_DEFAULT or a real setting for the entity
            $row['orig_group_ids'] = Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT;

            // Use store default ids if that is selected for the entity
            $row['group_ids'] = $this->_getStoreDefaultGroups($row['store_id']);
        } else {
            
            $row['group_ids'] = $this->_cleanGroupIdsAttributeValue($row['group_ids']);

            if (in_array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE, $row['group_ids'])) {
                $row['group_ids'] = array();
            }

            // This is needed for the additional missing store record handling
            // We need the group id's without the config mode settings applied
            $row['orig_group_ids'] = $row['group_ids'];

            // Apply the hide/show configuration settings
            $row['group_ids'] = $this->_helper()->applyConfigModeSettingByStore(
                $row['group_ids'],
                $this->_getEntityTypeCode(),
                $row['store_id']
            );
        }

        // NOTE: $row['group_ids'] is now an array with valid group ids with the store mode settings applied
    }

    /**
     * Take the groupscatalog attribute value and return an array with valid group ids
     *
     * @param string $groupIds
     * @return array
     */
    protected function _cleanGroupIdsAttributeValue($groupIds)
    {
        // We need the list of group ids as an array
        $groupIds = array_unique(explode(',', $groupIds));

        // Check for invalid group ids. This might happen when a customer
        // group is deleted but a category or product still references it
        $groupIds = array_intersect($groupIds, $this->_groupIds);

        return $groupIds;
    }

    /**
     * Add unhandled store default index records.
     *
     * Only stores where the module is active will be added, because
     * only such stores are included in the _frontendStoreIds array.
     *
     * @param array $data
     * @param int $entityId
     * @param array|string $entityDefaultGroupsWithoutMode
     * @param array $storesHandled
     * @param array $limitToGroupIds
     * @return void
     */
    protected function _addMissingStoreRecords(
        array &$data, $entityId, $entityDefaultGroupsWithoutMode, array $storesHandled, array $limitToGroupIds = null
    )
    {
        foreach (array_diff($this->_frontendStoreIds, $storesHandled) as $storeId) {
            if (Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT === $entityDefaultGroupsWithoutMode) {
                // Mode already applied
                $groupIds = $this->_getStoreDefaultGroups($storeId);
            } else {
                // Apply the hide/show configuration settings
                $groupIds = $this->_helper()->applyConfigModeSettingByStore(
                    $entityDefaultGroupsWithoutMode, $this->_getEntityTypeCode(), $storeId
                );
            }
            /* Handy debug code, keep around for now. Mage::log(array(
                'catalog_entity_id' => $entityId,
                'store_id' => $storeId,
                'default group ids' => $entityDefaultGroupsWithoutMode,
                'using' => $groupIds
            )); */

            foreach ($groupIds as $groupId) {
                $this->_addDataRow($data, $entityId, $groupId, $storeId, $limitToGroupIds);
            }
        }
    }
}
