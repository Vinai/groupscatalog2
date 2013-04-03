<?php

class Netzarbeiter_GroupsCatalog2_Model_Catalog_Resource_Category_Flat_Collection
    extends Mage_Catalog_Model_Resource_Category_Flat_Collection
{

    /**
     * Add filter by entity id(s).
     *
     * Original function had only 'entity_id' in addFieldToFilter(), which
     * is an ambigious name since the Observer joins the index table.
     * (copied from CE 1.7.0.2)
     *
     * @param mixed $categoryIds
     * @return Mage_Catalog_Model_Resource_Category_Flat_Collection
     */
    public function addIdFilter($categoryIds)
    {
        if (is_array($categoryIds)) {
            if (empty($categoryIds)) {
                $condition = '';
            } else {
                $condition = array('in' => $categoryIds);
            }
        } elseif (is_numeric($categoryIds)) {
            $condition = $categoryIds;
        } elseif (is_string($categoryIds)) {
            $ids = explode(',', $categoryIds);
            if (empty($ids)) {
                $condition = $categoryIds;
            } else {
                $condition = array('in' => $ids);
            }
        }
        $this->addFieldToFilter('main_table.entity_id', $condition);
        return $this;
    }

}