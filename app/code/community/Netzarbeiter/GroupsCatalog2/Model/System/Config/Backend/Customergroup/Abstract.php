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

abstract class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Abstract extends Mage_Core_Model_Config_Data
{
    /**
     * Return the indexer code for this backend entity
     *
     * @abstract
     * @return string
     */
    abstract protected function _getIndexerCode();

    /**
     * Disable saving if multiselect fields are disabled
     * 
     * @return $this|Mage_Core_Model_Abstract
     */
    public function save()
    {
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        if ($helper->getConfig('show_multiselect_field')) {
            parent::save();
        }
        return $this;
    }
    
    /**
     * Sanitize settings and set the index to require reindex
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (is_array($value) && 1 < count($value)) {
            // if USE_NONE is selected remove all other selected groups
            if (in_array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE, $value)) {
                $value = array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE);
                $this->setValue($value);
            }
        }
        // Can't use isValueChanged() because it compares string value (old) with array (new)
        $oldValue = explode(',', (string)$this->getOldValue());
        if ($this->getValue() != $oldValue) {
            $indexerCode = $this->_getIndexerCode();
            $process = Mage::getModel('index/indexer')->getProcessByCode($indexerCode);
            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
        return parent::_beforeSave();
    }
}
