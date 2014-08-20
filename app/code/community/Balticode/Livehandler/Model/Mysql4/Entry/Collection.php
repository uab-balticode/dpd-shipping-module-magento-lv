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
 * <p>Represents collection of balticode_livehandler database table entries</p>
 *
 * @author matishalmann
 */
class Balticode_Livehandler_Model_Mysql4_Entry_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('balticode_livehandler/entry');
    }
    
    /**
     * <p>Finds balticode_livehandler entries by Magento Request name</p>
     * <p>Request name is constructed as following:</p>
     * <pre>
        $routeName = $request->getRequestedRouteName();
        $controllerName = $request->getRequestedControllerName();
        $actionName = $request->getRequestedActionName();
        $path = $routeName . '/' . $controllerName . '/' . $actionName;
     * 
     * </pre>
     * @param string $path Magento request name
     * @param bool $isAdmin true, when balticode_livehandler is declared to be admin only.
     * @param int $website id of a website, this action should only run on.
     * @param int $store id of a store, this action should only run on.
     * @return Balticode_Livehandler_Model_Mysql4_Entry_Collection
     */
    public function setFilter($path, $isAdmin = false, $website = 0, $store = 0) {
        $this->addFieldToFilter('request_var', array('like' => $path.'%'));
        $this->addFieldToFilter('is_admin', (int)$isAdmin);
        $this->addFieldToFilter('is_enabled', 1);
        $this->getSelect()->where('main_table.website_id = '.((int)$website).' or main_table.website_id = 0');
        $this->getSelect()->where('main_table.store_id = '.((int)$store).' or main_table.store_id = 0');
        $this->addOrder('request_var', 'asc');
        $this->addOrder('website_id', 'desc');
        $this->addOrder('store_id', 'desc');
        return $this;
    }
    
    /**
     * <p>Finds balticode_livehandler entries by Magento model name</p>
     * @param string $model Magento model name
     * @param bool $isAdmin true, when balticode_livehandler is declared to be admin only.
     * @param int $website id of a website, this action should only run on.
     * @param int $store id of a store, this action should only run on.
     * @return Balticode_Livehandler_Model_Mysql4_Entry_Collection
     */
    public function setModelFilter($model, $isAdmin = false, $website = 0, $store = 0) {
        $this->addFieldToFilter('model_class', $model);
        $this->addFieldToFilter('is_admin', (int)$isAdmin);
        $this->addFieldToFilter('is_enabled', 1);
        $this->getSelect()->where('main_table.website_id = '.((int)$website).' or main_table.website_id = 0');
        $this->getSelect()->where('main_table.store_id = '.((int)$store).' or main_table.store_id = 0');
        $this->addOrder('request_var', 'asc');
        $this->addOrder('website_id', 'desc');
        $this->addOrder('store_id', 'desc');
        return $this;
    }
}

