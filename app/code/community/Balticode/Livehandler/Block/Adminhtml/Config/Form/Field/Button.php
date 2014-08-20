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

/**
 * <p>Renders list of available action buttons in Magento admin &gt; System &gt; Configuration &gt; Balticode Livehandler &gt; Admin Order Grid Helper configuration menu</p>
 * <p>Each button refers to subclass of <b>Balticode_Livehandler_Model_Action_Abstract</b> and contains following:</p>
 * <ul>
     <li><b>button_name</b> - name of the button relative to balticode_livehandler/action_&lt;button-name&gt; or full magento model name</li>
     <li><b>sort_order</b> - buttons in the order info display are sorted ascending order</li>
     <li><b>disabled</b> - 0 means button is enabled, 1 means button is disabled</li>
 </ul>
 *
 * @author Matis
 */
class Balticode_Livehandler_Block_Adminhtml_Config_Form_Field_Button extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {
    
    public function __construct() {
        $this->addColumn('button_name', array(
            'label' => Mage::helper('balticode_livehandler')->__('Button name'),
            'style' => 'width:120px',
/*            'class' => 'validate-code',*/
        ));
        $this->addColumn('sort_order', array(
            'label' => Mage::helper('adminhtml')->__('Sort order'),
            'style' => 'width:120px',
            'class' => 'validate-digits',
        ));
        $this->addColumn('disabled', array(
            'label' => Mage::helper('adminhtml')->__('Disabled'),
            'style' => 'width:120px',
            'class' => 'validate-digits-range  digits-range-0-1',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('balticode_livehandler')->__('Add button');
        parent::__construct();
    }
    
}

