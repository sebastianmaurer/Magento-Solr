<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Select extends Varien_Object
{
    const SOLR_AND        = 'AND';
    const SOLR_OR        = 'OR';

    const QUERY        = 'query';
    const LIMIT        = 'limit';
    const OFFSET    = 'offset';
    const PARAMS    = 'params';
    const ORDER        = 'order';
    const FIELDS    = 'fields';

    const ASC    = 'asc';
    const DESC    = 'desc';

    const DEFAULT_OFFSET = NULL;

    const DEFAULT_LIMIT = NULL;

    const DEFAULT_ORDER_DIRECT = 'asc';

    protected $_query = array();

    protected $_offset = self::DEFAULT_OFFSET;

    protected $_limit = self::DEFAULT_LIMIT;

    protected $_params = array();

    protected $_order = array();

    protected $_fields = null;

    public function __construct() {
        parent::__construct();
    }

    public function addParam($name, $value, $clear=false) {
        if($clear) unset($this->_params[$name]);
        $this->_params[$name][] = $value;
    }

    public function addField($name) {
        $this->_fields[] = $name;
    }

    public function deleteParam($name) {
        unset($this->_params[$name]);
    }

    public function deleteParams() {
        unset($this->_params);
    }

    public function setOffset($value) {
        $this->_offset = $value;
    }

    public function setLimit($value) {
        $this->_limit = $value;
    }


    /**
     * Build SQL statement for condition
     *
     * @param string $fieldName
     * @param integer|string|array $condition
     * @return string
     */
    public function where($cond, $condition = null) {
        if (!is_null($condition)) {
            $cond = str_replace('?', '"'.Apache_Solr_Service::escapePhrase($condition).'"', $cond);
        }
        $this->_query[] = array('type' => self::SOLR_AND, 'sql' => $cond);
        return true;
    }

    public function param($name, $value) {
        $this->addParam($name, $value);
        return true;
    }

    public function columns($column) {
        return true;
    }

    public function group($column) {
        return true;
    }

    /**
     * Build SQL statement for condition
     *
     * @param string $fieldName
     * @param integer|string|array $condition
     * @return string
     */
    public function orWhere($cond, $condition = null) {
        if (!is_null($condition) && is_null($condition)) {
            $cond = str_replace('?', '"'.Apache_Solr_Service::escapePhrase($condition).'"', $cond);
        }
        $this->_query[] = array('type' => self::SOLR_OR, 'sql' => $cond);
        return $sql;
    }

    public function order($order) {
        if(is_array($order)) {
            $order = array('field' => $order['field'], 'direct' => $order['direct']);
        }
        else {
            $orderArray = explode(' ', $order);
            if(isset($orderArray[1])) $order = array('field' => $orderArray[0], 'direct' => $orderArray[1]);
            else $order = array('field' => $orderArray[0], 'direct' => self::DEFAULT_ORDER_DIRECT);
        }
        $this->_order[] = $order;
    }

    public function reset($type=null) {
        if(is_null($type)) {
            $this->_query = '';
            $this->_offset = self::DEFAULT_OFFSET;
            $this->_limit = self::DEFAULT_LIMIT;
            $this->_params = array();
            $this->_order = array();
            $this->_fields = null;
        }
        else {
            switch($type) {
                case self::QUERY:
                    $this->_query = array();
                    break;
                case self::OFFSET:
                    $this->_offset = self::DEFAULT_OFFSET;
                    break;
                case self::LIMIT:
                    $this->_limit = self::DEFAULT_LIMIT;
                    break;
                case self::PARAMS:
                    $this->_params = array();
                    break;
                case self::ORDER:
                    $this->_order = array();
                    break;
                case self::FIELDS:
                    $this->_fields = array();
                    break;
            }
        }
    }

    public function getQuery() {
        if(count($this->_query)) {
            $sql = '';
            foreach($this->_query as $item) {
                $sql = strlen($sql) ? $sql.$item['type'].'('.$item['sql'].')' : '('.$item['sql'].')';
            }
        }
        else {
            $sql = '*';
        }
        return $sql;
    }

    public function getLimit() {
        return $this->_limit;
    }

    public function getOffset() {
        return $this->_offset;
    }

    public function getParams() {
        $params = $this->_params;
        $order = $this->getOrder();
        if(strlen($order)) $params['sort'] = $order;
        
        if(!is_null($params['fq']) && is_array($params['fq'])) {
            $params['fq'] = implode(',', $params['fq']);
        }
 
 // SM-DEBUG       
        if(!is_null($this->_fields) && is_array($this->_fields)) {
 #           $params['fl'] = implode(',', $this->_fields);
        }
       
        return $params;
    }

    public function getOrder() {
        $temp = array();
        foreach($this->_order as $orderItem) {
            $temp[] = $orderItem['field'].' '.$orderItem['direct'];
        }
        return count($temp) ? implode(', ', $temp) : '';
    }

    public function limitPage($page, $rowCount) {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        $this->_limit  = (int) $rowCount;
        $this->_offset = (int) $rowCount * ($page - 1);
    }

    public function __toString()
    {
        return $this->getQuery();
    }

    public function getSearchUrl($query, $offset = NULL, $limit = NULL, $params = array()) {
        if (!is_array($params)) {
            $params = array();
        }

        //construct our full parameters
        //sending the version is important in case the format changes
        $params['version'] = self::SOLR_VERSION;

        //common parameters in this interface
        $params['wt'] = self::SOLR_WRITER;
        $params['q'] = $query;
        
        if(!is_null($offset)) $params['start'] = $offset;
        if(!is_null($limit)) $params['rows'] = $limit;

        //escape all parameters appropriately for inclusion in the GET parameters
        $escapedParams = array();

        do
        {
            //because some parameters can be included multiple times, loop through all
            //params and include their value or their first array value. unset values as
            //they are fully added so that the params list can be iteratively added.
            //
            //NOTE: could be done all at once, but this way makes the query string more
            //readable at little performance cost
            foreach ($params as $key => &$value)
            {
                if (is_array($value))
                {
                    //parameter has multiple values that need passed
                    //array_shift pops off the first value in the array and also removes it
                    $escapedParams[] = urlencode($key) . '=' . urlencode(array_shift($value));

                    if (empty($value))
                    {
                        unset($params[$key]);
                    }
                }
                else
                {
                    //simple, single value case
                    $escapedParams[] = urlencode($key) . '=' . urlencode($value);
                    unset($params[$key]);
                }
            }
        } while (!empty($params));
        return $this->_searchUrl . $this->_queryDelimiter . implode($this->_queryStringDelimiter, $escapedParams);
    }
}
