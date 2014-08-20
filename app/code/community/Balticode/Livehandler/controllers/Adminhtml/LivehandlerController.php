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
 * <p>Handles the AJAX request defined for the backend (from database table balticode_livehandler)</p>
 * <p>Security model: If Page itself can be displayed, then it is allowed to run actions, which are bound to current Livehandler model. If user switches page, then current model actions cannot be run any more.</p>
 * <p>If user does not show any activity for Balticode_Livehandler_IndexController::ALLOWED time seconds, then actions cannot be run after timeout.</p>
 *
 * @author matishalmann
 */
class Balticode_Livehandler_Adminhtml_LivehandlerController extends Mage_Adminhtml_Controller_Action {
    const ALLOWED_TIME = 1800;
    protected function _initAction() {
        return $this;
    }
    
    /**
     * 
     * @throws Exception
     */
    public function processAction() {
        $result = array();
        if (!$this->_getBalticode()->getConfigData('balticode_livehandler/main/enabled')) {
            throw new Exception('Module Balticode Livehandler is not enabled');
        }
        /*
         * Check if the process is in Session allowed list.
         * 
         */
        $session = Mage::getSingleton('core/session');
        $time = time();
        
        $processName = base64_decode($this->getRequest()->getParam('__path'));
        $processEntries = $session->getData('balticode_livehandler_entries');
        $website = Mage::app()->getStore()->getWebsiteId();
        $store = Mage::app()->getStore()->getStoreId();

        
        if (is_array($processEntries) && isset($processEntries[$processName]) && $time - $processEntries[$processName] < self::ALLOWED_TIME) {
            
            
            //execute the action.
            $isAdmin = true;
            $model = $processName;
            
            //get action by action name, website, store, is_admin = false
            $actionsCollection = Mage::getModel('balticode_livehandler/entry')->getCollection()->setModelFilter($model, $isAdmin, $website, $store);
            
            $classesRan = array();
            
            $result = array();
            foreach ($actionsCollection as $action) {
                $action->load($action->getId());
                if (isset($classesRan[$action->getModelClass()])) {
                    continue;
                }
                
                
                if (!isset($result['_is_error']) || !$result['_is_error']) {
                    $processEntries[$processName] = $time;
                    $result = $action->runAdmin($this->getRequest()->getPost());
                }
                $classesRan[$action->getModelClass()] = true;
                
            }
            
            
            
            Mage::getSingleton('core/session')->setData('balticode_livehandler_entries', $processEntries);
        } else {
            $result['_is_error'] = true;
        }
        
        
        echo Zend_Json::encode($result);
        die();
    }
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
    
    
}


