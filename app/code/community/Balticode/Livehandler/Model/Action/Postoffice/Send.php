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
 * <p>Renders button at the Magento Administrators Sales Order Grid, which allows to send parcel data to remote server for carriers which support this feature.</p>
 * <p>Button is displayed when order shipping method supports automatic parcel data sending and parcel data is not already sent.</p>
 * <p>Does nothing when Balticode_Postoffice module is not installed</p>
 *
 * @author Matis
 */
class Balticode_Livehandler_Model_Action_Postoffice_Send extends Balticode_Livehandler_Model_Action_Abstract {
    /**
     * <p>Unique code relative to balticode_livehandler/action</p>
     * @var string
     */
    protected $_code = 'postoffice_send';
    protected $_label;
    private static $_module_exists;
    
    public function __construct() {
        if (self::$_module_exists === null) {
            $modulesArray = (array)Mage::getConfig()->getNode('modules')->children();
            self::$_module_exists = isset($modulesArray['Balticode_Postoffice']);
        }
        
    }




    /**
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function canDisplay(Mage_Sales_Model_Order $order) {
        if (self::$_module_exists) {
            $this->_label = Mage::helper('balticode_postoffice')->__('Send shipping data to server');
            if (Mage::helper('balticode_postoffice')->isDataSent($order->getIncrementId()) === false) {
                $barcode = Mage::helper('balticode_postoffice')->getBarcode($order->getIncrementId());
                if (is_string($barcode)) {
                    return false;
                }

                return true;
            }
        }
        return false;
    }

    /**
     * <p>Calls out automatic data send action for selected order and returns the result.</p>
     * <p>Does not support custom POST params</p>
     * @param Mage_Sales_Model_Order $order current order
     * @param array $params supplied POST params
     * @return boolean|array
     */
    public function performDesiredAction(Mage_Sales_Model_Order $order, array $params) {
        
        //get the carrier
        $shippingMethod = $order->getShippingMethod();
        $paymentMethod = $order->getPayment();
        $errors = array();
        //get the shipping code from the order and call the module from it.
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        
        if (!($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)){
            $errors[] = Mage::helper('balticode_postoffice')->__('This carrier is not subclass of Balticode_Postoffice_Model_Carrier_Abstract');
        }
        $shippingMethodModel->setStoreId($order->getStoreId());
        
        //determine if auto send is available
        if (!count($errors) && !$shippingMethodModel->isAutoSendAvailable()) {
            $errors[] = Mage::helper('balticode_postoffice')->__('Automatic data sending is not available for the selected carrier');
        }
        
        if (!count($errors) && round($order->getTotalDue(), 2) > 0 && (!$shippingMethodModel->getConfigData('enable_cod') || 
                (!count($errors) && $shippingMethodModel->getConfigData('enable_cod') && $paymentMethod->getMethod() != 'balticodecodpayment'))) {
            $errors[] = Mage::helper('balticode_postoffice')->__('This order has not yet been fully paid');
        }
        
        if (!count($errors) && ($order->isCanceled() || $order->getIsVirtual())) {
            $errors[] = Mage::helper('balticode_postoffice')->__('This order cannot be shipped');
        }
        
        
        //send the data
        $messages = array();
        if (!count($errors)) {
            Mage::helper('balticode_postoffice')->sendManualOrderData($order->getIncrementId(), $shippingMethodModel->getConfigData('senddata_event'));
            $messages[] = Mage::helper('balticode_postoffice')->__('Data sent to server, please verify the status from the order comments');
            
        }
        
        
        $result = array(
            'messages' => $messages,
            'errors' => $errors,
            'needs_reload' => false,
            'is_action_error' => false,
        );
        return $result;
    }
}

