<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
abstract class DMC_Solr_Model_SolrServer_Adapter_Abstract extends Varien_Object
{
    protected $_type = null;

    protected $_matchedEntities = array();

    abstract public function getSourceCollection($storeId = null);

    abstract public function processEvent(Mage_Index_Model_Event $event);

    abstract public function registerEvent(Mage_Index_Model_Event $event);

    public function getType()
    {
        return $this->_type;
    }

    public function getSolrDocument()
    {
        $solr = Mage::helper('solr')->getSolr();
        $types = $solr->getDocumentTypes();
        $class = "{$types[$this->_type]}_Document";
        return new $class();
    }

    public function getResultCollection()
    {
        $solr = Mage::helper('solr')->getSolr();
        $types = $solr->getDocumentTypes();
        $type = ucfirst(strtolower($this->_type));
        $class = "{$types[$this->_type]}_Collection";
        return new $class();
    }

    public function matchEntityAndType($entity, $type)
    {
        if (isset($this->_matchedEntities[$entity])) {
            if (in_array($type, $this->_matchedEntities[$entity])) {
                return true;
            }
        }
        return false;
    }

    protected function _skipReindex(Mage_Index_Model_Event $event)
    {
        $process = $event->getProcess();
        $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
    }
}
