<?php

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
        try
        {
            $step = $this->getRequest()->getParam('migration_step');
            if (!$step) {
                Mage::throwException($this->__('No migration step specified.'));
            }
            Mage::helper('netzarbeiter_groupscatalog2/migration')->doStep($step);

            $this->_getSession()->addSuccess($this->__('Finished migration step "%s" successfully.', $step));
        }
        catch (Exception $e)
        {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/tools/netzarbeiter_groupscatalog2');
    }
}
