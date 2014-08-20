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
 * <p>Wrapper class for communicating with DPD API</p>
 * <p>Each request is prefilled with username, password, return address data whenever possible.</p>
 * <p>Each response is json_decoded to assoc array and Exception is thrown when response error code is else than integer 0</p>
 *
 * @author Matis
 */
class Balticode_DpdLT_Model_Api extends Varien_Object {
    
    
    
    /**
     * Retrieve information from carrier configuration
     *
     * @param   string $field
     * @return  mixed
     */
    public function getConfigData($field) {
        if (!$this->getCode()) {
            return false;
        }
        $path = 'carriers/'.$this->getCode().'/'.$field;
        return Mage::getStoreConfig($path, $this->getStore());
    }
    
    
    /**
     * <p>Fetches list of parcel terminals from DPD API. (op=pudo)</p>
     * <p>This function can be used without DPD API account.</p>
     * <p>Parcel terminals are included in 'data' array key.</p>
     * @return array
     */
    public function getOfficeList() {
        $body = @$this->_getRequest();
        return $body;
    }
    
    
    /**
     * <p>Fetches available courier collection times. (op=date)</p>
     * @param array $requestData
     * @return array
     */
    public function getCourierCollectionTimes(array $requestData = array()) {
        //Mage::log("getCourierCollectionTimes requestData:".print_r($requestData, true), null, 'dpdlog.log');
        $details = array(
            'pickup_date' => date("Y-m-d"),
            'pickup_pcode' => $this->getConfigData('return_postcode'),
            'secret' => 'FcJyN7vU7WKPtUh7m1bx',
            'action' => 'pickup_info',
        );
        //Mage::log("getCourierCollectionTimes details:".print_r($details, true), null, 'dpdlog.log');
        foreach ($details as $key => $detail) {
            $requestData[$key] = $detail;
        }
        $requestResult = $this->_getRequest($requestData);
        return $requestResult;
        
    }
    
    /**
     * <p>Send parcel data to DPD server, prefills with return data from Magento configuration.</p>
     * @param array $requestData
     * @return array
     */
    public function autoSendData(array $requestData) {
        $returnDetails = array(
            'op' => 'order',
            'Po_name' => $this->getConfigData('return_name'),
            'Po_company' => $this->getConfigData('return_company'),
            'Po_street' => $this->getConfigData('return_street'),
            'Po_postal' => $this->getConfigData('return_postcode'),
            'Po_country' => strtolower($this->getConfigData('return_country')),
            'Po_city' => $this->getConfigData('return_citycounty'),
            'Po_contact' => $this->getConfigData('return_name'),
            'Po_phone' => $this->getConfigData('return_phone'),
            //po-remark
            'Po_email' => $this->getConfigData('return_email'),
            'Po_show_on_label' => $this->getConfigData('po_show_on_label')?'true':'false',
            'Po_save_address' => $this->getConfigData('po_save_address')?'true':'false',
//            'Po_type' => $this->getConfigData('senddata_service'),
            'LabelsPosition' => $this->getConfigData('label_position'),
            'action' => 'parcel_import',
            
        );
        
        foreach ($returnDetails as $key => $returnDetail) {
            $requestData[$key] = $returnDetail;
        }
        //if (!isset($requestData['Po_type'])) {
        //    $requestData['Po_type'] = $this->getConfigData('senddata_service');
        //}
        $requestResult = $this->_getRequest($requestData);
        return $requestResult;
    }
    
    /**
     * @param array $requestData
     * @return array
     */
    public function callCurier(array $requestData) {
        $returnDetails = array(
            'payerName' => $this->getConfigData('return_name'),
            'senderName' => $this->getConfigData('return_name'),
            'senderAddress' => $this->getConfigData('return_street'),
            'senderPostalCode' => $this->getConfigData('return_postcode'),
            'senderCountry' => strtolower($this->getConfigData('return_country')),
            'senderCity' => $this->getConfigData('return_citycounty'),
            'senderContact' => $this->getConfigData('return_name'),
            'senderPhone' => $this->getConfigData('return_phone'),
            'action' => 'dpdis/pickupOrdersSave',
            
        );
        
        foreach ($returnDetails as $key => $returnDetail) {
            $requestData[$key] = $returnDetail;
        }
        $requestResult = $this->_getRequest($requestData);
        return $requestResult;
    }


    /**
     * <p>Determines if courier has been called to pick up the packages.</p>
     * <p>If courier has been called to fetch packages and courier pickup time from has not yet been reached, then it returns array consisting following elements:</p>
     * <ul>
         <li>UNIX timestamp when courier pickup should start</li>
         <li>UNIX timestamp when courier pickup should end</li>
     </ul>
     * <p>On every other scenario this function returns boolean false</p>
     * @return boolean|array
     */
    public function isCourierComing() {
        $pickupTime = $this->getConfigData('courier_pickup_time');
        $time = time();
        if ($pickupTime) {
            $pickupTime = explode(',', $pickupTime);
        }
        if ($pickupTime[0] >= $time) {
            return $pickupTime;
        }
        return false;
    }
    
    public function getLabelData(array $barcode) {
        //Mage::log("getLabelData barcode:".print_r($barcode, true), null, 'dpdlog.log');
        $returnDetails = array(
            'action' => 'parcel_print',
            'parcels' => implode ("|",$barcode),
        );
        
        //foreach ($returnDetails as $key => $returnDetail) {
            //$requestData[$key] = $returnDetail;
        //}
        //if (!isset($requestData['Po_type'])) {
        //    $requestData['Po_type'] = $this->getConfigData('senddata_service');
        //}
        $requestResult = $this->_getRequest($returnDetails);
        return $requestResult;
    } 
    
    public function datasend() {
        $returnDetails = array(
            'action' => 'parcel_datasend',
        );
        $requestResult = $this->_getRequest($returnDetails);
        Mage::log("datasend requestResult:".print_r($requestResult, true), null, 'dpdlog.log');
        return $requestResult;
    } 

    public function getManifest() {
   		$data=$this->datasend();
    	if (!is_array($data) || !isset($data['errlog']) || $data['errlog'] !== '') {
    		Mage::throwException($this->_getDpdHelper()->__('DPD request failed with response: %s', print_r($resp->getBody(), true)));
    	}else{
        $returnDetails = array(
            'action' => 'parcel_manifest_print',
            'type' => 'manifest',
            'date' => date("YY-MM-DD"),
        );

        $requestResult = $this->_getRequest($returnDetails);
        return $requestResult;
        }
    } 
    
    /**
     * <p>Sends actual request to DPD API, prefills with username and password and json decodes the result.</p>
     * <p>Default operation (op=pudo), on such scenario username and password is not sent.</p>
     * <p>If return error code is else than 0, then exception is thrown.</p>
     * @param array $params
     * @param string $url
     * @return array
     */
    protected function _getRequest($params = array('action' => 'parcelshop_info'), $url = null) {
        if (!$url) {
            $url = $this->getConfigData('api_url');
        }
        $url .= $params['action'].'.php';
        $params['username'] = $this->getConfigData('sendpackage_username');
        $params['password'] = $this->getConfigData('sendpackage_password');
        
        $client = new Zend_Http_Client($url);
        $options = array(
            'timeout' => $this->getConfigData('http_request_timeout')>10?$this->getConfigData('http_request_timeout'):10,
        );
        $client->setConfig($options);
        $client->setParameterPost($params);
        $resp = $client->request(Zend_Http_Client::POST);
        //Mage::log("_getRequest params:".print_r($params, true), null, 'dpdlog.log');
        //Mage::log("_getRequest resp2:".print_r($resp->getBody(), true), null, 'dpdlog.log');
        $decodeResult = @json_decode($resp->getBody(), true);
        if (is_array($decodeResult) &  $params['action'] == 'pickup_info') {
            return $decodeResult;
        }
        if ($params['action'] == 'parcel_print' || $params['action'] == 'parcel_manifest_print') {
            //Mage::log("_getRequest params:".print_r($resp, true), null, 'dpdlog.log');
            return $resp;
        }
        if ($params['action'] == 'dpdis/pickupOrdersSave') {
            $decodeResult2 = $resp->getBody();
            if(strcmp(substr($decodeResult2, 3, 4), "DONE") == 0){
                Mage::throwException($this->_getDpdHelper()->__('DPD kurjeris sėkmingai iškviestas.'));  
            }  
            else {
                Mage::throwException($this->_getDpdHelper()->__('DPD kurjerio iškviesti nepavyko, klaida: %s', $decodeResult2));
            }
        }
        if (!is_array($decodeResult) || !isset($decodeResult['errlog']) || $decodeResult['errlog'] !== '') {
            Mage::throwException($this->_getDpdHelper()->__('DPD request failed with response: %s', print_r($resp->getBody(), true)));
        }
        return $decodeResult;
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
    
    
}
