<?php

// adminhtml/system_config_form_field
class Netzarbeiter_GroupsCatalog2_Block_Adminhtml_System_Config_Renderer_Customergroup
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        if (! $helper->getConfig('show_multiselect_field')) {
            $element->setComment(
                $this->__('This field is read-only.<br/>(change the "Show multiselect customer group fields" option further down to enable this field)')
            );
        }
        return parent::render($element);
    }
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = Mage::helper('netzarbeiter_groupscatalog2');
        if ($helper->getConfig('show_multiselect_field')) {
            $html = parent::_getElementHtml($element);
        } else {
            $label = new Varien_Data_Form_Element_Text();
            $label->setData($element->getData())
                ->setType('text')
                ->setExtType('textfield')
                ->addClass('input-text')
                ->setReadOnly(true)
                ->setForm($element->getForm())
                ->setId($element->getId());
            $value = explode(',', (string) $label->getValue());
            $groups = $helper->getGroupNamesAsString($value);
            $label->setValue($groups);
            $html = $label->getElementHtml();
        }
        return $html;
    }
} 