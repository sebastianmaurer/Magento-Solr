<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Adminhtml_System_Buttons_Ping extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $data = $this->getData();
        $form = $data['form'];
        $params = array();

        if(strlen($form->getWebsiteCode())) $params['website_code'] = 'website_code='.$form->getWebsiteCode();
        if(strlen($form->getStoreCode())) $params['store_code'] = 'store_code='.$form->getStoreCode();
        $paramsLine = implode('&', $params);
        $paramsLine = strlen($paramsLine) ? '?'.$paramsLine : '';
        $conf = Mage::getStoreConfig('web/url/use_store');
        $url = $this->getUrl('solr/adminhtml_index/ping') . $paramsLine;

        $html = '<div>'.$this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setType('button')
                    ->setClass('scalable')
                    ->setLabel(Mage::helper('solr')->__('Ping'))
                    ->setOnClick("ping();")
                    ->toHtml().'</div>';
        $html = $html.'<div id="ping_status" style="float:left"></div><script type="text/javascript">function ping( ) { new Ajax.Updater(\'ping_status\', \''.$url.'\', {method: \'post\',}); }</script>';
        return $html;
    }
}
?>
