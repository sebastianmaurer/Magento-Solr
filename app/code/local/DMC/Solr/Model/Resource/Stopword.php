<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Model_Resource_Stopword extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('solr/stopword', 'stopword_id');
    }
}
