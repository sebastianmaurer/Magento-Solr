<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Layer_Filter_Price extends Mage_Catalog_Block_Layer_Filter_Price
{
    public function __construct()
    {
        parent::__construct();
        if (Mage::helper('solr')->catalogCategoryMultiselectEnabled()){
            $this->setTemplate('dmc_solr/catalog/layer/price_slider.phtml');
        } else {
            $this->setTemplate('dmc_solr/catalog/layer/filter.phtml');
        }
        $this->_filterModelName = 'solr/catalog_layer_filter_price';
    }

    public function shouldDisplayProductCount()
    {
        return Mage::helper('solr')->displayFilterItemCount();
    }
    
    public function sliderGetUrl()
    {
        $currentAttrCode = $this->getAttributeModel()->getAttributeCode();
        $model = Mage::getModel('core/url');
        $currentQuery = $model->getRequest()->getQuery();
        if (isset($currentQuery[$currentAttrCode])) {
            unset($currentQuery[$currentAttrCode]);
        }
        return Mage::getUrl('*/*/*', array(/*'_current'=>true,*/ '_use_rewrite'=>true, '_query'=>$currentQuery));
    }

}
