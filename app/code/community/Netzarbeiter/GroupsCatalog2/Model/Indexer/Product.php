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

class Netzarbeiter_GroupsCatalog2_Model_Indexer_Product extends Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract
{
    /**
     * For the matched entity and events the _registerEvent() and _processEvent() methods will be called
     *
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
        )
    );

    /**
     * Initialize the resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('netzarbeiter_groupscatalog2/indexer_product');
    }

    /**
     * Return the name of the index in the adminhtml
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('netzarbeiter_groupscatalog2')->__('GroupsCatalog Products');
    }

    /**
     * Return the description of the index in the adminhtml
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('netzarbeiter_groupscatalog2')->__('Reindex Customergroup Product Visibility');
    }

    /**
     * Return the product ids from a category mass action event
     *
     * @param Varien_Object $entity
     * @return array|null
     * @see Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract::_registerEvent()
     */
    protected function _getEntityIdsFromEntity(Varien_Object $entity)
    {
        return $entity->getProductIds();
    }
}
