<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_TypeConverter
{
    const SUBPREFIX_SEARCH = 'search_';
    const SUBPREFIX_INDEX = 'index_';
    const SUBPREFIX_SORT = 'sort_';
    const SUBPREFIX_POSITION = 'position_';

    static protected $_staticFields = array( );
	static public $staticMapping = array();

    private $_items = array(
        'text' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_',
                        'solr_index' => true,
                        'solr_index_type' => 'string',
                        'solr_index_prefix' => 'attr_s_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'string',
                        'solr_sort_prefix' => 'attr_s_',
                    ),
        'textarea' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_',
                        'solr_index' => true,
                        'solr_index_type' => 'string',
                        'solr_index_prefix' => 'attr_s_',
                        'solr_sort' => false,
                    ),
        'boolean' => array(
                        'solr_search' => false,
                        'solr_index' => true,
                        'solr_index_type' => 'boolean',
                        'solr_index_prefix' => 'attr_b_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'boolean',
                        'solr_sort_prefix' => 'attr_b_',
                    ),
        'select' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_',
                        'solr_index' => true,
                        'solr_index_type' => 'int',
                        'solr_index_prefix' => 'attr_s_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'string',
                        'solr_sort_prefix' => 'attr_s_',
                    ),
        'multiselect' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_m_',
                        'solr_index' => true,
                        'solr_index_type' => 'int',
                        'solr_index_prefix' => 'attr_s_m_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'string',
                        'solr_sort_prefix' => 'attr_s_m_',
                    ),
        'date' => array(
                        'solr_search' => false,
                        'solr_index' => true,
                        'solr_index_type' => 'date',
                        'solr_index_prefix' => 'attr_dt_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'date',
                        'solr_sort_prefix' => 'attr_dt_',
                    ),
        'price' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_',
                        'solr_index' => true,
                        'solr_index_type' => 'float',
                        'solr_index_prefix' => 'attr_f_',
                        'solr_sort' => true,
                        'solr_sort_type' => 'float',
                        'solr_sort_prefix' => 'attr_f_',
                    ),
        'media_image' => array(
                        'solr_search' => true,
                        'solr_search_type' => 'textgen',
                        'solr_search_prefix' => 'attr_tg_',
                        'solr_index' => true,
                        'solr_index_type' => 'string',
                        'solr_index_prefix' => 'attr_s_',
                        'solr_sort' => false,
        ),
        'weee' => NULL,

    );
	
	public function getItems()
	{
		return $this->_items;
	}
	
    public function __construct($type = NULL) {
        if(!is_null($type)) $this->setType($type);
    }

    static public function isStaticField($code) {
        return array_key_exists($code, self::$_staticFields);
    }

    static public function getStaticFields() {
        return self::$_staticFields;
    }

    public function setType($type) {
        $this->clear();
        if(isset($this->_items[$type])) {
            $this->_type = $type;
            foreach($this->_items[$type] as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function clear() {
        $this->_type = NULL;
        foreach($this->_items as $key => $value) {
            if(isset($this->$key)) unset($this->$key);
        }
    }

    public function getType() {
        return $this->_type;
    }

    public function getTypeArray($key) {
        if(isset($this->_items[$key]) && is_array($this->_items[$key])) return $this->_items[$key];
        else return NULL;
    }

    public function isSearchable($key = NULL) {
        if(is_null($key)) $key = $this->_type;
        if(isset($this->_items[$key]['solr_search']) && $this->_items[$key]['solr_search']) return true;
        else return false;
    }

    public function isSortable($key = NULL) {
        if(is_null($key)) $key = $this->_type;
        if(isset($this->_items[$key]['solr_sort']) && $this->_items[$key]['solr_sort']) return true;
        else return false;
    }

    public function isIndexable($key = NULL) {
        if(is_null($key)) $key = $this->_type;
        if(isset($this->_items[$key]['solr_index']) && $this->_items[$key]['solr_index']) return true;
        else return false;
    }

    public function getProductAttributeName($solrAttribute) {
        $solrAttributeArray = explode('_', $solrAttribute);
        if(is_array($solrAttributeArray) && $solrAttributeArray[0] === 'attr') {
            if($solrAttributeArray[2] === trim(self::SUBPREFIX_SEARCH, '_')) {
                unset($solrAttributeArray[0]);
                unset($solrAttributeArray[1]);
                unset($solrAttributeArray[2]);
                return $productAttribute = implode('_', $solrAttributeArray);
            }
        }
    }
}