<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Resource_Product_Document extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('solr/not_exists_table', 'some_field');
    }

    public function getAttributeValues($attribute, $productIds)
    {
        $result = array();
        //try{
            $table = $attribute->getBackend()->getTable();
            $readAdapter = $this->_getReadAdapter();
            $select = clone $readAdapter->select();
            $select->reset();
            $select->from(array('attr_table' => $table), array('store_id','entity_id', 'value'))
            ->where("attr_table.entity_id in (?)", $productIds, Zend_Db::INT_TYPE)
            ->where("attr_table.attribute_id = ?",$attribute->getid(), Zend_Db::INT_TYPE);

            $result = $readAdapter->fetchAll($select);
        //} catch (Exception $e) {}

        return $result;
    }

}
