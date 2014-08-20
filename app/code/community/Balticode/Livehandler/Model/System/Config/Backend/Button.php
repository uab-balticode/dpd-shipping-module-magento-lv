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
 * <p>Represents backend for the <code>Balticode_Livehandler_Block_Adminhtml_Config_Form_Field_Button</code></p>
 * 
 *
 * @author Matis
 * @see Balticode_Livehandler_Block_Adminhtml_Config_Form_Field_Button
 */
class Balticode_Livehandler_Model_System_Config_Backend_Button extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array {
    protected $_eventPrefix = 'balticode_livehandler_system_config_backend_button';

    /**
     * <p>Attempts to auto load Order Manager action buttons from following locations:</p>
     * <ul>
         <li>app/code/community/Balticode/Livehandler/Model/Action</li>
         <li>app/code/local/Balticode/Livehandler/Model/Action</li>
     </ul>
     * <p>Verifies their validity and if new found, then they are added to the configuration automatically.</p>
     */
    protected function _afterLoad() {
        parent::_afterLoad();
//        Zend_Debug::dump($this->getValue());
        $dirnames = array(
            Mage::getBaseDir('code').'/community/Balticode/Livehandler/Model/Action',
            Mage::getBaseDir('code').'/local/Balticode/Livehandler/Model/Action',
        );
        $foundNames = array();
        foreach ($dirnames as $dirname) {
            if (is_dir($dirname) && !$this->getBalticode()->getConfigData('balticode_livehandler/admintools/disable_actions_read')) {
                $directoryLister = new Balticode_Livehandler_Model_Directory_Collection($dirname, true);
                $filenames = $directoryLister->filesPaths();
                foreach ($filenames as $filename) {
                    $className = str_replace(array($dirname, '\\'), array('', '/'), $filename);
                    if (strpos($className, '.php') !== false) {
                        $className = ltrim(strtolower(str_replace(array('.php', '/'), array('', '_'), $className)), '_');
                        if ($className != 'abstract') {
                            $foundNames[$className] = $className;
                        }
                    }
                }
                
            }
        }
        $values = $this->getValue();
        $existingValues = array();
        $valuesToRemove = array();
        if (is_array($values)) {
            uasort($values, array(__CLASS__, '_sortAction'));
            
            foreach ($values as $key => $value) {
                $existingValues[$value['button_name']] = $value['button_name'];
                $modelName = 'balticode_livehandler/action_'.$value['button_name'];
                if (strpos($value['button_name'], '/')) {
                    $modelName = $value['button_name'];
                }
                $testModel = Mage::getModel($modelName);
                if (!$testModel || !($testModel instanceof Balticode_Livehandler_Model_Action_Abstract)) {
                    $valuesToRemove[] = $key;
                }
                
            }
        }
        if (count($foundNames) && !is_array($values)) {
            $values = array();
        }
        $valueAdded = false;
        foreach ($foundNames as $foundName) {
            if (!isset($existingValues[$foundName])) {
                $testModel = Mage::getModel('balticode_livehandler/action_'.$foundName);
                if ($testModel && $testModel instanceof Balticode_Livehandler_Model_Action_Abstract) {
                    $values['_' . time() . '_' . mt_rand(0, 999)] = array(
                        'button_name' => $foundName,
                        'sort_order' => "0",
                        'disabled' => "0"
                    );
                    $valueAdded = true;
                }
                
            }
        }
        foreach ($valuesToRemove as $valueToRemove) {
            unset($values[$valueToRemove]);
        }
        if ($valueAdded || count($valuesToRemove) || true) {
            $this->setValue($values);
        }
        
        
    }
    
    public function isValueChanged() {
        $oldValue = @unserialize($this->getOldValue());
        if (is_array($oldValue)) {
            return $oldValue != $this->getValue();
        }
        return parent::isValueChanged();
    }


    /**
     * <p>Sorts buttons by their sort order ascending and leaves disabled buttons as least important.</p>
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function _sortAction($a, $b) {
        if ((bool)$a['disabled'] && !(bool)$b['disabled']) {
            return 1;
        }
        if (!(bool)$a['disabled'] && (bool)$b['disabled']) {
            return -1;
        }
        if ((int)$a['sort_order'] == (int)$b['sort_order']) {
            return 0;
        }
        return (int)$a['sort_order'] < (int)$b['sort_order']?-1:1;
    }
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function getBalticode() {
        return Mage::helper('balticode');
    }
    
    
    
}

