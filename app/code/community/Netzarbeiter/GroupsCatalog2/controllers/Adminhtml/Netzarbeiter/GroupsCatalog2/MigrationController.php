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

class Netzarbeiter_GroupsCatalog2_Adminhtml_Netzarbeiter_GroupsCatalog2_MigrationController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/tools/netzarbeiter_groupscatalog2');
        $this->renderLayout();
    }

    public function doStepAction()
    {
        try {
            $step = $this->getRequest()->getParam('migration_step');
            if (!$step) {
                Mage::throwException($this->__('No migration step specified.'));
            }
            Mage::helper('netzarbeiter_groupscatalog2/migration')->doStep($step);

            $this->_getSession()->addSuccess($this->__('Finished migration step "%s" successfully.', $step));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/tools/netzarbeiter_groupscatalog2');
    }
}
