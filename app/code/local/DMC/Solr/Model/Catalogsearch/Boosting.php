<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalogsearch_Boosting
{

    public function toOptionArray ()
    {
        return array(
            array(
                    'value' => 'desc',
                    'label' => Mage::helper('solr')->__('descending')
            ),
            array(
                    'value' => 'asc',
                    'label' => Mage::helper('solr')->__('ascending')
            )
        );
    }
}

