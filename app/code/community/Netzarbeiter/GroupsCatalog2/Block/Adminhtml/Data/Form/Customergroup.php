<?php

class Netzarbeiter_GroupsCatalog2_Block_Adminhtml_Data_Form_Customergroup
	extends Varien_Data_Form_Element_Multiselect
{
	/**
	 * Set the default value to USE_DEFAULT. This is needed if the extension is is installed
	 * after products already where created.
	 * 
	 * @return int
	 */
	public function getValue()
	{
		$value = parent::getValue();
		if (! is_null($value) && ! is_array($value))
		{
			$value = explode(',', (string) $value);
		}
		if (empty($value))
		{
			$value = array(Netzarbeiter_GroupsCatalog2_Helper_Data::USE_DEFAULT);
		}
		
		return $value;
	}

	public function getAfterElementHtml()
	{
		$html = parent::getAfterElementHtml();
		return $html . $this->_getAfterElementHtmlJs();
	}

	protected function _getAfterElementHtmlJs()
	{
		$id = $this->getHtmlId();
		$js = <<<EOT
<script type="text/javascript">
	Event.observe(window, 'load', function() {
		var label = $$('label[for="{$id}"]');
		alert(label[0].innerHTML);
		$(label[0]).innerHTML = 'Neuer Text';
		if (typeof label !== 'undefined') $(label[0]).innerHTML = 'ZZZZ';
		alert(label[0].innerHTML);
	});
</script>
EOT;
		return '';
	}
}
