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
 * <p>Carrier module represents unlimited amount of parcel terminals which can be placed in many countries.</p>
 * <p>Represents one entry from balticode_carriermodule database table.</p>
 * <p>Structure description:</p>
 * <ul>
     <li><b>id</b> - unique auto increment id</li>
     <li><b>carrier_code</b> - Magento shipping method carrier code</li>
     <li><b>class_name</b> - full Magento model name</li>
     <li><b>update_time</b> - time when this carrier module was last updated</li>
 </ul>
 * @author matishalmann
 */
class Balticode_Postoffice_Model_Carriermodule  extends Mage_Core_Model_Abstract {

    /**
     *
     * @var array
     */
    private $_groups = array();


    public function _construct() {
        parent::_construct();
        $this->_init('balticode_postoffice/carriermodule');
        
    }
    
    /**
     * <p>Attempts to synchronize list of parcel terminals with remote server if update time was earlier than update interval for the current carrier.</p>
     * @param bool $byPassTimeCheck when set to true, then data is updated anyway
     * @return null
     * @throws Exception
     */
    public function updateCarrierData($byPassTimeCheck) {
        $className = $this->getData('class_name');
        $date = new Zend_Date(time(), Zend_Date::TIMESTAMP);
        if (!$className || $className == '' || $this->getId() <= 0) {
            throw new Exception('Cannot update Carrier data for empty CarrierModule');
        }
        //load the carrier
        $shippingMethodModel = Mage::getModel($this->getData('class_name'));
        
        if (!($shippingMethodModel instanceof Balticode_Postoffice_Model_Carrier_Abstract)) {
            throw new Exception('This method can only update instances of Balticode_Postoffice_Model_Office carriers');
        }
        
        
        $lastUpdated = $shippingMethodModel->getConfigData('last_updated');
        $updateInterval = $shippingMethodModel->getConfigData('update_interval');
        
        if ($lastUpdated + ($updateInterval * 60) < $date->get(Zend_Date::TIMESTAMP) || $byPassTimeCheck) {
            $oldData = array();
        
            //load the old data
            $oldDataCollection = Mage::getModel('balticode_postoffice/office')->getCollection()
                    ->addFieldToFilter('remote_module_id', $this->getId())
                    ;
            
            foreach ($oldDataCollection as $oldDataElement) {
                $oldData[(string)$oldDataElement->getRemotePlaceId()] = $oldDataElement;
                
                if ($oldDataElement->getGroupName() != '' && $oldDataElement->getGroupId() > 0) {
                    $this->_groups[(string)$oldDataElement->getGroupId()] = $oldDataElement->getGroupName();
                }
                
            }
            //load the new data
            $newData = $shippingMethodModel->getOfficeList();
            
            Mage::log("updateCarrierData oldData:".print_r($oldData, true), null, 'dpdlog.log');
            Mage::log("updateCarrierData newData:".print_r($newData, true), null, 'dpdlog.log');

            if (!is_array($newData)) {
                //we had a failure loading new models
                //update the last updated interval and return
                $shippingMethodModel->setConfigData('last_updated', $date->get(Zend_Date::TIMESTAMP));
                return;
            }
            $processedPlaceIds = array();
            foreach ($newData as $newDataElement) {
                
                if (!isset($newDataElement['group_id']) || !isset($newDataElement['group_name'])
                     ||   $newDataElement['group_id'] == '' || $newDataElement['group_name'] == '') {
                    $this->assignGroup($newDataElement);
                    
                }
                if (!isset($newDataElement['group_sort'])) {
                    $newDataElement['group_sort'] = $shippingMethodModel->getGroupSort($newDataElement['group_name']);
                }
                
                if (!isset($oldData[(string)$newDataElement['place_id']])) {
                    //add to the list
                    $oldData[(string)$newDataElement['place_id']] = Mage::getModel('balticode_postoffice/office')
                            ->fromOfficeElement($newDataElement, $this->getId());
                } else {
                    //update the olddata element
                    $oldData[(string)$newDataElement['place_id']] = $oldData[(string)$newDataElement['place_id']]->fromOfficeElement($newDataElement);
                }
                $processedPlaceIds[(string)$newDataElement['place_id']] = (string)$newDataElement['place_id'];
                
            }
            
            //delete the removed elements
            foreach ($oldData as $placeId => $oldDataElement) {
                if (!isset($processedPlaceIds[(string)$placeId])) {
                    //delete the element
                    $oldDataElement->delete();
                    
                } else {
                    //save the element
                    $oldDataElement->save();
                    
                }
            }
            
            //save the config data too
            
            $shippingMethodModel->setConfigData('last_updated', $date->get(Zend_Date::TIMESTAMP));
            
            //all done!
            
        } 
        
    }
    
    /**
     * <p>Keeps track of generated group_id-s based on the group_names, making sure that each group has it's unique id.</p>
     * @param array $dataElement
     */
    protected function assignGroup(array &$dataElement) {
        $groupNames = array();
        if (isset($dataElement['county']) && !empty($dataElement['county'])) {
            $groupNames[] = $dataElement['county'];
        }
        if (isset($dataElement['city']) && !empty($dataElement['city'])) {
            $groupNames[] = $dataElement['city'];
        }
        if (count($groupNames) > 0) {
            $groupName = implode('/', $groupNames);
            if (in_array($groupName, $this->_groups)) {
                $dataElement['group_name'] = $groupName;
                $dataElement['group_id'] = array_search($groupName, $this->_groups);
            } else {
                $new_id = 1;
                if (count($this->_groups) > 0) {
                    $new_id = max(array_keys($this->_groups)) + 1;
                    
                }
                $this->_groups[(string)$new_id] = $groupName;
                $dataElement['group_name'] = $groupName;
                $dataElement['group_id'] = array_search($groupName, $this->_groups);
            }
        }
    }

    /**
     * <p>Assigns created time and update time before saving.</p>
     * @return Balticode_Postoffice_Model_Carriermodule
     */
    protected function _beforeSave() {
        if ($this->isObjectNew()) {
            $this->setCreatedTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
            $this->setUpdateTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
            
        } else {
            $this->setUpdateTime(self::$_date->get('yyyy-MM-dd HH:mm:ss'));
        }
        return parent::_beforeSave();
    }

    /**
     * <p>Initiates date object</p>
     */
    public static function balticode_init() {
        self::$_date = new Zend_Date(time(), Zend_Date::TIMESTAMP);
    }
    
    protected static $_date;

}
Balticode_Postoffice_Model_Carriermodule::balticode_init();
