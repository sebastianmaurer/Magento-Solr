<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Landingpage_Document extends DMC_Solr_Model_SolrServer_Adapter_AbstractDocument
{
    protected $_type = 'landingpage';

    public function setObject($object, $attributes = null)
    {
        try {
            parent::setObject($object);
            Mage::helper('solr/log')->addDebugMessage('Add Landingpage #'.$object->getId().' to index of store '.$this->getStoreId());
                    $staticFields = DMC_Solr_Model_SolrServer_Adapter_Landingpage_TypeConverter::getStaticFields();
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
        return $object->getData('query_text');
    }

    private function _get_value_store_id($object) {
        return $object->getStoreId();
    }

    private function _get_value_value_type_id($object) {
        return $object->getTypeId();
    }

    private function _get_value_redirect_url($object) {
        return $object->getRedirect();
    }
    
    public function getRedirectURL()
    {
        $tmp=$this->_fields;
        return $tmp['redirect_url'];
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
