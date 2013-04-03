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
 * @package	Netzarbeiter_GroupsCatalog2
 * @copyright  Copyright (c) 2012 Vinai Kopp http://netzarbeiter.com
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
	 *	       group1-id,
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
		if (!array_key_exists($store->getId(), $this->_storeDefaults))
		{
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
	 * Handle reindex all calls
	 *
	 * @return void
	 */
	public function reindexAll()
	{
		$this->_reindexEntity();
	}

	/**
	 * Update or rebuild the index.
	 *
	 * @param Mage_Index_Model_Event $event
	 * @return void
	 */
	protected function _reindexEntity($event = null)
	{
		Varien_Profiler::start($this->_getProfilerName() . '::reindexEntity');
		$entityType = Mage::getSingleton('eav/config')->getEntityType($this->_getEntityTypeCode());
		$attribute = Mage::getSingleton('eav/config')->getAttribute(
			$this->_getEntityTypeCode(), Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE
		);
		$select = $this->_getReadAdapter()->select()
			->from(array('e' => $this->getTable($entityType->getEntityTable())), array('entity_id' => 'e.entity_id'))
			->joinLeft(
				array('a' => $attribute->getBackend()->getTable()),
				$this->_getReadAdapter()->quoteInto('e.entity_id=a.entity_id AND a.attribute_id = ?', $attribute->getId()),
				array('group_ids' => 'value', 'store_id' => 'store_id')
			)
			->order('e.entity_id ASC')
			->order('a.store_id ASC');

		if (is_null($event))
		{
			$this->_getWriteAdapter()->truncateTable($this->_getIndexTable());
		}
		else
		{
			$entityIds = $event->getData('entity_ids');
			$select->where('e.entity_id IN (?)', $entityIds);
			$this->_getWriteAdapter()->delete($this->_getIndexTable(), array('catalog_entity_id IN (?)' => $entityIds));
		}
		$result = $this->_getReadAdapter()->fetchAll($select);
		$this->_insertIndexRecords($result);
		Varien_Profiler::stop($this->_getProfilerName() . '::reindexEntity');
	}

	/**
	 * Create the new index records for the indexer entity
	 *
	 * @param array $result
	 * @return void
	 */
	protected function _insertIndexRecords(array &$result)
	{
		Varien_Profiler::start($this->_getProfilerName() . '::reindexEntity::insert');
		$entityId = null;
		$useConfigDefaultGroups = null;
		$data = $storesHandled = $entityDefaultGroups = array();
		foreach ($result as $row)
		{
			$this->_prepareRow($row);

			// A new entity is being handled
			if ($entityId !== $row['entity_id'])
			{
				// Add missing store id records to the insert data array for the previous entity id
				// That is why we skip this condition on the first iteration.
				// We need to do this last because then $storesHandled is set completely for the $entityId
				if (null !== $entityId)
				{
					$this->_addMissingStoreRecords($data, $entityId, $entityDefaultGroups, $storesHandled, $useConfigDefaultGroups);
				}

				// Set new entity as default
				$entityId = $row['entity_id'];
				// Set default groups for new entity (store id 0 is the first one for each entity in $result)
				$entityDefaultGroups = $row['group_ids'];
				// Reset stores handled for new entity to empty list
				$storesHandled = array();
				// Flag if config settings or row value group ids be applied in _addMissingStoreRecords()
				$useConfigDefaultGroups = Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT === $row['orig_group_ids'];
				// We don't need an index entry for store id 0, simply use it as the default
				continue;
			}

            // Don't include stores in the index where the module is disabled anyway
            if ($this->_isModuleDisabledInStore($row['store_id'])) {
                continue;
            }

			// Add index record for each group id
			foreach ($row['group_ids'] as $groupId)
			{
				$data[] = array('catalog_entity_id' => $row['entity_id'], 'group_id' => $groupId, 'store_id' => $row['store_id']);
				$storesHandled[] = $row['store_id'];
			}

			// Insert INSERT_CHUNK_SIZE records at a time.
			// If INSERT_CHUNK_SIZE records exist in $data then it is reset to an empty array afterwards
			$this->_insertIndexRecordsIfMinChunkSizeReached($data, self::INSERT_CHUNK_SIZE);
		}

		// The last iterations over $result will probably not have hit the >= INSERT_CHUNK_SIZE mark,
		// so we still need to insert these, too.

		// Add missing store id records to the insert data array for the last $entityId
		$this->_addMissingStoreRecords($data, $entityId, $entityDefaultGroups, $storesHandled, $useConfigDefaultGroups);

		// Insert missing index records
		$this->_insertIndexRecordsIfMinChunkSizeReached($data, 1);

		Varien_Profiler::stop($this->_getProfilerName() . '::reindexEntity::insert');
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
        return ! in_array($storeId, $this->_frontendStoreIds);
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
		if (count($data) >= $minSize)
		{
			// Since _addMissingStoreRecords() potentially adds many records, chunk this into sizes that are ok by MySQL
			foreach (array_chunk($data, self::INSERT_CHUNK_SIZE) as $chunk)
			{
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
		if (null === $row['group_ids'])
		{
			$row['group_ids'] = Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT;
			$row['store_id'] = Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getId();
		}

		// This is needed for the additional missing store record handling
		// We need to know if it is USE_DEFAULT or a real setting for the entity
		$row['orig_group_ids'] = $row['group_ids'];

		if (Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT == $row['group_ids'])
		{
			// Use store default ids if that is selected for the entity
			$row['group_ids'] = $this->_getStoreDefaultGroups($row['store_id']);
		}
		else
		{
			// We need the list of group ids as an array
			$row['group_ids'] = array_unique(explode(',', $row['group_ids']));

			// Check for invalid group ids. This might happen when a customer
			// group is deleted but a category or product still references it
			$row['group_ids'] = array_intersect($row['group_ids'], $this->_groupIds);

			// Apply the hide/show configuration settings
			$row['group_ids'] = $this->_helper()->applyConfigModeSettingByStore(
				$row['group_ids'],
				$this->_getEntityTypeCode(),
				$row['store_id']
			);
		}
	}

	/**
	 * Add unhandled store default index records.
     *
     * Only stores where the module is active will be added, because
     * only such stores are included in the _frontendStoreIds array.
	 *
	 * @param array $data
	 * @param int $entityId
	 * @param array $entityDefaultGroups
	 * @param array $storesHandled
	 * @param bool $useConfigDefaultGroups
	 * @return void
	 */
	protected function _addMissingStoreRecords(array &$data, $entityId, array $entityDefaultGroups, array $storesHandled, $useConfigDefaultGroups)
	{
		foreach (array_diff($this->_frontendStoreIds, $storesHandled) as $storeId)
		{
			if ($useConfigDefaultGroups)
			{
				$groupIds = $this->_getStoreDefaultGroups($storeId);
			}
			else
			{
				$groupIds = $entityDefaultGroups;
			}
			/* Handy debug code, keep around for now. Mage::log(array(
				'catalog_entity_id' => $entityId,
				'store_id' => $storeId,
				'default group ids' => $entityDefaultGroups,
				'use config groups' => intval($useConfigDefaultGroups),
				'using' => $groupIds
			)); */
			foreach ($groupIds as $groupId)
			{
				$data[] = array('catalog_entity_id' => $entityId, 'group_id' => $groupId, 'store_id' => $storeId);
			}
		}
	}
}
