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
 * <p>Source model of all possible DPD API service codes</p>
 *
 * @author Matis
 */
class Balticode_DpdLT_Model_Source_Service {
    
    public function toOptionArray() {
        $options = array();
        $options[] = array(
            'label' => $this->_getDpdHelper()->__('Pickup Order only'),
            'value' => 'PO',
        );
        $options[] = array(
            'label' => $this->_getDpdHelper()->__('Labels Only'),
            'value' => 'LO',
        );
        $options[] = array(
            'label' => $this->_getDpdHelper()->__('Full Order'),
            'value' => 'FO',
        );
        $options[] = array(
            'label' => $this->_getDpdHelper()->__('Collection Request'),
            'value' => 'CR',
        );

        return $options;
        
    }
    
    /**
     * 
     * @return Balticode_DpdLT_Helper_Data
     */
    protected function _getDpdHelper() {
        return Mage::helper('balticode_dpdlt');
    }
}

