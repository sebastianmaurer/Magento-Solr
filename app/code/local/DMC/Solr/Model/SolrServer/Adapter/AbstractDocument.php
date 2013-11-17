<?php

class DMC_Solr_Model_SolrServer_Adapter_AbstractDocument implements Iterator
{
    protected $_type = null;

    protected $_fields = array();

    protected $_storeId = null;

    protected $_object = null;

    protected $_object_id = null;

    public function __construct()
    {
        $this->_fields['row_type'] = $this->getType();
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function __get($key)
    {
        if(isset($this->_fields[$key])) return $this->_fields[$key];
        return NULL;
    }

    public function __set($key, $value)
    {
        $this->_fields[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->_fields[$key]);
    }

    public function __unset($key)
    {
        unset($this->_fields[$key]);
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setMultiValue($key, $value)
    {
        if (!isset($this->_fields[$key]))
        {
            $this->_fields[$key] = array();
        }

        if (!is_array($this->_fields[$key]))
        {
            $this->_fields[$key] = array($this->_fields[$key]);
        }

        $this->_fields[$key][] = $value;
    }

    public function getFieldNames()
    {
        return array_keys($this->_fields);
    }

    public function rewind() {
        reset($this->_fields);
    }

    public function current() {
        return current($this->_fields);
    }

    public function key() {
        return key($this->_fields);
    }

    public function next() {
        return next($this->_fields);
    }

    public function valid() {
        return current($this->_fields) !== false;
    }

    public function getReindexer()
    {
        if(is_null($this->_reindexer)) {
            $this->_reindexer = DMC_Solr_Document_Reindexer::getInstance();
        }
        return $this->_reindexer;
    }

    protected function _getUniqueRowId()
    {
        if ($this->_object_id) {
            return $this->getType().'_'.$this->getStoreId().'_'.$this->_object_id;
        }

        return null;
    }

    public function getRowId()
    {
        return $this->_getUniqueRowId();
    }

    public function setObject($object)
    {
        $this->_object_id = $object->getId();
        $this->_fields['row_id'] = $this->_getUniqueRowId();
    }

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        $this->_fields['row_id'] = $this->_getUniqueRowId();
        $this->_fields['store_id'] = $this->_storeId;
    }

    public function getObject()
    {
        return $this->_object;
    }
}
