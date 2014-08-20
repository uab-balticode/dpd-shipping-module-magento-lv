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
 * <p>Represents one entry of balticode_livehandler database table.</p>
 * <p>Performs following:</p>
 * <ul>
     <li>Render javascript before_body_end part of the page, when Magento request variables match</li>
     <li>Generates unique URL, where sending data to causes to fire service() function on the model_class instance.</li>
     <li>Works only when javascript at the bottom of the page is rendered. Which means if you see this page you can fire the action.</li>
 </ul>
 * <p>Structure:</p>
 * <ul>
     <li><b>id</b> - unique auto incement id</li>
     <li><b>name</b> - Human readable description</li>
     <li><b>is_enabled</b> - 1 means action is enabled, 0 means action is disabled</li>
     <li><b>is_admin</b> - 1 means action is admin only, 0 means action is public only</li>
     <li><b>request_var</b> - Magento request var name, which current action must match. Example: adminhtml/sales_order/index - Magentos Sales Order Grid</li>
     <li><b>store_id</b> - id of the store this action should run in</li>
     <li><b>website_id</b> - if of the website this action should run in</li>
     <li><b>model_class</b> - full Magento model name for the implementing class. Must be instance of Balticode_Livehandler_Model_Abstract</li>
     <li><b>created_time</b> - when was this database entry created</li>
     <li><b>update_time</b> - when was this database entry updated</li>
     <li><b>cached_attributes</b> - not in use, could be used to store data in serialized form</li>
     <li><b>parameters</b> - not in use, could be used to store data in serialized form</li>
     <li><b>css</b> - used to declare extra css rules, those can also be declared by implementing class. Will be injected to before_body_end as style tag.</li>
     <li><b>js</b> - used to display extra javascript, those can be also be declared by implementing class. Will be injetected before_body_end in script[type=javascript] tag</li>
     <li><b>html</b> - used to display extra HTML, those can be also be declared by implementing class. Will be injetected before_body_end</li>
 </ul>
 *
 * @author matishalmann
 */
class Balticode_Livehandler_Model_Entry  extends Mage_Core_Model_Abstract {
    
    /**
     * <p>instance of implementing class</p>
     * @var Balticode_Livehandler_Model_Adminhtml_Gridmanager
     */
    private $_caller;
    
    public function _construct() {
        parent::_construct();
        $this->_init('balticode_livehandler/entry');
        
    }
    
    /**
     * <p>Sets up the instance of implementing class</p>
     * @return Balticode_Livehandler_Model_Entry
     */
    protected function _afterLoad() {
        $load = parent::_afterLoad();
        if ($this->getData('model_class') != '') {
            $this->_caller = Mage::getModel($this->getData('model_class'));
        }
        return $load;
    }

    /**
     * <p>Loads up extra javascript from js property or from implementing class (preferred).</p>
     * @return string
     */
    public function getJs() {
        $js = trim($this->getData('js'));
        if ($js == '' && $this->_caller != null) {
            $js = $this->_caller->getJs();
        }
        
        return $js;
        
    }
    

    /**
     * <p>Loads up extra CSS from css property or from implementing class (preferred).</p>
     * @return string
     */
    public function getCss() {
        $css = trim($this->getData('css'));
        if ($css == '' && $this->_caller != null) {
            $css = $this->_caller->getCss();
        }
        
        return $css;
        
    }
    
    /**
     * <p>Loads up extra HTML from html property or from implementing class (preferred).</p>
     * @return string
     */
    public function getHtml() {
        $html = trim($this->getData('html'));
        if ($html == '' && $this->_caller != null) {
            $html = $this->_caller->getHtml();
        }
        
        return $html;
        
    }
    

    /**
     * <p>Attempts to call implementing classes <code>service</code> function with posted parameters in frontend environment.</p>
     * @param array $postedData posted parameters
     * @return array returned result
     */
    public function runPublic($postedData) {
        $result = array();
        if ($this->_caller != null) {
            $result = $this->_caller->service($postedData);
        }
        return $result;
    }
    
    
    /**
     * <p>Attempts to call implementing classes <code>service</code> function with posted parameters in adminhtml environment.</p>
     * @param array $postedData posted parameters
     * @return array returned result
     */
    public function runAdmin($postedData) {
        $result = array();
        if ($this->_caller != null) {
            $result = $this->_caller->service($postedData);
        }
        return $result;
    }
}
