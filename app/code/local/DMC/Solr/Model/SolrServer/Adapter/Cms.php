<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Cms extends DMC_Solr_Model_SolrServer_Adapter_Abstract
{
    protected $_type = 'cms';

    protected $_matchedEntities = array(
        DMC_Solr_Model_Cms_Page::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        ),
    );

    public function getSourceCollection($storeId = null)
    {
        $collection = Mage::getModel('cms/page')->getCollection();
        if(!is_null($storeId)) {
            $collection->addStoreFilter($storeId);
        }
        return $collection;
    }

    public function registerEvent(Mage_Index_Model_Event $event)
    {

    }

    public function processEvent(Mage_Index_Model_Event $event)
    {
        if((int)Mage::getStoreConfig('solr/indexer/cms_update')) {
            $solr = Mage::helper('solr')->getSolr();
            $entity = $event->getEntity();
            $type = $event->getType();
            switch($entity) {
                case DMC_Solr_Model_Cms_Page::ENTITY:
                    if($type == Mage_Index_Model_Event::TYPE_SAVE) {
                        $object = $event->getDataObject();
                        $document = $this->getSolrDocument();
                        if($document->setObject($object)) {
                            $solr->addDocument($document);
                            $solr->addDocuments();
                            $solr->commit();
                        }
                    }
                    elseif($type == Mage_Index_Model_Event::TYPE_DELETE){
                        $object = $event->getDataObject();
                        $document = $this->getSolrDocument();
                        if($document->setObject($object)) {
                            $solr->deleteDocument($document);
                            $solr->commit();
                        }
                    }
                    break;
            }
        }
    }
}
