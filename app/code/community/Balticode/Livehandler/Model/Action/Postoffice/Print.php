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
 * <p>Renders button at the Magento Administrators Sales Order Grid, which allows to print packing slip from remote server for carriers which support this feature.</p>
 * <p>Button is displayed when order shipping method supports remote packing slip printing and parcel data is sent.</p>
 * <p>Does nothing when Balticode_Postoffice module is not installed</p>
 *
 * @author Matis
 */
class Balticode_Livehandler_Model_Action_Postoffice_Print extends Balticode_Livehandler_Model_Action_Abstract {
    /**
     * <p>Unique code relative to balticode_livehandler/action</p>
     * @var string
     */
    protected $_code = 'postoffice_print';
    protected $_label;
    
    
    private static $_module_exists;
    
    
    public function __construct() {
        if (self::$_module_exists === null) {
            $modulesArray = (array)Mage::getConfig()->getNode('modules')->children();
            self::$_module_exists = isset($modulesArray['Balticode_Postoffice']);
        }
        $this->_longOnClick = 'return false;';
        
    }




    /**
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function canDisplay(Mage_Sales_Model_Order $order) {
        Mage::log("canDisplay", null, 'dpdlog.log');
        if (self::$_module_exists) {
            $this->_label = Mage::helper('balticode_postoffice')->__('Print packing slip');
            $barcode = Mage::helper('balticode_postoffice')->getBarcode($order->getIncrementId());
            Mage::log("canDisplay barcode:".print_r($barcode, true), null, 'dpdlog.log');
            if (is_string($barcode)) {
                $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/addresscardpdf', array('order_id'=> $order->getId()));
                $this->_onClick = "setLocation('".$url."')";
                return true;
            }
        }
        return false;
    }

    /**
     * <p>Does nothing</p>
     * @param Mage_Sales_Model_Order $order
     * @param array $params
     * @return array
     */
    public function performDesiredAction(Mage_Sales_Model_Order $order, array $params) {
        
        $errors = array();
        $messages = array();
        
        $result = array(
            'messages' => $messages,
            'errors' => $errors,
            'needs_reload' => false,
            'is_action_error' => false,
        );
        return $result;
    }
}

