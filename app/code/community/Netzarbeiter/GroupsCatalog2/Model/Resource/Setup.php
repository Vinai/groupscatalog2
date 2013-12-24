<?php


class Netzarbeiter_GroupsCatalog2_Model_Resource_Setup extends Mage_Catalog_Model_Resource_Setup
{
    /**
     * @param string $entityType 'catalog_category' or 'catalog_product'
     * @return $this
     */
    public function dropIndexTable($entityType)
    {
        /*
         * Drop customer group index table of $type
         */
        $tableAlias = Mage::helper('netzarbeiter_groupscatalog2')
            ->getIndexTableByEntityType($entityType);
        $tableName = $this->getTable($tableAlias);

        // Make re-installs of this module possible, even if the db wasn't cleaned up completely
        if ($this->getConnection()->isTableExists($tableName)) {
            $this->getConnection()->dropTable($tableName);
        }
        return $this;
    }

    /**
     * @param string $entityType 'catalog_category' or 'catalog_product'
     * @return $this
     */
    public function createIndexTable($entityType)
    {
        /*
         * Create $type customer group index table
         */
        $tableAlias = Mage::helper('netzarbeiter_groupscatalog2')
            ->getIndexTableByEntityType($entityType);
        $tableName = $this->getTable($tableAlias);
        $entityTable = Mage::getSingleton('eav/config')
            ->getEntityType($entityType)
            ->getEntityTable();

        $table = $this->getConnection()->newTable($tableName)

            ->addColumn('catalog_entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'primary' => true,
                'unsigned' => true,
                'nullable' => false,
            ), 'Product ID')

            ->addColumn('group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'primary' => true,
                'unsigned' => true,
                'nullable' => false,
            ), 'Customer Group Id')

            ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'primary' => true,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
            ), 'Store ID')

            ->addForeignKey(
                $this->getFkName($tableName, 'catalog_entity_id', $entityTable, 'entity_id'),
                'catalog_entity_id', $this->getTable($entityTable), 'entity_id',
                Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

            ->addForeignKey(
                $this->getFkName($tableName, 'group_id', 'customer/customer_group', 'customer_group_id'),
                'group_id', $this->getTable('customer/customer_group'), 'customer_group_id',
                Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

            ->addForeignKey(
                $this->getFkName($tableName, 'store_id', 'core/store', 'store_id'),
                'store_id', $this->getTable('core/store'), 'store_id',
                Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

            ->setComment('GroupsCatalog2 ' . $entityType . ' Customer Group Index Table');
        $this->getConnection()->createTable($table);
    }

    public function addGroupsCatalogAttribute($entityType)
    {
        $attributeCode = Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE;
        $this->addAttribute($entityType, $attributeCode, array(
            'label' => 'Hide/Show from Customer Groups',
            'group' => 'General',
            'type' => 'text',
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
    }
} 