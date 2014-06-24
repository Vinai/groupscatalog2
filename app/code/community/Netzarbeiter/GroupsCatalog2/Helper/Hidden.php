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

class Netzarbeiter_GroupsCatalog2_Helper_Hidden extends Mage_Core_Helper_Abstract
{
    /**
     * If set to true, display the configured hidden entity message.
     * Avoid adding the configured message more then once if more then one hidden entity is loaded,
     * e.g. the main product and a related product or a product in a banner in the footer block.
     *
     * @var bool
     */
    protected $_addMessage = true;

    /**
     * List of routes that have to match the current request
     * for the configured message top be displayed.
     *
     * @var array
     */
    protected $_displayMessageRoutes = array();

    /**
     * Initialize the _displayMessageRoutes property.
     */
    public function __construct()
    {
        $this->_displayMessageRoutes = array(
            'catalog_product_view' => Mage_Catalog_Model_Product::ENTITY,
            'catalog_category_view' => Mage_Catalog_Model_Category::ENTITY
        );
    }

    /**
     * Main entry method to apply hidden entity handling.
     *
     * @param $entityTypeCode
     * @return Netzarbeiter_GroupsCatalog2_Helper_Hidden
     */
    public function applyHiddenEntityHandling($entityTypeCode)
    {
        $redirectSet = $this->_applyHiddenEntityRedirect($entityTypeCode);

        $this->_applyHiddenEntityMsg($entityTypeCode);

        if ($redirectSet) {
            // Since we want to redirect anyway there really is no need to continue.
            exit();
        }
        return $this;
    }

    /**
     * Apply redirects for hidden entity page requests if configured.
     *
     * @param string $entityTypeCode
     * @return bool true if redirect was set
     */
    protected function _applyHiddenEntityRedirect($entityTypeCode)
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $handlingTypeSetting = 'entity_hidden_behaviour_customer';
            $targetRouteSetting = 'entity_hidden_redirect_customer';
        } else {
            $handlingTypeSetting = 'entity_hidden_behaviour_guest';
            $targetRouteSetting = 'entity_hidden_redirect_guest';
        }
        $type = $this->_getHelper()->getConfig($handlingTypeSetting);
        switch ($type) {
            case Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_HiddenEntityHandling::HIDDEN_ENTITY_HANDLING_REDIRECT:
                return $this->_applyEntityRedirectTargetRoute($targetRouteSetting);
                break;

            case Netzarbeiter_GroupsCatalog2_Model_System_Config_Source_HiddenEntityHandling::HIDDEN_ENTITY_HANDLING_REDIRECT_PARENT:
                return $this->_applyEntityRedirectParentDirectory($targetRouteSetting);
                break;

            default:
                return false;
        }
    }

    /**
     * Redirect customer to configured target route.
     *
     * If the current request is to the configured target route, don't
     * redirect the customer at all.
     *
     * @param $targetRouteSetting
     * @return bool Redirect was applied
     */
    protected function _applyEntityRedirectTargetRoute($targetRouteSetting)
    {
        $targetRoute = $this->_getHelper()->getConfig($targetRouteSetting);
        if ($this->_isCurrentRoute($targetRoute)) {
            // Don't display the message if the current request matches the target route
            $this->_addMessage = false;
            return false;
        }
        if ('customer/account/login' == $targetRoute) {
            // Special case, set after_auth_url in session for redirects to the login page
            $currentUrl = $this->_getCurrentUrl();
            Mage::getSingleton('customer/session')->setAfterAuthUrl($currentUrl);
        }

        $this->_sendRedirectHeaders($targetRoute);
        return true;
    }

    /**
     * Redirect customer to parent directory of current request.
     *
     * If the current redirect is to the top level (matches the base URL), don't do a redirect.
     *
     * @param $targetRouteSetting
     * @return bool Redirect was applied
     */
    protected function _applyEntityRedirectParentDirectory($targetRouteSetting)
    {
        $currectUrl = $this->_getCurrentUrl();
        $baseUrl = Mage::getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_WEB,
            Mage::app()->getStore()->isCurrentlySecure()
        );

        // Cut off query string at the end if present
        if (($pos = strpos($currectUrl, '?')) !== false) {
            $currectUrl = substr($currectUrl, 0, $pos);
        }

        // Paranoid check - _getCurrentUrl() should always be within the current base URL ;)
        if (strpos($currectUrl, $baseUrl) === 0) {

            // Remove base URL from beginning
            $path = substr($currectUrl, strlen($baseUrl));

            if (strlen($path) > 0) {
                // Only apply dirname() if there is a parent directory
                $io = new Varien_Io_File;
                if (($path = $io->dirname($path)) === '.') {
                    $path = '';
                }

                // Append configured category file suffix if this still isn't a top level request
                if (strlen($path) > 0) {
                    $path .= Mage::helper('catalog/category')->getCategoryUrlSuffix();
                }
                $targetUrl = $baseUrl . $path;
                if ($targetUrl != $currectUrl) {
                    $this->_sendRedirectHeaders($targetUrl, false);
                    return true;
                }
            }
        }
        // Don't display configured message if we already are requesting the target (top level) url.
        $this->_addMessage = false;
        return false;
    }

    /**
     * Return the URL of the current request.
     *
     * @return string
     */
    protected function _getCurrentUrl()
    {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $currentUrl = Mage::getSingleton('core/url')->sessionUrlVar($currentUrl);
        return $currentUrl;
    }

    /**
     * Set redirect to passed target route or URL on response and send headers.
     *
     * @param string $url
     * @param bool $isMagentoRoute If set to true pass through Mage::getUrl()
     * @return Netzarbeiter_GroupsCatalog2_Model_Observer
     */
    protected function _sendRedirectHeaders($url, $isMagentoRoute = true)
    {
        if ($isMagentoRoute) {
            $url = Mage::getSingleton('core/url')->sessionUrlVar(Mage::getUrl($url));
        }
        Mage::app()->getResponse()
                ->setRedirect($url, 307)
                ->sendHeaders();
        Mage::app()->getRequest()->setDispatched(true);

        return $this;
    }

    /**
     * Check if the current request matches the passed route.
     *
     * @param string $targetRoute
     * @return bool
     */
    protected function _isCurrentRoute($targetRoute)
    {
        // Ignore parameters for now
        $targetRoute = array_slice(explode('/', $targetRoute), 0, 3);
        $front = Mage::app()->getFrontController();
        if (!isset($targetRoute[1])) {
            $targetRoute[1] = $front->getDefault('controller');
        }
        if (!isset($targetRoute[2])) {
            $targetRoute[2] = $front->getDefault('action');
        }
        $req = Mage::app()->getRequest();
        $current = array(
            $req->getModuleName(),
            $req->getControllerName(),
            $req->getActionName()
        );
        return $targetRoute === $current;
    }

    /**
     * Apply the configured splash message to display if a hidden entity is accessed.
     *
     * @param string $entityTypeCode
     */
    protected function _applyHiddenEntityMsg($entityTypeCode)
    {
        if ($this->_shouldDisplayMessage($entityTypeCode)) {
            $this->_addMessage = false;
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $message = $this->_getHelper()->getConfig('entity_hidden_msg_customer');
            } else {
                $message = $this->_getHelper()->getConfig('entity_hidden_msg_guest');
            }
            if (mb_strlen($message, 'UTF-8') > 0) {
                /* @var $session Mage_Core_Model_Session */
                $session = Mage::getSingleton('core/session');
                if (!$this->_messageExistsInSession($session, $message)) {
                    $session->addError($message);
                }
            }
        }
    }

    /**
     * Check if a configured message should be shown.
     *
     * @param string $entityTypeCode
     * @return bool
     */
    protected function _shouldDisplayMessage($entityTypeCode)
    {
        // Avoid double messages if two hidden entities are loaded
        if ($this->_addMessage) {
            if ($action = Mage::app()->getFrontController()->getAction()) {
                $fullActionName = $action->getFullActionName();
                if (isset($this->_displayMessageRoutes[$fullActionName])) {
                    if ($this->_displayMessageRoutes[$fullActionName] == $entityTypeCode) {
                        if ($this->_getHelper()->getConfig('display_entity_hidden_msg')) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * return true if the specified message already is set on the passed session model.
     *
     * Simply setting a flag on $this won't work if the parent directory we redirect to is also hidden.
     * Messages are applied during separate requests in that case, so a flag won't be available.
     *
     * @param Mage_Core_Model_Session_Abstract $session
     * @param string $message
     * @return bool
     */
    protected function _messageExistsInSession(Mage_Core_Model_Session_Abstract $session, $message)
    {
        foreach ($this->_getMessages($session) as $msg) {
            if ($msg->getCode() === $message) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return all currently registered messages from block and specified session.
     *
     * @param Mage_Core_Model_Session_Abstract $session
     * @return array
     */
    protected function _getMessages(Mage_Core_Model_Session_Abstract $session)
    {
        $messages = array();
        $type = 'error';

        // If the message block already has been instantiated, get those messages
        /** @var $block Mage_Core_Block_Messages */
        if ($block = Mage::app()->getLayout()->getBlock('messages')) {
            $messages = array_merge($messages, $block->getMessages($type));
        }

        // Merge in the messages from the session
        $messages = array_merge($messages, $session->getMessages()->getItems($type));
        return $messages;
    }

    /**
     * Helper convenience method.
     *
     * @return Netzarbeiter_GroupsCatalog2_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('netzarbeiter_groupscatalog2');
    }
}
