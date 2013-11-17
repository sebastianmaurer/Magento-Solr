<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword_Export_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));

        $general = $form->addFieldset('general', array('legend' => Mage::helper('solr')->__('Export')));
        
        $general->addField('export', 'hidden', array(
            'name'  => 'export',
            'value' => 1,
        ));

        $general->addField('outputfile', 'text', array(
            'name'     => 'outputfile',
            'label'    => Mage::helper('solr')->__('Outputfile'),
            'required' => true
        ));

        $form->setAction($this->getUrl('*/*/saveExport'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
