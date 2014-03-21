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

class Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Product
    extends Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Abstract
{
    /**
     * Initialize with table name and id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('netzarbeiter_groupscatalog2/product_index', 'id');
    }

    /**
     * Handle reindexing of single entity save events
     *
     * @param Mage_Index_Model_Event $event
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Product
     * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract::_processEvent()
     */
    public function catalogProductSave(Mage_Index_Model_Event $event)
    {
        $this->_reindexEntity($event);
        return $this;
    }

    /**
     * Handle reindexing of entity mass action events
     *
     * @param Mage_Index_Model_Event $event
     * @return Netzarbeiter_GroupsCatalog2_Model_Resource_Indexer_Product
     * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract::_processEvent()
     */
    public function catalogProductMassAction(Mage_Index_Model_Event $event)
    {
        $this->_reindexEntity($event);
        return $this;
    }

    /**
     * Return this indexers entity type code
     *
     * @return string
     */
    protected function _getEntityTypeCode()
    {
        return Mage_Catalog_Model_Product::ENTITY;
    }
}
