<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Layer_Filter_Price
{
    public function getTypeConverter($inputType)
    {
        return new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($inputType);
    }

    /**
     * Get maximum price from layer products set
     *
     * @return float
     */
    public function getMaxPriceInt()
    {
        $helper = Mage::helper('solr');
        $attribute = $this->getAttributeModel();
        $inputType = $attribute->getFrontend()->getInputType();
        $typeConverter = $this->getTypeConverter($inputType);
        $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
        $maxPrice = $this->getData('max_price_int');
        if (is_null($maxPrice)) {
            $collection = $this->getLayer()->getProductCollection();
            if ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection) {
                $fselect = clone $collection->getSelect($helper->catalogCategoryMultiselectEnabled());
                $fselect->param('stats', 'true');
                $fselect->param('stats.field', $fieldName);
                $responce = Mage::helper('solr')->getSolr()->fetchAll($fselect);
                $priceStats = $responce->__get('stats')->stats_fields->$fieldName;
                if(is_object($priceStats)) {
                    $maxPrice = $priceStats->max;
                    $minPrice = $priceStats->min;
                } else { 
                    $maxPrice = NULL;
                }
            }
            else {
                $maxPrice = $this->_getResource()->getMaxPrice($this);
            }
            $maxPrice = ceil($maxPrice);
            $this->setData('max_price_int', $maxPrice);
            if (isset($minPrice)) {
                $minPrice = floor($minPrice);
                $this->setData('min_price_int', $minPrice);
            }
        }
        return $maxPrice;
    }

    /**
     * Get information about products count in range
     *
     * @param   int $range
     * @return  int
     */
    public function getRangeItemCounts($range)
    {
        
        $helper = Mage::helper('solr');
        $attribute = $this->getAttributeModel();
        //$collection = $this->getLayer()->getProductCollection();
        $inputType = $attribute->getFrontend()->getInputType();
        $typeConverter = $this->getTypeConverter($inputType);
        $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
        $rangeKey = 'range_item_counts_' . $range;
        $items = $this->getData($rangeKey);
        if (is_null($items)) {
            $collection = $this->getLayer()->getProductCollection();
            if ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection) {
                $items = array();
                $fselect = clone $collection->getSelect($helper->catalogCategoryMultiselectEnabled());
                $fselect->param('facet', 'true', true);
                $fselect->param('facet.field', $fieldName, true);
                $min = 0;
                $max = $range-1;
                $maxPrice = $this->getMaxPriceInt();
                //while($max<=$range*10) {
                while($min <= $maxPrice) {
                        $rangeQuery = $fieldName.':['.$min.' TO '.$max.'.99]';
                    $fselect->param('facet.query', $rangeQuery);
                    $min = $max+1;
                    $max = $max+$range;
                }
                $responce = Mage::helper('solr')->getSolr()->fetchAll($fselect);
                $valueArray = get_object_vars($responce->__get('facet_counts')->facet_queries);
                $int = 1;
                foreach($valueArray as $value) {
                    if((int)$value !== 0) $items[$int] = $value;
                    $int++;
                }
            }
            else {
                $items = $this->_getResource()->getCount($this, $range);
            }
            $this->setData($rangeKey, $items);
        }
        return $items;
    }

    /**
     * Apply price range filter to collection
     *
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $helper = Mage::helper('solr');
        $filter = $request->getParam($this->getRequestVar());

        if (!$filter) {
            return $this;
        }

        $filterParams = explode(',', $filter);

        $filterQuery = array();
        $collection = $this->getLayer()->getProductCollection();
        foreach($filterParams as $filterParam){
            $interval = $this->_validateFilter($filterParam);
            if (!$interval) {
                return $this;
            }


            list($from, $to) = $interval;
            if ($to === '') {
                $to = $this->getMaxPriceInt();
                $to = (ceil($to/10))*10;
            } else {
                $to -= .01;
            }
            if ($from === '') $from = 0;

            if ( $collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection) {
                $collection->addPriceData($this->getCustomerGroupId(), $this->getWebsiteId());
                $typeConverter = $this->getTypeConverter($this->getAttributeModel()->getFrontend()->getInputType());
                $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
                //$rate = $this->getCurrencyRate();
                $filterQuery[] = $fieldName.':['.$from.' TO '.$to.']';
            } else {
                $range = $to - $from;
                $index = $to/$range;
                $this->_getResource()->applyFilterToCollection($this, $range, $index);
            }

            $this->getLayer()->getState()->addFilter($this->_createItem(
                $this->_renderRangeLabel(empty($interval[0]) ? 0 : $interval[0], $interval[1]),
                $interval
            ));
            if (!$helper->catalogCategoryMultiselectEnabled())
                $this->_items = array();
        }

        if (count($filterQuery)) {
            $query = implode(' OR ', $filterQuery);
            $collection->applyFilterToCollection($query);
        }

        return $this;
    }

    protected function _getItemsData()
    {
        /*if (Mage::app()->getStore()->getConfig(Mage_Catalog_Model_Layer_Filter_Price::XML_PATH_RANGE_CALCULATION) == Mage_Catalog_Model_Layer_Filter_Price::RANGE_CALCULATION_IMPROVED) {
            return $this->_getCalculatedItemsData();
        } elseif ($this->getInterval()) {
            return array();
        }*/

        $range      = $this->getPriceRange();
        $dbRanges   = $this->getRangeItemCounts($range);
        $data       = array();

        if (!empty($dbRanges) && is_array($dbRanges) && count($dbRanges) > 1) {
            $lastIndex = array_keys($dbRanges);
            $lastIndex = $lastIndex[count($lastIndex) - 1];

            foreach ($dbRanges as $index => $count) {
                $fromPrice = ($index == 1) ? '' : (($index - 1) * $range);
                $toPrice = ($index == $lastIndex) ? '' : ($index * $range);

                $data[] = array(
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'value' => $fromPrice . '-' . $toPrice,
                    'count' => $count,
                );
            }
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
