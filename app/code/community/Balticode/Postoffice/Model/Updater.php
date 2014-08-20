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
 * <p>Updates list of available parcel terminal in all carriers that are declared in balticode_carriermodule database table</p>
 * @author matishalmann
 */
class Balticode_Postoffice_Model_Updater extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('balticode_postoffice/updater');
        
    }
    /**
     *<p>Updates list of postoffices for all of the carriers.<p>
     * <p>If $forceUpdate param is not supplied, then the carrier will not be updated, if last_update + update_interval has not yet been passed.</p>
     * 
     * 
     * @param bool $forceUpdate when set to true, then update is performed anyway.
     */
    public function updateCarriers($forceUpdate = false) {
        $carriers = Mage::getModel('balticode_postoffice/carriermodule')->getCollection();
        
        foreach ($carriers as $carrier) {
            $carrier->updateCarrierData($forceUpdate);
        }
        
    }
    
}


