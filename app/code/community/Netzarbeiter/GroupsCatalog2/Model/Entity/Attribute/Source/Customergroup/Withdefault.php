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
 * @copyright  Copyright (c) 2012 Vinai Kopp http://netzarbeiter.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Netzarbeiter_GroupsCatalog2_Model_Entity_Attribute_Source_Customergroup_Withdefault
	extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	/**
	 * Return all options for the customergroups_groups attributes, i.e. a list of all
	 * customer groups with the additional options USE DEFAULT and NONE
	 *
	 * @return array
	 */
	public function getAllOptions()
	{
		if (is_null($this->_options))
		{
			$this->_options = array(
				array(
					'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT,
					'label' => Mage::helper('netzarbeiter_groupscatalog2')->__('[ USE DEFAULT ]')
				),
				array(
					'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::USE_NONE,
					'label' => Mage::helper('netzarbeiter_groupscatalog2')->__('[ NONE ]')
				)
			);
			foreach (Mage::helper('netzarbeiter_groupscatalog2')->getGroups() as $group)
			{
				/* @var $group Mage_Customer_Model_Group */
				$this->_options[] = array(
					'value' => $group->getId(),
					'label' => $group->getCode(),
				);
			}
		}
		return $this->_options;
	}

	/**
	 * Get the label for an option value. If the value is a comma
	 * separated string or an array, return an array of matching
	 * option labels.
	 *
	 * @param string|integer $value
	 * @return string|array
	 */
	public function getOptionText($value)
	{
		$options = $this->getAllOptions();

		if (is_scalar($value) && strpos($value, ','))
		{
			$value = explode(',', $value);
		}
		if (is_array($value))
		{
			$values = array();
			foreach ($options as $item)
			{
				if (in_array($item['value'], $value))
				{
					$values[] = $item['label'];
				}
			}
			return $values;
		}
		else
		{
			foreach ($options as $item)
			{
				if ($item['value'] == $value)
				{
					return $item['label'];
				}
			}
		}
		return false;
	}

	/**
	 * Retrieve the flat column definition.
	 * Probably not needed because of the planned index.
	 *
	 * @return array
	 */
	/*
	public function getFlatColums()
	{
		$attributeCode = $this->getAttribute()->getAttributeCode();
		$column = array(
			'default' => null,
			'extra' => null
		);

		if (Mage::helper('core')->useDbCompatibleMode())
		{
			$column['type'] = 'text';
			$column['is_null'] = true;
		}
		else
		{
			$column['type'] = Varien_Db_Ddl_Table::TYPE_TEXT;
			$column['length'] = Varien_Db_Ddl_Table::DEFAULT_TEXT_SIZE;
			$column['nullable'] = true;
			$column['comment'] = $attributeCode . ' column';
		}

		return array($attributeCode => $column);
	}
	*/

	/**
	 * No Index needed on flat table column.
	 * Probably not needed because of the planned index
	 *
	 * @return array
	 */
	/*
	public function getFlatIndexes()
	{
		return parent::getFlatIndexes();
	}
	*/

	/**
	 * Retrieve Select For Flat Attribute update.
	 * Probably not needed because of the planned index
	 *
	 * @param int $store
	 * @return Varien_Db_Select|null
	 */
	/*
	public function getFlatUpdateSelect($store)
	{
		return Mage::getResourceModel('eav/entity_attribute')
				->getFlatUpdateSelect($this->getAttribute(), $store);
	}
	*/
}
