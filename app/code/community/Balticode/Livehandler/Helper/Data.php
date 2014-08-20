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
 * <p>Handles saving and loading Magento's core_config_data values.</p>
 * <p>Can save and load PHP objects to core_config_data table.</p>
 *
 * @author Aktsiamaailm OÜ, Matis Halmann
 */
class Balticode_Livehandler_Helper_Data extends Mage_Core_Helper_Abstract {
    
    
    /**
     * <p>Behaves similar to Mage::getStoreConfig() method, but returns PHP objects instead of strings</p>
     * @param string $key configuration key to fetch
     * @param string|bool $default default value to return if the value does not exist or unserialization failed
     * @param int $storeId store id, to fetch the configuration value for
     * @return object|array|string
     */
    final public function getBigConfigData($key, $default = false, $storeId = null) {
        $i = 0;
        
        $selfValue = $this->getConfigData($key, $default, $storeId);
        while (strlen($loadedString = $this->getConfigData($key . $i, $default, $storeId)) > 0) {
            if (!is_string($loadedString)) {
                break;
            }
            $selfValue .= $loadedString;
            $i++;
        }
        
        $finalValue = @unserialize(@gzuncompress(@base64_decode($selfValue)));
        
        if (!is_array($finalValue)) {
            return $default;
        }
        return $finalValue;
    }
    
    /**
     * <p>Stores PHP object to core_config_data table using object serialization.</p>
     * <p>Save procedure of stored object:
     * <ul>
         <li>serialize php object</li>
         <li>gzcompress the serialized result</li>
         <li>base64 encode the compressed result</li>
         <li>If the compressed result is larger than 64K, then the resulting blocks will be saved under $key . $i ID, where $i starts with 0 and is incremented by 1 for each consecutive 64K block.</li>
     </ul>
     * </p>
     * @param string $key configuration key to store this value
     * @param object|array|string $value object to store into the configuration
     * @param string $scope Magento's configuration scope
     * @param int $scopeId store ID this configuration will be saved to
     * @param bool $skipFirst if this setting is true, then first segment will not be saved to database using this function. Useful, when overriding Mage_Core_Model_Config_Data saving functions.
     * @return string first saved segment of this saved configuration data.
     */
    final public function setBigConfigData($key, $value, $scope = 'default', $scopeId = 0, $skipFirst = false) {
        $strValue = base64_encode(gzcompress(serialize($value)));
        $strValues = str_split($strValue, 64000);
        $cnt = 0;
        
        $firstValue = array_shift($strValues);
        if (!$skipFirst) {
            $this->setConfigData($key, $firstValue, $scope, $scopeId, false);
        }
        
        foreach ($strValues as $strVal) {
            $this->setConfigData($key . $cnt, $strVal, $scope, $scopeId, false);
            $cnt ++;
        }
        $this->setConfigData($key . $cnt, '', $scope, $scopeId, true);
        return $firstValue;
    }
    
    
    /**
     * <p>Behaves similar to Mage::getStoreConfig() but attempts to return the resulting value as float.</p>
     * <p>Supports "." and "," as decimal separator</p>
     * @param string $key configuration key to load
     * @param bool|object $default default value to return if the configuration value does not exist.
     * @param int $storeId store id to load the configuration value for.
     * @return float
     */
    final public function getConfigDataF($key, $default = false, $storeId = null) {
        return $this->getConfigData($key, $default, $storeId, true);
    }
    
    /**
     * <p>Behaves similar to Mage::getStoreConfig() but returns array of elements, where each element is one line of data contained. Empty lines are not returned.</p>
     * @param string $key configuration key to load
     * @param bool|object $default default value to return if the configuration value does not exist.
     * @param int $storeId store id to load the configuration value for.
     * @return array
     */
    final public function getConfigDataA($key, $default = false, $storeId = null) {
        $value = $this->getConfigData($key, $default, $storeId, false);
        if (is_string($value)) {
            $rawModules = explode("\n", $value);
            return array_filter(array_map('trim', $rawModules));
        }
        return $value;
    }
    
    /**
     * <p>Behaves similar to Mage::getStoreConfig() function and can return the value as float.</p>
     * @param string $key configuration key to load
     * @param bool|object $default default value to return if the configuration value does not exist.
     * @param int $storeId store id to load the configuration value for.
     * @param bool $asFloat when true, then attemts to return the value as float
     * @return string
     */
    final public function getConfigData($key, $default = false, $storeId = null, $asFloat = false) {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $value = Mage::getStoreConfig($key, $storeId);
        if (is_null($value) || false === $value) {
            $value = $default;
        }
        if ($asFloat) {
            $value = str_replace(',', '.', $value);
        }
        return $value;
    }
    
    /**
     * <p>Writes the core_config_data value to database and resets the cache.</p>
     * @param string $key Identification path to write the configuarion to.
     * @param string $value Value to store
     * @param string $scope Magento's scope to apply
     * @param int $scopeId Store ID to apply
     * @param bool $resetCache If the cache should be reset after saving the configuration value.
     * @return Balticode_Livehandler_Helper_Data
     */
    final public function setConfigData($key, $value, $scope = 'default', $scopeId = 0, $resetCache = true) {
        $config = Mage::getConfig();
        $config->saveConfig($key, $value, $scope, $scopeId);
        if ($resetCache) {
            $config->cleanCache();
        }
        return $this;
    }
    
    
    
}


