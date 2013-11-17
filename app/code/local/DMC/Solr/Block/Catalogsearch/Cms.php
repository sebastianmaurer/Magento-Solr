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
 * echo $this->getLayout()->addBlock('solr/catalogsearch_cms', 'solr')->setTemplate('DMC/Solr/cms.phtml')->toHtml();
 */
class DMC_Solr_Block_Catalogsearch_Cms extends Mage_Core_Block_Template
{
    public function getPages()
    {
        $query = Mage::app()->getRequest()->getQuery('q');

        if(strlen($query)) {
            $this->setSearchFilter(Mage::app()->getRequest()->getQuery('q'));

            $collection = Mage::getModel('DMC_Solr_Model_SolrServer_Adapter_Cms_Collection');
            $collection->addStoreFilter();
            $collection->addSearchFilter($this->getSearchFilter());
            foreach($collection as $item) {
                $item->load($item->getId());
            }
            return $collection;
        }
        else {
            return array();
        }
    }
}
