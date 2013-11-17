<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct ()
    {
        parent::__construct();

        $this->_objectId   = 'stopword_id';
        $this->_blockGroup = 'solr';
        $this->_mode       = 'edit';
        $this->_controller = 'adminhtml_stopword';
        

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('solr')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";

        return $this;
    }

    public function getHeaderText()
    {
        if (Mage::registry('current_model')->getId() > 0) {
            return Mage::helper('solr')->__("Edit Stopword '%s'", $this->htmlEscape(Mage::registry('current_model')->getTitle()));
        } else {
            return Mage::helper('solr')->__("Add Stopword");
        }
    }
}
