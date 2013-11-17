<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 *
 *
 * Use the Magento indexer to add products 
 * to Solr instance
 */
class DMC_Solr_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    protected $_matchedEntities = array('catalog_product'=>array('save'));
    protected $_cacheProductAttributes = array();

    // batch indexing, products per iteration
    const PRODUCTS_BY_PERIOD = 100;
    const PRODUCTS_BY_PERIOD_CHECK_MEMORY_THRESHOLD = 10;
    const MEMORY_THRESHOLD = 20971520;
    const MEMORY_LIMIT = '1024M';

    protected function _getSolr()
    {
        return Mage::helper('solr')->getSolr();
    }

    /*
    public function matchEntityAndType($entity, $type) {
        $solr = Mage::helper('solr')->getSolr();
        return $solr->matchEntityAndType($entity, $type);
    }
    */

    public function getName()
    {
        return Mage::helper('solr')->__('Product Solr Data');
    }

    public function getDescription()
    {
        return Mage::helper('solr')->__('Index product solr data');
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
    
    }

    /**
     * This method is called by the core indexer process
     * in case of saving (insert/update) a product
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {

        $solr = Mage::helper('solr')->getSolr();
        $object = $event->getDataObject();
        
        // if the product is not active anymore, we will remove it from solr index
        if($object->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED ) {
            $solr->deleteByQuery("id:{$object->getId()}");
            Mage::helper('solr/log')->addDebugMessage('The object #' . $object->getId() 
                . ' is not active and was therefore deleted.');

            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('solr')->__('The Product has been removed from Solr.')
            );
            return;
        }

        // $object contains the full product object
        if (
            $event->getEntity() == 'catalog_product' 
            && $event->getType() == 'save' 
            && $event->getDataObject()
        ) {
            if((int)Mage::getStoreConfig('solr/indexer/product_update')) {
                
                $storeId = $object->getStoreId();

                // to-do
                // index just those store views, we need
                /*
                $adapter = new DMC_Solr_Model_SolrServer_Adapter_Product();
                $document = $adapter->getSolrDocument();
                if($document->setObject($object)) {
                    $document->setStoreId($storeId);
                    // add doducment to adapter object
                    $solr->addDocument($document);

                    #echo '<pre>';
                    #print_r($document);exit;
                }

                // send documents to solr
                $solr->addDocuments();
                */
                
              
                if (!$storeId) {
                    $storeIds = Mage::helper('solr')->getStoresForReindex();
                } else {
                    $storeIds = array($storeId);
                }
                $adapter = new DMC_Solr_Model_SolrServer_Adapter_Product();
                $productId = $object->getId();
                foreach($storeIds as $storeId) {
                    $object = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
                    $document = $adapter->getSolrDocument();
                    if($document->setObject($object)) {
                        $document->setStoreId($storeId);
                        $solr->addDocument($document);
                    }
                }
                $solr->addDocuments();
          

                // commit data to solr
                $solr->commit();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('solr')->__('The Product has been updated at Solr.')
                );
            }
        }
    }

    public function reindexAll($storeIds = null, $types = null)
    {
        $solr = $this->_getSolr();
        if(is_null($storeIds)) {
            $storeIds = $this->getStoresForReindex();
        }

        Mage::helper('solr/log')->addDebugMessage('Call full reindex');
   
        try {
            
            Mage::helper('solr/log')->addDebugMessage('Start full reindex');

            if(Mage::helper('solr')->isEnabled() && $solr->ping()) {
                foreach($storeIds as $storeId) {
                    Mage::helper('solr/log')->addDebugMessage('Reindex store id '.$storeId);
                    $this->reindexStore($storeId, $types);
                }
            }
            else {
                throw new Exception('Solr is unavailable, please check connection settings and solr server status');
            }
        }
        catch(Exception $e) {
            Mage::helper('solr/log')->addMessage($e, false);
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        return $this;
    }

    protected function __getAttributes($object)
    {
        if ($object instanceof Mage_Catalog_Model_Product) {
            $attributeSetId = $object->getAttributeSetId();
            if (!isset($this->_cacheProductAttributes[$attributeSetId])) {
                $this->_cacheProductAttributes[$attributeSetId] = $object->getAttributes();
            }
            return $this->_cacheProductAttributes[$attributeSetId];
        }
        return null;
    }

    public function reindexStore($storeCode, $types = null)
    {
        $memLimit = ini_get('memory_limit');
        if ($memLimit > 0) {
            ini_set('memory_limit', self::MEMORY_LIMIT);
        }
        $ussageMemoryInit = memory_get_usage();
        $solr = $this->_getSolr();
        $store = Mage::getModel('core/store')->load($storeCode);
        $types = $solr->getDocumentTypes();

        if($solr) {
            $i = 0;
            foreach($types as $name => $class) {
                $solr->deleteDocuments($name, $store->getId());
                $adapter = new $class();
                $attrToSelect = null;
                if ($name == 'product') {
                    $attrToSelect = '*';
                }
                $items = $adapter->getSourceCollection($store->getId(), $attrToSelect);
                foreach($items as $item) {
                    $item->setStoreId($store->getId());
                    $doc = $adapter->getSolrDocument();
                    $attributes = $this->__getAttributes($item);
                    if($doc->setObject($item, $attributes)) {
                        $doc->setStoreId($store->getId());
                        if(!$solr->addDocument($doc)) {
                            $solr->deleteDocument($doc);
                        }
                        $i++;
                    }
                    else {
                        //echo $item->id.' !! ';
                    }

                    unset($doc);
                    if ($i >= self::PRODUCTS_BY_PERIOD_CHECK_MEMORY_THRESHOLD) {
                        $usageMemory = memory_get_usage();
                        if (($usageMemory - $ussageMemoryInit) > self::MEMORY_THRESHOLD) {
                            $ussageMemoryInit = $usageMemory;
                            $solr->addDocuments();
                            $i = 0;
                        }
                    }

                    if ($i >= self::PRODUCTS_BY_PERIOD) {
                        $solr->addDocuments();
                        $i = 0;
                    }
                }
            }
            if ($i > 0) $solr->addDocuments();
            $solr->commit();
            $solr->optimize();
        }
        if ($memLimit > 0) {
            ini_set('memory_limit', $memLimit);
        }
    }

    public function getStoresForReindex()
    {
        $storeIds = array();
        $collections = Mage::getModel('core/store')->getCollection();
        $collections->addFieldToFilter('store_id', array('neq' => 0));
        $collections->load();
        foreach($collections as $store) {
            if(Mage::getStoreConfig('solr/general/enable', $store->getId())) {
                $storeIds[] = $store->getId();
            }
        }
        return $storeIds;
    }
}
