<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Cms_TypeConverter extends DMC_Solr_Model_SolrServer_TypeConverter
{
    static protected $_staticFields = array(
        'id' => null,
        'store_id' => null,
        'url' => null,
        'attr_t_search_title' => '',
        'attr_t_search_content' => '',
        'attr_t_search_content_heading' => '',
        'attr_t_search_title' => '',
    );
    
    static public $staticMapping = array();
	
    static public function getStaticFields() {
        return self::$_staticFields;
    }
}
?>
