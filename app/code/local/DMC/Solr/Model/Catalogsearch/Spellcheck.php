<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalogsearch_Spellcheck
{

    public function toOptionArray ()
    {
        return array(
                array(
                        'value' => 0,
                        'label' => Mage::helper('solr')->__('off')
                ),
                array(
                        'value' => 1,
                        'label' => Mage::helper('solr')->__('auto')
                ),
                array(
                        'value' => 2,
                        'label' => Mage::helper('solr')->__('suggest')
                )
        );
    }
}

