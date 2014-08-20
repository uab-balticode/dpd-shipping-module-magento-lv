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
 * <p>Base implementing class for balticode_livehandler.model_class property</p>
 * <p>This class should fire response for the actual ajax request</p>
 *
 * @author matishalmann
 */
abstract class Balticode_Livehandler_Model_Abstract  extends Mage_Core_Model_Abstract {
    
    //construct will be done in subclasses

    public function getJs() {
        return '';
    }

    public function getCss() {
        return '';
    }

    public function getHtml() {
        return '';
    }

    public function process($postedData) {
        return array();
    }
    
    /**
     * <p>Wrapper json_encode in order to make it easier to use in heredoc syntax</p>
     * @param mixed $input
     * @return string
     */
    protected function _toJson($input) {
        return json_encode($input);
    }

}