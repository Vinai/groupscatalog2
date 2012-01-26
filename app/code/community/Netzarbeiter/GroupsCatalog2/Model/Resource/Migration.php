<?php

class Netzarbeiter_GroupsCatalog2_Model_Resource_Migration
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Implement method required by abstract
     */
    protected function _construct()
    {
    }

    /**
     * Copy all eav attribute values for a specified attribute from one table to another eav attribute table,
     * updating the attribute id. The old records remain in the old value table.
     *
     * @param Mage_Eav_Model_Entity_Attribute $oldAttribute
     * @param Mage_Eav_Model_Entity_Attribute $newAttribute
     * @return array Affected entity ids
     */
    public function copyAttributeValues(Mage_Eav_Model_Entity_Attribute $oldAttribute, Mage_Eav_Model_Entity_Attribute $newAttribute)
    {
        $selectFields = array('entity_type_id', new Zend_Db_Expr($newAttribute->getId()), 'store_id', 'entity_id', 'value');
        $insertFields = array('entity_type_id', 'attribute_id',                           'store_id', 'entity_id', 'value');
        $select = $this->_getWriteAdapter()->select()
                ->from($oldAttribute->getBackendTable(), $selectFields)
                ->where('attribute_id=?', $oldAttribute->getId());
        $update = $this->_getWriteAdapter()->insertFromSelect(
            $select, $newAttribute->getBackendTable(), $insertFields, Varien_Db_Adapter_Interface::INSERT_IGNORE
        );
        Mage::log($update);
        //$this->_getWriteAdapter()->query($update);

        $select->reset()->distinct(true)->from($oldAttribute->getBackendTable(), 'entity_id')
                ->where('attribute_id=:attribute_id');

        $entityIds = $this->_getReadAdapter()->fetchCol($select, array('attribute_id' => $oldAttribute->getId()));
        return $entityIds;
    }

    /**
     * Remove configuration settings from the core_config_data table by path
     *
     * @param string $path
     * @param bool $like
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Migration
     */
    public function _removeOldConfigTableSettings($path, $like = true)
    {
        if ($like)
        {
            $where = $this->_getWriteAdapter()->quoteInto('path LIKE ?', new Zend_Db_Expr("{$path}%"));
        }
        else
        {
            $where = $this->_getWriteAdapter()->quoteInto('path IN(?)', $path);
        }
        $this->_getWriteAdapter()->delete($this->getTable('core/config_data'), $where);
        return $this;
    }
}
