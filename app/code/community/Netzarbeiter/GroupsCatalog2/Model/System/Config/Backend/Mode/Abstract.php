<?php
 
abstract class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Mode_Abstract
	extends Mage_Core_Model_Config_Data
{
	/**
	 * Return the indexer code for this backendss entity
	 *
	 * @abstract
	 * @return vstring
	 */
	abstract protected function _getIndexerCode();

	/**
	 * Set the index to require reindex
	 * 
	 * @return void
	 */
	protected function _afterSave()
	{
		if ($this->isValueChanged())
		{
			$indexerCode = $this->_getIndexerCode();
			$process = Mage::getModel('index/indexer')->getProcessByCode($indexerCode);
			$process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
		}
	}
}
