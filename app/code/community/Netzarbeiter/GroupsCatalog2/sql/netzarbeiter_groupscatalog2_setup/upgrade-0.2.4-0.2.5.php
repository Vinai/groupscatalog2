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

/* @var $installer Netzarbeiter_GroupsCatalog2_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
/** @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

foreach (array('product', 'category') as $entity) {
    $tableAlias = 'netzarbeiter_groupscatalog2/' . $entity . '_index';
    $tableName = $installer->getTable($tableAlias);

    // Drop existing indexes and foreign key constraints.
    // We should only need to drop the foreign keys referring to the entity_id field,
    // but that actually doesn't seem to be enough in all cases (see
    // https://github.com/Vinai/groupscatalog2/issues/44 )
    $fk = $installer->getConnection()->getForeignKeys($tableName);
    foreach ($fk as $info) {
        $connection->dropForeignKey($tableName, $info['FK_NAME']);
    }
    $idx = $installer->getConnection()->getIndexList($tableName);
    foreach ($idx as $info) {
        if ('PRIMARY' != $info['KEY_NAME']) {
            $connection->dropIndex($tableName, $info['KEY_NAME']);
        }
    }

    $columns = $connection->describeTable($tableName);
    if (! isset($columns['catalog_entity_id'])) {
        // The table already was modified - this is just to make it easier to debug/re-run this script

        // Change column entity_id to catalog_entity_id
        $connection->changeColumn($tableName, 'entity_id', 'catalog_entity_id', array(
            'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'unsigned' => true,
            'nullable' => false,
            'comment' => ucwords($entity) . ' ID'
        ));
    }

    // Recreate indexes and foreign key constraints to confirm with the new column name
    $connection->addIndex(
        $tableName,
        $installer->getIdxName($tableName, array('catalog_entity_id', 'group_id', 'store_id')),
        array('catalog_entity_id', 'group_id', 'store_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    );
    $connection->addForeignKey(
        $installer->getFkName($tableName, 'catalog_entity_id', 'catalog/product', 'entity_id'),
        $tableName,
        'catalog_entity_id', $installer->getTable('catalog/' . $entity), 'entity_id'
    );
    $connection->addForeignKey(
        $installer->getFkName($tableName, 'group_id', 'customer/customer_group', 'customer_group_id'),
        $tableName,
        'group_id', $installer->getTable('customer/customer_group'), 'customer_group_id'
    );
    $connection->addForeignKey(
        $installer->getFkName($tableName, 'store_id', 'core/store', 'store_id'),
        $tableName,
        'store_id', $installer->getTable('core/store'), 'store_id'
    );
}

$installer->endSetup();