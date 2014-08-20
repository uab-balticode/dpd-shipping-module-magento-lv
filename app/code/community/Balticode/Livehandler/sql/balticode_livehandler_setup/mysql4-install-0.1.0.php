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

/**
 * <p>Structure:</p>
 * <ul>
     <li><b>id</b> - unique auto incement id</li>
     <li><b>name</b> - Human readable description</li>
     <li><b>is_enabled</b> - 1 means action is enabled, 0 means action is disabled</li>
     <li><b>is_admin</b> - 1 means action is admin only, 0 means action is public only</li>
     <li><b>request_var</b> - Magento request var name, which current action must match. Example: adminhtml/sales_order/index - Magentos Sales Order Grid</li>
     <li><b>store_id</b> - id of the store this action should run in</li>
     <li><b>website_id</b> - if of the website this action should run in</li>
     <li><b>model_class</b> - full Magento model name for the implementing class. Must be instance of Balticode_Livehandler_Model_Abstract</li>
     <li><b>created_time</b> - when was this database entry created</li>
     <li><b>update_time</b> - when was this database entry updated</li>
     <li><b>cached_attributes</b> - not in use, could be used to store data in serialized form</li>
     <li><b>parameters</b> - not in use, could be used to store data in serialized form</li>
     <li><b>css</b> - used to declare extra css rules, those can also be declared by implementing class. Will be injected to before_body_end as style tag.</li>
     <li><b>js</b> - used to display extra javascript, those can be also be declared by implementing class. Will be injetected before_body_end in script[type=javascript] tag</li>
     <li><b>html</b> - used to display extra HTML, those can be also be declared by implementing class. Will be injetected before_body_end</li>
 </ul>
 * 
 */
$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('balticode_livehandler')};
    
    CREATE TABLE {$this->getTable('balticode_livehandler')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `name` varchar(255) NOT NULL,
        `is_enabled` tinyint(1) unsigned NOT NULL default 0,
        `is_admin` tinyint(1) unsigned NOT NULL default 0,
        `request_var` varchar(255) NOT NULL,
        `store_id` int(11) unsigned NOT NULL default 0,
        `website_id` int(11) unsigned NOT NULL default 0,
        `model_class` varchar(255) NULL,
        `created_time` datetime NULL,
        `update_time` datetime NULL,
        `cached_attributes` text NULL,
        `parameters` text NULL,
        `css` text NULL,
        `js` text NULL,
        `html` text NULL,
    
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");


$installer->endSetup();
