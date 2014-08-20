<?php
/*

 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * or OpenGPL v3 license (GNU Public License V3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * or
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@balticode.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category   Balticode
 * @package    Balticode_Livehandler
 * @copyright  Copyright (c) 2013 Aktsiamaailm LLC (http://en.balticode.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt  GNU Public License V3.0
 * @author     Matis Halmann
 * 
 
 */

/**
 * <p>Writes javascript before_body_end when request_var from balticode_livehandler table matches Magentos request full name.</p>
 * <p>Sets up listener for corresponding Magento model, which comes from model_class field.</p>
 * <p>Listener works only when matching javascript is rendered and user has not left the page.</p>
 * <p>On other cases this class does nothing</p>
 *
 * @author matishalmann
 */
class Balticode_LiveHandler_Block_Footer extends Mage_Core_Block_Template {
    //put your code here
    
    
    protected function _toHtml() {
        
        $resultHtml = '';
        
        if (!$this->_getBalticode()->getConfigData('balticode_livehandler/main/enabled')) {
            Mage::getSingleton('core/session')->unsetData('balticode_livehandler_entries');
            return $resultHtml;
        }
        
        $isAdmin = false;
        $urlKey = 'balticode_livehandler/index/process';
        if (Mage::app()->getStore()->isAdmin()) {
            $isAdmin = true;
            $urlKey = 'balticode_livehandler/adminhtml_livehandler/process';
        }
        $path = '';
        $website = Mage::app()->getStore()->getWebsiteId();
        $store = Mage::app()->getStore()->getStoreId();
        $request = Mage::app()->getRequest();
        
        Mage::getSingleton('core/session')->unsetData('balticode_livehandler_entries');
        
        if ($isAdmin) {
            if ($request->getParam('store')) {
                $store = Mage::getModel('core/store')->load($request->getParam('store'))->getId();
            }
            if ($request->getParam('website')) {
                $website = Mage::getModel('core/website')->load($request->getParam('website'))->getId();
            }
            
        }
        
        $routeName = $request->getRequestedRouteName();
        $controllerName = $request->getRequestedControllerName();
        $actionName = $request->getRequestedActionName();
        $path = $routeName . '/' . $controllerName . '/' . $actionName;
//        $resultHtml .= $path . '/' . $website . '/' . $store;


        $actionsCollection = Mage::getModel('balticode_livehandler/entry')->getCollection()->setFilter($path, $isAdmin, $website, $store);
        $lastRequestVar = null;
        $allowedActions = array();
        foreach ($actionsCollection as $id => $action) {
            $suppliedParams = array();
            $action->load($action->getId());
            $targetUrl = '';
            $css = '';
            $js = '';
            $html = '';
            if ($action->getData('request_var') !== $lastRequestVar || true) {
                //if we do have same path and defined many actions, then only the first one in the path will be executed.

                $js = trim($action->getJs());
                $css = trim($action->getCss());
                $html = trim($action->getHtml());

                if ($action->getData('model_class') != '') {
                    $allowedActions[$action->getData('model_class')] = time();
                }
                $params['__path'] = base64_encode($action->getData('model_class'));

                if ($isAdmin) {
                    $targetUrl = Mage::helper('adminhtml')->getUrl($urlKey, $params);
                } else {
                    $targetUrl = $this->getUrl($urlKey, $params);
                }

                $lastRequestVar = $action->getData('request_var');

                if ($html != '') {
                    $resultHtml .= $html;
                }
                if ($css != '') {
                    $resultHtml .= <<<EOT
    <style type="text/css">
                {$css}
    </style>
EOT;
                }
                if ($js != '') {
                    $urlString = '';
                    if ($targetUrl != '') {
                        
                        $urlString = <<<EOT
   var action_url = '{$targetUrl}';
EOT;
                    }
                    $resultHtml .= <<<EOT
    <script type="text/javascript">
                //<![CDATA[
                (function() {
                    {$urlString}

                    {$js}
                })();
                //]]>
    </script>
EOT;
                }
            }
            
            //execute each action and supply parameters
            
            //execute makes the following
            /*
             * echo the target URL for the proper action handler
             * echo action handlers CSS
             * echo action handlers JS
             * echo action handler HTML
             * 
             * action url would have to execute the correct action
             * 
             * 
             * 
             */
        }
        Mage::getSingleton('core/session')->setData('balticode_livehandler_entries', $allowedActions);
        return $resultHtml;
    }
    
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
}

