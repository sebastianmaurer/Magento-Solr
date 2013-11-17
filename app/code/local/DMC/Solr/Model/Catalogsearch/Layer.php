<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalogsearch_Layer extends Mage_CatalogSearch_Model_Layer //Mage_Catalog_Model_Layer
{
    protected $_selectAttributes = array();

    protected $_staticFields = array();

    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        }
        else {
            // check, if solr is active
            // if yes, then use our code. if not, then follow the magento way
            if(Mage::helper('solr')->isEnabledOnSearchResult()) 
            {
                $collection = Mage::getModel('DMC_Solr_Model_SolrServer_Adapter_Product_Collection');
                $this->prepareSolrProductCollection($collection);
                
                if (Mage::helper('solr')->catalogCategoryMultiselectEnabled()) 
                {
                    $collection->dumpSelect();
                }
            }
            // magento way
            else {
                $collection = Mage::getResourceModel('catalogsearch/fulltext_collection');
                $this->prepareProductCollection($collection);
            }
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }

    // sm: removed the duplication of magento code
    // instead we inherit from magento search layer
/*
    public function prepareProductCollection($collection)
    {
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addSearchFilter(Mage::helper('catalogsearch')->getQuery()->getQueryText())
            ->setStore(Mage::app()->getStore())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite();

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);
        return $this;
    }
*/
    public function prepareSolrProductCollection($collection)
    {
        $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->setStore(Mage::app()->getStore())
            ->addStoreFilter()
            ->addUrlRewrite();
        $this->_addVisibleInSearchFilterToCollection($collection);
        return $this;
    }

    protected function _addVisibleInSearchFilterToCollection($collection) {
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInSearchIds());
    }

    protected function _getSetIds()
    {
        $setIds = $this->getProductCollection()->getSetIds();
        return $setIds;
    }

    protected function _prepareAttributeCollection($collection)
    {
        if (Mage::helper('solr')->isEnabledOnSearchResult()) {
            $collection->addFieldToFilter('additional_table.is_filterableBySolr', array('gt' => 0));
        } else {
            $collection->addIsFilterableFilter();
        }
        return $collection;
    }
}
