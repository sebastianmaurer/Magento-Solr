<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalogsearch_Mysql4_Advanced extends Mage_CatalogSearch_Model_Mysql4_Advanced
{
    /**
     * Add filter by attribute price
     *
     * @param Mage_CatalogSearch_Model_Advanced $object
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param string|array $value
     */
    public function addPriceFilter($object, $attribute, $value)
    {
        if (empty($value['from']) && empty($value['to'])) {
            return false;
        }
        if(Mage::helper('solr')->isEnabled()) {
			$typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter();
            $code = $attribute->getAttributeCode();
			$items = $typeConverter->getItems();
		
            $select     = $object->getProductCollection()->getSelect();
            $rate = 1;
            if (!empty($value['currency'])) {
                $rate = Mage::app()->getStore()->getBaseCurrency()->getRate($value['currency']);
            }
            if (strlen($value['from']) > 0) {
                $from =  $value['from'] * $rate;
            } else {
                $from = 0;
            }
            if (strlen($value['to']) > 0) {
                $to = $value['to'] * $rate;
            } else {
                $to = 99999999;
            }
            $select->where($items[$attribute->getFrontend()->getInputType()]['solr_index_prefix'].$typeConverter::SUBPREFIX_INDEX.'price:['.$from.' TO '.$to.']');
        } else {
            $adapter = $this->_getReadAdapter();

            $conditions = array();
            if (strlen($value['from']) > 0) {
                $conditions[] = $adapter->quoteInto('price_index.min_price %s * %s >= ?', $value['from']);
            }
            if (strlen($value['to']) > 0) {
                $conditions[] = $adapter->quoteInto('price_index.min_price %s * %s <= ?', $value['to']);
            }

            if (!$conditions) {
                return false;
            }

            $object->getProductCollection()->addPriceData();
            $select     = $object->getProductCollection()->getSelect();
            $response   = $this->_dispatchPreparePriceEvent($select);
            $additional = join('', $response->getAdditionalCalculations());

            $rate = 1;
            if (!empty($value['currency'])) {
                $rate = Mage::app()->getStore()->getBaseCurrency()->getRate($value['currency']);
            }

            foreach ($conditions as $condition) {
                $select->where(sprintf($condition, $additional, $rate));
            }
        }
        return true;
    }


    public function addIndexableAttributeFilter($object, $attribute, $value)
    {
        if (is_string($value) && strlen($value) == 0) {
            return false;
        }
        if(Mage::helper('solr')->isEnabled()) {
				
				$typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter();
                $code = $attribute->getAttributeCode();
				$items = $typeConverter->getItems();
                $field = $items[$attribute->getFrontend()->getInputType()]['solr_index_prefix'].$typeConverter::SUBPREFIX_INDEX.$attribute->getAttributeCode();
                if (is_array($value) && (isset($value['from']) || isset($value['to']))) {
                    if (isset($value['from']) && !empty($value['from'])) {
                        $from = $value['from'];
                    } else {
                        $from = '*';
                    }
                    if (isset($value['to']) && !empty($value['to'])) {
                        $to = $value['to'];
                    } else {
                        $to = '*';
                    }
                    $select->where($field . ':['.$from.' TO '.$to.']');
                    return true;
                } else if (is_array($value)) {
                    $value = implode(' ', $value);
                } else {
                    $value = Apache_Solr_Service::escape($value);
                    $value = $this->addFuzzySearch($value);
                }
                $object->getProductCollection()->getSelect()->where($field.':'.$value);
            
        } else {
            if ($attribute->getIndexType() == 'decimal') {
                $table = $this->getTable('catalog/product_index_eav_decimal');
            } else {
                $table = $this->getTable('catalog/product_index_eav');
            }

            $tableAlias = 'ast_' . $attribute->getAttributeCode();
            $storeId    = Mage::app()->getStore()->getId();
            $select     = $object->getProductCollection()->getSelect();

            $select->distinct(true);
            $select->join(
                array($tableAlias => $table),
                "e.entity_id={$tableAlias}.entity_id AND {$tableAlias}.attribute_id={$attribute->getAttributeId()}"
                    . " AND {$tableAlias}.store_id={$storeId}",
                array()
            );

            if (is_array($value) && (isset($value['from']) || isset($value['to']))) {
                if (isset($value['from']) && !empty($value['from'])) {
                    $select->where("{$tableAlias}.`value` >= ?", $value['from']);
                }
                if (isset($value['to']) && !empty($value['to'])) {
                    $select->where("{$tableAlias}.`value` <= ?", $value['to']);
                }
                return true;
            }

            $select->where("{$tableAlias}.`value` IN(?)", $value);
        }

        return true;
    }

    public function addFuzzySearch($query)
    {
        if((int)Mage::getStoreConfig('solr/searcher/fuzzy_enable')) {
            $terms = explode(' ', trim($query));
            $factor = null;
            if ((float)Mage::getStoreConfig('solr/searcher/fuzzy_similarity_factor')) {
                $factor = (float)Mage::getStoreConfig('solr/searcher/fuzzy_similarity_factor');
            }

            foreach ($terms as $key => $one) {
                if ($one) {
                    $newterms[] = $one . '~' . $factor;
                }
            }
            $query = implode(' ', $newterms);
        }

        return $query;
    }

    public function addFilter($object, $attribute, $value)
    {
        if (strlen($value) > 0) {
            $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter();

                $code = $attribute->getAttributeCode();
                $items = $typeConverter->getItems();
                $field = $items[$attribute->getFrontend()->getInputType()]['solr_index_prefix'].$typeConverter::SUBPREFIX_INDEX.$attribute->getAttributeCode();
				
                if (is_array($value) && (isset($value['from']) || isset($value['to']))) {
                    if (isset($value['from']) && !empty($value['from'])) {
                        $from = $value['from'];
                    } else {
                        $from = '*';
                    }
                    if (isset($value['to']) && !empty($value['to'])) {
                        $to = $value['to'];
                    } else {
                        $to = '*';
                    }
                    $select->where($field . ':[' . $from . ' TO ' . $to . ']');
                    return true;
                } else if (is_array($value)) {
                    $value = implode(' ', $value);
                } else {
                    $value = Apache_Solr_Service::escape($value);
                    $value = $this->addFuzzySearch($value);
                }
                $object->getProductCollection()->getSelect()->where($field.':'.$value);
                return true;
            
        }
        return false;
    }

}

