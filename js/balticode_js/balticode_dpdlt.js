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
 * @copyright  Copyright (c) 2013 UAB BaltiCode (http://www.balticode.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt  GNU Public License V3.0
 * @author     Matis Halmann
 * 

 */

var BalticodeDpdLT = new Class.create();

/**
 * <p>Helper class for displaying/submitting DPD courier on-demand-call-block under order management for Merchant.</p>
 * <p>on-demand-call-block is called as infoBox</p>
 * @type BalticodeDpdLT
 */
BalticodeDpdLT.prototype = {
    /**
     * <p>Initialize on-demand-courier-call-block</p>
     * @param {string} infoBoxId unique HTML id to track this block
     * @param {object} styleRules CSS style rules prototype js format.
     * @param {function} afterCreate function to execute after this infoblock has been created. Parameter is created infoblock.
     * @param {function} afterShow function to execute after block is shown. Parameter is created infoblock.
     * @param {function} afterEndResult function to execute after data is submitted. Parameter is submitted form data and function must return same data or false in order to prevent the form to be posted.
     * @returns {BalticodeDpdLT}
     */
    initialize: function(infoBoxId, styleRules, afterCreate, afterShow, afterEndResult) {
        this.infoBoxId = infoBoxId;
        this.styleRules = styleRules;
        this.afterCreate = afterCreate;
        this.afterShow = afterShow;
        this.afterEndResult = afterEndResult;
        this.offset = $(this.infoBoxId).positionedOffset();
        this.orderId = false;
        this.created = false;
    },
    /**
     * <p>Creates or returns block instance.</p>
     * @returns {Element} created on-demand-courier-call-block
     */
    getInfoBox: function() {
        if (!this.infoBox || !$(this.infoBoxId + '_box')) {
            if (Control.Modal.current) {
                $$('.balticode_window .button_box').invoke('hide');
                this.infoBox = new Element('div');
                this.infoBox.setAttribute('id', this.infoBoxId + '_box');
                this.infoBox.setAttribute('class', 'button_box');
                this.infoBox.setStyle({
                    position: 'absolute',
                    top: (this.offset.top - 135) + 'px',
                    left: (this.offset.left) + 'px'
                });
                if (this.styleRules) {
                    this.infoBox.setStyle(this.styleRules);
                }
                Control.Modal.current.container.insert({bottom: this.infoBox});
                
                //create after goes here
                if (this.afterCreate) {
                    this.afterCreate(this.infoBox);
                }
                this.created = true;
                
            } else {
                this.infoBox = new Element('div');
                this.infoBox.setAttribute('id', this.infoBoxId + '_box');
                this.infoBox.setAttribute('class', 'button_box');
                this.infoBox.setStyle({
                    position: 'absolute',
                    top: (this.offset.top - 135) + 'px',
                    left: (this.offset.left) + 'px'
                });
                if (this.styleRules) {
                    this.infoBox.setStyle(this.styleRules);
                }
                $$('body').first().insert({bottom: this.infoBox});
                
                //create after goes here
                if (this.afterCreate) {
                    this.afterCreate(this.infoBox);
                }
                this.created = true;
                
            }
        }
        return this.infoBox;
    },
    /**
     * <p>Updates infobox instance with supplied HTML contents</p>
     * <p>Executes check action on the massaction object for supplied orderId.</p>
     * <p>Updates number of parcels in accordance of selected orders under massActionObject.</p>
     * @param {string} htmlContents
     * @param {string|int} orderId
     * @param {varienGridMassaction} massActionObject
     * @returns {undefined}
     */
    update: function(htmlContents, orderId, massActionObject) {
        var isOrderSelected = false,
                parcelQty = 1,
                orders = orderId,
                orderMassActionCheckbox;
        //if trying to call the courier, check the current order
        if (orderId) {
            orderMassActionCheckbox = $$('.balticode_window .window_checkbox input[value=' + orderId + ']');
            if (orderMassActionCheckbox) {
                orderMassActionCheckbox = orderMassActionCheckbox.first();
                if (orderMassActionCheckbox && orderMassActionCheckbox.getValue()) {
                    isOrderSelected = true;
                }
            }
            if (!isOrderSelected && orderMassActionCheckbox) {
                orderMassActionCheckbox.click();
            }
        }
        if (massActionObject) {
            parcelQty = varienStringArray.count(massActionObject.getCheckedValues());
            orders = massActionObject.getCheckedValues();
        }
        if (this.orderId && orderId !== this.orderId || !$(this.infoBoxId + '_box')) {
            this.infoBox = false;
            this.getInfoBox();
            if (htmlContents) {
                this.infoBox.update(htmlContents);
                //update the parcels
                $('Po_parcel_qty').writeAttribute('value', parcelQty);
                $('balticode_dpdlt_order_ids').writeAttribute('value', orders);
            }
        } else if (!this.orderId) {
            this.getInfoBox();
            if (htmlContents) {
                this.infoBox.update(htmlContents);
                //update the parcels
                $('Po_parcel_qty').writeAttribute('value', parcelQty);
                $('balticode_dpdlt_order_ids').writeAttribute('value', orders);
            }
        }
        this.orderId = orderId;
    },
    /**
     * 
     * <p>Attempts to submit data from infoBox form fields to server.</p>
     * <p>If submit cannot be done, then it hides infoBox.</p>
     * <p>If infoBox is hidden, then it displays infoBox.</p>
     * @param {string} url not required, when provided, ajax request also is sent.
     * @returns {object|Boolean}
     */
    submit: function(url, successFunction) {
        var endResult = false;
        if (this.infoBox) {
            if (this.infoBox.visible()) {
                endResult = Form.serializeElements(this.infoBox.select('input, text, textarea, select'), {"hash":true});
                if (endResult === {} || endResult === []) {
                    endResult = false;
                }
                if (this.afterEndResult) {
                    endResult = this.afterEndResult(endResult);
                }
                if (endResult && url) {
                    new Ajax.Request(url, {
                        method: 'post',
                        parameters: endResult,
                        evalJSON: 'force',
                        onSuccess: function(transport){
                            if (successFunction) {
                                successFunction(transport.responseJSON, this.infoBox);
                            }
                        }.bind(this),
                        onFailure: function(transport){
                            alert('Request failed, check your error logs');
                        }
                    });
                }
                if (!endResult && !this.created) {
                    this.infoBox.hide();
                }
                if (this.created) {
                    this.created = false;
                }
            } else {
                this.infoBox.show();
                if (this.afterShow) {
                    this.afterShow(this.infoBox);
                }
            }
            
        }
        return endResult;
    }
};