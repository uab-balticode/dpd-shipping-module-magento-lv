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
 *
 * Contains the functions, which helps store administrator to place an order using this carrier or the subclasses of this carrier and
 * also contains actions related to printing packing slips and sending automatic parcel data to the third party carrier server.
 * @author matishalmann
 */
class Balticode_Postoffice_Adminhtml_PostofficeController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        return $this;
    }
    
    protected function _construct() {
        
    }
    
    /**
     *  Rebuilds the list of postoffices for the selected carrier immediately and returns json response.
     * 
     *  
     */
    public function rebuildAction() {
        $result = $this->_rebuild();
        echo json_encode($result);
        die();
    }
    
    protected function _rebuild() {
        $carrierCode = $this->getRequest()->getParam('carrier_code', '');
        if ($carrierCode == '') {
            return array('error' => Mage::helper('balticode_postoffice')->__('Invalid Carrier code'));
        }
        $carrierModule = Mage::getModel('balticode_postoffice/carriermodule')->load($carrierCode, 'carrier_code');
        if (!is_object($carrierModule) ||$carrierModule->getId() <= 0) {
            return array('error' => Mage::helper('balticode_postoffice')->__('Invalid Carrier code'));
        }
        try {
            $carrierModule->updateCarrierData(true);
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
        return array('success' => true);
        
    }
    
    /**
     *  Prints out packing slip pdf for the selected order as response or echoes that barcode is not available.
     * 
     * 
     * @return type 
     */
    public function addresscardpdfAction() {
        Mage::log("addresscardpdfAction PostofficeController", null, 'dpdlog.log');
        $orderId = (int)$this->getRequest()->getParam('order_id', 0);
        if ($orderId <= 0) {
            return;
        }
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order || $order->getId() <= 0) {
            return;
        }
        $incrementId[] = $order->getIncrementId();
        Mage::log("addresscardpdfAction incrementId:".print_r($incrementId, true), null, 'dpdlog.log');
        $res = Mage::helper('balticode_dpdlt')->getBarcodePdf2($incrementId);
        if ($res !== false) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="addresscard-' . $incrementId . '.pdf"');

            echo $res->getBody();
        } else {
            echo 'No barcode available';
        }
    }

    public function labelsAction() {
        $orderIds = $this->getRequest()->getPost('order_ids');
        if ($orderIds <= 0) {
            return;
        }
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if (!$order || $order->getId() <= 0) {
                return;
            }
            $incrementIds[] = $order->getIncrementId();
        }
        Mage::log("labelsAction incrementIds:".print_r($incrementIds, true), null, 'dpdlog.log');
        $res = Mage::helper('balticode_dpdlt')->getBarcodePdf2($incrementIds);
        if ($res !== false) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="labels-' . date("Y-m-d") . '.pdf"');

            echo $res->getBody();
        } else {
            echo 'No barcode available';
        }
    }

    public function manifestAction() {
        $orderIds = $this->getRequest()->getPost('order_ids');
        if ($orderIds <= 0) {
            Mage::log("addresscardpdfAction Manifest", null, 'dpdlog.log');
            $res = Mage::helper('balticode_dpdlt')->getManifest();
            if ($res !== false) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="manifest-' . date("Y-m-d") . '.pdf"');

                echo $res->getBody();
            } else {
                echo 'No manifest available';
            }
        }else{
            $res = Mage::helper('balticode_dpdlt')->getManifestPdf($orderIds);
            if ($res !== false) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="manifest-' . date("Y-m-d") . '.pdf"');

                echo $res->getBody();
            } else {
                echo 'No barcode available';
            }
        }
    }
    /**
     *  Attempts to automatically send the shipment data for the selected order to the third party carrier server
     * and returns the result as json response.
     * 
     * 
     *  
     */
    public function autosendAction() {
        $result = $this->_autoSend();
        echo json_encode($result);
        die();
    }
    
    protected function _autoSend() {
        //get the order id
        $orderId = (int)$this->getRequest()->getParam('order_id', 0);
        if ($orderId <= 0) {
            return array('error' => Mage::helper('balticode_postoffice')->__('Invalid Order ID'));
        }
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$order || $order->getId() <= 0) {
            return array('error' => Mage::helper('balticode_postoffice')->__('Invalid Order ID'));
        }
        
        //get the carrier
        $shippingMethod = $order->getShippingMethod();
        $paymentMethod = $order->getPayment();
        
        //get the shipping code from the order and call the module from it.
        $shippingCarrierCode = substr($shippingMethod, 0, strpos($shippingMethod, '_'));
        $shippingMethodModel = Mage::getModel('shipping/shipping')->getCarrierByCode($shippingCarrierCode);
        
        if (!($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)){
            return array('error' => Mage::helper('balticode_postoffice')->__('This carrier is not subclass of Balticode_Postoffice_Model_Carrier_Abstract'));
        }
        $shippingMethodModel->setStoreId($order->getStoreId());
        
        //determine if auto send is available
        if (!$shippingMethodModel->isAutoSendAvailable()) {
            return array('error' => Mage::helper('balticode_postoffice')->__('Automatic data sending is not available for the selected carrier'));
        }
        
        if (round($order->getTotalDue(), 2) > 0 && (!$shippingMethodModel->getConfigData('enable_cod') || 
                ($shippingMethodModel->getConfigData('enable_cod') && $paymentMethod->getMethod() != 'balticodecodpayment'))) {
            return array('error' => Mage::helper('balticode_postoffice')->__('This order has not yet been fully paid'));
        }
        
        if (($order->isCanceled() || $order->getIsVirtual())) {
            return array('error' => Mage::helper('balticode_postoffice')->__('This order cannot be shipped'));
        }
        
        
        //send the data
        Mage::helper('balticode_postoffice')->sendManualOrderData($order->getIncrementId(), $shippingMethodModel->getConfigData('senddata_event'));
        
        
        //return the results
        return array('success' => true);
        
    }
    
    /**
     *  <p>Returns the contents for the selected carriers.</p>
     * <p>Mainly it returns two select menus, where first select menu contains all the groups and
     * second select menu contains actual offices, which belong to the selected group</p>
     * 
     * 
     * 
     * 
     * @return html
     * @throws Exception 
     */
    public function officeAction() {
        try {
            if ($this->getRequest()->isPost()) {

                $post = $this->getRequest()->getPost();
                $storeId = (int)$this->getRequest()->getParam('store_id', 0);
                if ($storeId <= 0) {
                    throw new Exception('Store ID must be supplied');
                }
                $url = $this->getUrl('balticode_postoffice/adminhtml_postoffice/office', array('store_id' => $storeId, '_secure' => true));
                $addressId = $post['address_id'];
                $carrierCode = $post['carrier_code'];
                $carrierId = $post['carrier_id'];
                $divId = $post['div_id'];
                $groupId = isset($post['group_id']) ? ((int) $post['group_id']) : 0;
                $placeId = isset($post['place_id']) ? ((int) $post['place_id']) : 0;
                $shippingModel = Mage::getModel('shipping/shipping')->getCarrierByCode($carrierCode);
                
                //we are in admin section, so we need to set the store it manually
                $shippingModel->setStoreId($storeId);
                
                if (!$shippingModel->isAjaxInsertAllowed($addressId)) {
                    throw new Exception('Invalid Shipping method');
                }
                if (!($shippingModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
                    throw new Exception('Invalid Shipping model');
                }

                if ($placeId > 0) {
                    $place = $shippingModel->getTerminal($placeId);
                    if ($place) {
                        $shippingModel->setOfficeToSession($addressId, $place);
                        echo 'true';
                        return;
                    } else {
                        echo 'false';
                        return;
                    }
                }

                $groups = $shippingModel->getGroups($addressId);
                $html = '';

                if ($groups) {
                     $groupSelectWidth = (int)$shippingModel->getConfigData('group_width');
                    $style = '';
                    if ($groupSelectWidth > 0) {
                        $style = ' style="width:'.$groupSelectWidth.'px"';
                    }
                    $html .= '<select onclick="return false;" ' . $style . ' name="' . $carrierCode . '_select_group" onchange="new Ajax.Request(\'' . $url . '\',{method:\'post\',parameters:{carrier_id:\'' . $carrierId . '\',carrier_code:\'' . $carrierCode . '\',div_id:\'' . $divId . '\',address_id:\'' . $addressId . '\',group_id: $(this).getValue()},onSuccess:function(a){$(\'' . $divId . '\').update(a.responseText)}});">';
                    $html .= '<option value="">';
                    $html .= htmlspecialchars(Mage::helper('balticode_postoffice')->__('-- select --'));
                    $html .= '</option>';

                    foreach ($groups as $group) {
                        $html .= '<option value="' . $group->getGroupId() . '"';
                        if ($groupId > 0 && $groupId == $group->getGroupId()) {
                            $html .= ' selected="selected"';
                        }
                        $html .= '>';
                        $html .= $shippingModel->getGroupTitle($group);
                        $html .= '</option>';
                    }
                    $html .= '</select>';
                }

                //get the group values
                if ($groupId > 0 || $groups === false) {
                    $terminals = array();
                    if ($groups !== false) {
                        $terminals = $shippingModel->getTerminals($groupId, $addressId);
                    } else {
                        $terminals = $shippingModel->getTerminals(null, $addressId);
                    }
                    $officeSelectWidth = (int)$shippingModel->getConfigData('office_width');
                    $style = '';
                    if ($officeSelectWidth > 0) {
                        $style = ' style="width:'.$officeSelectWidth.'px"';
                    }
                    $html .= '<select onclick="return false;" '.$style.' name="' . $carrierCode . '_select_office"  onchange="var sel = $(this); new Ajax.Request(\'' . $url . '\',{method:\'post\',parameters:{carrier_id:\'' . $carrierId . '\',carrier_code:\'' . $carrierCode . '\',div_id:\'' . $divId . '\',address_id:\'' . $addressId . '\',place_id: sel.getValue()},onSuccess:function(a){  if (a.responseText == \'true\') { $(\'' . $carrierId . '\').writeAttribute(\'value\', \'' . $carrierCode . '_' . $carrierCode . '_\' + sel.getValue()); $(\'' . $carrierId . '\').click(); }}});">';
                    $html .= '<option value="">';
                    $html .= htmlspecialchars(Mage::helper('balticode_postoffice')->__('-- select --'));
                    $html .= '</option>';

                    $optionsHtml = '';
                    $previousGroup = false;
                    $optGroupHtml = '';
                    $groupCount = 0;

                    foreach ($terminals as $terminal) {
                        if ($shippingModel->getGroupTitle($terminal) != $previousGroup && !$shippingModel->getConfigData('disable_group_titles')) {
                            if ($previousGroup != false) {
                                $optionsHtml .= '</optgroup>';
                                $optionsHtml .= '<optgroup label="'.$shippingModel->getGroupTitle($terminal).'">';
                            } else {
                                $optGroupHtml .= '<optgroup label="'.$shippingModel->getGroupTitle($terminal).'">';
                            }
                            $groupCount++;
                        }
                        $optionsHtml .= '<option value="' . $terminal->getRemotePlaceId() . '"';
                        if (false) {
                            $optionsHtml .= ' selected="selected"';
                        }
                        $optionsHtml .= '>';
                        $optionsHtml .= $shippingModel->getTerminalTitle($terminal);
                        $optionsHtml .= '</option>';
                        
                        $previousGroup = $shippingModel->getGroupTitle($terminal);
                    }
                    if ($groupCount > 1) {
                        $html .= $optGroupHtml . $optionsHtml . '</optgroup>';
                    } else {
                        $html .= $optionsHtml;
                    }

                    $html .= '</select>';
                    
                    
                }


                echo $html;
            } else {
                throw new Exception('Invalid request method');
            }
        } catch (Exception $e) {
            $this->getResponse()->setHeader('HTTP/1.1', '500 Internal error');
            $this->getResponse()->setHeader('Status', '500 Internal error');
            throw $e;
        }
        return;
    }
    
    
    /**
     * <p>If older instance of Balticode_Postoffice exists, then this function attempts to remove it</p>
     * @return null
     */
    public function removeAction() {
        $result = array('status' => 'failed');
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('remove') == 'true') {
            $dirName = Mage::getBaseDir('code').'/local/Balticode/Postoffice';
            if (is_dir($dirName) && file_exists($dirName.'/etc/config.xml')) {
                $directory = new Varien_Io_File();
                $deleteResult = $directory->rmdir($dirName, true);
                if ($deleteResult) {
                    $result['status'] = 'success';
                }
            }
            
        }
        $this->getResponse()->setRawHeader('Content-type: application/json');
        $this->getResponse()->setBody(json_encode($result));
        return;
    }
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
    
    
}

