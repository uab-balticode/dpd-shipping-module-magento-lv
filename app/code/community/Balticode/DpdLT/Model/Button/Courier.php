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
 */
class Balticode_DpdLT_Model_Button_Courier extends Balticode_Livehandler_Model_Adminhtml_Gridmanager {
    protected $_id = 'balticode_dpdlt__button_courier';
    protected $_idm = 'balticode_dpdlt__button_manifest';
    protected $_shippingMethodCode;
    
    public function _construct() {
        parent::_construct();
        $this->_init('balticode_dpdlt/button_courier');
        
        //check if auto send is enabled and courier call is enabled
        $this->_shippingMethodCode = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_PARCEL_TERMINAL;
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($this->_shippingMethodCode);
        if (!$shippingMethodModel || !$shippingMethodModel->getConfigData('active')) {
            $this->_shippingMethodCode = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_FLAT;
            $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($this->_shippingMethodCode);
        }
        Mage::log("_construct shippingMethodModel:".print_r($shippingMethodModel, true), null, 'dpdlog.log');
        if ($shippingMethodModel && ($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract) 
                && $shippingMethodModel->getConfigData('active')
                && $shippingMethodModel->getConfigData('senddata_enable')
                && $shippingMethodModel->getConfigData('courier_enable')
                ) {
            $this->addActionButton($this->_id, $this->_getDpdHelper()->__('Order courier to pick up goods'), 'return false;');
            $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/manifest');
            //$this->addActionButton('balticode_print_manifest', Mage::helper('balticode_postoffice')->__('Print Manifest'), "setLocation('".$url."')");
            
        }
        
    }
    
    
    /**
     * 
     * @param string $currentJs
     * @return string
     */
    protected function _getAdditionalJs($currentJs) {
        if (!count($this->_actionButtons)) {
            return '';
        }
        $js = <<<JS
                var balticode_dpdJsObject = new BalticodeDpdLT('{$this->_id}', null, function(infoBox) {
                    new Ajax.Request(action_url, {
                        method: 'post',
                        parameters: {},
                        asynchronous: false,
                        evalJSON: 'force',
                        onSuccess: function(transport){
                            var ul = new Element('ul', {'class': 'messages'});
                            if (transport.responseJSON.errors) {
                                transport.responseJSON.errors.each(function(message) {
                                    ul.insert({bottom: '<li class="success-msg">' + message + '</li>'});
                                });
                            }
                            if (transport.responseJSON.messages) {
                                transport.responseJSON.messages.each(function(message) {
                                    ul.insert({bottom: '<li class="success-msg">' + message + '</li>'});
                                });
                            }
                            if (transport.responseJSON.errors || transport.responseJSON.messages) {
                                infoBox.update(ul);
                            }
                            if (transport.responseJSON.html) {
                                infoBox.update(transport.responseJSON.html);
                            }
                        },
                        onFailure: function(transport){
                            alert('Request failed, check your error logs');
                        }
                    });
                    
                }, null, function(endResult) { if (endResult['Po_parcel_qty'] == '0' & endResult['Po_pallet_qty'] == '0' & endResult['Po_remark'] == '') { return false; } return endResult; });
                $('{$this->_id}').observe('click', function(event) {
                    var submitResult;
                    balticode_dpdJsObject.update('');
                    
                    submitResult = balticode_dpdJsObject.submit(action_url, function(json, infoBox) {
                        var ul = new Element('ul', {'class': 'messages'});
                        if (json.errors || json.messages) {
                            if (json.errors) {
                                json.errors.each(function(message) {
                                    ul.insert({bottom: '<li class="success-msg">' + message + '</li>'});
                                });
                            }
                            if (json.messages) {
                                json.messages.each(function(message) {
                                    ul.insert({bottom: '<li class="success-msg">' + message + '</li>'});
                                });
                            }
                            
                            infoBox.update(ul);
                    
                        } else {
                            if (json.html) {
                                balticode_dpdJsObject.update(json.html, 'dummy', null);
                            }
                            
                        }
                    
                    
                    });
                
                    
                    
                });
                
JS;
        return $js;
    }
    
    
    /**
     * <p>Sends DPD courier call request from press of Call Courier Button to server and returns the result in following format:</p>
     * <pre>
            $result = array(
                'messages' => array of successmessages (optional),
                'errors' => array of error messages (optional),
                'html' => html to be replaced in Courier call infobox,
                'needs_reload' => false (not used),
                'is_action_error' => false (not used),
            );
     * 
     * </pre>
     * <p></p>
     * @param array $params posted Parameters from request
     * @return boolean|array
     */
    public function service($params) {
        if (!count($this->_actionButtons)) {
            //if no button available, do nothing when request is received
            return array();
        }
        try {
            $api = $this->_getDpdHelper()->getApi(Mage_Core_Model_App::ADMIN_STORE_ID);
            //TODO: time check
            //
            $isCourierComing = $api->isCourierComing();
            if (isset($params['Po_parcel_qty']) && !$isCourierComing) {
                //send the data
               
                $orderSendData = array(
                    'nonStandard' => isset($params['Po_remark']) ? $params['Po_remark'] : '',
                    'parcelsCount' => $params['Po_parcel_qty'],
                    'palletsCount' => $params['Po_pallet_qty'],
                );

                $orderSendResult = $api->callCurier($orderSendData);

                $courierArrivalDate = $orderSendData['Po_Date'];
                $courierArrivalTime = $orderSendData['Po_Time_from'] . ' ' . $this->_getDpdHelper()->__('and') . ' ' . $orderSendData['Po_Time_til'];
                
                
                //set the config data for courier pickup time
                $pickupTimeFrom = new Zend_Date(0, Zend_Date::TIMESTAMP);
                $pickupTimeFrom->setTimezone('Europe/Tallinn');
                $pickupTimeFrom->set($orderSendData['Po_Date'], 'yyyy-MM-dd');
                
                //we need this shitty way, because setting time directly with one format definition results in wrong timestamp                
                if (strlen($orderSendData['Po_Time_from']) > 2) {
                    $pickupTimeFrom->add(substr($orderSendData['Po_Time_from'], strlen($orderSendData['Po_Time_from']) - 2), Zend_Date::MINUTE);
                } else {
                    $pickupTimeFrom->add($orderSendData['Po_Time_from'], 'H');
                }
                
                // . '-' . $this->_getTimeFrom($params['Po_Time'], false)
                $pickupTimeTill = new Zend_Date(0, Zend_Date::TIMESTAMP);
                $pickupTimeTill->setTimezone('Europe/Tallinn');
                $pickupTimeTill->set($orderSendData['Po_Date'] , 'yyyy-MM-dd');
                
                if (strlen($orderSendData['Po_Time_til']) > 2) {
                    $pickupTimeTill->add(substr($orderSendData['Po_Time_til'], strlen($orderSendData['Po_Time_til']) - 2), Zend_Date::MINUTE);
                } else {
                    $pickupTimeTill->add($orderSendData['Po_Time_til'], 'H');
                }
                
                

                $configTimeStamp = $pickupTimeFrom->get(Zend_Date::TIMESTAMP) . ',' . $pickupTimeTill->get(Zend_Date::TIMESTAMP);
                $this->_getBalticode()->setConfigData('carriers/balticodedpdlt/courier_pickup_time', $configTimeStamp, 'default', 0, true);
            } else {
                if (!$isCourierComing) {
                    $dpdBlock = Mage::getSingleton('core/layout')
                            ->createBlock('balticode_dpdlt/order_courier')
                            ->setCode(Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_PARCEL_TERMINAL)
                            ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
                    ;

                    $result = array(
                        'needs_reload' => true,
                        'is_action_error' => false,
                        'html' => $dpdBlock->toHtml(),
                    );
                    return $result;
                } else {
                    $dateFormatIso = Mage::app()->getLocale()
                            ->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

                    $pickupTimeFrom = new Zend_Date($isCourierComing[0], Zend_Date::TIMESTAMP);
                    $pickupTimeTill = new Zend_Date($isCourierComing[1], Zend_Date::TIMESTAMP);
                    $pickupTimeFrom->setTimezone('Europe/Tallinn');
                    $pickupTimeTill->setTimezone('Europe/Tallinn');
                    $courierArrivalDate = $pickupTimeFrom->get($dateFormatIso);
                    $courierArrivalTime = $pickupTimeFrom->get(Zend_Date::HOUR_SHORT) . ' ' . $this->_getDpdHelper()->__('and') . ' ' . $pickupTimeTill->get(Zend_Date::HOUR_SHORT);
                }
            }
        } catch (Mage_Core_Exception $e) {
            $result = array(
                'errors' => array($e->getMessage()),
                'needs_reload' => false,
                'is_action_error' => false,
            );
            return $result;
        } catch (Exception $e) {
            $result = array(
                'errors' => array($this->_getDpdHelper()->__('Cannot call courier.').$e->__toString()),
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
     * <p>Gets Time from from timefrom-timetill construct</p>
     * @param string $input
     * @param bool $removeMinutesIfZero when true 900 will be converted to 9
     * @return string
     */
    protected function _getTimeFrom($input, $removeMinutesIfZero = true) {
        $parts = explode('-', $input);
        if ($removeMinutesIfZero) {
            return str_replace('00', '', $parts[0]);
        } else {
            return $parts[0];
        }
    }
    
    /**
     * <p>Gets Time till from timefrom-timetill construct</p>
     * @param string $input
     * @param bool $removeMinutesIfZero when true 900 will be converted to 9
     * @return string
     */
    protected function _getTimeTil($input, $removeMinutesIfZero = true) {
        $parts = explode('-', $input);
        if ($removeMinutesIfZero) {
            return str_replace('00', '', $parts[1]);
        } else {
            return $parts[1];
        }
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
     * <p>Wrapper json_encode in order to make it easier to use in heredoc syntax</p>
     * @param mixed $input
     * @return string
     */
    protected function _toJson($input) {
        return json_encode($input);
    }
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
    
    
    
}
