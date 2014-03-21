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

class Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Product
    extends Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Abstract
{
    /**
     * Return the indexer code
     *
     * @return string
     * @see Netzarbeiter_GroupsCatalog2_Model_System_Config_Backend_Customergroup_Abstract::_afterSave()
     */
    protected function _getIndexerCode()
    {
        return 'groupscatalog2_product';
    }
}
