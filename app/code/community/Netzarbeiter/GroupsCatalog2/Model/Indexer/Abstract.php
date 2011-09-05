<?php

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

	/**
	 * @param Mage_Index_Model_Event $event
	 * @return void
	 */
	protected function _registerEvent(Mage_Index_Model_Event $event)
	{
		/* @var $entity Varien_Object */
		$entity = $event->getDataObject(); // could be a catalog/product or catalog/category entity, too
		$eventType = $event->getType();
		$attrCode = Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE;
		
		if ($eventType == Mage_Index_Model_Event::TYPE_SAVE)
		{
			if ($entity->dataHasChangedFor($attrCode))
			{
				$event->setData('entity_ids', array($entity->getId()));
			}
		}
		elseif ($eventType == Mage_Index_Model_Event::TYPE_MASS_ACTION)
		{
			$attributeData = $entity->getAttributesData();
			if (isset($attributeData[$attrCode]))
			{
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
		if ($event->getData('entity_ids'))
		{
			$this->callEventHandler($event);
		}
	}
}
