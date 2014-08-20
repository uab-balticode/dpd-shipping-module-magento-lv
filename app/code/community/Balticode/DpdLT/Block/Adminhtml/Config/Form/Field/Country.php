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
 * <p>Displays list of countries at the shipping modules configuration field.</p>
 * <p>Number of countries are unlimited and if more than one of the same country exist in the list, then last country declaration overrrides previous one.</p>
 * <p>Each country declaration contains following:</p>
 * <ul>
     <li>Base shipping price</li>
     <li>Additional price for each Kilogram. (data is taken from products weight field)</li>
     <li>Free shipping starts from price (Without VAT). Free shipping is disabled when this field is left empty.</li>
 </ul>
 *
 * @author Matis
 */
class Balticode_DpdLT_Block_Adminhtml_Config_Form_Field_Country extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {
    
    public function __construct() {

        $this->addColumn('country_id', array(
            'label' => $this->_getDpdHelper()->__('Country'),
            'style' => 'width:120px',
            'type' => 'select',
            'source_model' => 'adminhtml/system_config_source_country',
/*            'class' => 'validate-code',*/
        ));
        $this->addColumn('base_price', array(
            'label' => $this->_getDpdHelper()->__('Base shipping price'),
            'style' => 'width:120px',
            'class' => 'validate-number',
        ));
        $this->addColumn('kg_price', array(
            'label' => $this->_getDpdHelper()->__('Price per additional 10kg over base 10kg'),
            'style' => 'width:120px',
            'class' => 'validate-number',
        ));
        $this->addColumn('free_shipping_from', array(
            'label' => $this->_getDpdHelper()->__('Free shipping from price'),
            'style' => 'width:120px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = $this->_getDpdHelper()->__('Add shipping country');
        parent::__construct();
        
    }
    
    
    
    /**
     * Add a column to array-grid
     *
     * @param string $name
     * @param array $params
     */
    public function addColumn($name, $params) {
        $this->_columns[$name] = array(
            'label'     => empty($params['label']) ? 'Column' : $params['label'],
            'size'      => empty($params['size'])  ? false    : $params['size'],
            'style'     => empty($params['style'])  ? null    : $params['style'],
            'class'     => empty($params['class'])  ? null    : $params['class'],
            'type'     => empty($params['type'])  ? null    : $params['type'],
            'source_model'     => empty($params['source_model'])  ? null    : $params['source_model'],
            'renderer'  => false,
        );
        if ((!empty($params['renderer'])) && ($params['renderer'] instanceof Mage_Core_Block_Abstract)) {
            $this->_columns[$name]['renderer'] = $params['renderer'];
        }
    }
    
    
    
    /**
     * Since select menus are not supported and renderer does not work when single quotes are included in the select value list
     * then we need to override this method to support select dropdowns
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName) {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
        
        if (isset($column['type']) && $column['type']) {
            if ($column['type'] == 'select') {
                $html = '<select name="' . $inputName . '"   value="#{' . $columnName . '}" ' .
                        ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
                        (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
                        (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
                
                $options = Mage::getModel($column['source_model'])->toOptionArray();
                foreach ($options as $option) {
                    $html .= '<option value="' . htmlspecialchars($option['value']);
                    $html .= '" ';
                    $html .= '>';
                    $html .= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8');
                    $html .= '</option>';
                }
                $html .= '</select>';
                
                
                return $html;
            }
        }

        return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
    }
    
    
    
    /**
     * Because JS Prototype Template engine does not support html select value updates, then we need to override
     * toHtml method in order to set select values manually after page load.
     *
     * @return string
     */
    protected function _toHtml() {
        $this->setHtmlId('_'.  uniqid());
        if (!$this->_isPreparedToRender) {
            $this->_prepareToRender();
            $this->_isPreparedToRender = true;
        }
        if (empty($this->_columns)) {
            throw new Exception('At least one column must be defined.');
        }
        return parent::_toHtml().<<<JS
<script type="text/javascript">
    // <![CDATA[
    document.observe('dom:loaded', function() {
        $$('#grid{$this->getHtmlId()} select').each(function(iterator) {
            iterator.setValue(iterator.readAttribute('value'));
        });
    });
    // ]]>
</script>
JS;
    }
    
    
    
    
    /**
     * 
     * @return Balticode_DpdLT_Helper_Data
     */
    protected function _getDpdHelper() {
        return Mage::helper('balticode_dpdlt');
    }
    
    
}
