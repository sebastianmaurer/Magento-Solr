<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

class DMC_Solr_Model_Catalog_Layer_Filter_Decimal extends Mage_Catalog_Model_Layer_Filter_Decimal
{
    public function getTypeConverter($inputType)
    {
        return new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter($inputType);
    }

    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
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
                $to = $this->getMaxValue();
                $to = (ceil($to/10))*10;
            } else {
                $to -= .01;
            }
            if ($from === '') $from = 0;

            if ( $collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection) {
                //$collection->addPriceData($this->getCustomerGroupId(), $this->getWebsiteId());
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
        }

        if (count($filterQuery)) {
            $query = implode(' OR ', $filterQuery);
            $collection->applyFilterToCollection($query);
        }

        return $this;
    }

    public function getMaxValue()
    {
        $helper = Mage::helper('solr');
        $attribute = $this->getAttributeModel();
        $inputType = $attribute->getFrontend()->getInputType();
        $typeConverter = $this->getTypeConverter($inputType);
        $fieldName = $typeConverter->solr_index_prefix.'index_'.$this->_requestVar;
        $maxValue = $this->getData('max_value');
        if (is_null($maxValue)) {
            $collection = $this->getLayer()->getProductCollection();
            if ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection) {
                $fselect = clone $collection->getSelect($helper->catalogCategoryMultiselectEnabled());
                $fselect->param('stats', 'true');
                $fselect->param('stats.field', $fieldName);
                $responce = Mage::helper('solr')->getSolr()->fetchAll($fselect);
                $decStats = $responce->__get('stats')->stats_fields->$fieldName;
                if(is_object($decStats)) $maxValue = $decStats->max;
                else $maxValue = NULL;
            }
            else {
                list($min, $max) = $this->_getResource()->getMinMax($this);
                $this->setData('max_value', $max);
                $this->setData('min_value', $min);
            }
            $maxValue = floor($maxValue);
            $this->setData('max_value', $maxValue);
        }
        return $maxValue;
    }

    public function getRangeItemCounts($range)
    {
        $helper = Mage::helper('solr');
        $attribute = $this->getAttributeModel();
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
                $maxV = $this->getMaxValue();
                while($min <= $maxV) {
                    $rangeQuery = $fieldName.':['.$min.' TO '.$max.'.9999]';
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


    protected function _getItemsData()
    {
        $key = $this->_getCacheKey();

        $data = $this->getLayer()->getAggregator()->getCacheData($key);
        if ($data === null) {
            $data       = array();
            $range      = $this->getRange();
            $dbRanges   = $this->getRangeItemCounts($range);

            foreach ($dbRanges as $index => $count) {
                $data[] = array(
                    'label' => $this->_renderItemLabel($range, $index),
                    'value' => $index . '-' . $range,
                    'count' => $count,
                );
            }


        }
        return $data;
    }


    protected function _validateFilter($filter)
    {
        $filter = explode('-', $filter);
        if (count($filter) != 2) {
            return false;
        }
        foreach ($filter as $v) {
            if (($v !== '' && $v !== '0' && (float)$v <= 0) || is_infinite((float)$v)) {
                return false;
            }
        }

        return $filter;
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
