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
 * <p>Represents one entry from balticode_postoffice table.</p>
 * <p>Represents one parcel terminal, which can be selected as preferred shipping location for end user.</p>
 * <p>Structure:</p>
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
 * @author matishalmann
 */
class Balticode_Postoffice_Model_Office  extends Mage_Core_Model_Abstract {
    //put your code here
    
    public function _construct() {
        parent::_construct();
        $this->_init('balticode_postoffice/office');
        
    }

    public function fromOfficeElement(array $officeElement, $remoteModuleId = false) {
        $remoteModuleId = (int)$remoteModuleId;
        if ($remoteModuleId > 0) {
            $remoteModule = Mage::getModel('balticode_postoffice/carriermodule')->load($remoteModuleId);
            if (!is_object($remoteModule) || $remoteModule->getId() <= 0) {
                throw new Exception('Carrier module could not be detected for this Office model');
            }
            $this->setData('remote_module_id', $remoteModule->getId());
            $this->setData('remote_module_name', $remoteModule->getCarrierCode());
        } else {
            if ($this->getData('remote_module_id') == '' || $this->getData('remote_module_name') == '' ) {
                throw new Exception('Remote module ID and Remote Module Name have to be defined');
            }
        }
        
        //start setting the data
        //mandatory
        $this->setData('remote_place_id', $officeElement['place_id']);
        $this->setData('name', $officeElement['name']);

        if (isset($officeElement['servicing_place_id'])) {
            $this->setData('remote_servicing_place_id', $officeElement['servicing_place_id']);
        }
        if (isset($officeElement['city'])) {
            $this->setData('city', $officeElement['city']);
        }
        if (isset($officeElement['county'])) {
            $this->setData('county', $officeElement['county']);
        }
        if (isset($officeElement['zip'])) {
            $this->setData('zip_code', $officeElement['zip']);
        }
        if (isset($officeElement['country'])) {
            $this->setData('country', $officeElement['country']);
        }
        if (isset($officeElement['description'])) {
            $this->setData('description', $officeElement['description']);
        }
        if (isset($officeElement['group_id']) && isset($officeElement['group_name'])) {
            $this->setData('group_id', $officeElement['group_id']);
            $this->setData('group_name', $officeElement['group_name']);
            if (isset($officeElement['group_sort'])) {
                $this->setData('group_sort', $officeElement['group_sort']);
            }
        }
        
        if (isset($officeElement['extra']) && is_array($officeElement['extra'])) {
            $this->setData('cached_attributes', serialize($officeElement['extra']));
        }


        return $this;
        
    }
    
    public function loadByCodeAndRemoteId($code, $remoteId) {
        return $this->getCollection()
                ->addFieldToFilter('remote_module_name', $code)
                ->addFieldToFilter('remote_place_id', $remoteId)
                ->getFirstItem();
    }
    
    
    protected function _beforeSave() {
        if (method_exists($this, 'isObjectNew') && $this->isObjectNew()) {
            $this->setCreatedTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
            $this->setUpdateTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
            
        } else {
            $this->setUpdateTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
        }
        if ($this->getCreatedTime() == '') {
            $this->setCreatedTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
        }
        return parent::_beforeSave();
    }
    
    public static function balticode_init() {
        self::$_date = new Zend_Date(time(), Zend_Date::TIMESTAMP);
    }
    
    protected static $_date;

}
Balticode_Postoffice_Model_Office::balticode_init();
