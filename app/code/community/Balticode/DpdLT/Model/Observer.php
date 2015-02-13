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
 * <p>Holds all Mage::dispatchEvent() actions</p>
 *
 * @author Matis
 */
class Balticode_DpdLT_Model_Observer {
    
    
    /**
     * <p>Adds tracking numbers to orders created with DPD right after shipment is first time saved</p>
     * <p>If parcel data is not sent to server, then tracking numbers will not be created, even if data is sent to server after creation of shipment</p>
     * @param Varien_Event_Observer $observer
     */
    public function addTrackingToShipment($observer) {
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment
                && $this->_getDpdHelper()->isShippingMethodApplicable($shipment->getOrder())) {
            //add the tracks here.....
            $dataSavedToOrder = $this->_getOfficeHelper()->getDataFromOrder($shipment->getOrder(), Balticode_DpdLT_Model_Post::ORDER_COMMENT_START_PREFIX);
            if (isset($dataSavedToOrder['Parcel_numbers'])) {
                $this->_addTracksToShipment($shipment, $dataSavedToOrder['Parcel_numbers']);
            }
            
        }
    }

    
    /**
     * <p>Adds tracking numbers to shipment, when it is known that we are dealing with DPD order</p>
     * <p>If tracks with same carrier code as order shipping method carrier code have already been added, then this function does nothing</p>
     * @param Mage_Sales_Model_Order_Shipment $shipment shipment, to add the tracking numbers for
     * @param array $trackingNumbers array of DPD tracking numbers
     */
    protected function _addTracksToShipment(Mage_Sales_Model_Order_Shipment $shipment, array $trackingNumbers) {
        /* @var $shippingMethodInstance Balticode_Postoffice_Model_Carrier_Abstract */
        $shippingMethodInstance = $this->_getOfficeHelper()->getShippingMethodInstance($shipment->getOrder()->getIncrementId());
        $trackExists = false;
        if ($shippingMethodInstance) {
            $oldTracks = $shipment->getAllTracks();
            foreach ($oldTracks as $oldTrack) {
                if ($oldTrack->getCarrierCode() == $shippingMethodInstance->getCarrierCode()) {
                    $trackExists = true;
                }
            }
            if (!$trackExists) {
                foreach ($trackingNumbers as $trackingNumber) {
                    $track = $this->_getTrackingModel()
                            ->setNumber($trackingNumber)
                            ->setCarrierCode($shippingMethodInstance->getCarrierCode())
                            ->setTitle($shippingMethodInstance->getConfigData('title'))
                            ->setShipment($shipment);
                    $track->save();
                }
            }
        }
    }

    /**
     * 
     * @return Mage_Sales_Model_Order_Shipment_Track
     */
    protected function _getTrackingModel() {
        return Mage::getModel('sales/order_shipment_track');
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
    
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('Balticode_DpdLT_model_feed');
            /* @var $feedModel Mage_AdminNotification_Model_Feed */

            $feedModel->checkUpdate();
        }

    }
    
    
}
