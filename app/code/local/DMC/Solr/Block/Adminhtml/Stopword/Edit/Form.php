<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('current_model');

        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));

        $general = $form->addFieldset('general', array('legend' => Mage::helper('solr')->__('General Information')));

        if ($model->getId()) {
            $general->addField('stopword_id', 'hidden', array(
                'name'      => 'stopword_id',
                'value'     => $model->getId(),
            ));
        }

        $general->addField('word', 'text', array(
            'name'     => 'word',
            'label'    => Mage::helper('solr')->__('stopword'),
            'required' => true,
            'value'    => $model->getQueryText(),
        ));

            if (!Mage::app()->isSingleStoreMode()) {
            $general->addField('store', 'select', array(
                'label'    => Mage::helper('solr')->__('Store View'),
                'required' => true,
                'name'     => 'store',
                'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
            ));
        } else {
            $general->addField('store', 'hidden', array(
                'name'  => 'store',
                'value' => Mage::app()->getStore(true)->getId()
            ));
        }

        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
