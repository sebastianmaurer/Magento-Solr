<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalogsearch_Advanced extends Mage_CatalogSearch_Model_Advanced
{
    public function getProductCollection(){
        if (is_null($this->_productCollection)) {
            if(Mage::helper('solr')->isEnabled()) {
                $collection = Mage::getModel('DMC_Solr_Model_SolrServer_Adapter_Product_Collection');
            }
            else {
                $collection = Mage::getResourceModel('catalogsearch/advanced_collection');
            }
            $this->prepareProductCollection($collection);
            $this->_productCollection = $collection;
        }

        return $this->_productCollection;
    }

    public function prepareProductCollection($collection)
    {
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addStoreFilter();
                if(!Mage::helper('solr')->isEnabled()) {
                    $collection->addMinimalPrice()
                            ->addTaxPercents();
                    Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($this->_productCollection);
                    Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($this->_productCollection);
                }

        return $this;
    }

     public function addFilters($values)
    {
        $attributes     = $this->getAttributes();
        $hasConditions  = false;
        $allConditions  = array();

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!isset($values[$attribute->getAttributeCode()])) {
                continue;
            }
            $value = $values[$attribute->getAttributeCode()];

            if ($attribute->getAttributeCode() == 'price') {
                if ($this->_getResource()->addPriceFilter($this, $attribute, $value)) {
                    $hasConditions = true;
                    $this->_addSearchCriteria($attribute, $value);
                }
            } else if ($attribute->isIndexable()) {
                if ($this->_getResource()->addIndexableAttributeFilter($this, $attribute, $value)) {
                    $hasConditions = true;
                    $this->_addSearchCriteria($attribute, $value);
                }
            } else {
                if(Mage::helper('solr')->isEnabled()) {
                    if ($this->_getResource()->addFilter($this, $attribute, $value)) {
                        $this->_addSearchCriteria($attribute, $value);
                        $hasConditions = true;
                    }
                } else {
                    $condition = $this->_prepareCondition($attribute, $value);
                    if ($condition === false) {
                        continue;
                    }

                    $this->_addSearchCriteria($attribute, $value);

                    $table       = $attribute->getBackend()->getTable();
                    if ($attribute->getBackendType() == 'static'){
                        $attributeId = $attribute->getAttributeCode();
                    } else {
                        $attributeId = $attribute->getId();
                    }
                    $allConditions[$table][$attributeId] = $condition;
                }
            }
        }

        if ($allConditions) {
            $this->getProductCollection()->addFieldsToFilter($allConditions);
        } else if (!$hasConditions) {
            Mage::throwException(Mage::helper('catalogsearch')->__('Please specify at least one search term.'));
        }
        return $this;
    }

}

