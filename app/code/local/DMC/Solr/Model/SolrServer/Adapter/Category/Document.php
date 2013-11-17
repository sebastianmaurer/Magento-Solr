<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Category_Document extends DMC_Solr_Model_SolrServer_Adapter_AbstractDocument
{
    protected $_type = 'category';

    public function setObject($object, $attributes = null)
    {
        try {
            parent::setObject($object);
            Mage::helper('solr/log')->addDebugMessage('Add Category #'.$object->getId().' to index of store '.$this->getStoreId());
                    $staticFields = DMC_Solr_Model_SolrServer_Adapter_Category_TypeConverter::getStaticFields();
            foreach ( $staticFields as $key => $defaultValue ) {
                $methodName = '_get_value_'.$key;
                if(method_exists($this, $methodName)) {
                    $value = $this->{$methodName}($object);
                }
                elseif(is_null($defaultValue)) continue;
                else {
                    $value = $defaultValue;
                }
                if ( is_array( $value ) ) {
                    foreach ( $value as $datum ) {
                        $this->setMultiValue( $key, $datum );
                    }
                }
                else {
                    $this->_fields[$key] = $value;
                }
            }
            if (is_null($attributes)) $attributes = $object->getAttributes();
            
            foreach($attributes as $name => $attribute) {
                $attrCode = $attribute->getAttributeCode();
                
                if(!DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::isStaticField($attrCode)) {
                    $inputType = $attribute->getFrontendInput();
                    $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($inputType);

                    if(isset($typeConverter->solr_index)) {
                        if ($storeId = $object->getStoreId()) $attribute->setStoreId($storeId);
                        $values = $attribute->getFrontend()->getValue($object);
            
                        $indexes = $object->getData($attrCode);
                        if ($attrCode == 'tier_price') {
                            if (is_array($indexes)) {
                                $res = '';
                                foreach ($indexes as $one) {
                                    $res .= $one['website_price'] . ' ';
                                }
                                $indexes = $res;
                            }
                        }
                        else if($inputType === 'multiselect') {
                            $values = is_string($values) && strlen($values) ? explode(',', $values) : array();
                            $indexes = (is_string($indexes) && strlen($indexes)) ? explode(',', $indexes) : array();
                            array_walk($values, array($this, 'itemTrim'));
                            array_walk($indexes, array($this, 'itemTrim'));
                        }
                        elseif($inputType === 'date') {
                            $indexes = $this->dateConvert($indexes);
                        }
            
                        if(($attribute->getData('is_searchable') ||
                                $attribute->getData('used_in_product_listing') ||
                                $attribute->getData('is_visible_in_advanced_search')) && $typeConverter->isSearchable()) {
                            $key = $typeConverter->solr_search_prefix.'search_'.$attrCode;
                            if(is_array($indexes) && count($indexes)) {
            
                                foreach ( $values as $value ) {
                                    $this->setMultiValue( $key, trim($value) );
                                }
                            }
                            elseif(is_string($indexes) && strlen($indexes)) {
                                $this->$key = trim($values);
                            }
                        }
            
                        $key = $typeConverter->solr_index_prefix.'index_'.$attrCode;
            
                        if(is_array($indexes) && count($indexes)) {
            
                            foreach ( $indexes as $index ) {
                                $this->setMultiValue( $key, trim($index) );
                            }
                        }
                        elseif(is_string($indexes) && strlen($indexes)) {
                            $this->$key = trim($indexes);
                        }
            
                        if(    $attribute->getData('used_for_sort_by') && $typeConverter->isSortable()) {
                            $key = $typeConverter->solr_sort_prefix.'sort_'.$attrCode;
            
                            if(is_array($indexes) && count($indexes)) {
            
                                foreach ( $values as $value ) {
                                    $this->setMultiValue( $key, trim($value) );
                                }
                            }
                            elseif(is_string($indexes) && strlen($indexes)) {
                                $this->$key = trim($values);
                            }
                        }
                    }
                }
            }
        }
        catch(DMC_Solr_Model_Catalog_Product_Exception $e) {
            Mage::helper('solr/log')->addDebugMessage($e->getMessage());
            return false;
        }
        return true;
    }

    
    private function _get_value_id($object) {
        return $object->getId();
    }
    
    private function _get_value_name($object) {
        return $object->getName();
    }

    private function _get_value_store_id($object) {
        return $object->getStoreId();
    }

    private function _get_value_value_type_id($object) {
        return $object->getTypeId();
    }

    private function _get_value_description($object) {
        return $object->getDescription();
    }

    private function _get_value_url_path($object) {
        return $object->getUrlPath();
    }
    
    private function _get_value_url($object) {
        return $object->getUrlKey();
    }
	
    public function itemTrim(&$item1, $key) {
        $item1 = trim($item1);
    }
	
    public function dateConvert($item1) {
        $item1 = trim($item1);
        $item1 = str_replace(' ', 'T', $item1);
        return null;
    }
   

}
