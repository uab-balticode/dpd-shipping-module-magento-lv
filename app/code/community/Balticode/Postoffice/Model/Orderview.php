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
 * <p>Creates the extra buttons in the sales/order/view in admin.</p>
 * <p>Added buttons are:
 * <ul>
 * <li>Send parcel data to server - if automatic data sending is available for the selected order and data has not been already sent</li>
 * <li>Print packing slip - If automatic data has been sent and packing slip print functionality is available.</li>
 * </ul>
 * </p>
 * 
 *
 * @author matishalmann
 */
class Balticode_Postoffice_Model_Orderview extends Balticode_Livehandler_Model_Adminhtml_Gridmanager {

    private $_incrementId;
    private $_orderId;

    public function _construct() {
        parent::_construct();
        $this->_init('balticode_postoffice/orderview');
        $orderId = Mage::app()->getRequest()->getParam('order_id');

        $order = Mage::getModel('sales/order')->load($orderId);
        if (is_object($order) && $order->getId() > 0) {
            $this->_incrementId = $order->getIncrementId();
            $this->_orderId = $order->getId();
            $barcode = Mage::helper('balticode_postoffice')->getBarcode($order->getIncrementId());
            if (is_string($barcode)) {
                $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/addresscardpdf', array('order_id'=> $orderId));
                
                $this->addActionButton('balticode_get_addresscard', Mage::helper('balticode_postoffice')->__('Print packing slip'), "setLocation('".$url."')");
            }
            
            if (Mage::helper('balticode_postoffice')->isDataSent($order->getIncrementId()) === false) {
            $confirmText = str_replace('\"', '"', addslashes(Mage::helper('balticode_postoffice')->__('Send shipping data to server') . '?'));
            $dataSendTextSuccess = str_replace('\"', '"', addslashes(Mage::helper('balticode_postoffice')->__('Data sent to server, please verify the status from the order comments')));
            $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/autosend', array('order_id' => $this->_orderId));
$js = <<<EOT
if(confirm("{$confirmText}")){new Ajax.Request("{$url}",{method:"get",onSuccess:function(a){json=a.responseText.evalJSON(true);if(json){if(json["error"]){alert(json["error"])}else if(json["success"]){alert("{$dataSendTextSuccess}");location.reload(true)}}else{alert("Fatal error")}},onFailure:function(){alert("Fatal error")}})}; return false;
EOT;
                
                $this->addActionButton('balticode_send_data_to_server', Mage::helper('balticode_postoffice')->__('Send shipping data to server'), $js);
            }
            
        }
        
        
    }
    
    protected function _getAdditionalJs($currentJs) {
        if ($currentJs != '' && $this->_incrementId != '') {
            $confirmText = str_replace('\"', '"', addslashes(Mage::helper('balticode_postoffice')->__('Send shipping data to server') . '?'));
            $dataSendTextSuccess = str_replace('\"', '"', addslashes(Mage::helper('balticode_postoffice')->__('Data sent to server, please verify the status from the order comments')));
            $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/autosend', array('order_id' => $this->_orderId));
            return '';


            return <<<EOT
            
function balticode_autosend_data() {
    var balticode_confirmR = confirm('{$confirmText}');
    if (confirm('{$confirmText}')) {
        new Ajax.Request('{$url}', {
            method: 'get',
            onSuccess: function(transport) {
                json = transport.responseText.evalJSON(true);
                if (json) {
                    if (json['error']) {
                        alert(json['error']);
                    } else if (json['success']) {
                        alert('{$datasendTextSuccess}');
                        location.reload(true);
                    }
                } else {
                    alert('Fatal error');
                }
            },
            onFailure: function() {
               alert('Fatal error');
            }
        });
    }
}
EOT;
        }
        return '';
    }
    
    
    public function service($postData) {
        return array();
    }
    
}


