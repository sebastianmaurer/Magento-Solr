<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Catalogsearch_Layer_View extends Mage_CatalogSearch_Block_Layer
{
    protected function _initBlocks()
    {
        parent::_initBlocks();
        $this->_stateBlockName              = 'catalog/layer_state';
        $this->_categoryBlockName           = 'solr/layer_filter_category';
        $this->_attributeFilterBlockName    = 'solr/layer_filter_attribute';
        $this->_priceFilterBlockName        = 'solr/layer_filter_price';
        $this->_decimalFilterBlockName      = 'solr/layer_filter_decimal';
    }
    
    public function getLayer()
    {
        return Mage::getSingleton('solr/catalogsearch_layer');
    }
}