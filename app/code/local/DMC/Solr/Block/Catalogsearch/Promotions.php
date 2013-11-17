<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

class DMC_Solr_Block_Catalogsearch_Promotions extends Mage_Core_Block_Template
{
    protected $_promoContent;

    protected function _prepareLayout()
    {
        $solr = Mage::helper( 'solr' )->getSolr();
        $this->_promoContent = $solr->getPromotion( Mage::helper( 'catalogSearch' )->getQuery() );

        return parent::_prepareLayout();
    }
    
    public function getPromotionContent()
    {
        return $this->_promoContent['additional_content'];
    }

    public function canShow()
    {
        $position = $this->_promoContent['position'];
        return $this->getBlockAlias() == 'promocontent.'.$position;
    }
}