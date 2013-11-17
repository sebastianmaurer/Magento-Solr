<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter extends DMC_Solr_Model_SolrServer_TypeConverter
{

    static protected $_staticFields = array(
        'id' => null,
        'type_id' => 'simple',
        'store_id' => null,
        'url' => null,
        'thumb'=>null,
        'category_ids' => null,
        'available_category_ids' => null,
        'in_stock' => null,
        'rewrite_path' => '',
        'visibility' => '',
        'attribute_set_id' => '',
        'is_salable' => null,
        'status' => null,
        'image'=>'',
        'small_image'=>'',
        'group_price'=>null
    );
    
    static public function getStaticFields() {
        return self::$_staticFields;
    }
}