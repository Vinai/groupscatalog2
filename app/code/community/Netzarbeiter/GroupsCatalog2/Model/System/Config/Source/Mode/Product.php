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

class Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_Mode_Product
{
    /**
     * Return the mode options for the product configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        return array(
            array(
                'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_SHOW_BY_DEFAULT,
                'label' => $helper->__('Show products by default')
            ),
            array(
                'value' => Netzarbeiter_GroupsCatalog2_Helper_Data::MODE_HIDE_BY_DEFAULT,
                'label' => $helper->__('Hide products by default')
            ),
        );
    }
}
