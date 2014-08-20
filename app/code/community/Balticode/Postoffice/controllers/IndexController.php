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
 * <p>Displays list of available parcel terminals for current carrier and address if carrier supports parcel terminals feature</p>
 *
 * @author matishalmann
 */
class Balticode_Postoffice_IndexController extends Mage_Core_Controller_Front_Action {
    

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
                $url = Mage::getUrl('balticode_postoffice/index/office', array('_secure' => true));

                $post = $this->getRequest()->getPost();
                $addressId = $post['address_id'];
                $carrierCode = $post['carrier_code'];
                $carrierId = $post['carrier_id'];
                $divId = $post['div_id'];
                $groupId = isset($post['group_id']) ? ((int) $post['group_id']) : 0;
                $placeId = isset($post['place_id']) ? ((int) $post['place_id']) : 0;
                $shippingModel = Mage::getModel('shipping/shipping')->getCarrierByCode($carrierCode);
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
                    $html .= '<select onclick="return false;" '.$style.' name="' . $carrierCode . '_select_group" onchange="new Ajax.Request(\'' . $url . '\',{method:\'post\',parameters:{carrier_id:\'' . $carrierId . '\',carrier_code:\'' . $carrierCode . '\',div_id:\'' . $divId . '\',address_id:\'' . $addressId . '\',group_id: $(this).getValue()},onSuccess:function(a){$(\'' . $divId . '\').update(a.responseText)}});">';
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

}

