<?php
 
class Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_Customergroup
{
	/**
	 * @var $_options array
	 */
	protected $_options;

	/**
	 * Return all customer groups as an option array.
	 * The normally hidden customer groups are included, e.g. NOT LOGGED IN
	 * @return array
	 */
	public function toOptionArray()
	{
		if (is_null($this->_options))
		{
			$this->_options = array(
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
}
