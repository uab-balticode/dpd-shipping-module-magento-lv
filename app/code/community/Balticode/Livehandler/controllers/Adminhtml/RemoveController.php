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
 * <p>Removes old livehandler from app/local folder</p>
 *
 * @author matishalmann
 */
class Balticode_Livehandler_Adminhtml_RemoveController extends Mage_Adminhtml_Controller_Action {
    protected function _initAction() {
        return $this;
    }
    
    public function removeAction() {
        $result = array('status' => 'failed');
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('remove') == 'true') {
            $dirName = Mage::getBaseDir('code').'/local/Balticode/Livehandler';
            if (is_dir($dirName) && file_exists($dirName.'/etc/config.xml')) {
                $directory = new Varien_Io_File();
                $deleteResult = $directory->rmdir($dirName, true);
                if ($deleteResult) {
                    $result['status'] = 'success';
                }
            }
            
        }
        $this->getResponse()->setRawHeader('Content-type: application/json');
        $this->getResponse()->setBody(json_encode($result));
        return;
    }
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function _getBalticode() {
        return Mage::helper('balticode');
    }
    
    
}


