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
 * <p>Replaces Varien_Directory_Collection for PHP5.3+ compatibility</p>
 *
 * @author Matis
 */
class Balticode_Livehandler_Model_Directory_Collection implements IteratorAggregate, Countable {
    protected $_path='';
    protected $_dirName='';
    protected $_recursionLevel=0;
    protected $_isRecursion;
    protected $_filters = array();
    
    /**
     * Constructor
     *
     * @param   string $path - path to directory
     * @param   bool $is_recursion - use or not recursion
     * @return  none
     */
    public function __construct($path,$isRecursion=true,$recursionLevel = 0) {
        $this->setPath($path);
        $this->_dirName = $this->lastDir();
        $this->setRecursion($isRecursion);
        $this->setRecursionLevel($recursionLevel);
        if($this->getRecursion() || $this->getRecursionLevel()==0){
            $this->parseDir();
        }
    }
    /**
     * Get name of this directory
     *
     * @return  string - name of this directory
     */
    public function getDirName() {
        return $this->_dirName;
    }
    /**
     * Get recursion
     *
     * @return  bool - is or not recursion
     */
    public function getRecursion() {
        return $this->_isRecursion;
    }
    /**
     * Get recursion level
     *
     * @return  int - recursion level
     */
    public function getRecursionLevel() {
        return $this->_recursionLevel;
    }
    /**
     * Get path
     *
     * @return  string - path to this directory
     */
    public function getPath() {
        return $this->_path;
    }
    /**
     * Set path to this directory
     * @param   string $path - path to this directory
     * @param   bool $isRecursion - use or not recursion
     * @return  none
     */
    public function setPath($path, $isRecursion='') {
        if(is_dir($path)){
            if(isset($this->_path) && $this->_path!=$path && $this->_path!=''){
                $this->_path = $path;
                if($isRecursion!='')$this->_isRecursion = $isRecursion;
                $this->parseDir();
            } else {
                $this->_path = $path;
            }
        } else {
            throw new Exception($path. 'is not dir.');
        }
    }
    /**
     * Set recursion
     *
     * @param   bool $isRecursion - use or not recursion
     * @return  none
     */
    public function setRecursion($isRecursion) {
        $this->_isRecursion = $isRecursion;
    }
    /**
     * Set level of recursion
     *
     * @param   int $recursionLevel - level of recursion
     * @return  none
     */
    public function setRecursionLevel($recursionLevel) {
        $this->_recursionLevel = $recursionLevel;
    }
    /**
     * get latest dir in the path
     *
     * @param   string $path - path to directory
     * @return  string - latest dir in the path
     */
    public function lastDir() {
        return self::getLastDir($this->getPath());
    }
    /**
     * get latest dir in the path
     *
     * @param   string $path - path to directory
     * @return  string - latest dir in the path
     */
    static public function getLastDir($path) {
        if($path=='') $path = $this->getPath();
        $last = strrpos($path, "/");
        return substr($path,$last+1);
    }
    /**
     * add item to collection
     *
     * @param   IFactory $item - item of collection
     * @return  none
     */
    public function addItem($item) {
        $this->_items[] = $item;
    }
    /**
     * parse this directory
     *
     * @return  none
     */
    protected function parseDir() {
        $this->clear();
        $iter = new RecursiveDirectoryIterator($this->getPath());
        while ($iter->valid()) {
            $curr = (string)$iter->getSubPathname();
            if (!$iter->isDot() && $curr[0]!='.'){
                $this->addItem(self::getFactory($iter->current(),$this->getRecursion(),$this->getRecursionLevel()));
            }
            $iter->next();
        }
    }
    
    static public function getFactory($path,$is_recursion = true,$recurse_level=0) {
        if(is_dir($path)){
            $obj = new Balticode_Livehandler_Model_Directory_Collection($path,$is_recursion,$recurse_level+1);
            return $obj;
        } else {
            return new Balticode_Livehandler_Model_File_Object($path);
        }
    }
    
    
    /**
     * set filter using
     *
     * @param   bool $useFilter - filter using
     * @return  none
     */
    public function useFilter($useFilter) {
        $this->_renderFilters();
        $this->walk('useFilter', array($useFilter));
    }
    /**
     * get files names of current collection
     *
     * @return  array - files names of current collection
     */
    public function filesName() {
        $files = array();
        $this->getFilesName($files);
        return $files;

    }
    /**
     * get files names of current collection
     *
     * @param   array $files - array of files names
     * @return  none
     */
    public function getFilesName(&$files) {
        $this->walk('getFilesName', array(&$files));
    }
    /**
     * get files paths of current collection
     *
     * @return  array - files paths of current collection
     */
    public function filesPaths() {
        $paths = array();
        $this->getFilesPaths($paths);
        return $paths;
    }
    /**
     * get files paths of current collection
     *
     * @param   array $files - array of files paths
     * @return  none
     */
    public function getFilesPaths(&$paths) {
        $this->walk('getFilesPaths', array(&$paths));
    }
    /**
     * get SplFileObject objects of files of current collection
     *
     * @return  array - array of SplFileObject objects
     */
    public function filesObj() {
        $objs = array();
        $this->getFilesObj($objs);
        return $objs;
    }
    /**
     * get SplFileObject objects of files of current collection
     *
     * @param   array $objs - array of SplFileObject objects
     * @return  none
     */
    public function getFilesObj(&$objs) {
        $this->walk('getFilesObj', array(&$objs));
    }
    /**
     * get names of dirs of current collection
     *
     * @return  array - array of names of dirs
     */
    public function dirsName() {
        $dir = array();
        $this->getDirsName($dir);
        return $dir;
    }
    /**
     * get names of dirs of current collection
     *
     * @param   array $dirs - array of names of dirs
     * @return  none
     */
    public function getDirsName(&$dirs) {
        $this->walk('getDirsName', array(&$dirs));
        if($this->getRecursionLevel()>0)
        $dirs[] = $this->getDirName();
    }
    /**
     * set filters for files
     *
     * @param   array $filter - array of filters
     * @return  none
     */
    protected function setFilesFilter($filter) {
        $this->walk('setFilesFilter', array($filter));
    }
    /**
     * display this collection as array
     *
     * @return  array
     */
    public function __toArray() {
        $arr = array();
        $this->toArray($arr);
        return $arr;
    }
    /**
     * display this collection as array
     * @param   array &$arr - this collection array
     * @return  none
     */
    public function toArray($arrRequiredFields = array()) {
        return array();
    }
    /**
     * get this collection as xml
     * @param   bool $addOpenTag - add or not header of xml
     * @param   string $rootName - root element name
     * @return  none
     */
    public function __toXml($addOpenTag=true,$rootName='Struct') {
        $xml='';
        $this->toXml($xml,$addOpenTag,$rootName);
        return $xml;
    }
    /**
     * get this collection as xml
     * @param   string &$xml - xml
     * @param   bool $addOpenTag - add or not header of xml
     * @param   string $rootName - root element name
     * @return  none
     */
    public function toXml() {
        return '';
    }
    /**
     * apply filters
     * @return  none
     */
    protected function _renderFilters() {
        $exts = array();
        $names = array();
        $regName = array();
        foreach ($this->_filters as $filter){
            switch ($filter['field']){
                case 'extension':
                    if(is_array($filter['value'])){
                        foreach ($filter['value'] as $value){
                            $exts[] = $value;
                        }
                    } else {
                        $exts[] = $filter['value'];
                    }
                    break;
                case 'name':
                    if(is_array($filter['value'])){
                        foreach ($filter['value'] as $value){
                            $names[] = $filter['value'];
                        }
                    } else {
                        $names[] = $filter['value'];
                    }
                    break;
                case 'regName':
                    if(is_array($filter['value'])){
                        foreach ($filter['value'] as $value){
                            $regName[] = $filter['value'];
                        }
                    } else {
                        $regName[] = $filter['value'];
                    }
                    break;
            }
        }
        $filter = array();
        if(count($exts)>0) {
            $filter['extension'] = $exts;
        } else {
            $filter['extension'] = null;
        }
        if(count($names)>0) {
            $filter['name']=$names;
        } else {
            $filter['name']=null;
        }
        if(count($regName)>0) {

            $filter['regName']=$regName;
        } else {
            $filter['regName']=null;
        }
        $this->setFilesFilter($filter);
    }
    /**
     * add filter
     * @return  none
     */
    public function addFilter($field, $value, $type = 'and') {
        $filter = array();
        $filter['field']   = $field;
        $filter['value']   = $value;
        $this->_filters[] = $filter;
        $this->_isFiltersRendered = false;
        $this->walk('addFilter',array($field, $value));
        return $this;
    }

    public function count() {
        $this->load();
        return count($this->_items);
        
    }

    public function getIterator() {
        $this->load();
        return new ArrayIterator($this->_items);
        
    }
    /**
     * Load data
     *
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false) {
        return $this;
    }

    /**
     * Load data
     *
     * @return  Varien_Data_Collection
     */
    public function load($printQuery = false, $logQuery = false) {
        return $this->loadData($printQuery, $logQuery);
    }
    /**
     * Clear collection
     *
     * @return Varien_Data_Collection
     */
    public function clear() {
        $this->_setIsLoaded(false);
        $this->_items = array();
        return $this;
    }
    /**
     * Retrieve collection loading status
     *
     * @return bool
     */
    public function isLoaded() {
        return $this->_isCollectionLoaded;
    }

    /**
     * Set collection loading status flag
     *
     * @param unknown_type $flag
     * @return unknown
     */
    protected function _setIsLoaded($flag = true) {
        $this->_isCollectionLoaded = $flag;
        return $this;
    }
    /**
     * Walk through the collection and run model method or external callback
     * with optional arguments
     *
     * Returns array with results of callback for each item
     *
     * @param string $method
     * @param array $args
     * @return array
     */
    public function walk($callback, array $args=array()) {
        $results = array();
        $useItemCallback = is_string($callback) && strpos($callback, '::')===false;
        foreach ($this->getItems() as $id=>$item) {
            if ($useItemCallback) {
                $cb = array($item, $callback);
            } else {
                $cb = $callback;
                array_unshift($args, $item);
            }
            $results[$id] = call_user_func_array($cb, $args);
        }
        return $results;
    }
    
    /**
     * Retrieve collection items
     *
     * @return array
     */
    public function getItems() {
        $this->load();
        return $this->_items;
    }
    

    
}

