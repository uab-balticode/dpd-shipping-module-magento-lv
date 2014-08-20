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
/*
 * <p>Structure description for balticode_carriermodule:</p>
 * <ul>
     <li><b>id</b> - unique auto increment id</li>
     <li><b>carrier_code</b> - Magento shipping method carrier code</li>
     <li><b>class_name</b> - full Magento model name</li>
     <li><b>update_time</b> - time when this carrier module was last updated</li>
 </ul>
 * <p>Structure description for balticode_postoffice</p>
 * <ul>
     <li><b>id</b> - unique auto incement id for entry</li>
     <li><b>remote_module_id</b> - refers to balticode_carriermodule.id</li>
     <li><b>remote_module_name</b> - Magento carrier code, also refers to balticode_carriermodule.carrier_code</li>
     <li><b>remote_place_id</b> - Parcel terminal numeric ID, which is provided by remote server.</li>
     <li><b>remote_servicing_place_id</b> - not used</li>
     <li><b>name</b> - human readable remote parcel terminal short name</li>
     <li><b>city</b> - city where remote parcel terminal is located (optional)</li>
     <li><b>county</b> - region or county where remote parcel terminal is located (optional)</li>
     <li><b>zip_code</b> - zip code for the remote parcel terminal (optional)</li>
     <li><b>country</b> - ISO-3166 country code for the remote parcel terminal</li>
     <li><b>description</b> - Extra human readable information can be entered here like address, opening times, etc...</li>
     <li><b>group_id</b> - unique id that represents city/county combination group. Module auto generates this id for you.</li>
     <li><b>group_name</b> - merged city/county if any of those is set. Otherwise left empty</li>
     <li><b>group_sort</b> - higher the number, the more important group is and thus parcel terminal belonging to more important group is displayed before the others.</li>
     <li><b>local_carrier_id</b> - not used</li>
     <li><b>created_time</b> - time, when this parcel terminal entry was created</li>
     <li><b>update_time</b> - time, when this parcel terminal was last updated</li>
     <li><b>cached_attributes</b> - not used, but can store data in serialized form</li>
 </ul>
 */
$installer->run("
    DROP TABLE IF EXISTS {$this->getTable('balticode_postoffice')};
    DROP TABLE IF EXISTS {$this->getTable('balticode_carriermodule')};
    
    CREATE TABLE {$this->getTable('balticode_postoffice')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `remote_module_id` int(11) unsigned NOT NULL,
        `remote_module_name` varchar(255) NOT NULL,
        `remote_place_id` int(11) unsigned NOT NULL,
        `remote_servicing_place_id` int(11) unsigned NULL,

        `name` varchar(255) NOT NULL,
        `city` varchar(255) NULL,
        `county` varchar(255) NULL,
        `zip_code` varchar(255) NULL,
        `country` varchar(2) NULL,
        `description` text NULL,

        `group_id` int(11) unsigned NULL,
        `group_name` varchar(255) NULL,
        `group_sort` int(11) unsigned NULL,

        `local_carrier_id` int(11) unsigned NULL,
    
        `created_time` datetime NULL,
        `update_time` datetime NULL,
        `cached_attributes` text NULL,
    
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ALTER TABLE {$this->getTable('balticode_postoffice')} ADD UNIQUE (
		`remote_module_id`,
		`remote_place_id`
	);
	
    CREATE TABLE {$this->getTable('balticode_carriermodule')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `carrier_code` varchar(255) NOT NULL,
        `class_name` varchar(255) NOT NULL,
        `update_time` datetime NULL,
    
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    

");


$installer->endSetup();
