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
 * <p>Base class for operating in Magento admin Grids</p>
 *
 * @author matishalmann
 */
class Balticode_Livehandler_Model_Adminhtml_Gridmanager extends Balticode_Livehandler_Model_Abstract {
    
    protected $_actionButtons = array();
    //put your code here
    public function _construct() {
        parent::_construct();
        $this->_init('balticode_livehandler/adminhtml_gridmanager');
    }
    
    /**
     * <p>Adds action button to top right of the grid or its edit view.</p>
     * @param string $id buttons HTML id attribute
     * @param string $title buttons printed label
     * @param string $onclick buttons HTML onclick attribute
     * @return Balticode_Livehandler_Model_Adminhtml_Gridmanager
     */
    public function addActionButton($id, $title, $onclick) {
        $this->_actionButtons[$id] = array(
            'title' => $title,
            'onclick' => $onclick,
        );
        return $this;
    }
    
    /**
     * <p>Removes previusly entered actionbutton by its HTML id</p>
     * @param string $id buttons HTML id attribute
     * @return Balticode_Livehandler_Model_Adminhtml_Gridmanager
     */
    public function removeActionButton($id) {
        if (isset($this->_actionButtons[$id])) {
            unset($this->_actionButtons[$id]);
        }
        return $this;
    }
    
    /**
     * Encodes input to JSON
     * @param mixed $input
     * @return string
     */
    protected function _encode($input) {
        return json_encode($input);
        
    }
    
    /**
     * <p>Renders javascript, which adds declared action buttons to the top right of the page next to others.</p>
     * @return string
     */
    public function getJs() {
        $js = '';
        
        foreach ($this->_actionButtons as $id => $actionButton) {
            $onclick = json_encode($actionButton['onclick']);
            $title = json_encode($actionButton['title']);
            $js .= <<<EOT
   var button_{$id} = new Element('button');
   button_{$id}.writeAttribute('id', '{$id}');
   button_{$id}.writeAttribute('type', 'button');
   button_{$id}.writeAttribute('class', 'scalable');
   button_{$id}.writeAttribute('onclick', {$onclick});
   button_{$id}.update('<span>' + {$title} + '</span>');
       
    $$('.form-buttons').first().insert(button_{$id}, { position: 'bottom'});
   
EOT;
        }
        
        $js .= $this->_getAdditionalJs($js);
        
        return $js;
    }
    
    /**
     * <p>Subclasses should add their javascript by overriding this function</p>
     * @param string $currentJs currently generated javascript so far
     * @return string resulting javascript
     */
    protected function _getAdditionalJs($currentJs) {
        return '';
    }
    
    
    /**
     * <p>This function is called, when this class outputs javascript and ajax data is sent to its generated URL.</p>
     * <p>Contained POST parameters are passed as assoc array</p>
     * @param array $postData
     * @return array
     */
    public function service($postData) {
        return array();
    }
}

