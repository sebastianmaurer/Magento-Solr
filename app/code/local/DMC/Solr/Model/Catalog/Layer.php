<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalog_Layer extends Mage_Catalog_Model_Layer
{
 

    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        }
        else {

            #if(Mage::helper('solr')->isEnabledOnCatalog()) {

                $collection = Mage::getModel('DMC_Solr_Model_SolrServer_Adapter_Product_Collection');
                
                $collection->setStoreId($this->getStoreId());
                $collection->addCategoryFilter($this->getCurrentCategory());
                $this->prepareSolrProductCollection($collection);
                
                if (Mage::helper('solr')->catalogCategoryMultiselectEnabled()) {
                     $collection->dumpSelect();
                }
            #}
            #else {
            #    $collection = $this->getCurrentCategory()->getProductCollection();
            #    $this->prepareProductCollection($collection);
            #}
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }
        return $collection;
    }


    public function prepareSolrProductCollection($collection)
    {
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->setStore(Mage::app()->getStore())
            //->addMinimalPrice()
            //->addFinalPrice()
            //->addTaxPercents()
            ->addStoreFilter()
            ->addUrlRewrite();

        //$this->_addVisibleInSearchFilterToCollection($collection);

        //$collection->addSearchFilter( $this->getSearchFilter() );

        $collection->addUrlRewrite($this->getCurrentCategory()->getId());
        return $this;
    }

}
