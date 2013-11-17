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

require_once $LIB_PATH.DS.'Response.php';
require_once $LIB_PATH.DS.'Service.php';

class DMC_Solr_Model_SolrServer_Adapter extends Apache_Solr_Service
{
    const DEFAULT_PING_TIMEOUT = 5;

    const XML_SOLR_DOCUMENT_TYPES = 'global/solr_document_types';

    protected $_documentTypes = null;
    protected $_serverUrl = NULL;
    protected $_solr = NULL;
    protected $_products = array();
    protected $_error = false;
    protected $_lastPing = NULL;
    protected $_solrServerUrl = null;

    public function __construct() {
        $url = $this->getSolrServerUrl();
        $url = parse_url($url);
        if(isset($url['host']) && isset($url['port']) && isset($url['path'])) {
            parent::__construct($url['host'], $url['port'], $url['path']);
        }
    }

    public function getSolrServerUrl() {
        if(is_null($this->_solrServerUrl)) {
            if(strlen(Mage::getStoreConfig('solr/general/server_url'))) {
                $this->_solrServerUrl = trim(Mage::getStoreConfig('solr/general/server_url'), '/');
            }
        }
        return $this->_solrServerUrl;
    }

    public function getDocumentTypes($inTypes=null)
    {
        if(is_null($this->_documentTypes)) {
            $config = Mage::getConfig()->getNode(self::XML_SOLR_DOCUMENT_TYPES)->children();
            foreach($config as $name=>$item) {
                $value = $item->children()->asArray();
                $this->_documentTypes[$name] = $value;
            }
        }
        if(is_array($inTypes) && count($inTypes)) {
            foreach($inTypes as $name) {
                $types[$name] = $this->_documentTypes[$name];
            }
        }
        else {
            $types = $this->_documentTypes;
        }

        return $types;
    }

    public function getDocumentType($type) {
        $types = $this->getDocumentTypes();
        return isset($types[$type]) ? $types[$type] : null;
    }

    public function matchEntityAndType($entity, $type)
    {
        $types = $this->getDocumentTypes();
        foreach($types as $name=>$class) {
            $adapter = new $class();
            if($adapter->matchEntityAndType($entity, $type)) {
                return true;
            }
        }
        return false;
    }

    public function processEvent(Mage_Index_Model_Event $event) {
        $entity = $event->getEntity();
        $type = $event->getType();
        $types = $this->getDocumentTypes();
        foreach($types as $name=>$class) {
            $adapter = new $class();
            if($adapter->matchEntityAndType($entity, $type)) {
                $adapter->processEvent($event);
            }
        }
    }

    public function registerEvent(Mage_Index_Model_Event $event)
    {
        $entity = $event->getEntity();
        $type = $event->getType();
        $types = $this->getDocumentTypes();
        foreach($types as $name=>$class) {
            $adapter = new $class();
            if($adapter->matchEntityAndType($entity, $type)) {
                $adapter->registerEvent($event);
            }
        }
    }

    public function getSearchUrl($query, $offset = NULL, $limit = NULL, $params = array()) 
    {
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

    public function fetchAll($queryObject, $offset = NULL, $limit = NULL, $params = array())
    {
        $offset = $queryObject->getOffset();
        $limit = $queryObject->getLimit();
        $queryObject->addParam('fq', $queryObject->getQuery());
        $params = $queryObject->getParams();

        $url = $this->getSearchUrl(DMC_Solr_Model_SolrServer_Adapter_Product_Collection::escape(Mage::helper('solr')->getQueryText()), $offset, $limit, $params);

        #if(Mage::helper( 'solr' )->isDebugMode()) {
        #    echo 'SOLR-DEBUG:<br><textarea cols="100%" rows="4">'.$url.'</textarea>';
        #} 

        Varien_Profiler::start('SOLR:fetchAll: '.$url);
        $return = $this->_sendRawGet($url);
        Varien_Profiler::stop('SOLR:fetchAll: '.$url);

        return $return;
    }

    public function checkIfRedirect($queryObject)
    {
        $offset = $queryObject->getOffset();
        $limit = $queryObject->getLimit();

        $queryText = DMC_Solr_Model_SolrServer_Adapter_Product_Collection::escape(Mage::helper('solr')->getQueryText());
        $url = $this->getSearchUrl($queryText, $offset, $limit, array('fq' => '(row_type:landingpage AND name:'.$queryText.')'));
        $return = $this->_sendRawGet($url);
        
        if($return->__get('response')->numFound != 0)
        {
            return $return->__get('response')->docs{0};
        }
        return false;
    }
    public function getPromotion( $queryObject )
    {
        $offset = $queryObject->getOffset();
        $limit = $queryObject->getLimit();
        $queryText = DMC_Solr_Model_SolrServer_Adapter_Product_Collection::escape( Mage::helper( 'solr' )->getQueryText() );
        $url = $this->getSearchUrl( $queryText, $offset, $limit, array(
                'fq' => '(row_type:promotion AND name:' . $queryText . ')' 
        ) );
        $return = $this->_sendRawGet( $url );
        
        if( $return->__get( 'response' )->numFound != 0 )
        {
            $tmp = $return->__get( 'response' )->docs{0};
            return $tmp->getValues();
        }
        return false;
    }
    
    public function ping($timeout = self::DEFAULT_PING_TIMEOUT) {
        set_error_handler(array(get_class($this), 'ping_error'), E_ALL);
        $ping = parent::ping($timeout);
        restore_error_handler();
        if($this->_error || !$ping) {
            $this->_error = false;
            $this->_lastPing = NULL;
            return false;
        }
        else {
            $this->_lastPing = $ping;
            return true;
        }
    }

    static public function ping_error()
    {
    }

    public function getLastPingMessage() {
        if(!is_null($this->_lastPing)) {
            $message = '<font color="green">'.Mage::helper('solr')->__('Service is working. Responce time is ').sprintf("%01.5f", $this->_lastPing).Mage::helper('solr')->__(' sec.').'</font>';
        }
        else {
            $message = '<font color="red">'.Mage::helper('solr')->__('Solr service not responding').'</font>';
        }
        return $message;
    }

    public function deleteDocuments($type = null, $storeId = null) {
        $query[] = 'row_id:*';
        if(!is_null($type)) {
            $query[] = 'row_type:'.$type;
        }
        if(!is_null($storeId)) {
            $query[] = 'store_id:'.$storeId;
        }
        $query = implode(' AND ', $query);
        $return = $this->deleteByQuery($query);
        $this->commit();
        return $return;
    }

    public function addDocument(DMC_Solr_Model_SolrServer_Adapter_AbstractDocument $document, $allowDups = false, $overwritePending = true, $overwriteCommitted = true) {
        try {

            $this->_products[] = $document;

            #$storeId = $document->getStoreId();
            #if(is_null($storeId) || ($storeId == 0)) {
            #    $storeIds = Mage::helper('solr')->getStoresForReindex();
            #}
            #else {
            #    $storeIds[] = $storeId;
            #}

            #if(count($storeIds)) {
            #    foreach($storeIds as $storeId) {
            #        $document->setStoreId($storeId);
            #        $this->_products[] = $document;
            #    }
            #}
        }
        catch(Exception $e) {
            if(Mage::helper('solr')->isDebugMode()) {
                Mage::getModel('core/session')->addError($e->getMessage());
            }
            Mage::helper('solr/log')->addException($e, false);
            return false;
        }
        return true;
    }

    public function addDocuments($documents=null, $allowDups = false, $overwritePending = true, $overwriteCommitted = true) {
        
        if(count($this->_products)) {

            // sent the xml query to solr
            parent::addDocuments( $this->_products );
            Mage::helper('solr/log')->addDebugMessage('Sent '.count($this->_products).' items to the solr server');
            $this->clearDocuments();
        }
    }

    protected function clearDocuments() {
        unset($this->_products);
        $this->_products = array();

    }

    public function deleteDocument($object)
    {
        $storeId = $object->getStoreId();
        if(is_null($storeId) || ($storeId == 0)) {
            $storeIds = Mage::helper('solr')->getStoresForReindex();
        }
        else {
            $storeIds[] = $storeId;
        }

        if(count($storeIds)) {
            foreach($storeIds as $storeId) {
                $this->deleteById($object->getRowId(), $storeId);
            }
        }
    }
}
