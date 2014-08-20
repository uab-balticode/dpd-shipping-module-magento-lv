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

$installer->run("

");

//insert action button with name of 'balticode_dpdlt/action_carrier_order_courier'
/* @var $configBackend Balticode_Livehandler_Model_System_Config_Backend_Button */
$configBackend = Mage::getModel('balticode_livehandler/system_config_backend_button');
$configBackend->load('balticode_livehandler/admintools/buttons', 'path');

$foundValue = $configBackend->getValue();
if (!is_array($foundValue)) {
    $foundValue = array();
}



$foundValue['_' . time() . '_' . mt_rand(0, 999)] = array(
    'button_name' => 'balticode_dpdlt/action_carrier_order_courier',
    'sort_order' => "0",
    'disabled' => "0"
);
$configBackend->setValue($foundValue);

/* @var $helper Balticode_Livehandler_Helper_Data */
$helper = Mage::helper('balticode');
$helper->setConfigData('balticode_livehandler/admintools/buttons', serialize($foundValue), 'default', 0, true);
//$configBackend->save();
//end install

$installer->endSetup();
