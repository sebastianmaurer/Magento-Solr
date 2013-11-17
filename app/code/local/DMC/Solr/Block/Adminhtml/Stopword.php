<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_stopword';
        $this->_blockGroup = 'solr';
        $this->_headerText = Mage::helper('solr')->__('Dictionary of stopwords');
        
        $this->_addButton('import', array(
                'label'     => Mage::helper('solr')->__('Import Dictionary'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/import') .'\')',
                'class'     => 'import',
        ));
        $this->_addButton('export', array(
                'label'     => Mage::helper('solr')->__('Export Dicitinary'),
                'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/export') .'\')',
                'class'     => 'export',
        ));

        parent::__construct();
    }
}
