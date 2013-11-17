<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Adminhtml_System_Buttons_Partreindex extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '<a id="solr-part-reind-popup-link" href="#solr_part_reind_popup_content"><div>'.$this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel(Mage::helper('solr')->__('Open'))
                    ->toHtml().'</div></a>';
        $html .= $this->getLayout()->createBlock('solr/adminhtml_partreindex')->setTemplate('dmc_solr/particular_reindexation_popup.phtml')->toHtml();
        return $html;
    }
}
