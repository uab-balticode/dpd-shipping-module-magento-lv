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
 * <p>Renders HTML block which allows to call DPD courier to pick up shipment.</p>
 * <p>Template is located at <b>balticode_dpdlt/order/courier.phtml</b> in adminhtml theme folder</p>
 * <p>Information that can be sent to courier:</p>
 * <ul>
     <li>Merchant can pick suitable date from available list when courier should come</li>
     <li>Merchant can pick suitable time-range from available list for the selected date</li>
     <li>Merchant can specify how many envelopes, parcels, pallets should be picked up</li>
     <li>Merchant can leave comment for the courier</li>
     <li>By default one checked order equals one parcel</li>
 </ul>
 *
 * @author Matis
 */
class Balticode_DpdLT_Block_Order_Courier extends Mage_Core_Block_Template {
    protected $_dateFormat = 'yyyy-MM-dd';
    protected $_timeFormat = 'HHmm';
    protected $_timeFormatNice = 'HH:mm';
    
    protected $_availableDates;
    protected $_apiResult;
    
    public function _construct() {
        $this->setTemplate('balticode_dpdlt/order/courier.phtml');
        parent::_construct();
    }
    
    public function getHtmlId() {
        return 'balticode_carrier_order_courier_box';
    }
    




    /**
     * <p>Returns shipping method code that this courier HTML block is used by.</p>
     * @return string
     */
    public function getCode() {
        if (!$this->getData('code')) {
            return 'balticodedpdlt';
        }
        return $this->getData('code');
    }
    
    /**
     * <p>Returns default envelope quantity</p>
     * @return string
     */
    public function getEnvelopeQty() {
        return '0';
    }
    
    
    /**
     * <p>Returns default parcel quantity</p>
     * @return string
     */
    public function getParcelQty() {
        return '0';
        
    }
    
    
    /**
     * <p>Returns default pallet quantity</p>
     * @return string
     */
    public function getPalletQty() {
        return '0';
        
    }
    
    /**
     * 
     * @return Balticode_DpdLT_Helper_Data
     */
    protected function _getDpdHelper() {
        return Mage::helper('balticode_dpdlt');
    }
    
    
}
