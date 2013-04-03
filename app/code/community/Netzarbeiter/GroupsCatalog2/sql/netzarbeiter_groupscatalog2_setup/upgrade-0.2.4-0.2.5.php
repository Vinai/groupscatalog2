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
 * @copyright  Copyright (c) 2013 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/* @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
/** @var $connection Varien_Db_Adapter_Pdo_Mysql */
$connection = $installer->getConnection();

foreach (array('product', 'category') as $entity) {
    $tableAlias = 'netzarbeiter_groupscatalog2/' . $entity . '_index';
    $tableName = $installer->getTable($tableAlias);

    // Drop existing index and foreign key constraint
    $connection->dropIndex(
        $tableName, $installer->getIdxName($tableName, array('entity_id', 'group_id', 'store_id'))
    );
    $connection->dropForeignKey(
        $tableName, $installer->getFkName($tableName, 'entity_id', 'catalog/' . $entity, 'entity_id')
    );

    // Change column entity_id to catalog_entity_id
    $connection->changeColumn($tableName, 'entity_id', 'catalog_entity_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => false,
        'comment' => ucwords($entity) . ' ID'
    ));

    // Recreate index and foreign key constraint to confirm with the new column name
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
}

$installer->endSetup();