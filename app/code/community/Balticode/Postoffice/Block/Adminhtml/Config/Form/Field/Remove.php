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
 * <p>Renders button, which allows to delete old instance of Balticode_Postoffice from app/code/local folder in Magento admin &gt; System &gt; Configuration &gt; Shipping Methods &gt; DPD - dpd.lt configuration menu, if such old instance exists</p>
 *
 * @author Matis
 */
class Balticode_Postoffice_Block_Adminhtml_Config_Form_Field_Remove extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        $divId = $element->getId();
        $helper = Mage::helper('balticode_livehandler');
        $res = '';
        $dirName = Mage::getBaseDir('code') . '/local/Balticode/Postoffice';
        if (is_dir($dirName) && file_exists($dirName.'/etc/config.xml')) {
                $res .= <<<HTML
   <button class="scalable" id="{$divId}_button" type="button" onclick="{$divId}make_request(); return false;">{$this->_getOfficeHelper()->__('Delete instance of this module from %s folder', 'app/code/local')}</button>
                
HTML;
            $res .= <<<HTML
<script type="text/javascript">
//<![CDATA[

function {$divId}make_request(actionName) {
    var confirmR = confirm({$this->_toJson($helper->__('Most probably you have older version of this module in the system. Do you want to remove the instance of this module from %s folder?', $dirName))});
    
    if (confirmR) {
        new Ajax.Request(
            '{$this->getUrl('balticode_postoffice/adminhtml_postoffice/remove', array())}',
            {
                method: 'post',
                asynchronous: true,
                parameters: {"remove": "true"},
                onSuccess: function(transport) {
                        var json = transport.responseText.evalJSON(true);
                        if (json['status'] && json['status'] == 'success') {
                            alert({$this->_toJson($this->_getOfficeHelper()->__('Folder %s deleted!', $dirName))});
                            \$({$this->_toJson($divId . '_button')}).hide();
                        } else {
                            alert({$this->_toJson($this->_getOfficeHelper()->__('Folder %s delete failed!', $dirName))});
                        }
                },
                onFailure: function(transport) {
                    alert(transport.responseText);
                }
        });
    }

}
//]]>
</script>

HTML;
            
        }
        return $res;
    }
    
    private function _toJson($input) {
        return json_encode($input);
    }
    
    /**
     * 
     * @return Balticode_Postoffice_Helper_Data
     */
    protected function _getOfficeHelper() {
        return Mage::helper('balticode_postoffice');
    }
    
    

}

