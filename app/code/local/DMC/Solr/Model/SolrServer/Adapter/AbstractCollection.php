<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

$LIB_PATH = Mage::getBaseDir('lib').DS.'DMC'.DS.'Solr';
require_once $LIB_PATH.DS.'Service.php';

class DMC_Solr_Model_SolrServer_Adapter_AbstractCollection extends Varien_Data_Collection
{
    /**
     * DB connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_conn;

    /**
     * Select oblect
     *
     * array(
     *         'query' =>    (array)$query,
     *         'offset' => (int)$offset,
     *        'limit' =>    (int)$limit,
     *        'params' =>    (array)$params,
     * );
     * @var Zend_Db_Select
     */
    protected $_select;

    /**
     * Select oblect
     *
     * @var Zend_Db_Select
     */
    protected $_entity;

    /**
     * Identifier fild name for collection items
     *
     * Can be used by collections with items without defined
     *
     * @var string
     */
    protected $_idFieldName;

    /**
     * List of binded variables for select
     *
     * @var array
     */
    protected $_bindParams = array();

    /**
     * All collection data array
     * Used for getData method
     *
     * @var array
     */
    protected $_data = null;

    /**
     * Product websites table name
     *
     * @var string
     */
    protected $_productWebsiteTable;

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

    /**
     * Product limitation filters
     *
     * Allowed filters
     *  store_id                int;
     *  category_id             int;
     *  category_is_anchor      int;
     *  visibility              array|int;
     *  website_ids             array|int;
     *  store_table             string;
     *  use_price_index         bool;   join price index table flag
     *  customer_group_id       int;    required for price; customer group limitation for price
     *  website_id              int;    required for price; website limitation for price
     *
     * @var array
     */
    protected $_productLimitationFilters    = array();

    /**
     * Category product count select
     *
     * @var Zend_Db_Select
     */
    protected $_productCountSelect = null;

    /**
     * @var bool
     */
    protected $_isWebsiteFilter = false;

    protected $_searchableAttributes = NULL;

    protected $_totalRecords = NULL;

    protected $_storeId = null;

    protected $_facetCategoryCount = null;

    protected $_priceStats = null;

    protected $_setIds = null;

    public function __construct($conn=null)
    {
        parent::__construct();
        $this->_select = new DMC_Solr_Model_SolrServer_Select;
    }

    protected function _init($model, $entityModel=null)
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
    public function setEntity($entity)
    {
        if ($entity instanceof Mage_Eav_Model_Entity_Abstract) {
            $this->_entity = $entity;
        } elseif (is_string($entity) || $entity instanceof Mage_Core_Model_Config_Element) {
            $this->_entity = Mage::getModel('eav/entity')->setType($entity);
        } else {
            Mage::throwException(Mage::helper('eav')->__('Invalid entity supplied: %s', print_r($entity,1)));
        }
        return $this;
    }

    public function setStore($store)
    {
        $this->setStoreId(Mage::app()->getStore($store)->getId());

        return $this;
    }

    public function setStoreId($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }
        $this->_storeId = $storeId;
        $this->_productLimitationFilters['store_id'] = $this->_storeId;
        return $this;
    }

    protected function _renderFilters() {
        parent::_renderFilters();
        return $this;
    }

    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
        return $this->_storeId;
    }

    public function getDefaultStoreId()
    {
        return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }

    /**
     * Add variable to bind list
     *
     * @param string $name
     * @param mixed $value
     * @return Varien_Data_Collection_Db
     */
    public function addBindParam($name, $value)
    {
        $this->_bindParams[$name] = $value;
        return $this;
    }

    /**
     * Specify collection objects id field name
     *
     * @param string $fieldName
     * @return Varien_Data_Collection_Db
     */
    protected function _setIdFieldName($fieldName)
    {
        $this->_idFieldName = $fieldName;
        return $this;
    }

    /**
     * Id field name getter
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->_idFieldName;
    }

    /**
     * Get collection item identifier
     *
     * @param Varien_Object $item
     * @return mixed
     */
    protected function _getItemId(Varien_Object $item)
    {
        if ($field = $this->getIdFieldName()) {
            return $item->getData($field);
        }
        return parent::_getItemId($item);
    }

    /**
     * Set database connection adapter
     *
     * @param Zend_Db_Adapter_Abstract $conn
     * @return Varien_Data_Collection_Db
     */
    public function setConnection()
    {
        $this->_conn = Mage::helper('solr')->getSolr();
        return $this->_conn;
    }

    /**
     * Get Zend_Db_Select instance
     *
     * @return Varien_Db_Select
     */
    public function getSelect()
    {
        return $this->_select;
    }

    /**
     * Retrieve connection object
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getConnection()
    {
        if(is_null($this->_conn)) {
            $this->setConnection();
        }
        return $this->_conn;
    }

    /**
     * Get collection size
     *
     * @return int
     */
    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            $select = clone $this->_select;
            $select->addField('id');
            $this->_fetchAll($select);
        }
        return intval($this->_totalRecords);
    }

    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(DMC_Solr_Model_SolrServer_Select::PARAMS);
        $countSelect->reset(DMC_Solr_Model_SolrServer_Select::LIMIT);
        $countSelect->reset(DMC_Solr_Model_SolrServer_Select::OFFSET);

        return $countSelect;
        return 1;
    }

    /**
     * Get sql select string or object
     *
     * @param   bool $stringMode
     * @return  string || Zend_Db_Select
     */
    function getSelectSql($stringMode = false)
    {
        echo $this->getConnection()->getSearchUrl($this->_select['query'], $this->_select['offset'], $this->_select['limit'], $this->getParams());
    }

    /**
     * self::setOrder() alias
     *
     * @param string $field
     * @param string $direction
     * @return Varien_Data_Collection_Db
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        return $this->_setOrder($field, $direction);
    }

    /**
     * Add select order to the beginning
     *
     * @param string $field
     * @param string $direction
     * @return Varien_Data_Collection_Db
     */
    public function unshiftOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        return $this->_setOrder($field, $direction, true);
    }

    /**
     * Add field filter to collection
     *
     * If $attribute is an array will add OR condition with following format:
     * array(
     *     array('attribute'=>'firstname', 'like'=>'test%'),
     *     array('attribute'=>'lastname', 'like'=>'test%'),
     * )
     *
     * @see self::_getConditionSql for $condition
     * @param string|array $attribute
     * @param null|string|array $condition
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addFieldToFilter($field, $condition=null)
    {
        $this->getSelect()->where($field, $condition);
        return $this;
    }

    /**
     * Render sql select orders
     *
     * @return  Varien_Data_Collection_Db
     */
    protected function _renderOrders()
    {
        foreach ($this->_orders as $orderExpr) {
            $this->_select->order($orderExpr);
        }
        return $this;
    }

    /**
     * Render sql select limit
     *
     * @return  Varien_Data_Collection_Db
     */
    protected function _renderLimit()
    {
        if($this->_pageSize){
            $this->_select->limitPage($this->getCurPage(), $this->_pageSize);
        }

        return $this;
    }

    /**
     * Set select distinct
     *
     * @param bool $flag
     */
    public function distinct($flag)
    {
        $this->_select->distinct($flag);
        return $this;
    }

    /**
     * Load data
     *
     * @return  Varien_Data_Collection_Db
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_renderFilters()
             ->_renderOrders()
             ->_renderLimit();


        $data = $this->getData();
        $this->resetData();



        // The following loops all data we got from solr
        // and makes (product) objects in the magento style.
        // To achieve this, the TypeConverter just removes the 
        // type prefixes (like attr_s_index_) from the returned attributes
        // and we get the same attribute keys as we originally have in magento.
        if (is_array($data)) {
            $setIds = array();
            foreach ($data as $row) {
                $item = $this->getNewEmptyItem();
                if ($this->getIdFieldName()) {
                    $item->setIdFieldName($this->getIdFieldName());
                }
                $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter();
                foreach($row as $key => $value) {
                    $productAttrName = $typeConverter->getProductAttributeName($key);
                    if(!is_null($productAttrName)) {
                        $row[$productAttrName] = $value;
                    }
                }

                $row['entity_id']       = $row['id'];
                $row['page_id']         = $row['id'];
                $row['request_path']    = $row['rewrite_path'];

                #echo '<pre>';
                #print_r($row);
                #echo '</pre>';

                $item->addData($row);
                $this->addItem($item);
            }
        }

        $this->_setIsLoaded();
        $this->_afterLoad();
        return $this;
    }

    /**
     * Proces loaded collection data
     *
     * @return Varien_Data_Collection_Db
     */
    protected function _afterLoadData()
    {
        return $this;
    }

    /**
     * Reset loaded for collection data array
     *
     * @return Varien_Data_Collection_Db
     */
    public function resetData()
    {
        $this->_data = null;
        return $this;
    }

    protected function _afterLoad()
    {
        return $this;
    }

    public function loadData($printQuery = false, $logQuery = false)
    {
        return $this->load($printQuery, $logQuery);
    }

    /**
     * Reset collection
     *
     * @return Varien_Data_Collection_Db
     */
    protected function _reset()
    {
        $this->getSelect()->reset();
        $this->_initSelect();
        $this->_setIsLoaded(false);
        $this->_items = array();
        $this->_data = null;
        return $this;
    }

    /**
     * Retrieve EAV Config Singleton
     *
     * @return Mage_Eav_Model_Config
     */
    public function getEavConfig ()
    {
        return Mage::getSingleton('eav/config');
    }


    /**
     * Fetch collection data
     *
     * @param   Zend_Db_Select $select
     * @return  array
     */
    /*
    protected function _fetchAll($select)
    {
        $select->param('facet', 'true');
        $select->param('facet.field', 'available_category_ids');
        $product = Mage::getModel('catalog/product');
		$dataObject = $this->getConnection()->fetchAll($select);
        $data = $dataObject->__get('response');
        $facet = $dataObject->__get('facet_counts');
        $this->_facetCategoryCount = $facet->facet_fields->available_category_ids;
        $this->_totalRecords = $data->numFound;
        $fields = null;
        $retData = array();
        foreach($facet->facet_fields as $name=>$value) {
            $this->_statistic[$name] = $value;
        }

        foreach($data->docs as $row) {
            if(is_null($fields)) $fields = $row->getFieldNames();
            $retRow = array();
            foreach($fields as $field) {
                $retRow[$field] = $row->$field;
            }
            $retData[] = $retRow;
        }
        return $retData;
    }

    */

    protected function _fetchStatistic($select)
    {
        $select->addField('row_id');
        $select->addField('row_type');
        $select->addField('attribute_set_id');
        $dataObject = $this->getConnection()->fetchAll($select);
        $data = $dataObject->__get('response');
        $fields = null;
        $retData = array();

        $this->_totalRecords = $data->numFound;

        if(count($data->docs)) {
            $setIds = array();
            foreach($data->docs as $row) {
                $setIds[] = $row->attribute_set_id;
            }
            $this->_setIds = array_unique($setIds);
        }
    }

    /**
     * Get all data array for collection
     *
     * @return array
     */
    public function getData()
    {
        if ($this->_data === null) {
            $this->_data = $this->_fetchAll($this->_select);
            $this->_afterLoadData();
        }
        return $this->_data;
    }

    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return Mage_CatalogSearch_Model_Mysql4_Fulltext_Collection
     */
    public function setOrder($attributeCode, $dir='desc')
    {
        if ($attributeCode == 'relevance') {
            $this->getSelect()->order(array('field' => 'score', 'direct'=>$dir));
        }
        elseif ($attributeCode == 'position') {
            if(isset($this->_productLimitationFilters['category_id'])) {
                $field = DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_POSITION.$this->_productLimitationFilters['category_id'];
                $this->getSelect()->order(array('field' => $field, 'direct'=>$dir));
            }
        }
        else {
            $entityType = $this->getEavConfig()->getEntityType('catalog_product');
            $attribute = Mage::getModel('catalog/entity_attribute')->loadByCode($entityType, $attributeCode);
            $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($attribute->getFrontend()->getInputType());
            $field = $typeConverter->solr_index_prefix.DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_SORT.$attributeCode;
            $this->getSelect()->order(array('field' => $field, 'direct'=>$dir));
        }
        return $this;
    }

    /**
     * Add attribute to entities in collection
     *
     * If $attribute=='*' select all attributes
     *
     * @param   array|string|integer|Mage_Core_Model_Config_Element $attribute
     * @param   false|string $joinType flag for joining attribute
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addAttributeToSelect($attribute, $joinType=false)
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
     * @param   Mage_Eav_Model_Entity_Collection_Abstract $categoryCollection
     * @return  Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function addCountToCategories($categoryCollection)
    {
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

    public function addCategoryFilter(Mage_Catalog_Model_Category $category)
    {
        $this->_productLimitationFilters['category_id'] = $category->getId();
        if ($category->getIsAnchor()) {
            $this->getSelect()->where('available_category_ids:'.$category->getId());
        }
        else {
            $this->getSelect()->where('available_category_ids:'.$category->getId());
        }

        return $this;
    }

    /**
     * Add search query filter
     *
     * @param   Mage_CatalogSearch_Model_Query $query
     * @return  Mage_CatalogSearch_Model_Mysql4_Search_Collection
     */
    public function addSearchFilter($query = NULL)
    {
        if(is_null($query)) $query = Mage::helper('solr')->getQuery();
        $where = '';
        $query = Apache_Solr_Service::escape($query);
        $query = $this->addFuzzySearch($query);
        foreach($this->_searchableAttributes as $attribute) {
            $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($attribute->getFrontend()->getInputType());
            if(isset($typeConverter->solr_search) && $typeConverter->solr_search) {
                $code = $attribute->getAttributeCode();
                $field = $typeConverter->solr_search_prefix.DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter::SUBPREFIX_SEARCH.$attribute->getAttributeCode();
                $where = strlen($where) ? $where.' OR '.$field.':'.$query : $field.':'.$query;
            }
        }
        $this->getSelect()->where($where);
        return $this;
    }

    /**
     * Add store availability filter. Include availability product
     * for store website
     *
     * @param   mixed $store
     * @return  Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addStoreFilter($store=null)
    {
        if (is_null($store)) {
            $store = $this->getStoreId();
        }
        $store = Mage::app()->getStore($store);

        if (!$store->isAdmin()) {
            $this->setStoreId($store);
            $where = 'store_id:'.$this->getStoreId();
            $this->getSelect()->where($where);
            return $this;
        }

        return $this;
    }

    /**
     * Add website filter to collection
     *
     * @param Mage_Core_Model_Website|int|string|array $website
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addWebsiteFilter($websites = null)
    {
        if (!is_array($websites)) {
            $websites = array(Mage::app()->getWebsite($websites)->getId());
        }

        $this->_productLimitationFilters['website_ids'] = $websites;
        $this->_applyProductLimitations();

        return $this;
    }

    /**
     * Add price filter to collection
     *
     * @param Mage_Core_Model_Website|int|string|array $website
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addPriceFilter($index, $range, $rate)
    {
        $from = $range * ($index - 1);
        $to = $range * $index;
        $this->getSelect()->where('price:['.$from.' TO '.$to.']');
    }

    public function applyFilterToCollection($cond, $condition = null) {
        $this->getSelect()->where($cond, $condition);
    }

    /**
     * Add Price Data to result
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addPriceData($customerGroupId = null, $websiteId = null)
    {
        return $this;
    }

    /**
     * Set product visibility filter for enabled products
     *
     * @param array $visibility
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function setVisibility($visibility)
    {
        if(is_numeric($visibility)) {
            (array)$visibility;
        }

        if(isset($visibility) && is_array($visibility)) {
            foreach($visibility as $item) {
                $parts[] = 'visibility:'.$item;
            }
            $this->getSelect()->where(implode(' OR ', $parts));
        }
    }

    public function addUrlRewrite() {
        return $this;
    }

    public function getSetIds()
    {
        if(is_null($this->_setIds)) {
            $this->_renderFilters()
                 ->_renderOrders()
                 ->_renderLimit();

            $select = clone $this->_select;
            $this->_fetchStatistic($select);
        }
        return $this->_setIds;
    }

    public function getPriceStats()
    {
        return $this->_priceStats;
    }

    public function getAttributeCount($name) {
        $attrName = '_'.$name.'Count';
        if(isset($this->$attrName)){
            return $this->$attrName;
        }
        else return NULL;
    }
}
