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
 * <p>Adds extra button to each row of Magento Adminhtml Sales Order grid called 'Show order info'</p>
 * <p>Clicking on this button shows almost full information about the order and orders can be switched with press of up-down-left-right arrows.</p>
 * <p>Order view is synchronized with massaction checkboxes and Merchant can individually check/uncheck orders in this view.</p>
 * <p>Extra buttons can be added to order view using <code>Balticode_Livehandler_Model_Action_Abstract</code> interface which allow faster order processing than default Magento offers.</p>
 *
 * @author matishalmann
 * @see Balticode_Livehandler_Model_Action_Abstract
 * @see Balticode_Livehandler_Model_Action_Postoffice_Send
 * @see Balticode_Livehandler_Model_Action_Postoffice_Print
 */
class Balticode_Livehandler_Model_Ordergrid extends Balticode_Livehandler_Model_Adminhtml_Gridmanager {
    
    /**
     * <p>Instance of allowed buttons displayed in order view</p>
     * @var array
     */
    private static $_allowedActions;
    
    /**
     *
     * @var bool true if at least one button exists.
     */
    private static $_hasButtons = false;
    
    /**
     * Buttons are cached forever or as long as configuration cache is alive.
     */
    CONST CACHE_KEY = 'balticode_livehandler_model_ordergrid';
    
    protected $_loadedButtons = array();

    
    public function _construct() {
        parent::_construct();
        $this->_init('balticode_livehandler/ordergrid');
        $this->_initButtons();
    }
    
    /**
     * Attempts to load action buttons from cache and if not successful, then from configuration and place it into cache.
     * @see Balticode_Livehandler_Block_Adminhtml_Config_Form_Field_Button
     * 
     */
    private function _initButtons() {
        if (self::$_allowedActions === null) {
            //first check the folder model/actions if the checked result is not already stored in cache
            //effectively each time cache is cleared existense for new button classes is checked
            //if cache is disabled entirely then each load time existense of the button classes is checked when 
            //order detail view screen is loaded
            $cache = Mage::app()->getCache();
            /* @var $cache Mage_Core_Model_Cache */
            $configStored = $cache->load(self::CACHE_KEY);
            if ($configStored != 'true') {
                //reload the config
                /* @var $configBackend Balticode_Livehandler_Model_System_Config_Backend_Button */
                $configBackend = Mage::getModel('balticode_livehandler/system_config_backend_button');
                $configBackend->load('balticode_livehandler/admintools/buttons', 'path');
                $configBackend->save();
                if ($configBackend->isValueChanged() && is_array($configBackend->getValue())) {
                    $this->getBalticode()->setConfigData('balticode_livehandler/admintools/buttons', serialize($configBackend->getValue()));
                }
                
                //since the actual configuration is saved in core_config_data, which is cached anyway
                //then we only need a mark that we checked for the existence
                //put lifetime=forever, because new check should be performed only when cache is cleared
                //cache tag should be configuration, because we hold the actual value in Magentos configuration
                $cache->save('true', self::CACHE_KEY, array(Mage_Core_Model_Config::CACHE_TAG), 0);
                
            }
            
            //read the available button classes from the configuration
            //because detecting the buttons from the folder is slow, that is why only configuration is checked.
            $allowedActions = @unserialize(Mage::getStoreConfig('balticode_livehandler/admintools/buttons'));
            if (!is_array($allowedActions)) {
                //we have no buttons, or the saved configuration was garbage
                $allowedActions = array();
            }
            self::$_allowedActions = array();
            //sort the allowed actions by sort_order
            uasort($allowedActions, array(__CLASS__, '_sortAction'));
            foreach ($allowedActions as $allowedAction) {
                //exclude disabled button classes
                if (!(bool)$allowedAction['disabled']) {
                    self::$_allowedActions[] = $allowedAction['button_name'];
                }
                self::$_hasButtons = true;
            }
        }
    }
    
    
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
     * <p>Returns json with order data and attempts to detect if any action buttons was pressed.</p>
     * <p>If any action buttons were pressed then corresponding Balticode_Livehandler_Model_Action_Abstract::service() is invoked with supplied extra POST parameters.</p>
     * <p>If this module is disabled, then assoc array of 
     * array('result' => false); is returned.</p>
     * 
     * 
     * @param array $postData
     * @return array json with order data
     */
    public function service($postData) {
        $errors = array();
        $messages = array();
            
        $result = array(
            'success' => false,
            'set_location' => false,
        );
        if (!Mage::getStoreConfig('balticode_livehandler/admintools/enabled')) {
            return $result;
        }
        if (isset($postData['order_id'])) {
            $order = Mage::getModel('sales/order')->load($postData['order_id']);
            if ($order->getId() > 0) {
                $isActionError = false;
                
                
                    //send_email
                    //unhold
                    //hold
                    //cancel
                    //invoice
                if (isset($postData['action']) && is_string($postData['action'])) {
                    $postData['action'] = str_replace('__', '/', $postData['action']);
                }
                if (isset($postData['action']) && in_array($postData['action'], self::$_allowedActions)) {
                    if (strpos($postData['action'], '/')) {
                        $actionModel = Mage::getSingleton($postData['action']);
                    } else {
                        $actionModel = Mage::getSingleton('balticode_livehandler/action_' . $postData['action']);
                    }
                    
//                    $actionModel = Mage::getSingleton('balticode_livehandler/action_'.$postData['action']);
                    /* @var $actionModel Balticode_Livehandler_Model_Action_Abstract */
                    if ($actionModel && $actionModel instanceof Balticode_Livehandler_Model_Action_Abstract
                            && $actionModel->canDisplay($order)) {
                        $data = isset($postData['extra_data'])?json_decode($postData['extra_data'], true):array();
                        if (!is_array($data)) {
                            $data = array();
                        }
                        $actionResult = $actionModel->performDesiredAction($order, $data);
                        if (isset($actionResult['errors'])) {
                            $errors = array_merge($messages, $actionResult['errors']);
                        }
                        $messages = array_merge($messages, $actionResult['messages']);
                        $isActionError = $actionResult['is_action_error'];
                        $result['needs_reload'] = $actionResult['needs_reload'];
                        if (isset($actionResult['set_location'])) {
                            $result['set_location'] = $actionResult['set_location'];
                        }
                    } else {
                        $isActionError = true;
                    }
                } else {
//                    $isActionError = true;
                }
                if ($isActionError) {
                    $errors[] = Mage::helper('balticode_livehandler')->__('Invalid action');
                    $result['needs_reload'] = true;
                }
                if ($result['set_location'] && strlen($result['set_location'])> 3) {
                    return $result;
                }
                
                $address = $order->getShippingAddress();
                if (!$address) {
                    $address = $order->getBillingAddress();
                }
                
                $result['_order'] = $order->debug();
                $result['address'] = $address->format('html');
                $result['customer_email'] = htmlspecialchars($order->getCustomerEmail());
                $result['total_paid'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseTotalPaid(), true, false));
                $result['grand_total'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseGrandTotal(), true, false));
                $result['base_shipping'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseShippingAmount(), true, false));
                $result['base_total'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseGrandTotal() - $order->getBaseShippingAmount(), true, false));
                $result['base_subtotal_to_refund'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseSubtotalInvoiced() - $order->getBaseSubtotalRefunded(), true, false));
                $result['base_total_to_refund'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseTotalInvoiced() - $order->getBaseTotalRefunded(), true, false));
                $result['payment'] = htmlspecialchars($order->getPayment()->getMethodInstance()->getTitle());
                $result['shipping'] = htmlspecialchars($order->getShippingDescription());
                if ($result['shipping'] == '') {
                    $result['shipping'] = Mage::helper('balticode_livehandler')->__('Not applicable');
                }
                $result['status_label'] = htmlspecialchars($order->getStatusLabel());
                $result['discount_description'] = htmlspecialchars($order->getDiscountDescription());
                if ($order->getBaseDiscountAmount() > 0) {
                    $result['discount_amount'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseDiscountAmount() * -1, true, false));
                    
                } else {
                    $result['discount_amount'] = htmlspecialchars(Mage::helper('core')->currency($order->getBaseDiscountAmount(), true, false));
                    
                }
                $orderItems = $order->getAllVisibleItems();
                $products = array();
                foreach ($orderItems as $orderItem) {
                    
//                    $products[] = $orderItem->debug() + array('prod_options' => $orderItem->getProductOptions());
                    
                    $html = <<<EOT
   <tr>
       <td class="a-left">%name%</td>
       <td>%sku%</td>
       <td>%qty%</td>
       <td>%price%</td>
       <td>%total%</td>
   </tr>
                    
EOT;
                    $name = htmlspecialchars($orderItem->getName());
                    $options = $orderItem->getProductOptions();
                    $option = '';
                    if (isset($options['options'])) {
                        foreach ($options['options'] as $optionValues) {
                            if ($optionValues['value']) {
                                $option .= '<br/><strong><i>'. htmlspecialchars($optionValues['label']).'</i></strong>: ';
                                $_printValue = isset($optionValues['print_value']) ? $optionValues['print_value'] : strip_tags($optionValues['value']);
                                $values = explode(', ', $_printValue);
                                foreach ($values as $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $_value) {
                                            $option .= htmlspecialchars($_value);
                                        }
                                    } else {
                                        $option .= htmlspecialchars($value);
                                    }
                                    $option .= '<br/>';
                                }
                            }
                        }
                    }
                    $name .= $option;
                    $basePrice = $orderItem->getBasePriceInclTax();
                    if ($basePrice == null) {
                        $basePrice = $orderItem->getBasePrice();
                    }
//                    echo '<pre>'.htmlspecialchars(print_r($orderItem->debug(), true)).'</pre>';
//                    exit;
                    $toReplace = array(
                        'name' => $name,
                        'sku' => htmlspecialchars($orderItem->getSku()),
                        'qty' => round($orderItem->getQtyOrdered()) .'/'.round(Mage::getModel('cataloginventory/stock_item')->loadByProduct($orderItem->getProductId())->getQty()),
                        'price' => Mage::helper('core')->currency($basePrice, true, false),
                        'total' => Mage::helper('core')->currency($basePrice * $orderItem->getQtyOrdered(), true, false),
                    );
                    
                    foreach ($toReplace as $key => $value) {
                        $html = str_replace('%'.$key.'%', $value, $html);
                    }
                    
                    $products[] = array(
                        'name' => $orderItem->getName(),
                        'base_original_price' => $orderItem->getBaseOriginalPrice(),
                        'base_price' => $orderItem->getBasePrice(),
                        'base_price_incl_tax' => $orderItem->getBasePriceInclTax(),
                        'original_price' => $orderItem->getOriginalPrice(),
                        'price' => $orderItem->getPrice(),
                        'price_incl_tax' => $orderItem->getPriceInclTax(),
                        'product_id' => $orderItem->getProductId(),
                        'product_options' => $orderItem->getProductOptions(),
                        'qty_ordered' => $orderItem->getQtyOrdered(),
                        'row_total' => $orderItem->getRowTotal(),
                        'row_total_incl_tax' => $orderItem->getRowTotalInclTax(),
                        'sku' => $orderItem->getSku(),
                        'tax_amount' => $orderItem->getTaxAmount(),
                        'tax_percent' => $orderItem->getTaxPercent(),
                        'html' => $html,
                    );
                    
                }
                
                $buttonsHtml = '';
                if (!self::$_hasButtons && !$this->getBalticode()->getConfigData('balticode_livehandler/admintools/disable_url')) {
                    $buttonsHtml .= '<span class="balticode_commercial">'.$this->getBalticode()->__('Manage the order from here by adding <a href=\'%s\' target=\'_blank\'>action buttons</a>', $this->getBalticode()->__($this->getBalticode()->getConfigData('balticode_livehandler/admintools/buttons_url'))).'</span>';
                }

                foreach (self::$_allowedActions as $allowedAction) {
                    if (strpos($allowedAction, '/')) {
                        $actionButtonModel = Mage::getSingleton($allowedAction);
                    } else {
                        $actionButtonModel = Mage::getSingleton('balticode_livehandler/action_' . $allowedAction);
                    }
                    /* @var $actionButtonModel Balticode_Livehandler_Model_Action_Abstract */
                    if ($actionButtonModel && $actionButtonModel instanceof Balticode_Livehandler_Model_Action_Abstract && $actionButtonModel->canDisplay($order)) {
                        $buttonsHtml .= $this->__makeActionButton($actionButtonModel->getLabel(), $actionButtonModel->getOnClick(), $actionButtonModel->getCssClass(), 'balticode_' . $actionButtonModel->getCode());
                        $this->_loadedButtons = $actionButtonModel;
                    }
                }


                /* @var $adminHelper Mage_Adminhtml_Helper_Data */
                $adminHelper = Mage::helper('adminhtml');
                $result['success'] = true;
                $result['header_html'] = <<<EOT
<div class="grid products_grid">
<table><thead>
    <tr class="headings">
        <th>{$adminHelper->__('Product')}</th>
        <th>{$adminHelper->__('SKU')}</th>
        <th>{$adminHelper->__('Qty')}</th>
        <th>{$adminHelper->__('Price')}</th>
        <th>{$adminHelper->__('Row Total')}</th>
    </tr>
    </thead>
    <tbody>
EOT;
                $result['footer_html'] = <<<EOT
</tbody>
</table>
</div>
EOT;
                
                $result['customer_address_html'] = '';
                
                if (count($errors) > 0 || count($messages) > 0) {
                    $result['customer_address_html'] .= '<ul class="messages">';
                    
                    if (count($messages) > 0) {
                        $result['customer_address_html'] .= '<li class="success-msg"><ul>';
                        foreach ($messages as $message) {
                            $result['customer_address_html'] .= '<li>'.  htmlspecialchars($message).'</li>';
                        }
                        $result['customer_address_html'] .= '</ul></li>';
                    }

                    if (count($errors) > 0) {
                        $result['customer_address_html'] .= '<li class="error-msg"><ul>';
                        foreach ($errors as $error) {
                            $result['customer_address_html'] .= '<li>'.  htmlspecialchars($error).'</li>';
                        }
                        $result['customer_address_html'] .= '</ul></li>';
                    }
                    
                    $result['customer_address_html'] .= '</ul>';
                }
                $_blank = '';
                if (Mage::getStoreConfig('balticode_livehandler/admintools/open_in_new')) {
                    $_blank = ' target="_blank"';
                }
                $orderUrl = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId()));
                $result['customer_address_html'] .= <<<EOT
<div class="grid detail_grid">
<table><thead>
    <tr class="headings">
        <th>{$result['total_paid']}/{$result['grand_total']}</th>
        <th class="a-right"><div class="balticode_buttons">{$buttonsHtml}</div>&nbsp;<a href="{$orderUrl}" title="{$adminHelper->__('View')}" {$_blank}>{$result['status_label']}</a></strong></th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td class="a-left"><strong><a href="mailto:{$result['customer_email']}">{$result['customer_email']}</a></strong><br/>{$result['address']}</td>
            <td>
                <table>
                    <thead>
                        <tr>
                            <th>{$adminHelper->__('Payment Information')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$result['payment']}</td>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <thead>
                        <tr>
                            <th>{$adminHelper->__('Shipping &amp; Handling Information')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{$result['shipping']}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
    </table>
</div>
                
EOT;
                            
                if ($order->getBaseDiscountAmount() < 0 || $order->getBaseDiscountAmount() > 0) {
                    if ($result['discount_description'] == '') {
                        $result['discount_description'] = $order->getCouponCode();
                    }
                    $result['footer_html'] .= <<<EOT
<div class="grid discounts_grid">
<table><thead>
    <tr class="headings">
        <th>{$adminHelper->__('Discount')}: {$result['discount_description']}</th>
        <th class="a-right"><strong>{$result['discount_amount']}</strong></th>
    </tr>
    </thead>
    <tbody>
    </tbody>
    </table>
</div>
                    
EOT;
                }
                $date = $order->getCreatedAtFormated('medium');
                $result['title_html'] = htmlspecialchars(sprintf(Mage::helper('sales')->__('Order #%s - %s'), $order->getIncrementId(), $date));
                $result['products'] = $products;
            }
            
        }
        return $result;
    }
    
    /**
     *  Returns JS code in the footer for this concrete action.
     * 
     * @param string $currentJs previusly generated JS
     * @return string 
     */
    protected function _getAdditionalJs($currentJs) {
        $js = '';
        if (!Mage::getStoreConfig('balticode_livehandler/admintools/enabled')) {
            return $js;
        }
        /* @var $adminHelper Mage_Adminhtml_Helper_Data */
        $adminHelper = Mage::helper('adminhtml');
        $showProductsText = Mage::helper('balticode_livehandler')->__('Show order info');
        $showProductsText = htmlspecialchars(str_replace('\\\'', '\'', addslashes($showProductsText)));

        $additionalCssClass = '';
        if (substr(Mage::getVersion(), 0, 3) == '1.3') {
            $additionalCssClass = ' balticode_window_bg';
        }
        $longOnClicks = array();

        foreach (self::$_allowedActions as $allowedAction) {
            if (strpos($allowedAction, '/')) {
                $actionButtonModel = Mage::getSingleton($allowedAction);
            } else {
                $actionButtonModel = Mage::getSingleton('balticode_livehandler/action_' . $allowedAction);
            }
            /* @var $actionButtonModel Balticode_Livehandler_Model_Action_Abstract */
            if ($actionButtonModel && $actionButtonModel instanceof Balticode_Livehandler_Model_Action_Abstract) {
                if ($actionButtonModel->getLongOnClick() && strlen($actionButtonModel->getLongOnClick())) {
                    $longOnClicks[$actionButtonModel->getCode()] = $actionButtonModel->getLongOnClick();
                }
            }
        }




        $js .= <<<EOT
   
   var clickFirst = false, clickLast = false;
    var balticodeAdmintoolsGrid = function() {

        var position = 0,
        filters = $$('tr.filter').first().childElements(),
        needs_reload = false,
        pageNum = parseInt($$('input[name=page]').first().getValue(), 10),
        pageLimit = parseInt($$('select[name=limit]').first().getValue(), 10),
        current_products = [];

            if (Control.Modal.current) {
                Control.Modal.current.close();
            }

        for (var i = 0, cnt = filters.length; i < cnt; i++) {
                if (filters[i].select('input[name=billing_name]').length == 1) {
                    position = i;
                    break;
                }
        }


            var rows = $$('table#sales_order_grid_table tbody tr');
            if (rows.length == 1 && rows[0].childElements().length == 1) {
                return;
            }

            for (var i = 0, cnt = rows.length; i < cnt; i++) {
                rows[i].childElements()[position].insert('<button id="balticode-order_' + rows[i].select('input[class=massaction-checkbox]').first().readAttribute('value') + '" style="float: right;" class="scalable show-hide balticode-order-products" onclick="Event.stop(event); return false;" type="button"><span><b>{$showProductsText}</b></span></button>', { position: 'bottom'})
            }
            
            var style_window = function(container, options) {
                var window_header = new Element('div', { className: 'window_header'});
                var window_title = new Element('div', { className: 'window_title'});
                var window_close = new Element('div', { className: 'window_close'});
                var window_checkbox = new Element('span', { className: 'window_checkbox'});
                var window_contents = new Element('div', { className: 'window_contents'});

                var w = new Control.Modal(container, Object.extend({
                    className: 'balticode_window{$additionalCssClass}',
                    /*closeOnClick: window_close,*/
                    draggable: window_header,
                    insertRemoteContentAt: window_contents,
                    iframeshim: false,
                    afterOpen: function() {
                        var balticode_order_products = $$('button.balticode-order-products').toArray(),
                        order_id = options.caller.readAttribute('id').split('_')[1],
                        post_params = {'order_id': order_id },
                        is_order_selected = false,
                        order_massaction_checkbox;
                        if (order_id) {
                            order_massaction_checkbox = $$('table.data input.massaction-checkbox[value=' + order_id + ']');
                            if (order_massaction_checkbox) {
                                order_massaction_checkbox = order_massaction_checkbox.first();
                            }
                            if (order_massaction_checkbox && order_massaction_checkbox.getValue()) {
                                is_order_selected = true;
                            }
                            
                        }
                        if (order_massaction_checkbox) {
                            
                        }
                        if (options.target) {
                            post_params.action = options.target;
                        }
                        if (options.extra_data) {
                            post_params.extra_data = Object.toJSON(options.extra_data);
                        }
        //                window_title.update(container.readAttribute('title'));
                            if (order_massaction_checkbox) {
                          window_checkbox.select('input').first().writeAttribute('value', order_id);
                          window_checkbox.select('input').first().observe('change', function(event) {
                            if (order_massaction_checkbox) {
                                order_massaction_checkbox.setValue(event.originalTarget.getValue());
                                sales_order_grid_massactionJsObject.setCheckbox(order_massaction_checkbox);
                            }
                          });
                            }
                          

                          if (is_order_selected) {
                              window_checkbox.select('input').first().writeAttribute('checked', 'checked');
                          } else {
                          }
                        if (options.caller) {
                            window_close.observe('click', function(event) {
                                if (Control.Modal.current) {
                                    Control.Modal.current.close();
                                }
                                if (needs_reload) {
                                    window.location.reload();
                                }
                            });
                            new Ajax.Request(action_url, {
                                method: 'post',
                                parameters: post_params,
                                evalJSON: 'force',
                                onSuccess: function(transport) {
                                    var html = '';
                                    if (transport.responseJSON) {
                                        var html = '', json = transport.responseJSON, longClicks = {};
                                        if (json.set_location) {
                                            setLocation(json.set_location);
                                            return;
                                        }
                                        if (json.success) {
                                            html += json.customer_address_html;
                                            html += json.header_html;
                                            json.products.each(function(item) {
                                                html += item.html;
                                            });
                                            html += json.footer_html;
                                            window_contents.update(html);
                                            window_title.update(json.title_html);
                                            //add the click handlers....
                                            
                                            
EOT;
                    foreach ($longOnClicks as $key => $longOnClick) {
                        $js .= <<<EOT
   longClicks[{$this->_encode($key)}] = function(event, caller, json) {
       {$longOnClick};
   };
   
EOT;
                    }
                    
                    
                    
                    
                    $js .= <<<EOT
   
                        window_contents.insert({before: '<span id="balticode_previous" class="balticode_arrow" title="' + {$this->_encode($adminHelper->__('Previous'))} + '" onclick="Podium.keydown(38);"></span><span id="balticode_next" class="balticode_arrow" title="' + {$this->_encode($adminHelper->__('Next'))}+ '" onclick="Podium.keydown(40);"></span>'});
                                            
                                            window_contents.select('.balticode_orderview_actionbutton').each(function(item) {
                                                var item_id = item.readAttribute('id'), passed = true;
                                                if (item_id) {
                                                    item.observe('click', function(event) {
EOT;
                    //here goes extra long click
                    /*
                     * var longClicks = {
                     *  'comment' : function(event) { function body goes here... },
                     *  'cancel' : function(event) { function body goes here... },
                     * };
                     * 
                     * if (longClicks[item_id.replace('balticode_', '')]) {
                     *      longClicks[item_id.replace('balticode_', '')](event);
                     * }
                     * 
                     */
                    
                    $js .= <<<EOT
   
   if (longClicks[item_id.replace('balticode_', '')]) {
        passed = longClicks[item_id.replace('balticode_', '')](event, options.caller, json);       
   }
   if (passed !== false) {
                                                        handleButtonClick(item_id.replace('balticode_', ''), options.caller, passed);
   }
                                                        event.stop();
                                                    });
                                                }
                                            });
                                            if (json.needs_reload) {
                                                needs_reload = true;
                                            }

                                        } else {
                                            window.location.reload();
                                        }
                                    } else {
                                        window.location.reload();
                                    }
                                }
                            });
                        }
                        if (options.hasOwnProperty('callerIndex')) {
                            document.observe('keydown', function(event) {
                                var keyCode = event.keyCode;
                                if (keyCode == 38 || keyCode == 37) {
                                    if (options.callerIndex > 0) {
                                        balticode_order_products[options.callerIndex - 1].click();
                                    } else {
                                        if (balticode_order_products.length && pageNum > 1) {
                                            sales_order_gridJsObject.setPage(pageNum - 1, false);
                                            clickLast = true;
                                        }
                                    }
                                    return event.stop();
                                } else if (keyCode == 40 || keyCode == 39) {
                                    if (options.callerIndex < (balticode_order_products.length - 1)) {
                                        balticode_order_products[options.callerIndex + 1].click();
                                    } else {
                                        if (balticode_order_products && balticode_order_products.length >= pageLimit) {
                                            sales_order_gridJsObject.setPage(pageNum + 1, true);
                                            clickFirst = true;
                                        }
                                    }
                                    return event.stop();
                                } else if (keyCode == 27) {
                                    //esc key
                                    Control.Modal.current.close(event);
                                    if (needs_reload) {
                                        window.location.reload();
                                    }
                                    return event.stop();
                                }
                                return event;
                            });
                        }
                    },
                    afterClose: function() {
                        if (options.caller) {
                            options.caller.removeClassName('disabled');
                        }
                        if (options.hasOwnProperty('callerIndex')) {
                            document.stopObserving('keydown');
                            $$('balticode_orderview_actionbutton').each(function(item) {
                                item.stopObserving('click');
                            });
                            window_close.stopObserving('click');
                        }
                        this.destroy();
                    }
                }, options || {}));

                w.container.insert(window_header);
                window_header.insert(window_title);
                window_checkbox.update('<label><input type="checkbox" value="1" onchange=""/></label>');
                window_header.insert(window_checkbox);
                window_header.insert(window_close);
                w.container.insert(window_contents);
                return w;
            };
            $$('button.balticode-order-products').each(function(item, index) {
                var order_id = item.readAttribute('id').split('_')[1];
                item.observe('click', function(event) {
                    item.addClassName('disabled');
                    var event_arguments = { title: '', caller: item, callerIndex: index};
                    if (event.memo && event.memo.target) {
                        event_arguments.target = event.memo.target;
                    }
                    var style_window_one = style_window('', event_arguments);
                    style_window_one.open();
                    event.stop();
                });
                item.observe('balticode:click', function(event) {
                    item.addClassName('disabled');
                    var event_arguments = { title: '', caller: item, callerIndex: index};
                    if (event.memo && event.memo.target) {
                        event_arguments.target = event.memo.target;
                    }
                    if (event.memo && event.memo.extra_data) {
                        event_arguments.extra_data = event.memo.extra_data;
                    }
                    var style_window_one = style_window('', event_arguments);
                    style_window_one.open();
                    event.stop();
                });
            });
            if (clickFirst) {
                current_products = $$('button.balticode-order-products').toArray();
                if (current_products && current_products[0]) {
                    current_products[0].click();
                }
                clickFirst = false;
            }
            if (clickLast) {
                current_products = $$('button.balticode-order-products').toArray();
                if (current_products && current_products.length) {
                    current_products[current_products.length - 1].click();
                }
                clickLast = false;
            }
            

        }; //end of balticodeAdminToolsGrid
    
   var oldInitGridRows = false;
    if (Control && Control.Modal) {
        balticodeAdmintoolsGrid();
        oldInitGridRows = sales_order_gridJsObject.initGridRows;
    }
   
    if (oldInitGridRows) {
        sales_order_gridJsObject.initGridRows = function() {
            $$('button.balticode-order-products').each(function(item) {
                item.stopObserving('click');
                item.stopObserving('balticode:click');
            });
            oldInitGridRows();
            balticodeAdmintoolsGrid();
        };
    } else if (Control && Control.Modal) {
       var oldInitGridRows = varienGrid.prototype.reload;
        varienGrid.prototype.reload = function(url) {
            $$('button.balticode-order-products').each(function(item) {
                item.stopObserving('click');
                item.stopObserving('balticode:click');
            });

        if (!this.reloadParams) {
            this.reloadParams = {form_key: FORM_KEY};
        }
        else {
            this.reloadParams.form_key = FORM_KEY;
        }
        url = url || this.url;
        if(this.useAjax){
            new Ajax.Request(url + ((url.match(new RegExp('\\\\?')) ? '&ajax=true' : '?ajax=true') ), {
                loaderArea: this.containerId,
                parameters: this.reloadParams || {},
                evalScripts: true,
                onFailure: this._processFailure.bind(this),
                onComplete: this.initGrid.bind(this),
                onSuccess: function(transport) {
                    try {
                        if (transport.responseText.isJSON()) {
                            var response = transport.responseText.evalJSON()
                            if (response.error) {
                                alert(response.message);
                            }
                            if(response.ajaxExpired && response.ajaxRedirect) {
                                setLocation(response.ajaxRedirect);
                            }
                        } else {
                            $(this.containerId).update(transport.responseText);
                                balticodeAdmintoolsGrid();
                        }
                    }
                    catch (e) {
                        $(this.containerId).update(transport.responseText);
                    }
                }.bind(this)
            });
            return;
        }
        else{
            if(this.reloadParams){
                \$H(this.reloadParams).each(function(pair){
                    url = this.addVarToUrl(pair.key, pair.value);
                }.bind(this));
            }
            location.href = url;
        }
                    
    
        };
        
    }
   
    //handle the clicks......
    function handleButtonClick(call_action, caller_button, passed) {
        //make ajax request
        caller_button.fire('balticode:click', { target: call_action, extra_data: passed});
    }



       
EOT;
        
        
        return $js;
    }

    private function __makeActionButton($title, $onclick = '', $cssClass = '', $id = '') {
        $html = '';
        
//        $onclick = htmlspecialchars(str_replace('\\\'', '\'', addslashes($onclick)));
        $onclick = htmlspecialchars($onclick);
        
        $html .= <<<EOT
<button class="scalable balticode_orderview_actionbutton {$cssClass}" id="{$id}" type="button" onclick="{$onclick}"><span>{$title}</span></button>
    
EOT;
        return $html;
    }
    
    protected function _encode($input) {
        return json_encode($input);
        
    }
    
    /**
     * 
     * @return Balticode_Livehandler_Helper_Data
     */
    protected function getBalticode() {
        return Mage::helper('balticode');
    }
    
}

