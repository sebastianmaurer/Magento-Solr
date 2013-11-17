<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogSearch
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_SolrServer_Adapter_Cms_Collection extends DMC_Solr_Model_SolrServer_Adapter_AbstractCollection
{
    public function __construct($conn=null)
    {
        parent::__construct();
        $this->_init('cms/page');
    }

    protected function _init($model, $entityModel=null)
    {
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName($model));
        return $this;
    }


    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_renderFilters()
            ->_renderOrders()
            ->_renderLimit();

        $data = $this->getData();
        $this->resetData();

        if (is_array($data)) {
            $setIds = array();
            foreach ($data as $row) {
                $item = $this->getNewEmptyItem();
                if ($this->getIdFieldName()) {
                    $item->setIdFieldName($this->getIdFieldName());
                }
                $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Cms_TypeConverter();
                foreach($row as $key => $value) {
                    $productAttrName = $typeConverter->getProductAttributeName($key);
                    if(!is_null($productAttrName)) {
                        $row[$productAttrName] = $value;
                    }
                }

                $row['page_id'] = $row['id'];
                $row['request_path'] = isset($row['rewrite_path']) ? $row['rewrite_path'] : null;

                $item->addData($row);
                $this->addItem($item);
            }
        }

        $this->_setIsLoaded();
        $this->_afterLoad();
        return $this;
    }

   
    /**
     * Fetch collection data
     *
     * @param   Zend_Db_Select $select
     * @return  array
     */
    protected function _fetchAll($select)
    {
        $product = Mage::getModel('catalog/product');
        $select->where('row_type:cms');
        $dataObject = $this->getConnection()->fetchAll($select);
        $data = $dataObject->__get('response');
        $facet = $dataObject->__get('facet_counts');
        if(isset($facet->facet_fields)) {
            $this->_facetCategoryCount = $facet->facet_fields->available_category_ids;
        }
        $this->_totalRecords = $data->numFound;
        $fields = null;
        $retData = array();

        foreach($data->docs as $row) {
            if(is_null($fields)) $fields = $row->getFieldNames();
            $retRow = array();
            foreach($fields as $field) {
                $retRow[$field] = $row->$field;
            }
            $retData[] = $retRow;
        }
        return $retData;
    }



    protected function _fetchStatistic($select)
    {
        $select->addField('row_id');
        $select->addField('row_type');
        $select->addField('attribute_set_id');
        $dataObject = $this->getConnection()->fetchAll($select);
        $data = $dataObject->__get('response');
        $fields = null;
        $retData = array();

        $this->_totalRecords = $data->numFound;

        if(count($data->docs)) {
            $setIds = array();
            foreach($data->docs as $row) {
                $setIds[] = $row->attribute_set_id;
            }
            $this->_setIds = array_unique($setIds);
        }
    }

   
    /**
     * Add search query filter
     *
     * @param   Mage_CatalogSearch_Model_Query $query
     * @return  Mage_CatalogSearch_Model_Mysql4_Search_Collection
     */
    public function addSearchFilter($query = NULL)
    {
        if(is_null($query)) $query = Mage::helper('solr')->getQuery();
        $where = '';
        $query = Apache_Solr_Service::escape($query);
        //$query = $this->addFuzzySearch($query);
        $where = 'attr_t_search_content_heading:'.$query.'*';
        $where .= ' OR attr_t_search_content:'.$query.'*';
        $where .= ' OR attr_t_search_title:'.$query.'*';
        $this->getSelect()->where($where);
        return $this;
    }
}

