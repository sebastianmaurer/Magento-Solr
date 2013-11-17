<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

class DMC_Solr_Block_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{
    public function getLayer()
    {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        if(Mage::helper('solr')->isEnabledOnCatalog()) {
            return Mage::getSingleton('solr/catalog_layer');
        }
        else {
            return Mage::getSingleton('catalog/layer');
        }
    } 

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
/*
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
    
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }

            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
                }
            }
            $this->_productCollection = $layer->getProductCollection();

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }

        return $this->_productCollection;
    }    
    */
}
