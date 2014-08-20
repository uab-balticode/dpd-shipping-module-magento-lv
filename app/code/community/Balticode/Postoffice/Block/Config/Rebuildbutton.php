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
 * <p>Renders the button under the Magento Configuration panel.</p>
 * <p>Purpose of this button is to invoke the procedure, which rebuilds the list of Postoffices/Parcel terminals
 * which are directly related to this carrier.</p>
 * <p>This button is only intended to use in the Magento -> System -> Configuration -> Shipping methods section
 * and the carrier, that is using this button in the configuration panel should extend Balticode_Postoffice_Model_Carrier_Abstract class.</p>
 *  
 * <p>In order to use this button in the system.xml configuration use the following example:</p>
 * <p><pre><code>
 *                         &lt;rebuild_all translate="label"&gt;
                            &lt;label&gt;Rebuild Postoffice List&lt;/label&gt;
                            &lt;frontend_type&gt;label&lt;/frontend_type&gt;
                            &lt;sort_order&gt;999&lt;/sort_order&gt;
                            &lt;frontend_model&gt;balticode_postoffice/config_rebuildbutton&lt;/frontend_model&gt;
                            &lt;show_in_default&gt;1&lt;/show_in_default&gt;
                            &lt;show_in_website&gt;1&lt;/show_in_website&gt;
                            &lt;show_in_store&gt;1&lt;/show_in_store&gt;
                            &lt;comment&gt;If post offices are not displayed correctly, then rebuilding the list may help&lt;/comment&gt;
                        &lt;/rebuild_all&gt;

 * </code>
 * </pre></p>
 * <p>Most important parts of this example are <b>frontend_model</b> declaration, which refers to this block. Also <b>frontend_type</b> should be label.</p>
 *
 * @author matishalmann
 */
class Balticode_Postoffice_Block_Config_Rebuildbutton extends Mage_Adminhtml_Block_System_Config_Form_Field {
    
    /**
     *  Pre-render the element HTML.
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     * @throws Exception 
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $html = '';
        $rebuildTextSuccess = Mage::helper('balticode_postoffice')->__('Rebuild successful');
        
        //get the carrier code
        $transformedNames = explode('/', str_replace(array('[', ']'), array('/', ''), $element->getName()));
        $carrierName = trim($transformedNames[1]);
        if ($carrierName == '') {
            throw new Exception('Invalid carrier name');
        }
        $url = Mage::helper('adminhtml')->getUrl('balticode_postoffice/adminhtml_postoffice/rebuild', array('carrier_code' => $carrierName));
        Mage::log("_getElementHtml url:".print_r($url, true), null, 'dpdlog.log');

        $rebuildText = Mage::helper('balticode_postoffice')->__('Rebuild postoffices for this carrier');
        $rebuildTextConfirm = addslashes(sprintf(Mage::helper('balticode_postoffice')->__('Rebuilding postoffices for the carrier %s takes a little while... Continue?'), $carrierName));
        
        $divId = $element->getId();
        
        $html .= <<<EOT
   <button class="scalable" type="button" onclick="{$divId}_balticode_office_rebuild(); return false;">
       <span>{$rebuildText}</span>
   </button>
<script type="text/javascript">
//<![CDATA[
function {$divId}_balticode_office_rebuild() {
    var confirmR = confirm("{$rebuildTextConfirm}");
    if (confirmR) {
        new Ajax.Request('{$url}', {
            method: 'get',
            onSuccess: function(transport) {
                json = transport.responseText.evalJSON(true);
                if (json) {
                    if (json['error']) {
                        alert(json['error']);
                    } else if (json['success']) {
                        alert('{$rebuildTextSuccess}');
                    }
                } else {
                    alert('Rebuild failed!');
                }
            }
        });
    }
}


//]]>    
</script>

EOT;
        
        return $html;
    }
}


