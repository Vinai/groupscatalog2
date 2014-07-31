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

class Netzarbeiter_GroupsCatalog2_Model_CatalogSearch_Resource_Fulltext_Collection
    extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
    /**
     * Add the groupscatalog filter to the select object so the number of search
     * results on the pager is correct.
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $select = parent::getSelectCountSql();
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        if ($helper->isModuleActive() && !$helper->isDisabledOnCurrentRoute()) {
            $customerGroupId = $helper->getCustomerGroupId();
            Mage::getResourceSingleton('netzarbeiter_groupscatalog2/filter')
                    ->addGroupsCatalogFilterToSelectCountSql($select, $customerGroupId, $this->getStoreId());
        }
        return $select;
    }
}
