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
 * <p>Base class for action buttons, which are displayed at Magento Administrators Sales Order Grid.</p>
 * <p>Buttons are shown when clicking on 'Show Order Info' button.</p>
 * <p>Buttons can have onclick handler and desired action bound to it, when it is clicked.</p>
 *
 * @author Matis
 */
abstract class Balticode_Livehandler_Model_Action_Abstract {
    /**
     * <p>Buttons are sorted by position in ascending order</p>
     * @var int
     */
    protected $_position;
    
    /**
     * <p>name of the button relative to balticode_livehandler/action_&lt;button-name&gt; or full magento model name</p>
     * @var string
     */
    protected $_code;
    protected $_label;
    protected $_onClick;
    protected $_longOnClick;
    protected $_cssClass;
    
    /**
     * Decides whether the button can be displayed for the current order
     * @return bool true, when this button can be displayed for current order.
     */
    abstract public function canDisplay(Mage_Sales_Model_Order $order);
    
    /**
     * <p>This function is called when current button is clicked and data along with it is sent to server.</p>
     * <p>Returned data should be in following format:</p>
     * <pre>
        $result = array(
            'messages' => array of messages, which are displayed as success messages to the user,
            'errors' => array of error, which are displayed as errors to the user,
            'needs_reload' => when set to true, whole page is reloaded when Order Manager is closed,
            'is_action_error' => when set to true whole page is reloaded immediately,
        );
     * </pre>
     * @param Mage_Sales_Model_Order $order current order that is being displayed
     * @param array $params assoc array of POST params
     * @return bool|array when action failed, return false, otherwise return array.
     */
    abstract public function performDesiredAction(Mage_Sales_Model_Order $order, array $params);
    
    /**
     * 
     * <p>Buttons are sorted by position in ascending order</p>
     * @param int $position desired position
     */
    public function setPosition($position) {
        $this->_position = $position;
    }
    
    /**
     * <p>Buttons are sorted by position in ascending order</p>
     * @return int current position
     */
    public function getPosition() {
        return $this->_position;
    }
    
    
    /**
     * <p>name of the button relative to balticode_livehandler/action_&lt;button-name&gt; or full magento model name</p>
     * @return string
     */
    public function getCode() {
        return $this->_code;
    }
    
    /**
     * <p>Label printed on the action button</p>
     * @return string
     */
    public function getLabel() {
        return $this->_label;
    }
    
    /**
     * <p>Gets the onclick parameter for the current action button</p>
     * @return string javascript
     */
    public function getOnClick() {
        return $this->_onClick;
    }
    
    
    /**
     * <p>Gets the class parameter for the current action button</p>
     * @return string
     */
    public function getCssClass() {
        return $this->_cssClass;
    }
    
    /**
     * <p>Gets the Event.observe('click') javascript for current action button.</p>
     * @return string javascript
     */
    public function getLongOnClick() {
        return $this->_longOnClick;
    }
    
    /**
     * <p>Wrapper function for json_encode for usage in heredoc</p>
     * @param mixed $input
     * @return string json encoded string
     */
    protected function _toJson($input) {
        return json_encode($input);
    }
    
}

