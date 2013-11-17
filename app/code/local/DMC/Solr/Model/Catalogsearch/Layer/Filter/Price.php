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
    /**
     * Get maximum price from layer products set
     *
     * @return float
     */
    public function getMaxPriceInt()
    {
        $attribute = $this->getAttributeModel();
        $collection = $this->getLayer()->getProductCollection();
        $inputType = $attribute->getFrontend()->getInputType();
        $typeConverter = new DMC_Solr_Document_TypeConverter($inputType);
        $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
        $maxPrice = $this->getData('max_price_int');
        if (is_null($maxPrice)) {

            if (get_class($this->getLayer()->getProductCollection()) === 'DMC_Solr_Model_SolrServer_Adapter_Product_Collection') {
                $select = $this->getLayer()->getProductCollection()->getSelect();
                $fselect = clone $select;
                $fselect->param('stats', 'true');
                $fselect->param('stats.field', $fieldName);
                $responce = Mage::helper('solr')->getSolrConnector()->fetchAll($fselect);
                $priceStats = $responce->__get('stats')->stats_fields->$fieldName;
                //$price = $this->getLayer()->getProductCollection()->getPriceStats();
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
            }        }
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
        $attribute = $this->getAttributeModel();
        $collection = $this->getLayer()->getProductCollection();
        $inputType = $attribute->getFrontend()->getInputType();
        $typeConverter = new DMC_Solr_Document_TypeConverter($inputType);
        $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
        $rangeKey = 'range_item_counts_' . $range;
        $items = $this->getData($rangeKey);
        if (is_null($items)) {
            if (get_class($this->getLayer()->getProductCollection()) === 'DMC_Solr_Model_SolrServer_Adapter_Product_Collection') {
                $items = array();
                $select = $this->getLayer()->getProductCollection()->getSelect();
                $fselect = clone $select;
                $fselect->param('facet', 'true', true);
                $fselect->param('facet.field', $fieldName, true);
                $min = 0;
                $max = $range-1;
                while($max<=$range*10) {
                    $rangeQuery = $fieldName.':['.$min.'.99 TO '.$max.'.99]';
                    $fselect->param('facet.query', $rangeQuery);
                    $min = $max+1;
                    $max = $max+$range;
                }
                $responce = Mage::helper('solr')->getSolrConnector()->fetchAll($fselect);
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
        /**
         * Filter must be string: $index,$range
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        $filter = explode(',', $filter);
        if (count($filter) != 2) {
            return $this;
        }

        list($index, $range) = $filter;

        if ((int)$index && (int)$range) {
            $this->setPriceRange((int)$range);

            if (get_class($this->getLayer()->getProductCollection()) === 'DMC_Solr_Model_SolrServer_Adapter_Product_Collection') {
                $collection = $this->getLayer()->getProductCollection();
                $collection->addPriceData($this->getCustomerGroupId(), $this->getWebsiteId());
                $typeConverter = new DMC_Solr_Document_TypeConverter($this->getAttributeModel()->getFrontend()->getInputType());
                $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
                $rate = $this->getCurrencyRate();
                $from = $range * ($index - 1);
                $to = $range * $index;
                $collection->getSelect()->where($fieldName.':['.$from.'.99 TO '.$to.'.99]');
            }
            else {
                $this->_getResource()->applyFilterToCollection($this, $range, $index);
            }

            $this->getLayer()->getState()->addFilter(
                $this->_createItem($this->_renderItemLabel($range, $index), $filter)
            );

            $this->_items = array();
        }
        return $this;
    }
}
