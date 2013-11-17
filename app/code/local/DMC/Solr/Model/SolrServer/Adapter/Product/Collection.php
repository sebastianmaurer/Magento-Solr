<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Product_Collection extends DMC_Solr_Model_SolrServer_Adapter_AbstractCollection
{

    const SPELLCHECK_OFF = 0;

    const SPELLCHECK_AUTO = 1;

    const SPELLCHECK_SUGGEST = 2;


    /**
     * Product categories table name
     *
     * @var string
     */
    protected $_productCategoryTable;

    /**
     * Is add URL rewrites to collection flag
     *
     * @var bool
     */
    protected $_addUrlRewrite = false;

    /**
     * Add URL rewrite for category
     *
     * @var int
     */
    protected $_urlRewriteCategory = '';

    /**
     * Is add minimal price to product collection flag
     *
     * @var bool
     */
    protected $_addMinimalPrice = false;

    /**
     * Is add final price to product collection flag
     *
     * @var unknown_type
     */
    protected $_addFinalPrice = false;

    /**
     * Is add tax percents to product collection flag
     *
     * @var bool
     */
    protected $_addTaxPercents = false;

    protected $_statistic = array();

    protected $_suggest = false;

    protected $_productLimitationFilters = array();

    protected $_facetCategoryCount = null;

    protected $_priceStats = null;

    protected $_setIds = null;

    public function __construct ($conn = null)
    {
        parent::__construct();
        $this->_init('catalog/product');
        $this->_select = new DMC_Solr_Model_SolrServer_Select();
        $this->_getSearchableAttributes();
    }

    protected function _init ($model, $entityModel = null)
    {
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName($model));
        if (is_null($entityModel)) {
            $entityModel = $model;
        }
        $entity = Mage::getResourceSingleton($entityModel);
        $this->setEntity($entity);
        return $this;
    }


    /**
     * Set entity to use for attributes
     *
     * @param Mage_Eav_Model_Entity_Abstract $entity            
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function setEntity ($entity)
    {
        if ($entity instanceof Mage_Eav_Model_Entity_Abstract) {
            $this->_entity = $entity;
        } elseif (is_string($entity) ||
                 $entity instanceof Mage_Core_Model_Config_Element) {
            $this->_entity = Mage::getModel('eav/entity')->setType($entity);
        } else {
            Mage::throwException(
                    Mage::helper('eav')->__('Invalid entity supplied: %s', 
                            print_r($entity, 1)));
        }
        return $this;
    }


    /**
     * Fetch collection data
     *
     * @param Zend_Db_Select $select            
     * @return array
     */
    protected function _fetchAll ($select)
    {
        $select->param('facet', 'true');
        $select->param('facet.field', 'available_category_ids');

        //$product = Mage::getModel('catalog/product');

        $dataObject = $this->getConnection()->fetchAll($select);

        $data = $dataObject->__get('response');
        $facet = $dataObject->__get('facet_counts');
        
        $this->_facetCategoryCount = $facet->facet_fields->available_category_ids;
        $this->_totalRecords = $data->numFound;
        
        $fields = null;
        $retData = array();
        
        foreach ($facet->facet_fields as $name => $value) {
            $this->_statistic[$name] = $value;
        }
        
        foreach ($data->docs as $row) {
            if (is_null($fields))
                $fields = $row->getFieldNames();
            $retRow = array();
            foreach ($fields as $field) {
                $retRow[$field] = $row->$field;
            }
            $retData[] = $retRow;
        }
        return $retData;
    }

    public function getStatistic()
    {
        return $this->_statistic;
    }

    protected function _fetchStatistic ($select)
    {
        $select->addField('row_id');
        $select->addField('row_type');
        $select->addField('attribute_set_id');
        
        $dataObject = $this->getConnection()->fetchAll($select);
        $data = $dataObject->__get('response');
        $fields = null;
        $retData = array();
        
        $this->_totalRecords = $data->numFound;
        $suggestion = $dataObject->__get('spellcheck');
        
        if (Mage::getStoreConfig('solr/spellcheck_flag/spellcheck') !=
                 self::SPELLCHECK_OFF) {
            if ($this->_totalRecords == 0 && is_array($suggestion) &&
             ! $this->_suggest) {
        if (! isset($suggestion['collation'])) {
            return;
        }
        $this->_suggest = true;
        $misspelled = implode(' ', $suggestion['misspelled']);
        $suggest = array();
        $helper = Mage::helper('solr');
        
        if (Mage::getStoreConfig('solr/spellcheck_flag/spellcheck') ==
                 self::SPELLCHECK_AUTO) {
            foreach ($suggestion['collation'] as $item) {
                $tmp = $item[Apache_Solr_Response::SPELLCHECK_SUGGEST];
                $suggest['suggest'][] = $tmp;
                $suggest['term'][] = "({$tmp})";
            }
            $suggest['suggest'] = implode(
                    $helper->getTranslation(", "), 
                    $suggest['suggest']);
            $note = $helper->getTranslation(
                    "Your Search got corrected from ");
            $note .= "<span class=\"highlight\">{$misspelled}</span>";
            $note .= $helper->getTranslation(" to ");
            $note .= "<span class=\"highlight\">{$suggest['suggest']}</span>";
            $helper->setQueryText(
                    implode(' OR ', $suggest['term']));
            self::_fetchStatistic($select);
            $helper->setSuggest($suggest['suggest']);
        } elseif (Mage::getStoreConfig('solr/spellcheck_flag/spellcheck') ==
                 self::SPELLCHECK_SUGGEST) {
            $note = $helper->getTranslation(
                    "But there are Results for: ");
            foreach ($suggestion['collation'] as $item) {
                $suggest['suggesthits'] = $item[Apache_Solr_Response::SPELLCHECK_HITS];
                $suggest['suggest'] = $item[Apache_Solr_Response::SPELLCHECK_SUGGEST];
                $suggest['suggestlink'] = "/catalogsearch/result/?q={$suggest['suggest']}";
                
                $note .= "<a href=\"{$suggest['suggestlink']}\"><span class=\"highlight\">{$suggest['suggest']} ({$suggest['suggesthits']})</span></a> ";
            }
        }
    }
    }
        if (! empty($suggest['suggest'])) {
            Mage::helper('catalogsearch')->addNoteMessage($note);
        }
        if (count($data->docs)) {
            $setIds = array();
            foreach ($data->docs as $row) {
                $setIds[] = $row->attribute_set_id;
            }
            $this->_setIds = array_unique($setIds);
        }
    }


    /**
     * Set Order field
     *
     * @param string $attribute            
     * @param string $dir            
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection
     */
    public function setOrder ($attributeCode, $dir = 'desc')
    {
    // if ($attributeCode == 'relevance') {
        if (Mage::getStoreConfig('solr/elevate_settings/boosting_enable')) {
            $this->getSelect()->order(
                    array(
                            'field' => Mage::getStoreConfig(
                                    'solr/elevate_settings/boosting_attribute'),
                            'direct' => Mage::getStoreConfig(
                                    'solr/elevate_settings/sort_order')
                    ));
        }
        $this->getSelect()->order(
            array(
                    'field' => 'score',
                    'direct' => 'desc'
            ));
        /*
         * } elseif ($attributeCode == 'position') { if
         * (isset($this->_productLimitationFilters['category_id'])) { $field =
         * DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_POSITION .
         * $this->_productLimitationFilters['category_id']; $this->getSelect()->order(
         * array( 'field' => $field, 'direct' => $dir )); } } else { $entityType =
         * $this->getEavConfig()->getEntityType( 'catalog_product'); $attribute =
         * Mage::getModel('catalog/entity_attribute')->loadByCode( $entityType,
         * $attributeCode); $typeConverter = new
         * DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter(
         * $attribute->getFrontend()->getInputType()); $field =
         * $typeConverter->solr_index_prefix .
         * DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_SORT .
         * $attributeCode; $this->getSelect()->order( array( 'field' => $field, 'direct'
         * => $dir )); }
         */
        return $this;
    }

    /**
     * Add attribute to entities in collection
     *
     * If $attribute=='*' select all attributes
     *
     * @param array|string|integer|Mage_Core_Model_Config_Element $attribute            
     * @param false|string $joinType
     *            flag for joining attribute
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addAttributeToSelect ($attribute, $joinType = false)
    {
        if (is_array($attribute)) {
            foreach ($attribute as $a) {
                $this->addAttributeToSelect($a, $joinType);
            }
            return $this;
        }
        $this->_selectAttributes[] = $attribute;

        return $this;
    }

    /**
     * Adding product count to categories collection
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract $categoryCollection            
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addCountToCategories ($categoryCollection)
    {
        $this->load();
        $isAnchor = array();
        $isNotAnchor = array();

        foreach ($categoryCollection as $category) {
            $_count = 0;
            $productCounts = $this->_facetCategoryCount;
            if (isset($productCounts[$category->getId()])) {
                $_count = $productCounts[$category->getId()];
            }
            $category->setProductCount($_count);
        }
        return $this;
        }

        public function addCategoryFilter (Mage_Catalog_Model_Category $category)
        {
        $this->_productLimitationFilters['category_id'] = $category->getId();
        if ($category->getIsAnchor()) {
            $this->getSelect()->where('available_category_ids:' . $category->getId());
        } else {
            $this->getSelect()->where('available_category_ids:' . $category->getId());
        }

        return $this;
    }

   

  

    /**
     * NOT IN USE FOR NOW
     * Add search query filter
     *
     * @param Mage_CatalogSearch_Model_Query $query            
     * @return DMC_Solr_Model_SolrServer_Adapter_Product_Collection
     */
    public function addSearchFilter ($query = NULL)
    {
        //die('/Users/sebastian/Documents/code/solr-modul/app/code/local/DMC/Solr/Model/SolrServer/Adapter/Product/Collection.php//addSearchFilter');

        if (is_null($query))
            $query = Mage::helper('solr')->getQuery();
        $where = '';
        $query = self::escape($query);
        $words = preg_split("/\s+/", $query);
        if (count($words) > 1) {
            $proximity_search = Mage::helper('solr')->getProximitySearch();
            $nquery = ($proximity_search) ? '("' : '(';
            $nquery .= implode(' ', $words);
            $nquery .= ($proximity_search) ? '"~' . $proximity_search . ')' : ')';
            $query = $nquery;
        } else {
            $query = $this->addFuzzySearch($query);
        }

        foreach ($this->_searchableAttributes as $attribute) {
            $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter(
                    $attribute->getFrontend()->getInputType());
            if (isset($typeConverter->solr_search) && $typeConverter->solr_search) {
                $code = $attribute->getAttributeCode();
                $field = $typeConverter->solr_search_prefix .
                         DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_SEARCH .
                         $attribute->getAttributeCode();
                $where = strlen($where) ? $where . ' OR ' . $field . ':' . $query : $field .
                         ':' . $query;
            }
        }
        echo '- addSearchFilter ->'.$query;

        $this->getSelect()->where($query);
        return $this;
    }

    static public function escape ($value)
    {
        // list taken from
        // http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
        $pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
        $replace = '\\\$1';

        return preg_replace($pattern, $replace, $value);
    }

    /**
     * NOT IN USE FOR NOW
     *
     * Retrieve Searchable attributes
     * @return array
     */
    protected function _getSearchableAttributes ($backendType = null)
    {
        if (is_null($this->_searchableAttributes)) {
            $this->_searchableAttributes = array();
            $entityType = $this->getEavConfig()->getEntityType(
                    'catalog_product');
            
            $resource = Mage::getModel(
                    'Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection');
            
            $whereCond = array(
                    $resource->getConnection()->quoteInto(
                            'additional_table.is_searchable=?', 1),
                    $resource->getConnection()->quoteInto(
                            'main_table.attribute_code IN(?)', 
                            array(
                                    'status',
                                    'visibility'
                            ))
            );
            
            $select = $resource->getConnection()
                ->select()
                ->from(
                    array(
                            'main_table' => $resource->getTable('eav/attribute')
                    ))
                ->join(
                    array(
                            'additional_table' => $resource->getTable(
                                    'catalog/eav_attribute')
                    ), 'additional_table.attribute_id = main_table.attribute_id')
                ->where('main_table.entity_type_id=?', 
                    $entityType->getEntityTypeId())
                ->where(join(' OR ', $whereCond));
            
            $attributesData = $resource->getConnection()->fetchAll($select);

            foreach ($attributesData as $attributeData) {
                $attribute = Mage::getModel('catalog/entity_attribute')->loadByCode(
                        $entityType, $attributeData['attribute_code']);
                $this->_searchableAttributes[$attribute->getId()] = $attribute;
            }
            unset($attributesData);
        }
        if (! is_null($backendType)) {
            $attributes = array();
            foreach ($this->_searchableAttributes as $attribute) {
                if ($attribute->getBackendType() == $backendType) {
                    $attributes[$attribute->getId()] = $attribute;
                }
            }
            return $attributes;
        }
        return $this->_searchableAttributes;
    }

    /**
     * Add price filter to collection
     *
     * @param Mage_Core_Model_Website|int|string|array $website            
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addPriceFilter ($index, $range, $rate)
    {
        $from = $range * ($index - 1);
        $to = $range * $index;
        $this->getSelect()->where('price:[' . $from . ' TO ' . $to . ']');
    }

    public function applyFilterToCollection ($cond, $condition = null)
    {
        $this->getSelect()->where($cond, $condition);
    }

/**
 * Add Price Data to result
 *
 * @param int $customerGroupId            
 * @param int $websiteId            
 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
 */
public function addPriceData ($customerGroupId = null, $websiteId = null)
{
return $this;
}

/**
 * Set product visibility filter for enabled products
 *
 * @param array $visibility            
 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
 */
public function setVisibility ($visibility)
{
if (is_numeric($visibility)) {
    (array) $visibility;
}

if (isset($visibility) && is_array($visibility)) {
    foreach ($visibility as $item) {
        $parts[] = 'visibility:' . $item;
    }
    $this->getSelect()->where(implode(' OR ', $parts));
}
}

public function addUrlRewrite ()
{
return $this;
}

public function getSetIds ()
{
if (is_null($this->_setIds)) {
    $this->_renderFilters()
        ->_renderOrders()
        ->_renderLimit();
    
    $select = clone $this->_select;
    $this->_fetchStatistic($select);
}
return $this->_setIds;
}

public function getPriceStats ()
{
return $this->_priceStats;
}

public function getAttributeCount ($name)
{
$attrName = '_' . $name . 'Count';
if (isset($this->$attrName)) {
    return $this->$attrName;
} else
    return NULL;
}


    public function dumpSelect ()
    {
        $this->_dumpedSelect = clone $this->getSelect();
    }
}
