<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ));

        $general = $form->addFieldset('general', array('legend' => Mage::helper('solr')->__('Import')));
        
        $general->addField('import', 'hidden', array(
            'name'  => 'import',
            'value' => 1,
        ));

        $general->addField('file', 'select', array(
            'name'     => 'file',
            'label'    => Mage::helper('solr')->__('Dictionary'),
            'required' => true,
            'values'   => $this->getDictionaries(),
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

        $form->setAction($this->getUrl('*/*/saveImport'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getDictionaries()
    {
        $values = array();
        $path   = Mage::getBaseDir('media').DS.'solr'.DS.'stopwords'.DS.'default'.DS;
        if (file_exists($path)) {
            if ($handle = opendir($path)) {
                while (false !== ($entry = readdir($handle))) {
                    if (substr($entry, 0, 1) != '.') {
                        $values[] = array(
                            'label' => $entry,
                            'value' => $path.DS.$entry
                        );
                    }
                }
                closedir($handle);
            }
        }

        return $values;
    }
}
