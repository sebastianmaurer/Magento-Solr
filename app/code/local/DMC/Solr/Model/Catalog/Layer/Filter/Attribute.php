<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalog_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
    //protected $_beforeApplySelect = null;

    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $helper = Mage::helper('solr');
        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }
        if($filter) {
            $filters = explode(',', $filter);
            $collection = $this->getLayer()->getProductCollection();
            $isSolrCollection = ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection);
            $filterQuery = array();

            foreach($filters as $filter) {
                $text = $this->_getOptionText($filter);
                if ($isSolrCollection && $filter && !$text) {
                    $text = $filter;
                }
                if ($filter && $text) {
                    $attribute = $this->getAttributeModel();
                    $inputType = $attribute->getFrontend()->getInputType();
                    $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($inputType);
                    $solrField = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;

                    if ($isSolrCollection) {
                        $filterQuery[] = $solrField.':"'.$filter.'"';
                    }
                    else {
                        $this->_getResource()->applyFilterToCollection($this, $filter);
                    }
                    $this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
                    if (!$helper->catalogCategoryMultiselectEnabled())
                        $this->_items = array();
                }
            }

            if (count($filterQuery) && $isSolrCollection) {
                //$this->saveBeforeApplySelect($collection->getSelect());
                $query = implode(' OR ', $filterQuery);
                $collection->applyFilterToCollection($query);
            }
        }

        return $this;
    }

    /*protected function saveBeforeApplySelect($select)
    {
        if(is_null($this->_beforeApplySelect)) {
            $this->_beforeApplySelect = clone $select;
        }
    }*/

    protected function _getItemsData()
    {
        $helper = Mage::helper('solr');
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();
        $key = $this->getLayer()->getStateKey() . '_' . $this->_requestVar;
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $options = $attribute->getFrontend()->getSelectOptions();
            $inputType = null;

            $collection = $this->getLayer()->getProductCollection();
            $isSolrCollection = ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection);

            if ($isSolrCollection) {
                $inputType = $attribute->getFrontend()->getInputType();
                if ($inputType == null) {
                    $optionsCount = 0;
                } else {
                    $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($inputType);
                    $solrField = $typeConverter->solr_index_prefix . 'index_' . $this->_requestVar;
                    $fselect = clone $collection->getSelect($helper->catalogCategoryMultiselectEnabled());
                    $fselect->param('facet', 'true', true);
                    $fselect->param('facet.field', $solrField, true);

                    $fselect->limitPage(1,1);

                    $responce = Mage::helper('solr')->getSolr()->fetchAll($fselect);
                    $optionsCount = $responce->__get('facet_counts')->facet_fields->$solrField;
                }
            } else {
                $optionsCount = $this->_getResource()->getCount($this);
            }
            $data = array();
            $optionsIsOperated = false;

            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }

                if (Mage::helper('core/string')->strlen($option['value'])) {
                    // Check filter type
                    if (/*$this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS || */$isSolrCollection) {
                        $optionValue = $option['value'];
                        if ($inputType === 'boolean') {
                            $optionValue = $option['value'] ? 'true' : 'false';
                        }
                        if (!empty($optionsCount[$optionValue])) {
                            $data[] = array(
                                    'label' => $option['label'],
                                    'value' => $optionValue,
                                    'count' => $optionsCount[$optionValue],
                            );
                        }
                    } else {
                        $data[] = array(
                                'label' => $option['label'],
                                'value' => $option['value'],
                                'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                        );
                    }
                    $optionsIsOperated = true;
                }
            }


            if ($isSolrCollection && !$optionsIsOperated) {
                foreach ($optionsCount as $key => $value) {
                    if (!$value)
                    continue;
                    $data[] = array(
                            'label' => $key,
                            'value' => $key,
                            'count' => $value,
                    );
                }
            }

            if (count($data) < 2 ) $data = array();

            $tags = array(
                Mage_Eav_Model_Entity_Attribute::CACHE_TAG . ':' . $attribute->getId()
            );

            $tags = $this->getLayer()->getStateTags($tags);
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }


    protected function _createItem($label, $value, $count=0)
    {
        return Mage::getModel('solr/catalog_layer_filter_item')
            ->setFilter($this)
            ->setLabel($label)
            ->setValue($value)
            ->setCount($count);
    }
    
}
