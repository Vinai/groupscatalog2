<?php

/* @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'groupscatalog2_groups', array(
		'label' => 'Hide/Show from Customer Groups',
		'group' => 'General',
		'type' => 'varchar',
		'input' => 'multiselect',
		'source' => 'netzarbeiter_groupscatalog2/entity_attribute_source_customergroup_withdefault',
		'backend' => 'netzarbeiter_groupscatalog2/entity_attribute_backend_customergroups',
		'frontend' => 'netzarbeiter_groupscatalog2/entity_attribute_frontend_customergroups',
		'input_renderer' => 'netzarbeiter_groupscatalog2/adminhtml_data_form_customergroup',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'required' => 0,
		'default' => Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT,
		'user_defined' => 0,
		'filterable_in_search' => 0,
		'is_configurable' => 0,
		'used_in_product_listing' => 1,
	));

$installer->addAttribute('catalog_category', 'groupscatalog2_groups', array(
		'label' => 'Hide/Show from Customer Groups',
		'group' => 'General Information',
		'type' => 'varchar',
		'input' => 'multiselect',
		'source' => 'netzarbeiter_groupscatalog2/entity_attribute_source_customergroup_withdefault',
		'backend' => 'netzarbeiter_groupscatalog2/entity_attribute_backend_customergroups',
		'input_renderer' => 'netzarbeiter_groupscatalog2/adminhtml_data_form_customergroup',
		'frontend' => 'netzarbeiter_groupscatalog2/entity_attribute_frontend_customergroups',
		'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
		'required' => 0,
		'default' => Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT,
		'user_defined' => 0,
	));

/*
 * Create product customer group index table
 */
$tableName = $installer->getTable('netzarbeiter_groupscatalog2/product_index');
$table = $installer->getConnection()->newTable($tableName)
	->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'nullable' => false,
		'primary' => true,
	), 'Product ID')
	->addColumn('group_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'nullable' => false,
		'primary' => true,
	), 'Customer Group ID')
		->setComment('GroupsCatalog2 Product Customer Group Index Table');
$installer->getConnection()->createTable($table);
$installer->getConnection()->addForeignKey(
	$installer->getFkName($tableName, 'product_id', 'catalog/product', 'entity_id'),
	$tableName, 'product_id',
	$installer->getTable('catalog/product'), 'entity_id'
);

/*
 * Create category customer group index table
 */
$tableName = $installer->getTable('netzarbeiter_groupscatalog2/category_index');
$table = $installer->getConnection()->newTable($tableName)
	->addColumn('category_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'nullable' => false,
		'primary' => true,
	), 'Category ID')
	->addColumn('group_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'nullable' => false,
		'primary' => true,
	), 'Customer Group ID')
		->setComment('GroupsCatalog2 Category Customer Group Index Table');
$installer->getConnection()->createTable($table);
$installer->getConnection()->addForeignKey(
	$installer->getFkName($tableName, 'category_id', 'catalog/category', 'entity_id'),
	$tableName, 'category_id',
	$installer->getTable('catalog/category'), 'entity_id'
);

$installer->endSetup();