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
 * @package    Balticode_Livehandler
 * @copyright  Copyright (c) 2013 Aktsiamaailm LLC (http://en.balticode.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt  GNU Public License V3.0
 * @author     Matis Halmann
 * 
 
 */

$installer = $this;

$installer->startSetup();
/*
 * Insert Magento Admin Sales Order Grid manager
 * Insertion to request_var=adminhtml/sales_order/view must be done because clicking on the order status label causes user to leave the page, thus disabling current grid manager when this entry is not present.
 */
$installer->run("
    
    DELETE FROM {$this->getTable('balticode_livehandler')} WHERE model_class = 'balticode_admintools/ordergrid';
    
    INSERT INTO {$this->getTable('balticode_livehandler')} 
    (name, is_enabled, is_admin, request_var, model_class, store_id, website_id, created_time, update_time)
    VALUES
    ('Adminhtml Sales Order Grid show products', 1, 1, 'adminhtml/sales_order/index', 'balticode_livehandler/ordergrid', 0, 0, NOW(), NOW());
    
    INSERT INTO {$this->getTable('balticode_livehandler')} 
    (name, is_enabled, is_admin, request_var, model_class, store_id, website_id, created_time, update_time)
    VALUES
    ('Adminhtml Sales Order Grid show products', 1, 1, 'adminhtml/sales_order/view', 'balticode_livehandler/ordergrid', 0, 0, NOW(), NOW());
    

");


$installer->endSetup();
