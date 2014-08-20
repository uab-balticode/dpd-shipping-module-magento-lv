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
 * <p>Base class for carriers, which ask customer to pick parcel terminal of choice from dropdown list.</p>
 * <p>Offers following business functions:</p>
 * <ul>
     <li>Chosen parcel terminal is forwarded to the Merchant</li>
     <li>Monitors if shipment parcel data is sent to remote server</li>
     <li>Offers PDF packing slip printing function, if subclasses implement actual fetch procedure.</li>
     <li>Offers auto update functionality for parcel terminals if subclasses implement actual parcel terminal fetch procedure</li>
     <li>Offers cash on delivery functionality with help of extra plugins.</li>
 * <li>Offers clickable tracking url or iframe functionality from Magento</li>
 </ul>
 * @author matishalmann
 */
abstract class Balticode_Postoffice_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract {
    
    /**
     * <p>Actual URL can be entered here, where %s in the url would be replaced with supplied tracking number.</p>
     * <p>When tracking number is entered, then user is provided with link to track the status of package on remote server.</p>
     * @var bool|string
     */
    protected $_tracking_url = false;
    
    /**
     * <p>When set to true and $_tracking_url is provided, then instead of clickable link iframe is displayed instead.</p>
     * @var bool
     */
    protected $_track_iframe = false;
    
    
    /**
     *  <p>This method should be implemented in subclasses of this carrier.</p>
     * <p>Purpose of this method is to return array of Postoffices/Parcel terminals associated to this carrier.</p>
     * <p>This method is called automatically by Magento's cron and the update frequency is regulated by the carriers
     * configuration data of <code>update_interval</code> which is expressed in minutes.</p>
     * <p>Usually the carriers are fetched from some remote location and sometimes the fetching may fail, in this case
     * this method should return boolean false in order to avoid Postoffice list being empty.</p>
     *  
    * <p>Each element contained in this array should have the following structure:
     * <pre>
     * <code>
     *         array(
                    'place_id' => (int)unique remote id for this office (Mandatory),
                    'name' => Unique name for this office (Mandatory),
                    'city' => City where this office is located,
                    'county' => County where this office is located,
                    'description' => Description where this office is located,
                    'country' => Country ID where this office is located format: EN, EE, FI,
                    'zip' => Zip code for this office,
                    'group_sort' => higher number places group higher in the parcel terminal list, defaults to 0,
                );

     * </code>
     * </pre>
     * </p>
     * <p>You can also supply <code>group_sort</code> parameter, if you have it. Offices which have greater group_sort parameter are displayed before
     * other postoffices. Alternatively you can write your own group_sort generator function by overwriting the getGroupSort(group_name) method
     * which should return integer value.</p>
     * <p>It is advised to supply at least city or county parameter, because first select menu will be created from group_name parameters
     * and group_name parameter is basically: county_name/city_name</p>
     * 
     * @return array of postoffices, where each element is assoc array with described structure.
     *  
     */
    abstract public function getOfficeList();
    
    
    /**
     * <p>If you would like to display certain counties or cities before the others, then you can overwrite this function.</p>
     * <p>This function should return positive integer. The greater the number returned means this county/city is more important than the others
     * and should be displayed before the others.</p>
     * <p>In general postoffices will be displayed according to the following rules:
     * <ul>
     * <li>group_sort descending</li>
     * <li>group_name ascending</li>
     * <li>name ascending</li>
     * </ul>
     * </p>
     * <p>This function is called only when getOfficeList function does not supply group_sort parameter</p>
     * 
     * 
     * @param string $group_name county/city or county or city
     * @return int greater value, means this office belongs to more important group.
     */
    public function getGroupSort($group_name) {
        return 0;
    }
    
    /**
     *  <p>This function is called right after:
     * <ul>
     * <li>This carrier is active</li>
     * <li>Cart's max weight does not exceed the limit</li>
     * <li>Cart's min weight is not below the limit</li>
     * </ul>
     * </p>
     * <p>If this function returns false, then this carrier is not available.</p>
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return boolean false, if this carrier should not be available.
     */
    protected function _isAvailable(Mage_Shipping_Model_Rate_Request $request) {
        return true;
    }
    
    /**
     * <p>If carrier supports external tracking URL's then it should return true</p>
     * @return boolean
     */
    public function isTrackingAvailable() {
        if ($this->getTrackingUrl()) {
            return true;
        }
        return false;
    }
    


    /**
     * <p>Returns tracking URL for current carrier if one exists.</p>
     * @return bool|string
     */
    public function getTrackingUrl() {
        return $this->_tracking_url;
    }

    /**
     * <p>When set to true and $_tracking_url is provided, then instead of clickable link iframe is displayed instead.</p>
     * @return bool
     */
    public function getTrackingInIframe() {
        return $this->_track_iframe;
    }
    
    /**
     * <p>Attempts to display tracking link or tracking iframe if tracking is supported by the carrier and tracking url is available.</p>
     * @param string $number tracking number
     * @return Varien_Object|null
     */
    public function getTrackingInfo($number) {
        if ($this->getTrackingUrl()) {
            $custom = new Varien_Object();

            $trackingUrl = sprintf($this->getTrackingUrl(), $number);
            if ($this->getTrackingInIframe()) {

                $custom->setTracking('<iframe src="' . $trackingUrl . '" style="border:0px #FFFFFF none;" name="abc" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" height="100%" width="100%"></iframe>');
            } else {
                $custom->setTracking(sprintf('<a href="%s" target="_blank">%s</a>', $trackingUrl, $this->_getBalticode()->__('Tracking URL')));
            }

            return $custom;
        }
        return null;
    }

    /**
     *  Returns true if this Mage_Shipping_Model_Rate_Request is multishipping.
     * 
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return boolean 
     */
    protected function _isMultishipping(Mage_Shipping_Model_Rate_Request $request) {
        foreach ($request->getAllItems() as $item) {
            if ($item instanceof Mage_Sales_Model_Quote_Address_Item) {
                return true;
            }
            break;
        }
        return false;
    }


    /**
     *  <p>use <code>_isAvailable()</code> function to determine if the carrier is available.</p>
     * <p>use <code>_calculateAdditionalShippingPrice</code> function to calculate any additional shipping cost.</p>
     * 
     *  <p>Checks for the following conditions:
     * <ul>
     * <li>This carrier is active</li>
     * <li>Cart's max weight does not exceed the limit</li>
     * <li>Cart's min weight is not below the limit</li>
     * </ul>
     * </p>
     *  <p>Calculates the shipping price based on the following:
     * <ul>
     * <li>Configurations like <code>enable_free_shipping</code>,<code>free_shipping_from</code> are enabled or disabled </li>
     * <li>Configuration like <code>handling_fee</code> which is the base shipping price.</li>
     * <li>Configuration like <code>handling_action</code> which determines whether the shipping price is based on per order or per item</li>
     * <li>If shopping cart rules specify free shipping.</li>
     * <li>Price found from the conditions above will be passed as second parameter to the function <code>_calculateAdditionalShippingPrice</code> which in turn can alter the shipping price further.</li>
     * </ul>
     * </p>
     * 
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return boolean|Balticode_Postoffice_Model_Carrier_Result false, if carrier is not available.
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        //determine if this shipping method is available
        $addressId = (string)$this->getAddressId($request);
        if (!$this->getConfigData('active')) {
            $this->clearAddressId($addressId);
            return false;
        }
        if ($this->getConfigData('use_per_item_weight')) {
            //we compare each item individually
            //if any of the items is overweight, disable
            //if any of the items is underweight, disable
            $loadedProductWeights = array();
            if ($this->getConfigData('max_package_weight') > 0 || $this->getConfigData('min_package_weight') > 0) {
                //get request items
                if ($request->getAllItems()) {
                    foreach ($request->getAllItems() as $requestItem) {
                        $productToWeight = $this->_getProductModel()->load($requestItem->getProduct()->getId());
                        for ($i = 0; $i < $requestItem->getQty(); $i++) {
                            $loadedProductWeights[] = $productToWeight->getWeight();
                        }
                    }
                }
            }
            if ($this->getConfigData('max_package_weight') > 0) {
                if (max($loadedProductWeights) > (float) $this->getConfigData('max_package_weight')) {
                    $this->clearAddressId($addressId);
                    return false;
                }
            }
            if ($this->getConfigData('min_package_weight') > 0) {
                if (min($loadedProductWeights) < (float) $this->getConfigData('min_package_weight')) {
                    $this->clearAddressId($addressId);
                    return false;
                }
            }
            $request->setBalticodeProductWeights($loadedProductWeights);
            
        } else {
            //we summarize weight of all cart and apply the weight rule
            //total cart is overweight, disable
            //total cart is underweight, disable
            if ($this->getConfigData('max_package_weight') > 0) {
                if ($request->getPackageWeight() > (float) $this->getConfigData('max_package_weight')) {
                    $this->clearAddressId($addressId);
                    return false;
                }
            }
            if ($this->getConfigData('min_package_weight') > 0) {
                if ($request->getPackageWeight() < (float) $this->getConfigData('min_package_weight')) {
                    $this->clearAddressId($addressId);
                    return false;
                }
            }
        }

        $isAvailable = $this->_isAvailable($request);
        if ($isAvailable === false) {
            $this->clearAddressId($addressId);
            return false;
        }
        
        //determine the shipping price
        $price = 0;
        
        //fetch the base handling fee
        $handlingFee = (float)str_replace(',', '.', $this->getConfigData('handling_fee'));
        //shipping method is available. Find the price.
        $isFree = false;
        
        //if we have defined free shipping from certain cart subtotal level
        if ($this->getConfigData('enable_free_shipping') && $request->getPackageValueWithDiscount() >= $this->getConfigData('free_shipping_from')) {
            $price = 0;
            $isFree = true;
        }
        
        //if there is free shipping defined by shopping cart rule
        if ($request->getFreeShipping() === true && !$isFree) {
            $isFree = true;
        }

        if (!$isFree) {
            $freeBoxes = 0;
            //if there are any free boxes defined by shopping cart rule
            foreach ($request->getAllItems() as $item) {
                if ($item->getFreeShipping() && !$item->getProduct()->isVirtual()) {
                    $freeBoxes += $item->getQty();
                }
            }
            $this->setFreeBoxes($freeBoxes);
        }
        
        
        //if there is no free shipping, attempt to calcualte the price
        //on per order or per item basis
        if ($this->getConfigData('handling_action') == 'O' && !$isFree) {
            //handling action per_order basis
            $price = $handlingFee;
        } else if ($this->getConfigData('handling_action') == 'P' && !$isFree) {
            //handling action per item basis
            if ($this->getConfigData('use_per_item_weight')) {
                //calculate number of packages first
                $price = ($this->_getOfficeHelper()->getNumberOfPackagesFromItemWeights($loadedProductWeights, $this->getConfigData('max_package_weight'))  - $this->getFreeBoxes()) * $handlingFee;
                
            } else {
                $price = ($request->getPackageQty() * $handlingFee) - ($this->getFreeBoxes() * $handlingFee);
            }
            if ($price == 0) {
                $isFree = true;
            }
        } else if (!$isFree) {
            //handling action type could not be detected, return false
            //shipping method not available
            $this->clearAddressId($addressId);
            return false;
        }
        
        //if there is any additional logic for calculating the price
        if (!$isFree) {
            $price = $this->_calculateAdditionalShippingPrice($request, $price);
        }
        if ($price === false) {
            return false;
        }
        $session = Mage::getSingleton('core/session');
        
        $this->registerAddressId($addressId);

        
        
        $result = Mage::getModel('balticode_postoffice/carrier_result');
        //list the offices
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        
        $method->setMethod($this->_code);
        //TODO: get the correct price
        $method->setPrice($price);
        $loadingText = Mage::helper('balticode_postoffice')->__('Loading offices...');
        $html = '';
        if ($this->_isMultishipping($request)) {
            $html .= '<div id="balticode_carrier_'.$addressId.'_'.$this->_code.'" style="display:inline-block;">'.$loadingText.'</div>';
        } else {
            $html .= '<div id="balticode_carrier_'.$this->_code.'" style="display:inline-block;"></div>';
        }
        $url = Mage::getUrl('balticode_postoffice/index/office', array('_secure' => true));
        
        if (Mage::app()->getStore()->isAdmin()) {
            $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/office', array('store_id' => $request->getStoreId(), '_secure' => true));
        }
        
        $carrierId = 's_method_'.$this->_code.'_'.$this->_code;
        $divId = 'balticode_carrier_'.$this->_code;
        if ($this->_isMultishipping($request)) {
            $carrierId = 's_method_'.$addressId.'_'.$this->_code.'_'.$this->_code;
            $divId = 'balticode_carrier_'.$addressId.'_'.$this->_code;
        }
        $carrierCode = $this->_code;
        
        $html .= <<<EOT
        <script type="text/javascript">
            /* <![CDATA[ */
                $('{$carrierId}').writeAttribute('value', '');
                new Ajax.Request('{$url}', {
                    method: 'post',
                    parameters: {
                        carrier_id: '{$carrierId}',
                        carrier_code: '{$carrierCode}',
                        div_id: '{$divId}',
                        address_id: '{$addressId}',
                    },
                    onSuccess: function(transport) {
                        $('{$divId}').update(transport.responseText);
                    }
                    });
            /* ]]> */
        </script>
EOT;
        $method->setMethodTitle($html);
        $terminalsCollection = $this->getTerminals();
        if ($terminalsCollection->count() == 1) {
            //if we have only one terminal in the collection, then do not create select drop downs.
            //just render the single element and return the result.
            $office = $terminalsCollection->getFirstItem();
            $method->setMethodTitle($this->getTerminalTitle($office));
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
        
            $method->setMethod($this->_code.'_'.$office->getRemotePlaceId());
            $result->append($method);
            return $result;
        }
        

        $result->append($method);
        
        
        $addedMethods = array();
        //try to see, if we have any extra methods stored in a session
        $sessionData = $session->getData('balticode_carrier_' . $this->_code);
        if (is_array($sessionData) && isset($sessionData[$addressId])) {
            foreach ($sessionData[$addressId] as $subCarrier) {
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($subCarrier['carrier']);
                $method->setCarrierTitle($subCarrier['carrier_title']);
                $addedMethods[$subCarrier['method']] = $subCarrier['method'];

                $method->setMethod($subCarrier['method']);
                $method->setMethodTitle($subCarrier['method_title']);
                $method->setPrice($price);
                $result->append($method);
            }
        }

        //TODO: add the method if we are able to detect the postoffice automatically
        $additionalOffices = $this->getOfficesFromAddress($request);
        foreach ($additionalOffices as $office) {
            $methodName = $this->_code .'_' .$office->getRemoteModuleId();
            if (!isset($addedMethods[$methodName])) {
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));
                $addedMethods[$methodName] = $method;

                $method->setMethod($methodName);
                $method->setMethodTitle($this->getAdminTerminalTitle($office));
                $method->setPrice($price);
                $result->append($method);
                
            }
        }


        return $result;
        
    }
    
    
    
    /**
     * <p>Registers current address id in session, so no outsiders could tamper with selected parcel terminal data.</p>
     * @param string $addressId
     * @return Balticode_Postoffice_Model_Carrier_Abstract
     */
    private function registerAddressId($addressId) {
        $addressId = (string)$addressId;
        $session = Mage::getSingleton('core/session');
        $sessionData = $session->getData('balticode_allowedcarriers');
        if (!is_array($sessionData)) {
            $sessionData = array();
        }
        if (!isset($sessionData[$addressId])) {
            $sessionData[$addressId] = array();
        }
        if (!isset($sessionData[$addressId][$this->_code])) {
            $sessionData[$addressId][$this->_code] = $this->_code;
        }
        $session->setData('balticode_allowedcarriers', $sessionData);
        return $this;
    }
    
    /**
     *  Security  related, used to prevent customer to enter arbitrary data as their shipping carrier.
     * 
     * 
     * @param int $addressId
     * @return boolean 
     */
    final public function isAjaxInsertAllowed($addressId) {
        $addressId = (string)$addressId;
        $session = Mage::getSingleton('core/session');
        $sessionData = $session->getData('balticode_allowedcarriers');
        if (is_array($sessionData) && isset($sessionData[$addressId]) && isset($sessionData[$addressId][$this->_code])) {
            return true;
        }
        return false;
    }
    
    /**
     * <p>After successful checkout all entered parcel terminal data should be cleared by calling this function.</p>
     * @param string $addressId
     * @return Balticode_Postoffice_Model_Carrier_Abstract
     */
    private function clearAddressId($addressId) {
        $addressId = (string)$addressId;
        $session = Mage::getSingleton('core/session');
        $sessionData = $session->getData('balticode_allowedcarriers');
        if (is_array($sessionData) && isset($sessionData[$addressId]) && isset($sessionData[$addressId][$this->_code])) {
            unset($sessionData[$addressId][$this->_code]);
        }
        return $this;
    }
    
    /**
     *  <p>This function is called right after the initial price calculation by the </code>collectRates</code> has been called.</p>
     * <p>Overwrite this function if you want to apply more complicated price calculation rules, than </code>collectRates</code>
     * function offers.</p>
     * 
     * 
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param float $price pre-calculated price by the </code>collectRates</code> function.
     * @return float new price, this carrier should have. 
     */
    protected function _calculateAdditionalShippingPrice(Mage_Shipping_Model_Rate_Request $request, $price) {
        return $price;
    }
    
    /**
     * <p>Overwrite this function in your subclass if you would like to offer automatic postoffice selection based on the user entered address.</p>
     *
     * ["dest_country_id"] => string(2) "EE" 
     * ["dest_region_id"] => string(3) "345" 
     * ["dest_region_code"] => string(5) "EE-57" 
     * ["dest_street"] => string(11) "mina ei tea" 
     * ["dest_city"] => string(7) "tallinn" 
     * ["dest_postcode"] => string(5) "12345" 
     * ["package_value"] => float(16.33) 
     * ["package_value_with_discount"] => float(16.33) 
     * ["package_weight"] => float(16.16) 
     * ["package_qty"] => int(2) 
     * ["package_physical_value"] => float(16.33) 
     * ["free_method_weight"] => float(16.16) 
     * ["store_id"] => string(1) "2" 
     * ["website_id"] => string(1) "1" 
     * ["free_shipping"] => int(0) 
     * ["base_currency (Mage_Directory_Model_Currency)"] => array(1) { ["currency_code"] => string(3) "EUR" } 
     * ["package_currency (Mage_Directory_Model_Currency)"] => array(1) { ["currency_code"] => string(3) "EUR" } 
     * ["country_id"] => string(2) "EE" 
     * ["region_id"] => string(1) "0" 
     * ["city"] => string(0) "" 
     * ["postcode"] => string(0) "" 
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return array of 'balticode_postoffice/office' models belonging to this carrier.
     */
    protected function getOfficesFromAddress(Mage_Shipping_Model_Rate_Request $request) {
        return array();
    }
    
    /**
     *  <p>Returns distinct group_name,group_id,group_sort as Balticode_Postoffice_Model_Mysql4_Office_Collection of 'balticode_postoffice/office' models</p>
     * <p>Result of this function is used to render the first select menu (county/city) for this carrier.</p>
     * <p>If no groups can be found, then this function returns boolean false.</p>
     * 
     * 
     * @param int $addressId when supplied then only groups from the addressId country are returned.
     * @return boolean|array 
     */
    public function getGroups($addressId = null) {
        if( $this->getConfigData('drop_menu_selection')){
            return false;
        }
        $groups = Mage::getModel('balticode_postoffice/office')->getCollection()
                ->distinct(true)
                ->addFieldToFilter('remote_module_name', $this->_code);
        $groups->getSelect()->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('group_id', 'group_name', 'group_sort'));
        $groups->addOrder('group_sort', 'DESC');
        $groups->addOrder('group_name', 'ASC');
        if ($addressId) {
            $address = $this->_getAddressModel()->load($addressId);
            if ($address->getCountryId()) {
                $groups->addFieldToFilter('country', $address->getCountryId());
            }
        }
        
        
        if ($groups->count() <= 1) {
            return false;
        }
        return $groups;
    }

    /**
     * 
     *  <p>Returns Balticode_Postoffice_Model_Mysql4_Office_Collection which should contain the actual postoffices
     * which belong to the selected group_id in alplabetically sorted order.</p>
     * <p>If no $groupId is supplied, then all the postoffices are returned.</p>
     * <p>Offices are sorted by</p>
     * <ul>
         <li>group_sort descending</li>
         <li>group_name ascending</li>
         <li>name ascending</li>
     </ul>
     * @param int $groupId
     * @param int $addressId when supplied then only offices from the addressId country are returned.
     * @return Balticode_Postoffice_Model_Mysql4_Office_Collection
     * @see Balticode_Postoffice_Model_Carrier_Abstract::getGroupSort()
     */
    public function getTerminals($groupId = null, $addressId = null) {
        $terminals = Mage::getModel('balticode_postoffice/office')->getCollection()
                ->addFieldToFilter('remote_module_name', $this->_code)
                ->addOrder('group_sort', 'DESC')
                ->addOrder('group_name', 'ASC')
                ->addOrder('name', 'ASC')
        ;
        
        if ($addressId) {
            $address = $this->_getAddressModel()->load($addressId);
            if ($address->getCountryId()) {
                $terminals->addFieldToFilter('country', $address->getCountryId());
            }
        }
        
        if ($groupId !== null) {
            $terminals->addFieldToFilter('group_id', $groupId);
        }
        return $terminals;
    }
    
    
    /**
     *  <p>Returns the selected postoffice by it's place id.<p>
     * <p>If no postoffice found, then returns boolean false.</p>
     * 
     * @param int $placeId
     * @return Balticode_Postoffice_Model_Office|boolean false 
     */
    public function getTerminal($placeId) {
        $places = Mage::getModel('balticode_postoffice/office')->getCollection()
                ->addFieldToFilter('remote_module_name', $this->_code)
                ->addFieldToFilter('remote_place_id', $placeId);
        if ($places->count() == 1) {
            return $places->getFirstItem();
        }
        return false;
    }

    /**
     *  Determines how name of each postoffice in the second select menu should be rendered.
     * 
     * 
     * @param Balticode_Postoffice_Model_Office $office
     * @return string 
     */
    public function getTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        return htmlspecialchars($office->getName());
    }
    /**
     *  Determines how name of each county/city in the first select menu should be rendered.
     * 
     * 
     * @param Balticode_Postoffice_Model_Office $office
     * @return string 
     */
    public function getGroupTitle(Balticode_Postoffice_Model_Office $office) {
        return htmlspecialchars($office->getGroupName());
    }
    /**
     *  Determines how name of customer selected postoffice should be rendered and this is also how merchant sees the selected postoffice.
     * 
     * 
     * @param Balticode_Postoffice_Model_Office $office
     * @return string 
     */
    public function getAdminTerminalTitle(Balticode_Postoffice_Model_Office $office) {
        $name = $office->getName();
        if ($office->getGroupName() != '') {
            $name = $office->getGroupName().' - '.$office->getName();
        }
        return htmlspecialchars($name);
    }
    
    
    /**
     *
     * Used to fetch the price from 'sales/quote_address_rate' database table.
     * 
     * @param type $addressId
     * @return boolean 
     */
    final protected function getPriceFromAddressId($addressId) {
        $calculatedPriceModelCollection = Mage::getModel('sales/quote_address_rate')
                ->getCollection()
                ->addFieldToFilter('address_id', $addressId)
                ->addFieldToFilter('carrier', $this->_code)
                ->addFieldToFilter('code', $this->_code . '_' . $this->_code)
        ;
        if ($calculatedPriceModelCollection->count() == 1) {
            return $calculatedPriceModelCollection->getFirstItem()->getPrice();
        }
        return false;
    }
    
    /**
     *  <p>Initially this carrier does not set up all the shipping methods for this carrier in the 'sales/quote_address_rate' database table.
     * since storing too many methods in there is not the smartest thing to do</p>
     * <p>So once the user selects the actual office, an AJAX callback is performed and this one inserts the selected office to the database
     * and also to the session, in order the customer would easily reach latest selected offices and the order itself could be placed,
     * since user selected postoffices have to exist in the 'sales/quote_address_rate' database table.</p>
     * <p>This one results in less entries in 'sales/quote_address_rate' database table.</p>
     * 
     * 
     * @param int $addressId
     * @param Balticode_Postoffice_Model_Office $office
     * @throws Exception 
     */
    final public function setOfficeToSession($addressId, Balticode_Postoffice_Model_Office $office) {
        
        $addressRateModelCollection = Mage::getModel('sales/quote_address_rate')
                ->getCollection()
                ->addFieldToFilter('address_id', $addressId)
                ->addFieldToFilter('carrier', $this->_code)
                ->addFieldToFilter('code', $this->_code . '_' . $this->_code . '_' . $office->getRemotePlaceId())
        ;
        if ($addressRateModelCollection->count() == 0) {
            //insert
            $newRate = Mage::getModel('sales/quote_address_rate');
            $newRate->setData('address_id', $addressId);
            $newRate->setData('carrier', $this->_code);
            $newRate->setData('code', $this->_code . '_' . $this->_code . '_' . $office->getRemotePlaceId());
            $newRate->setData('method', $this->_code . '_' . $office->getRemotePlaceId());

            $title = $this->getAdminTerminalTitle($office);

            $newRate->setData('carrier_title', $this->getConfigData('title'));
            $newRate->setData('method_title', $title);
            $price = $this->getPriceFromAddressId($addressId);
            if ($price === false) {
                throw new Exception('Cannot calculate price');
            }
            $newRate->setPrice($price);
            $newRate->save();
            $session = Mage::getSingleton('core/session');
            $sessionData = $session->getData('balticode_carrier_' . $this->_code);
            if (!is_array($sessionData)) {
                $sessionData = array();
            }
            if (!isset($sessionData[(string)$addressId])) {
                $sessionData[(string)$addressId] = array();
            }
            if ($this->getConfigData('disable_session')) {
                foreach ($sessionData[(string)$addressId] as $k => $v) {
                    if ($k != $this->_code) {
                        unset($sessionData[(string)$addressId][$k]);
                    }
                }
                
            }
            $sessionData[(string)$addressId][$this->_code . '_' . $this->_code . '_' . $office->getRemotePlaceId()] = $newRate->debug();
            $session->setData('balticode_carrier_' . $this->_code, $sessionData);
        }
    }

    /**
     *  Clears all the session data created by this carrier.
     * 
     * 
     * @return \Balticode_Postoffice_Model_Carrier_Abstract 
     */
    final public function clearSession() {
        Mage::getSingleton('core/session')->unsetData('balticode_allowedcarriers');
        Mage::getSingleton('core/session')->unsetData('balticode_carrier_' . $this->_code);
        return $this;
    }
    


    /**
     * <p>Sets Magento store config for current carrier specified by key</p>
     * @param string $key
     * @param mixed $value
     * @return Balticode_Postoffice_Model_Carrier_Abstract|boolean
     */
    public function setConfigData($key, $value) {
        if (empty($this->_code)) {
            return false;
        }
        Mage::helper('balticode')->setConfigData('carriers/'.$this->_code.'/'.$key, $value);
        return $this;
    }
    
    /**
     *  Should return true, when this carrier can automatically send shipment data to third party carrier server.
     * 
     * 
     * @return bool
     */
    public function isAutoSendAvailable() {
        return (bool)$this->getConfigData('senddata_enable');
    }
    
    /**
     * <p>Should return the actual generated barcode by the third party carrier server.
     * Overwrite this method in the subclass.</p>
     * <p>This function is available to the merchant, if he/she wants to print the packing slip in the order view.</p>
     * 
     * 
     * 
     * @param Mage_Sales_Model_Order $order order, to get the barcode for.
     * @return boolean|string 
     */
    public function getBarcode(Mage_Sales_Model_Order $order) {
        return false;
    }
    
    /**
     * <p>Should return the actual Pdf binary which should be packing slip for this order and false in every other case.</p>
     * <p>This function is available to the merchant, if he/she wants to print the packing slip in the order view.</p>
     * 
     * 
     * @param Mage_Sales_Model_Order $order
     * @return boolean 
     */
    public function getBarcodePdf(Mage_Sales_Model_Order $order) {
        return false;
    }
    
    /**
     * <p>Returns true, if data has been sent</p>
     * <p>Returns false, if data has not been sent</p>
     * <p>Returns null, if data sending is not available.</p>
     * 
     * @param Mage_Sales_Model_Order $order
     * @return boolean 
     */
    public function isDataSent(Mage_Sales_Model_Order $order) {
        if ($this->isAutoSendAvailable()) {
            return false;
        }
        return null;
    }
    
    /**
     *
     * If the barcode function is available at all for this carrier.
     * @return boolean 
     */
    public function isBarcodeFunctionAvailable() {
        return false;
    }
    
    

    /**
     * <p> If automatic data sending is available, then this function should be overwritten and the actual data sending should be performed in here.</p>
     * <p>Also Configuration value of <code>senddata_enable</code> should be set to '1' or this function will never be called.</p>
     * <p>If automatic sending is not applicable, then this function should return boolean false.</p>
     * <p>If automatic data sending is successful, then the result will be added to the order comments using <code>print_r()</code> function.</p>
     * 
     * 
     * 
     * @param Mage_Sales_Model_Order $order
     * @param type $selectedOfficeId remote_place_id - id of the carrier, that the customer selected.
     * @return boolean|array 
     */
    public function autoSendData(Mage_Sales_Model_Order $order, $selectedOfficeId) {
        return false;
    }
    
    /**
     *  Wrapper function for the parent class, simply for security and session management.
     * For example, when user first selects country, where the carrier is available and then switches country and the carrier is not available any more,
     * then the session has to be cleared a bit, in order to avoid the user entering arbitrary data for the previously available carrier.
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return \Mage_Shipping_Model_Rate_Result_Error 
     */
    public function checkAvailableShipCountries(Mage_Shipping_Model_Rate_Request $request) {
        $checkResult = parent::checkAvailableShipCountries($request);
        if ($checkResult === false || ($checkResult instanceof Mage_Shipping_Model_Rate_Result_Error)) {
            $this->clearAddressId($this->getAddressId($request));
        }
        return $checkResult;
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
        return null;
    }
    
    /**
     *  Gets the address_id, which will be used in the 'sales/quote_address_rate' database table, in order to handle user selection of postoffice
     * related to this carrier.
     * 
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return type 
     */
    protected function getAddressId(Mage_Shipping_Model_Rate_Request $request) {
        if (Mage::app()->getStore()->isAdmin()) {
            return Mage::getSingleton('adminhtml/sales_order_create')->getQuote()->getShippingAddress()->getId();
        } else if ($this->_isMultishipping($request)) {
            $shippingAddresses = Mage::getSingleton('checkout/type_multishipping')->getQuote()->getAllShippingAddresses();
            foreach ($shippingAddresses as $shippingAddress) {
                if ($this->_compareAddressToRequest($request, $shippingAddress)) {
                    if ($shippingAddress->getId() <= 0) {
                        $shippingAddress->save();
                    }
                    return $shippingAddress->getId();
                }
            }
        } else {
            return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getId();
        }
        
    }
    
    /**
     * <p>In multishipping scenarios shipping addresses may not be saved and thus lacking correct id. In such scenario they need to be compared manually to detect the correct chosen parcel terminal</p>
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param Mage_Sales_Model_Quote_Address $address
     * @return boolean
     */
    protected function _compareAddressToRequest(Mage_Shipping_Model_Rate_Request $request, $address) {
        $compareFields = array(
            'dest_country_id' => 'country_id',
            'dest_region_id' => 'region_id',
            'dest_street' => 'street',
            'dest_city' => 'city',
            'dest_postcode' => 'postcode',
        );
        foreach ($compareFields as $requestField => $addressField) {
            if ($request->getData($requestField) != $address->getData($addressField)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * <p>Gets current carrier code for usage in other helper classes.</p>
     * @return string
     */
    public function getCode() {
        return $this->_code;
    }
    
    public function getAllowedMethods()
    {
        return array($this->_code=>$this->getConfigData('title'));
    }
    
    /**
     * 
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getAddressModel() {
        return Mage::getModel('sales/quote_address');
    }
    
    /**
     * 
     * @return Mage_Catalog_Model_Product
     */
    protected function _getProductModel() {
        return Mage::getModel('catalog/product');
    }
    
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
    
    /**
     * 
     * @return Balticode_Postoffice_Helper_Data
     */
    protected function _getOfficeHelper() {
        return Mage::helper('balticode_postoffice');
    }
    
    
    /**
     * 
     * @return Balticode_Postoffice_Helper_Countrycode
     */
    protected function _getDialCodeHelper() {
        return Mage::helper('balticode_postoffice/countrycode');
    }
    
    
}

