<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Cms_Document extends DMC_Solr_Model_SolrServer_Adapter_AbstractDocument
{
    protected $_type = 'cms';

    public function setObject($object)
    {
        try {
            if($object->getSolrIgnore() == 1) {
                throw new DMC_Solr_Model_Catalog_Product_Exception('The cms page #' . $object->getId() . ' was ignored');
            }
            parent::setObject($object);

            Mage::helper('solr/log')->addDebugMessage('Add CMS Page #'.$object->getId().' to index of store '.$this->getStoreId());
            $staticFields = DMC_Solr_Model_SolrServer_Adapter_Cms_TypeConverter::getStaticFields();
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
        }
        catch(DMC_Solr_Model_Catalog_Product_Exception $e) {
            Mage::helper('solr/log')->addDebugMessage($e->getMessage());
            return false;
        }
        return true;
    }

    private function getDynamicAttributeValue($key, $object) {
        return $object->getData($key);
    }

    private function _get_value_id($object) {
        return $object->getId();
    }

    private function _get_value_url($object) {
        $baseUrl = Mage::app()->getStore()->getBaseUrl();
        $url = str_replace($baseUrl, '', Mage::helper('cms/page')->getPageUrl($object->getId()));
        return $url;
    }

    private function _get_value_store_id($object) {
        return $object->getStoreId();
    }

    private function _get_value_attr_t_search_title($object) {
        return $object->getData('title');
    }

    private function _get_value_attr_t_search_content($object) {
        return $object->getData('content');
    }

    private function _get_value_attr_t_search_content_heading($object) {
        return $object->getData('content_heading');
    }
}
?>
