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
 * @package    Balticode_Dpd
 * @copyright  Copyright (c) 2013 Aktsiamaailm LLC (http://en.balticode.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt  GNU Public License V3.0
 * @author     Matis Halmann
 * 

 */

/**
 * Helper class for dealing with situation, when Carrier provider needs some shipment data to be sent to Carrier providers server.
 *
 * @author Aktsiamaailm OÜ, Matis Halmann
 */
class Balticode_Postoffice_Helper_Data extends Mage_Core_Helper_Abstract {
    private static $timeDiff = null;

    /**
     *  <p>Attempts to send order shipment data right at the moment, when user lands on the success page
     * and user has just completed onepage checkout.</p>
     * <p>Shipment data is sent to the server only, when automatic data sending is enabled and the timing is set to right after order completion.</p>
     * <p>Shipment data is sent to the server in the following conditions:
     * <ul>
     * <li>Order has been fully paid, or payment method is Cash on delivery (to be implemented in future) and cash on delivery is allowed</li>
     * <li>Carrier supports automatic shipment data sending to the Carrier server</li>
     * <li>Merchant has enabled automatic shipment data sending to the server</li>
     * <li>Shipment data has not been sent to the server earlier</li>
     * </ul>
     * </p>
     * @see Balticode_Postoffice_Model_Source_Sendevent source for possible automatic data sending moments.
     *  
     */
    public function autoSendAfterOnepage() {
        $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        if ($incrementId != '') {
            $this->handleOrder($incrementId, 'after_checkout');
        }
        
    }
    
    /**
     *  <p>Attempts to send order shipment data right at the moment, when user lands on the success page
     * and user has just completed multishipping checkout.</p>
     * <p>Shipment data is sent to the server only, when automatic data sending is enabled and the timing is set to right after order completion.</p>
     * <p>Shipment data is sent to the server in the following conditions:
     * <ul>
     * <li>Order has been fully paid, or payment method is Cash on delivery (to be implemented in future) and cash on delivery is allowed</li>
     * <li>Carrier supports automatic shipment data sending to the Carrier server</li>
     * <li>Merchant has enabled automatic shipment data sending to the server</li>
     * <li>Shipment data has not been sent to the server earlier</li>
     * </ul>
     * </p>
     * @see Balticode_Postoffice_Model_Source_Sendevent source for possible automatic data sending moments.
     *  
     */
    public function autoSendAfterMultishipping() {
        $ids = Mage::getSingleton('core/session')->getOrderIds(true);
        if ($ids && is_array($ids)) {
            foreach ($ids as $item) {
                $this->handleOrder($item, 'after_checkout');
            }
        }
        
    }
    
    /**
     *  <p>Attempts to send order shipment data right at the moment, when Merchant has finished "Create Shipping" procedure for the order.
     * </p>
     * <p>Shipment data is sent to the server only, when automatic data sending is enabled and the timing is set to right after order completion.</p>
     * <p>Shipment data is sent to the server in the following conditions:
     * <ul>
     * <li>Order has been fully paid, or payment method is Cash on delivery (to be implemented in future) and cash on delivery is allowed</li>
     * <li>Carrier supports automatic shipment data sending to the Carrier server</li>
     * <li>Merchant has enabled automatic shipment data sending to the server</li>
     * <li>Shipment data has not been sent to the server earlier</li>
     * </ul>
     * </p>
     * @see Balticode_Postoffice_Model_Source_Sendevent source for possible automatic data sending moments.
     *  
     */
    public function autoSendAfterShipment($observer) {
        $event = $observer->getEvent();
        $shipment = $event->getShipment();
        $order = $shipment->getOrder();
        $incrementId = $order->getIncrementId();
        if ($shipment->getId() <= 0) {
            $this->handleOrder($incrementId, 'after_shipment');
        }
    }
    
    /**
     *  <p>Clears all the extra data created by this carrier or by the subclasses of this carrier.</p>
     * 
     * <p>It is called with the event <code>sales_order_place_before</code></p>
     * 
     * 
     * @param Varien_Object $observer
     * @throws Exception 
     */
    public function clearSessions($observer) {
        $carriers = Mage::getModel('balticode_postoffice/carriermodule')->getCollection();

        foreach ($carriers as $carrier) {
            $shippingModel = Mage::getModel($carrier->getData('class_name'));
            if (!($shippingModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
                throw new Exception('Invalid Shipping model');
            }
            $shippingModel->clearSession();
        }
    }
    
    /**
     *  <p>Attempts to send order shipment data right from the Merchant's manual call.
     * </p>
     * <p>Shipment data is sent to the server in the following conditions:
     * <ul>
     * <li>Order has been fully paid, or payment method is Cash on delivery (to be implemented in future) and cash on delivery is allowed</li>
     * <li>Carrier supports automatic shipment data sending to the Carrier server</li>
     * <li>Merchant has enabled automatic shipment data sending to the server</li>
     * <li>Shipment data has not been sent to the server earlier</li>
     * </ul>
     * </p>
     * @see Balticode_Postoffice_Model_Source_Sendevent source for possible automatic data sending moments.
     * 
     * @param string $incrementId Order increment ID
     * @param string $eventName Name of the event.
     *  
     */
    public function sendManualOrderData($incrementId, $eventName) {
        return $this->handleOrder($incrementId, $eventName);
    }
    
    /**
     *
     * <p>Attempts to fetch the barcode, which has been generated by the remote Carrier.</p>
     * <p>Fetching barcode is only possible, when shipment data is successfully sent to the carrier
     * and Carrier itself supports Barcodes.</p>
     * <p>Barcodes are used to print shipping labels.</p>
     * 
     * @param string $incrementId Order increment ID.
     * @return string|boolean barcode for the selected order, when successul, false in every other situation.
     */
    public function getBarcode($incrementId) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        if (!is_object($order) || $order->getId() <= 0) {
            return false;
        }
        $shippingMethod = $order->getShippingMethod();
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
            return $shippingMethodModel->getBarcode($order);
        }
        return false;
        
    }
    
    /**
     * <p>Attempts to fetch shipping method instance for specified order increment id.</p>
     * <p>Shipping method is fetched only if it is instance of <code>Balticode_Postoffice_Model_Carrier_Abstract</code> otherwise boolean false is returned</p>
     * @param string $incrementId order increment id
     * @return Balticode_Postoffice_Model_Carrier_Abstract|boolean
     */
    public function getShippingMethodInstance($incrementId) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        if (!is_object($order) || $order->getId() <= 0) {
            return false;
        }
        $shippingMethod = $order->getShippingMethod();
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
            $shippingMethodModel->setStore($order->getStore());
            return $shippingMethodModel;
        }
        return false;
    }
    

    /**
     *
     * <p>Attempts to fetch the shipping label PDF created by the remote Carrier server.</p>
     * <p>Fetching barcode is only possible, when shipment data is successfully sent to the carrier
     * and Carrier itself supports Barcodes.</p>
     * 
     * @param string $incrementId Order increment ID.
     * @return string|boolean Packing slip PDF binary for the selected order when successful, false in every other condition.
     */
    public function getBarcodePdf($incrementId) {
        Mage::log("getBarcodePdf data.php incrementId:".print_r($incrementId, true), null, 'dpdlog.log');
        //$order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $barcode = Mage::helper('balticode_postoffice')->getBarcode($incrementId);
        Mage::log("getBarcodePdf data.php barcode:".print_r($barcode, true), null, 'dpdlog.log');
        $requestResult = $this->_getDpdHelper()->getApi();
        Mage::log("getBarcodePdf data.php after", null, 'dpdlog.log');

        //if (!is_object($order) || $order->getId() <= 0) {
            return $requestResult;
        //}
        //$shippingMethod = $order->getShippingMethod();
        //$shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        //$shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        //if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
        //    return $shippingMethodModel->getBarcodePdf($order);
        //}
        //return false;
        
    }
    
    /**
     *  <p>Determines whether Order shipment data has been successfully sent to the remote carrier server.</p>
     * 
     * @param string $incrementId Order increment ID.
     * @return boolean true, if the data has been successfully sent to the server. False, if the data has not been sent
     * successfully to the server. NULL if automatic data sending is not enabled or not applicable for the Order selected carrier.
     */
    public function isDataSent($incrementId) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        if (!is_object($order) || $order->getId() <= 0) {
            return null;
        }
        $shippingMethod = $order->getShippingMethod();
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
            if ($shippingMethodModel->isAutoSendAvailable()) {
                return $shippingMethodModel->isDataSent($order);
            }
        }
        return null;
        
    }
    
    /**
     * <p>Returns true if all of the following are true</p>
     * <ul>
         <li>Carrier supports automatic data sending</li>
         <li>Automatic data sending is allowed</li>
         <li>Order is fully paid or payment method is COD</li>
         <li>Order is not canceled and contains physical goods</li>
     </ul>
     * @param Mage_Sales_Model_Order $order
     * @param type $shippingMethodModel
     * @return boolean
     */
    public function canSendData(Mage_Sales_Model_Order $order, &$shippingMethodModel = null) {
        if (!$shippingMethodModel) {
            $shippingMethod = $order->getShippingMethod();
            $paymentMethod = $order->getPayment();

            //get the shipping code from the order and call the module from it.
            $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
            $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        }
        
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract) 
                && $shippingMethodModel->getConfigData('senddata_enable')
                && !(round($order->getTotalDue(), 2) > 0 && (!$shippingMethodModel->getConfigData('enable_cod') || 
                ($shippingMethodModel->getConfigData('enable_cod') && $paymentMethod->getMethod() != 'balticodecodpayment')))
                && !($order->isCanceled() || $order->getIsVirtual())) {
            $shippingMethodModel->setStore($order->getStore());
            return true;
        }
        return false;
    }

    /**
     * 
     * <p>Attempts to auto send data to remote server and insert the result to order comments.</p>
     * <p>Shipment data is sent to the server in the following conditions:
     * <ul>
     * <li>Order has been fully paid, or payment method is Cash on delivery (to be implemented in future) and cash on delivery is allowed</li>
     * <li>Carrier supports automatic shipment data sending to the Carrier server</li>
     * <li>Merchant has enabled automatic shipment data sending to the server</li>
     * <li>Shipment data has not been sent to the server earlier</li>
     * <li>$eventName matches data sending event set up in the configuration.</li>
     * </ul>
     * </p>
     * @param string $incrementId order increment id
     * @param string $eventName event name for send data
     * @return null
     * @see Balticode_Postoffice_Model_Source_Sendevent
     */
    private function handleOrder($incrementId, $eventName) {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        if (!is_object($order) || $order->getId() <= 0) {
            return;
        }
        $shippingMethod = $order->getShippingMethod();
        $paymentMethod = $order->getPayment();
        
        //get the shipping code from the order and call the module from it.
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);

        
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract) 
                && $shippingMethodModel->getConfigData('senddata_enable')
                && $shippingMethodModel->getConfigData('senddata_event') == $eventName
                && !(round($order->getTotalDue(), 2) > 0 && (!$shippingMethodModel->getConfigData('enable_cod') || 
                ($shippingMethodModel->getConfigData('enable_cod') && $paymentMethod->getMethod() != 'balticodecodpayment')))
                && !($order->isCanceled() || $order->getIsVirtual())
                && $shippingMethodModel->isDataSent($order) === false) {
            try {
                $resultCarrierId = substr($shippingMethod, strrpos($shippingMethod, '_') + 1);
                //TODO - make sure the correct store is selected, for example in admin.
                $result = $shippingMethodModel->autoSendData($order, $resultCarrierId);
                if ($result) {
                    $order->addStatusHistoryComment(print_r($result, true));
                } else {
                    $order->addStatusHistoryComment($this->__('Automatic data sending not applicable'));
                }
                $order->save();
            } catch (Exception $e) {
                $order->addStatusHistoryComment($e->__toString());
                $order->save();
            }
        }
    }
    
    
    /**
     * <p>Attempts to fetch data from order that is stored by carriers extending Balticode_Postoffice_Model_Carrier_Abstract</p>
     * <p>Data is stored in not visible on front order comment base64 encoded form and starting with specified prefix.</p>
     * <p>If matching comment is found, it is decoded and returned as assoc array</p>
     * @param Mage_Sales_Model_Order $order Magento order instance to look up the data for
     * @param string $prefix unique string prefix order comment should start with.
     * @return array
     */
    public function getDataFromOrder(Mage_Sales_Model_Order $order, $prefix) {
        $orderData = array();
        foreach ($order->getAllStatusHistory() as $statusHistory) {
            /* @var $statusHistory Mage_Sales_Model_Order_Status_History */
            if ($statusHistory->getComment() && !$statusHistory->getVisibleOnFront()) {
                if ($this->_commentContainsValidData($statusHistory->getComment(), $prefix)) {
                    $orderData = @json_decode(@gzuncompress(@base64_decode($this->_getFromComment($statusHistory->getComment(), $prefix))), true);
                    if (!is_array($orderData)) {
                        //unserialize error on recognized pattern, should throw error or at least log
                        $orderData = array();
                    }
                }
            }
            
            
        }
        return $orderData;
        
    }
    
    /**
     * <p>Stores extra data for specified order in single order comment, which will start with specified prefix.</p>
     * <p>If no matching order comment is found, then it is created automatically, otherwise old one is updated.</p>
     * <p>Order comment is stored using following procedure:</p>
     * <ul>
         <li>If old data is found, then it is merged with new data</li>
         <li>Data is json encoded and after that gzcompressed</li>
         <li>Now it is base64 encoded and divided into 40 char long lines and prefixed with $prefix</li>
         <li>Result is stored to one of the comments contained within the order.</li>
     </ul>
     * @param Mage_Sales_Model_Order $order Magento order instance to set up the data for
     * @param array $data
     * @param string $prefix
     * @return array
     */
    public function setDataToOrder(Mage_Sales_Model_Order $order, array $data, $prefix) {
        $oldOrderData = $this->getDataFromOrder($order, $prefix);
        if (isset($data['comment_id'])) {
            unset($data['comment_id']);
        }
        if (count($oldOrderData) && isset($oldOrderData['comment_id'])) {
            //we have old data
            $history = Mage::getModel('sales/order_status_history')->load($oldOrderData['comment_id']);
            if ($history && $history->getId()) {
                foreach ($data as $k => $v) {
                    $oldOrderData[$k] = $v;
                }
                $history->setComment($this->_getCommentFromData($oldOrderData, $prefix));
                $history->save();
            }
            //comment id for example.....
        } else {
            //we do not have old data, so add new comment
            //set the id also

            $history = Mage::getModel('sales/order_status_history')
                    ->setStatus($order->getStatus())
                    ->setOrderId($order->getId())
                    ->setParentId($order->getId())
                    ->setComment($this->_getCommentFromData($data, $prefix))
                    ->save();
            $commentId = $history->getId();
            
            $data['comment_id'] = $commentId;
            $history->setComment($this->_getCommentFromData($data, $prefix));
            $history->save();
        }



        return $history;
    }
    
    
    
    /**
     * <p>Returns Magento timestamp and time() difference in seconds</p>
     * @return int
     */
    public function getTimeDiff() {
        if (self::$timeDiff === null) {
            $time = time();
            $now = Mage::getModel('core/date')->timestamp($time);
            echo '<pre>'.htmlspecialchars(print_r($time, true)).'</pre>';
            echo '<pre>'.htmlspecialchars(print_r($now, true)).'</pre>';
            self::$timeDiff = $time - $now;
        }
        return self::$timeDiff;
        
    }
    

    protected function _getCommentFromData($data, $prefix) {
        return $prefix ."\n". chunk_split(base64_encode(gzcompress(json_encode($data))), 40, "\n");
    }
    
    protected function _getFromComment($comment, $prefix) {
        return str_replace($prefix, '', str_replace("\n", '', $comment));
    }


    
    protected function _commentContainsValidData($comment, $prefix) {
        //TODO: refactor to something better
        return strpos($comment, $prefix) === 0 
                && strlen($comment) > strlen($prefix);
    }
    
    /**
     * <p>Takes in array of parcel weights and returns number of packages calculated by maximum allowed weight per package</p>
     * <p>Uses better methology to find number of packages than regular cart weight divided by maximum package weight</p>
     * <p>For example, if maximum package weight is 31kg, ang we have 3x 20kg packages, then number of packages would be 3 (not 2)</p>
     * <p>If maximum package weight is not defined, then it returns 1</p>
     * <p>If single item in <code>$itemWeights</code> exceeds <code>$maximumWeight</code> then this function returns false</p>
     * @param array $itemWeights array of item weights
     * @param int $maximumWeight maximum allowed weight of one package
     * @return int
     */
    public function getNumberOfPackagesFromItemWeights(array $itemWeights, $maximumWeight) {
        $numPackages = 1;
        $weight = 0;
        if ($maximumWeight > 0) {
            
            foreach ($itemWeights as $itemWeight) {
                if ($itemWeight > $maximumWeight) {
                    return false;
                }
                $weight += $itemWeight;
                if ($weight > $maximumWeight) {
                    $numPackages++;
                    $weight = $itemWeight;
                }
            }
            
        }
        return 1; //return $numPackages;
    }
    
    
    
    
    
}
