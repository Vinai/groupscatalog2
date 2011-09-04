<?php

abstract class Netzarbeiter_GroupsCatalog2_Model_Indexer_Abstract extends Mage_Index_Model_Indexer_Abstract
{
	/**
	 * Return the entity ids depending on the entity type.
	 * E.g. for products return $entity->getProductIds()
	 *
	 * @abstract
	 * @param Varien_Object $container
	 * @return array
	 */
	abstract protected function _getEntityIdsFromContainer(Varien_Object $container);

	/**
	 * @param Mage_Index_Model_Event $event
	 * @return void
	 */
	protected function _registerEvent(Mage_Index_Model_Event $event)
	{
		/* @var $container Varien_Object */
		$container = $event->getDataObject(); // could be a catalog/product or catalog/category entity, too
		$eventType = $event->getType();
		$attrCode = Netzarbeiter_GroupsCatalog2_Helper_Data::HIDE_GROUPS_ATTRIBUTE;
		
		if ($eventType == Mage_Index_Model_Event::TYPE_SAVE)
		{
			if ($container->dataHasChangedFor($attrCode))
			{
				$event->setData('entity_ids', array($container->getId()));
			}
		}
		elseif ($eventType == Mage_Index_Model_Event::TYPE_MASS_ACTION)
		{
			$attributeData = $container->getAttributesData();
			if (isset($attributeData[$attrCode]) ||
				(isset($attributeData['force_reindex_required']) && $attributeData['force_reindex_required']))
			{
				$event->setData('entity_ids', $this->_getEntityIdsFromContainer($container));
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
