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
 * <p>Represents DPD parcel terminal shipping method.</p>
 * <p>Extra order data is stored under specialized order comment</p>
 * <p>Can perform following business actions:</p>
 * <ul>
     <li>Calculate shipping price based on country and weight</li>
     <li>Display list of user selectable parcel terminals, which is auto updated.</li>
     <li>Send information about shipment data to DPD server.</li>
     <li>Display tracking link to user when tracking code is added to the shipment.</li>
     <li>Call courier to pick up the shipment that was ordered using this carrier.</li>
     <li>Print out packing slip PDF from Order view.</li>
 </ul>
 *
 * @author matishalmann
 */
class Balticode_DpdLT_Model_Post extends Balticode_Postoffice_Model_Carrier_Abstract {

    protected $_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_PARCEL_TERMINAL;
    
    /**
     * If order comment starts with prefix marked here and is not visible on the frontend, then it is considered as extra data order comment.
     */
    const ORDER_COMMENT_START_PREFIX = '-----BALTICODE_DPDLT-----';
    

    /**
     * <p>%s in the URL is replaced with tracking number.</p>
     * @var string
     */
    protected $_tracking_url = 'https://tracking.dpd.de/cgi-bin/delistrack?typ=1&lang=en&pknr=%s';



    /**
     * <p>If disable shipping by product comment is allowed and product's short description in shopping cart contains html comment &lt;!-- no dpd_ee_module --&gt; then it returns false.</p>
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return boolean true if this method is available.
     * @see Balticode_Postoffice_Model_Carrier_Abstract::_isAvailable()
     */
    protected function _isAvailable(Mage_Shipping_Model_Rate_Request $request) {
        $productItem = true;
        if ($this->getConfigData('checkitems') == 1) {
            $productItem = true;
            if ($request->getAllItems()) {
                foreach ($request->getAllItems() as $item) {
                    $custom = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                    $desc = $custom->getShortDescription();
                    if (stristr($desc, '<!-- no dpd_ee_module -->')) {
                        $productItem = false;
                        break;
                    }
                }
            }
        }
        if (!$productItem) {
            return false;
        }
        return true;
    }
    
    
    /**
     * <p>Attemps to calculate shipping price from price-country shipping price matrix.</p>
     * <p>If unsuccessful, then default handling fee is returned.</p>
     * <p>If shipping calculation mode is set Per Item, then price will be multiplied by number of packages</p>
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param double $price
     * @return double
     */
    public function _calculateAdditionalShippingPrice(Mage_Shipping_Model_Rate_Request $request, $price) {
        $shippingMatrix = $this->_decodeShippingMatrix($this->getConfigDataForThis('handling_fee_country'));
        if ($request->getDestCountryId() && isset($shippingMatrix[$request->getDestCountryId()])) {
            //free price?
            if ($shippingMatrix[$request->getDestCountryId()]['free_shipping_from'] !== '') {
                if ($request->getPackageValueWithDiscount() >= $shippingMatrix[$request->getDestCountryId()]['free_shipping_from']) {
                    return 0;
                }
            }
            //subtraction is required because edges are 0-10,10.00001-20,....,....
            $packageWeight = $request->getPackageWeight() - 0.000001;
            $weightSet = 10;
            //we need to have price per every kg, where
            //0-10kg consists only base price
            //10,1-20kg equals base price + extra price
            //20,1-30kg equals base price + extra price * 2
            $extraWeightCost = max(floor($packageWeight / $weightSet) * $shippingMatrix[$request->getDestCountryId()]['kg_price'], 0);
            
            $handlingFee = $shippingMatrix[$request->getDestCountryId()]['base_price'];
            if ($this->getConfigData('handling_action') == 'P') {
                $handlingFee = ($this->_getOfficeHelper()->getNumberOfPackagesFromItemWeights($request->getBalticodeProductWeights(), $this->getConfigData('max_package_weight'))  - $this->getFreeBoxes()) * $handlingFee;
            }
            $handlingFee += $extraWeightCost;
            
            return $handlingFee; 
        }
        return $price;
    }
    
    
    /**
     * <p>Decodes json encoded string to assoc array (array keys are country ISO codes) and returns in following format:</p>
     * <ul>
         <li><code>country_id</code> - Country ISO code, also array key for this element</li>
         <li><code>base_price</code> - base shipping price up to 10kg</li>
         <li><code>kg_price</code> - additional shipping price for each 10kg</li>
         <li><code>free_shipping_from</code> - when and if to apply free shipping</li>
     </ul>
     * @param string $input
     * @return array
     */
    protected function _decodeShippingMatrix($input) {
        $shippingMatrix = @unserialize($input);
        $result = array();
        if (!is_array($shippingMatrix)) {
            return $result;
        }
        foreach ($shippingMatrix as $countryDefinition) {
            $result[$countryDefinition['country_id']] = $countryDefinition;
        }
        return $result;
    }
    
    
    
    /**
     * <p>Fetches one line long human readable parcel terminal description from DPD Pudo instance</p>
     * @param array $parcelT
     * @return string
     */
    protected function _getDescription($parcelT) {
        Mage::log("_getDescription Post.php $parcelT:".print_r($parcelT, true), null, 'dpdlog.log');
        if (!isset($parcelT['worktime']) || !$parcelT['worktime']) {
            return trim($parcelT['street'] . ' ' . $parcelT['city'] . ' ' . $parcelT['postal']. ', ' . $parcelT['country']. ' '.$parcelT['phone']);
        } else {
            return trim($parcelT['street'] . ' ' . $parcelT['city'] . ' ' . $parcelT['postal']. ', ' . $parcelT['country']. ' '.$parcelT['phone']
                    .' '.$this->_getDpdHelper()->getOpeningsDescriptionFromTerminal($parcelT['worktime'], Zend_Locale::getLocaleToTerritory(strtoupper($parcelT['Sh_country']))));
        }
    }
    
    
    
    
    /**
     *
     * If the barcode function is available globally
     * @return boolean 
     */
    public function isBarcodeFunctionAvailable() {
        
        return $this->isAutoSendAvailable();
    }
    
    /**
     * <p>Sends parcel data to DPD server for specified order and selected parcel terminal id.</p>
     * @param Mage_Sales_Model_Order $order
     * @param type $selectedOfficeId
     * @return array comma separated parcel numbers in array key of 'barcode'
     */
    public function autoSendData(Mage_Sales_Model_Order $order, $selectedOfficeId) {
        $shippingAddress = $order->getShippingAddress();
        $selectedOffice = $this->getTerminal($selectedOfficeId);
        $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
        $requestData = array(
            'name1' => $shippingAddress->getName(),
            'name2' => $selectedOffice->getName(),
            'street' => $selectedOffice->getName(),
            'pcode' => $selectedOffice->getZipCode(),
            'country' => strtoupper($selectedOffice->getCountry()),
            'city' => $selectedOffice->getCity(),
            'phone' => $this->_getPhoneFromDescription($selectedOffice),
            'remark' => $this->_getRemark($order),
            'Sh_pudo' => 'true',
            'parcelshop_id' => $selectedOfficeId,
            'num_of_parcel' => $this->_getNumberOfPackagesForOrder($order),
            'order_number' => $order->getIncrementId(),
            'idm' => 'Y',
            'idm_sms_rule' => 902,
            'phone' => $order->getShippingAddress()->getTelephone(),
            'parcel_type' => 'PS',
            
        );
        if ($payment_method_code == 'cashondelivery') {
            $requestData['cod_amount'] = $order->getGrandTotal();
        }
        
        $phoneNumbers = $this->_getDialCodeHelper()->separatePhoneNumberFromCountryCode($shippingAddress->getTelephone(), $shippingAddress->getCountryId());
        $requestData['Sh_notify_phone_code'] = $phoneNumbers['dial_code'];
        $requestData['Sh_notify_contact_phone'] = $phoneNumbers['phone_number'];
        $requestResult = $this->_getDpdHelper()->getApi($this->getStore(), $this->_code)
                ->autoSendData($requestData);
        
        $this->_setDataToOrder($order, $requestResult);
        
        //on failure return false
        //is success
        return array('barcode' => '##'.  implode(',', $requestResult['pl_number']).'##');
        
    }
    
    
    
    /**
     * <p>Returns empty string</p>
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    protected function _getRemark($order) {
        return '';
    }


    /**
     * <p>Returns true if parcel data is sent to DPD server for specified order.</p>
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    public function isDataSent(Mage_Sales_Model_Order $order) {
        $orderData = $this->getDataFromOrder($order);
        if (isset($orderData['pl_number'])) {
            return true;
        }
        return false;
    }
    
    /**
     * <p>Returns packing slip URL if data is sent or false otherwise.</p>
     * @param Mage_Sales_Model_Order $order
     * @return boolean|string
     */
    public function getBarcode(Mage_Sales_Model_Order $order) {
        if (!$this->isBarcodeFunctionAvailable()) {
            return false;
        }
        
        $orderData = $this->getDataFromOrder($order);
        //if (isset($orderData['PDF_URL']) && $orderData['PDF_URL']) {
            Mage::log("getBarcode Post.php orderData['pl_number']:".print_r($orderData['pl_number'], true), null, 'dpdlog.log');
            return $orderData['pl_number'][0];
       // }
       // Mage::log("getBarcode fail['PDF_URL']:".print_r($orderData['PDF_URL'], true), null, 'dpdlog.log');
        //return false;
    }

    /**
     * <p>Returns Packing slip PDF file, which can be echoed to browser for current order if one exists.</p>
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getBarcodePdf(Mage_Sales_Model_Order $order) {
        $orderData = $this->getDataFromOrder($order);
        if (isset($orderData['PDF_URL']) && $orderData['PDF_URL']) {
            return file_get_contents(urldecode($orderData['PDF_URL']));
        }
    }
    
    
    
    
    /**
     * <p>Attempts to decode extra data stored within order commetns and return it as array.</p>
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getDataFromOrder(Mage_Sales_Model_Order $order) {
        return $this->_getOfficeHelper()->getDataFromOrder($order, self::ORDER_COMMENT_START_PREFIX);
    }
    
    /**
     * <p>Sets extra data to order and creates specialized order comment for it when neccessary.</p>
     * @param Mage_Sales_Model_Order $order
     * @param array $data
     * @return array
     */
    public function _setDataToOrder(Mage_Sales_Model_Order $order, $data = array()) {
        Mage::log("_setDataToOrder post.php data:".print_r($data, true), null, 'dpdlog.log');
        return $this->_getOfficeHelper()->setDataToOrder($order, $data, self::ORDER_COMMENT_START_PREFIX);
    }





    /**
     * <p>Returns array of parcel terminals from DPD server or boolean false if fetching failed.</p>
     * @return array|boolean
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getOfficeList()
     */
    public function getOfficeList() {
        $body = $this->_getDpdHelper()->getApi($this->getStore(), $this->_code)->getOfficeList();
        Mage::log("getOfficeList body:".print_r($body, true), null, 'dpdlog.log');

        if (!$body || !is_array($body) || !isset($body['parcelshops'])) {
            return false;
        }
        $result = array();
        foreach ($body['parcelshops'] as $remoteParcelTerminal) {
                $result[] = array(
                    'place_id' => $remoteParcelTerminal['parcelshop_id'],
                    'name' => $remoteParcelTerminal['company'],
                    'city' => trim($remoteParcelTerminal['city']),
                    'county' => '',
                    'description' => $this->_getDescription($remoteParcelTerminal),
                    'country' => $remoteParcelTerminal['country'] ? $remoteParcelTerminal['country'] : "lt_LT",
                    'zip' => $remoteParcelTerminal['pcode'],
                    'group_sort' => $this->getGroupSort($remoteParcelTerminal['city']),
                );
            
        }
        if (count($result) == 0) {
            return false;
        }
        return $result;
    }
    
    

    /**
     * <p>Returns parcel terminal name when short names are enabled.</p>
     * <p>Returns parcel terminal name with address, telephone, opening times when short names are disabled.</p>
     * @param Balticode_Postoffice_Model_Office $office
     * @return string
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getTerminalTitle()
     */
    public function getTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        if ($this->getConfigData('shortname')) {
            return htmlspecialchars($office->getName());
        }
        return htmlspecialchars($office->getName() . ' (' . $office->getDescription().')');
    }
    
    
    /**
     * <p>Returns parcel terminal name when short names are enabled.</p>
     * <p>Returns parcel terminal name with address, telephone, opening times when short names are disabled.</p>
     * @param Balticode_Postoffice_Model_Office $office
     * @return string
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getAdminTerminalTitle()
     */
    public function getAdminTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        if ($this->getConfigData('shortname')) {
            return htmlspecialchars($office->getGroupName().' - '.$office->getName());
        }
        return htmlspecialchars($office->getGroupName().' - '.$office->getName() . ' ' . $office->getDescription());
    }
    

    /**
     * 
     * @param Balticode_Postoffice_Model_Office $selectedOffice
     * @return type
     */
    private function _getStreetFromDescription(Balticode_Postoffice_Model_Office $selectedOffice) {
        $zip = $selectedOffice->getZipCode();
        $encoding = 'UTF-8';
        return trim(mb_substr($selectedOffice->getDescription(), 0, mb_strpos($selectedOffice->getDescription(), $zip, 0, $encoding), $encoding));
    }
    
    /**
     * 
     * @param Balticode_Postoffice_Model_Office $selectedOffice
     * @return string|array
     */
    private function _getPhoneFromDescription(Balticode_Postoffice_Model_Office $selectedOffice) {
        $zip = $selectedOffice->getZipCode();
        $country = $selectedOffice->getCountry();
        $matches = array();
        $isMatched = preg_match('/(?s:[\+][0-9]+)/', $selectedOffice->getDescription(), $matches);
        if ($isMatched) {
            return $matches[0];
        }
        return '';
    }
    

    /**
     * <p>Groups parcel terminals by following rules:</p>
     * <ul>
         <li>In Estonia parcel terminals from Tallinn, Tartu, Pärnu are displayed first respectively and remaining parcel terminals are displayed in alphabetical order.</li>
         <li>In Latvia parcel terminals from Riga, Daugavpils, Liepaja, Jelgava, Jurmala are displayed first respectively and remaining parcel terminals are displayed in alphabetical order.</li>
         <li>In Lithuania parcel terminals from Vilnius, Kaunas, Klaipeda, Siauliai, Alytus are displayed first respectively and remaining parcel terminals are displayed in alphabetical order.</li>
     </ul>
     * @param string $group_name
     * @return int
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getGroupSort()
     */
    public function getGroupSort($group_name) {
        $group_name = trim(strtolower($group_name));
        $sorts = array(
            //Estonia
            'tallinn' => 20,
            'tartu' => 19,
            'pärnu' => 18,
            
            //Latvia
            'riga' => 20,
            'daugavpils' => 19,
            'liepaja' => 18,
            'jelgava' => 17,
            'jurmala' => 16,
            
            
            //Lithuania
            'vilnius' => 20,
            'kaunas' => 19,
            'klaipeda' => 18,
            'siauliai' => 17,
            'alytus' => 16,
            
        );
        if (isset($sorts[$group_name]) && $this->getConfigData('sort_offices')) {
            return $sorts[$group_name];
        }
        if (strpos($group_name, '/') > 0 && $this->getConfigData('sort_offices')) {
            return 0;
        }
        return 0;
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
        if (!$this->getConfigData('courier_enable')) {
            return null;
        }
        $orderData = $this->getDataFromOrder($order);
        if (isset($orderData['courier_call_id']) && $orderData['courier_call_id']) {
            return true;
        }
        return false;
    }
    
    
    /**
     * <p>Returns number or parcels for the order according to Maximum Package Weight defined in DPD settings</p>
     * @param Mage_Sales_Model_Order $order
     * @return int
     * @see Balticode_Postoffice_Helper_Data::getNumberOfPackagesFromItemWeights()
     */
    protected function _getNumberOfPackagesForOrder(Mage_Sales_Model_Order $order) {
        $productWeights = array();
        foreach ($order->getAllVisibleItems() as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            for ($i = 0; $i < ($orderItem->getQtyOrdered() - $orderItem->getQtyRefunded()); $i++) {
                $productWeights[] = $orderItem->getWeight();
            }
            
        }
        return $this->_getOfficeHelper()->getNumberOfPackagesFromItemWeights($productWeights, $this->getConfigData('max_package_weight'));
    }
    
    
    /**
     * Gets config data only for this instance
     * @param string $field
     * @return mixed
     */
    public function getConfigDataForThis($field) {
        if (empty($this->_code)) {
            return false;
        }
        $path = 'carriers/'.$this->_code.'/'.$field;
        return Mage::getStoreConfig($path, $this->getStore());
    }
    
    /**
     * <p>Override this function in order to return senddata_event always manual, when http_request_timeout is greater than 10 seconds</p>
     * @param string $field
     * @return mixed
     */
    public function getConfigData($field) {
        if (empty($this->_code)) {
            return false;
        }
        return $this->_getConfigDataOverride($field, $this->_code);
    }
    
    /**
     * <p>Override is performed here</p>
     * @param string $field
     * @param string $code
     * @return string
     * @see Balticode_DpdLT_Model_Post::getConfigData()
     */
    protected function _getConfigDataOverride($field, $code) {
        if ($field == 'senddata_event') {
            $timeout = Mage::getStoreConfig('carriers/'.$code.'/http_request_timeout', $this->getStore());
            if ($timeout > 10) {
                return 'manual';
            }
        }
        $path = 'carriers/'.$code.'/'.$field;
        return Mage::getStoreConfig($path, $this->getStore());
    }
    
    
    /**
     * 
     * @return Balticode_DpdLT_Helper_Data
     */
    protected function _getDpdHelper() {
        return Mage::helper('balticode_dpdlt');
    }
    

    

}

