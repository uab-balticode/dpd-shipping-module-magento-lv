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

$installer = $this;

$installer->startSetup();



//remove old action button from order info view with name of 'balticode_dpdlt/action_carrier_order_courier'
/* @var $configBackend Balticode_Livehandler_Model_System_Config_Backend_Button */
$configBackend = Mage::getModel('balticode_livehandler/system_config_backend_button');
$configBackend->load('balticode_livehandler/admintools/buttons', 'path');

$foundValue = $configBackend->getValue();
if (!is_array($foundValue)) {
    $foundValue = array();
}

$keyToUnset = false;
foreach ($foundValue as $key => $value) {
    if ($value['button_name'] == 'balticode_dpdlt/action_carrier_order_courier') {
        $keyToUnset = $key;
    }
}

if ($keyToUnset) {
    unset($foundValue[$keyToUnset]);
    
    $configBackend->setValue($foundValue);
    /* @var $helper Balticode_Livehandler_Helper_Data */
    $helper = Mage::helper('balticode');
    $helper->setConfigData('balticode_livehandler/admintools/buttons', serialize($foundValue), 'default', 0, true);
}



//add same button to regular sales order grid view
$installer->run("
    
    INSERT INTO {$this->getTable('balticode_livehandler')} 
    (name, is_enabled, is_admin, request_var, model_class, store_id, website_id, created_time, update_time)
    VALUES
    ('Adminhtml DPD courier order button', 1, 1, 'adminhtml/sales_order/index', 'balticode_dpdlt/button_courier', 0, 0, NOW(), NOW());
    

");


//end install
$installer->endSetup();
