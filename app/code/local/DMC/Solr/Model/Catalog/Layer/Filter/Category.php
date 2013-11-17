<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalog_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{
    public function getResetValue()
    {
        return null;
    }
    
    protected function _getItemsData()
    {
        $collection = $this->getLayer()->getProductCollection();

        $isSolrCollection = ($collection instanceof DMC_Solr_Model_SolrServer_Adapter_Product_Collection);
        if ($isSolrCollection) {
            $helper = Mage::helper('solr');
            if (!$helper->showCategories()) return array();
            $fselect = clone $collection->getSelect($helper->catalogCategoryMultiselectEnabled());
            $solrField = "available_category_ids";
            $fselect->param('facet', 'true', true);
            $fselect->param('facet.field', $solrField, true);
            $responce = Mage::helper('solr')->getSolr()->fetchAll($fselect);

            $optionsCount = $responce->__get('facet_counts')->facet_fields->$solrField;
            
            $tempArr = array();
            foreach($optionsCount as $catId => $count) {
                if ($count) {
                    $tempArr[$catId] = $count;
                }
            }
            $tempArr2 = array();
            unset($optionsCount);
            $categoryCollection = Mage::getModel('catalog/category')
                    ->getResourceCollection()
                    ->addIsActiveFilter()
                    ->addAttributeToSelect('name')
                    ->addFieldToFilter('level', array('gteq' => 3))
                    ->addIdFilter(array_keys($tempArr));
            foreach($tempArr as $catId => $count) {
                foreach($categoryCollection as $categoryObj) {
                    if ($categoryObj->getId() == $catId) {
                        $tempArr2[$catId] = array();
                        $tempArr2[$catId]['label'] = $categoryObj->getName();
                        $tempArr2[$catId]['count'] = $count;
                        break;
                    }
                }
            }
            unset($tempArr);
            
            $data = array();
            foreach($tempArr2 as $catId => $itemData) {
                $data[] = array(
                    'label' => $itemData['label'],
                    'value' => $catId,
                    'count' => $itemData['count'],
                );
            }
            return $data;
        } else {
            return parent::_getItemsData();
        }
    }
    
    public function getItems()
    {
        if (is_null($this->_items) || $this->_items == array()) {
            $this->_initItems();
        }
        return $this->_items;
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
