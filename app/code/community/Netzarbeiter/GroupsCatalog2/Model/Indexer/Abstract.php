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

abstract class Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Return the entity ids depending on the entity type.
     * E.g. for products return $entity->getProductIds()
     *
     * @abstract
     * @param Varien_Object $entity
     * @return array
     */
    abstract protected function _getEntityIdsFromEntity(Varien_Object $entity);

    public function __construct()
    {
        /**
         * Add the customer groups as a matched entity in addition to category or product entity.
         */
        $this->_matchedEntities[Mage_Customer_Model_Group::ENTITY] = array(
            Mage_Index_Model_Event::TYPE_SAVE
        );
        parent::__construct();
    }


    /**
     * @param Mage_Index_Model_Event $event
     * @return void
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        /* @var $entity Mage_Core_Model_Abstract|Varien_Object */
        $entity = $event->getDataObject(); // could be a catalog/product or catalog/category entity, too
        $eventType = $event->getType();
        $attrCode = Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE;

        if ($eventType == Mage_Index_Model_Event::TYPE_SAVE) {
            if ($entity instanceof Mage_Customer_Model_Group) {
                // only trigger reindex for new customer group
                if ($entity->isObjectNew()) {
                    $event->setData('entity_ids', array($entity->getId()));
                }
            }
            elseif ($entity->dataHasChangedFor($attrCode)) {
                $event->setData('entity_ids', array($entity->getId()));
            }
        } elseif ($eventType == Mage_Index_Model_Event::TYPE_MASS_ACTION) {
            $attributeData = $entity->getAttributesData();
            if (isset($attributeData[$attrCode])) {
                $event->setData('entity_ids', $this->_getEntityIdsFromEntity($entity));
            }
        }
    }

    /**
     * Calls entity_type + event_type handler on the indexer resource model
     * E.g. $this->getResource()->catalogProductMassAction() or ...->catalogCategorySave()
     *
     * @param Mage_Index_Model_Event $event
     * @return void
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        if ($event->getData('entity_ids')) {
            $this->callEventHandler($event);
        }
    }
}
