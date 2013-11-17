<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Promotion extends DMC_Solr_Model_SolrServer_Adapter_Abstract
{
    protected $_type = 'promotion';

    protected $_matchedEntities = array(
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION,
        ),
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
        ),
    );

    public function registerEvent(Mage_Index_Model_Event $event)
    {

    }

    public function getSourceCollection($storeId = null, $withAttributes = null)
    {
        $collection = Mage::getModel('solr/promotion')->getCollection();
        $collection->addFieldToFilter('is_active',1)->addFieldToFilter('store',$storeId);
        return $collection;
    }

    public function processEvent(Mage_Index_Model_Event $event)
    {
        if((int)Mage::getStoreConfig('solr/indexer/category_update')) {
            $solr = Mage::helper('solr')->getSolr();
            $entity = $event->getEntity();
            $type = $event->getType();
            switch($entity) {
                case Mage_Catalog_Model_Product::ENTITY:
                    if($type == Mage_Index_Model_Event::TYPE_MASS_ACTION) {
                        //$this->_deleteProducts();
                    }
                    elseif($type == Mage_Index_Model_Event::TYPE_SAVE) {
                        $object = $event->getDataObject();
                        $document = $this->getSolrDocument();
                        if($document->setObject($object)) {
                            $solr->addDocument($document);
                            $solr->addDocuments();
                            $solr->commit();
                        }
                    }
                    elseif($type == Mage_Index_Model_Event::TYPE_DELETE){
                    }
                    break;
                case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY:
                    $this->_skipReindex($event);
                    break;
            }
        }
    }
}
