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
 * <p>Represents DPD courier shipping method.</p>
 * <p>Extra order data is stored under specialized order comment</p>
 * <p>Can perform following business actions:</p>
 * <ul>
     <li>Calculate shipping price based on country and weight</li>
     <li>Send information about shipment data to DPD server.</li>
     <li>Display tracking link to user when tracking code is added to the shipment.</li>
     <li>Call courier to pick up the shipment that was ordered using this carrier.</li>
     <li>Print out packing slip PDF from Order view.</li>
 </ul>
 *
 * @author Matis
 */
class Balticode_DpdLT_Model_Flat extends Balticode_DpdLT_Model_Post {
    protected $_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_FLAT;
    protected $_parent_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_PARCEL_TERMINAL;
    
    /**
     * Automatic data sending is defined in Parcel terminal module configuration
     * @return bool
     */
    public function isAutoSendAvailable() {
        return (bool)$this->_getDpdHelper()->getApi($this->getStore(), $this->_parent_code)->getConfigData('senddata_enable');
    }
    
    
    /**
     * <p>Sends parcel data to DPD server for specified order</p>
     * @param Mage_Sales_Model_Order $order
     * @param type $selectedOfficeId not applicable.
     * @return array comma separated parcel numbers in array key of 'barcode'
     */
    public function autoSendData(Mage_Sales_Model_Order $order, $selectedOfficeId) {
        $shippingAddress = $order->getShippingAddress();
        $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
        
        $requestData = array(
            'name1' => $shippingAddress->getName(),
            'company' => $shippingAddress->getCompany(),
            'street' => $shippingAddress->getStreetFull(),
            'pcode' => $shippingAddress->getPostcode(),
            'country' => strtoupper($shippingAddress->getCountryId()),
            'city' => $shippingAddress->getCity(),
            'Sh_contact' => $shippingAddress->getName(),
            'phone' => $shippingAddress->getTelephone(),
            'remark' => $this->_getRemark($order),
            'Po_type' => $this->getConfigData('senddata_service'),
            'num_of_parcel' => $this->_getNumberOfPackagesForOrder($order),
            'order_number' => $order->getIncrementId(),
            'parcel_type' => $payment_method_code == 'cashondelivery' ? 'D-COD-B2C' : 'D-B2C'
        );
        if ($payment_method_code == 'cashondelivery') {
            $requestData['cod_amount'] = $order->getGrandTotal();
        }
        
        if ($shippingAddress->getRegion()) {
            $requestData['city'] = $shippingAddress->getCity() . ', ' . $shippingAddress->getRegion();
        }
        Mage::log("autoSendData requestData:".print_r($requestData, true), null, 'dpdlog.log');
        
        $requestResult = $this->_getDpdHelper()->getApi($this->getStore(), $this->_parent_code)
                ->autoSendData($requestData);
        
        $this->_setDataToOrder($order, $requestResult);
        
        //on failure return false
        //is success
        return array('barcode' => '##'.  implode(',', $requestResult['pl_number']).'##');
    }
    
        
    
    /**
     * <p>This carrier has no parcel terminal selection feature, so one entry must still be added with shipping method title defined for this carrier.</p>
     * @return array single office element
     */
    public function getOfficeList() {
        //we have only one item to insert here
        $result = array();
        $result[] = array(
            'place_id' => 1,
            'name' => $this->getConfigData('title'),
            'city' => '',
            'county' => '',
            'description' => '',
            'country' => '',
            'zip' => '',
            'group_sort' => 0,
        );
        return $result;
    }
    
    /**
     * <p>Returns carrier title specified for this shipping method.</p>
     * @param Balticode_Postoffice_Model_Office $office
     * @return string
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getTerminalTitle()
     */
    public function getTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        return htmlspecialchars($this->getConfigData('title'));
    }
    
    
    /**
     * <p>Returns carrier title specified for this shipping method.</p>
     * @param Balticode_Postoffice_Model_Office $office
     * @return string
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getAdminTerminalTitle()
     */
    public function getAdminTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        return htmlspecialchars($this->getConfigData('title'));
    }
    
    /**
     * <p>Indicates if specified order has been picked up by courier.</p>
     * <p>Should return the following</p>
     * <ul>
         <li><b>true</b> - If the order has been picked up by courier</li>
         <li><b>false</b> - If the order has not been picked up by courier</li>
         <li><b>null</b> - If courier pickup is not applicable to specified order</li>
     </ul>
     * @param Mage_Sales_Model_Order $order
     * @return null|bool
     */
    public function isPickedUpByCourier(Mage_Sales_Model_Order $order) {
        $api = $this->_getDpdHelper()->getApi($this->getStore()->getId(), $this->_parent_code);
        if (!$api->getConfigData('courier_enable')) {
            return null;
        }
        $orderData = $this->getDataFromOrder($order);
        if (isset($orderData['courier_call_id']) && $orderData['courier_call_id']) {
            return true;
        }
        return false;
    }
    
    /**
     * <p>Nullifies address id from query, because in every country there needs to be available 'parcel-terminal'</p>
     * @param int $groupId
     * @param int $addressId
     * @return array
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getTerminals()
     */
    public function getTerminals($groupId = null, $addressId = null) {
        //fetching entry needs to be independendent of country
        return parent::getTerminals($groupId, null);
    }
    

    /**
     * <p>Nullifies address id from query, because in every country there needs to be available 'parcel-terminal'</p>
     * @param int $addressId
     * @return array
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getGroups()
     */
    public function getGroups($addressId = null) {
        return parent::getGroups(null);
        
    }
    
    

    /**
     * Gets the config data from this instance and if not existent, then tries to fetch it from parent instance.
     * 
     * @param string $field
     * @return mixed
     */
    public function getConfigData($field) {
        if (empty($this->_code)) {
            return false;
        }
        $path = 'carriers/'.$this->_code.'/'.$field;
        $value = $this->_getConfigDataOverride($field, $this->_code);
//        $value = Mage::getStoreConfig($path, $this->getStore());
        if ($value === false || $value === null) {
            $path = 'carriers/'.$this->_parent_code.'/'.$field;
            return $this->_getConfigDataOverride($field, $this->_parent_code);
//            return Mage::getStoreConfig($path, $this->getStore());
        } else {
            return $value;
        }
    }
    
    
    

}
