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
 * <p>Renders button at the Magento Administrators Sales Order Grid, which allows to call DPD courier.</p>
 * <p>Button is displayed when order shipping method is DPD and parcel data is sent to DPD server.</p>
 *
 * @see Balticode_DpdLT_Block_Order_Courier
 * @author Matis
 * @deprecated since version 0.1.4
 */
class Balticode_DpdLT_Model_Action_Carrier_Order_Courier extends Balticode_Livehandler_Model_Action_Abstract {
    
    /**
     * <p>Unique code that identifies this button. Matches button name at Magento admin &gt; System &gt; Configuration &gt; Balticode Livehandler &gt; Admin Order Grid Helper configuration menu</p>
     * @var string 
     */
    protected $_code = 'balticode_dpdlt__action_carrier_order_courier';
    
    /**
     * <p>Label displayed on this action button.</p>
     * <p>Default: <b>Order courier to pick up goods</b></p>
     * @var string
     */
    protected $_label;
    
    public function __construct() {
        $this->_label = Mage::helper('adminhtml')->__('Order courier to pick up goods');
        /* @var $salesHelper Mage_Sales_Helper_Data */
        $salesHelper = Mage::helper('sales');
        $this->_longOnClick = <<<EOT
                
                return balticode_dpdJsObject.submit();
                
EOT;
        
        
    }




    /**
     * <p>From version 0.1.4 this button is never displayed</p>
     * <p>Should display when:</p>
     * <ul>
         <li>Order is using DPD shipping method</li>
         <li>Order is fully paid or payment method is COD</li>
         <li>Courier pickup is allowed from the settings</li>
         <li>Automatic data sending is allowed and data is sent to DPD server</li>
         <li>Order has not yet been picked up by courier</li>
     </ul>
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function canDisplay(Mage_Sales_Model_Order $order) {
        return false;
        /* @var $shippingMethodModel Balticode_DpdLT_Model_Post */
        $shippingMethodModel = null;
        $res = $this->_getDpdHelper()->isShippingMethodApplicable($order) && $this->_getOfficeHelper()->canSendData($order, $shippingMethodModel)
                && $shippingMethodModel->isDataSent($order);
        if ($res) {
            if ($shippingMethodModel->isPickedUpByCourier($order) !== false) {
                return false;
            }
            
            $dpdBlock = Mage::getSingleton('core/layout')
                    ->createBlock('balticode_dpdlt/order_courier')
                    ->setCode('balticodedpdlt')
                    ->setStoreId($order->getStore()->getId())
            ;

            $this->_onClick = <<<EOT
                if (typeof(balticode_dpdJsObject) === 'undefined') {
                    balticode_dpdJsObject = new BalticodeDpdLT('balticode_{$this->_code}', null, null, null, function(endResult) { if (!endResult['Po_Date'] || endResult['Po_Date'] == '-') { return false; } return endResult; });
                }
   balticode_dpdJsObject.update({$this->_toJson($dpdBlock->toHtml())}, {$this->_toJson($order->getId())}, sales_order_grid_massactionJsObject);
EOT;
        }
        return $res;
    }

    /**
     * 
     * @return Balticode_DpdLT_Helper_Data
     */
    protected function _getDpdHelper() {
        return Mage::helper('balticode_dpdlt');
    }
    /**
     * 
     * @return Balticode_Postoffice_Helper_Data
     */
    protected function _getOfficeHelper() {
        return Mage::helper('balticode_postoffice');
    }
    

    /**
     * <p>Sends courier call pickup goods action to DPD server and returns information about its status.</p>
     * <p>Available POST params:</p>
     * <ul>
         <li><b>order_ids</b> - comma separated list of Magento Order IDs on which courier pickup call should be executed.</li>
         <li><b>Po_remark</b> - Note to sent to courier</li>
         <li><b>Po_Date</b> - Date when courier should pick up goods. Format: YYYY-MM-DD</li>
         <li><b>Po_Time</b> - Time range when courier should pick up goods. Format: HMM-HMM (timefrom-timetill)</li>
         <li><b>Po_envelope_qty</b> - Number of envelopes courier should pick up</li>
         <li><b>Po_parcel_qty</b> - Number of parcels courier should pick up</li>
         <li><b>Po_pallet_qty</b> - Number of pallets courier should pick up</li>
     </ul>
     * <p>If supplied orders are not applicable for DPD courier call, then exception is thrown.</p>
     * @param Mage_Sales_Model_Order $order
     * @param array $params assoc array of post params
     * @return boolean|array
     */
    public function performDesiredAction(Mage_Sales_Model_Order $order, array $params) {
        try {
            $failedOrderIds = array();
            //order_ids => comma separated list of orders
            $orders = $this->_getOrders($params['order_ids']);
            
            //validate if submitted orders can be called for courier.....
            foreach ($orders as $testOrder) {
                if (!$this->_isHandleableOrder($testOrder)) {
                    $failedOrderIds[] = $testOrder->getIncrementId();
                }
            }
            
            //if not, then list the invalid ids with error message
            if (count($failedOrderIds)) {
                Mage::throwException($this->_getDpdHelper()->__('Courier cannot be called for orders %s', implode(', ', $failedOrderIds)));
            }
            
            $prefix = Balticode_DpdLT_Model_Post::ORDER_COMMENT_START_PREFIX;
            
            //send the data
            $api = $this->_getDpdHelper()->getApi($order->getStore()->getId());

            $orderSendData = array(
                'Po_remark' => isset($params['Po_remark'])?$params['Po_remark']:'',
                'Po_type' => 'PO',
                'Po_Date' => $paramams['Po_Date'],
                'Po_Time_from' => $this->_getTimeFrom($params['Po_Time']),
                'Po_Time_til' => $this->_getTimeTil($params['Po_Time']),
                'Po_envelope_qty' => $params['Po_envelope_qty'],
                'Po_parcel_qty' => $params['Po_parcel_qty'],
                'Po_pallet_qty' => $params['Po_pallet_qty'],
                'Sh_envelope_qty' => $params['Po_envelope_qty'],
                'Sh_parcel_qty' => $params['Po_parcel_qty'],
                'Sh_pallet_qty' => $params['Po_pallet_qty'],
                'Sh_pudo' => 'false',
            );

            $orderSendResult = $api->autoSendData($orderSendData);
            Mage::log("performDesiredAction orderSendResult:".print_r($orderSendResult, true), null, 'dpdlog.log');
            //if validation passed
            foreach ($orders as $testOrder) {
                //mark each order as courier called
                $newOrderData = array(
                    'courier_call_id' => $orderSendResult['pl_number'],
                );
                $this->_getOfficeHelper()->setDataToOrder($testOrder, $newOrderData, $prefix);
                
            }
            $courierArrivalDate = $orderSendData['Po_Date'];
            $courierArrivalTime = $orderSendData['Po_Time_from'].' '.$this->_getDpdHelper()->__('and').' '.$orderSendData['Po_Time_til'];
            
        } catch (Mage_Core_Exception $e) {
            $result = array(
                'errors' => array($e->getMessage()),
                'needs_reload' => false,
                'is_action_error' => false,
            );
            return $result;
        } catch (Exception $e) {
            $result = array(
                'errors' => array($this->_getDpdHelper()->__('Cannot call courier.')),
                'needs_reload' => true,
                'is_action_error' => false,
            );
            return $result;
        }
        $result = array(
            'messages' => array($this->_getDpdHelper()->__('Courier comes to pick up your shipment on %1$s between %2$s', $courierArrivalDate, $courierArrivalTime)),
            'needs_reload' => true,
            'is_action_error' => false,
        );
        return $result;
    }
    
    /**
     * 
     * @param string $input
     * @return string
     */
    protected function _getTimeFrom($input) {
        $parts = explode('-', $input);
        return str_replace('00', '', $parts[0]);
    }
    protected function _getTimeTil($input) {
        $parts = explode('-', $input);
        return str_replace('00', '', $parts[1]);
    }
    
    /**
     * Returns true, if courier can be called for this order.
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    protected function _isHandleableOrder(Mage_Sales_Model_Order $order) {
        $shippingMethodModel = null;
        $res = $this->_getDpdHelper()->isShippingMethodApplicable($order) && $this->_getOfficeHelper()->canSendData($order, $shippingMethodModel);
        if ($res) {
            if ($shippingMethodModel->isPickedUpByCourier($order) !== false) {
                return false;
            }
        }
        return $res;
        
    }

    /**
     * Fetches an array of Mage_Sales_Model_Order from comma separated string
     * @param string $inputCsv
     * @return array
     * @throws Exception if input CSV is malformed or order does not exist
     */
    protected function _getOrders($inputCsv) {
        $orderIds = explode(',', $inputCsv);
        $orders = array();
        foreach ($orderIds as $orderId) {
            $order = $this->_getOrderModel()->load(trim($orderId));
            if (!$order || !$order->getId()) {
                throw new Exception($this->_getDpdHelper()->__('Order does not exist'));
            }
            $orders[] = $order;
        }
        return $orders;
    }
    
    
    /**
     * 
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrderModel() {
        return Mage::getModel('sales/order');
        
    }

    
    

}

