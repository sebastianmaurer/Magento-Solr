<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Stopword_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('solrStopwordGrid');
        $this->setDefaultSort('stopword_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('solr/stopword')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('stopword_id', array(
            'header' => Mage::helper('solr')->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'stopword_id',
        ));

        $this->addColumn('word', array(
            'header' => Mage::helper('solr')->__('Word'),
            'align'  => 'left',
            'index'  => 'word',
        ));

        $this->addColumn('store', array(
            'header'  => Mage::helper('solr')->__('Store'),
            'align'   => 'left',
            'width'   => '200px',
            'index'   => 'store',
            'type'    => 'options',
            'options' => Mage::getSingleton('adminhtml/system_store')->getStoreOptionHash()
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('stopword_id');
        $this->getMassactionBlock()->setFormFieldName('stopword');

        $this->getMassactionBlock()->addItem('delete', array(
            'label'   => Mage::helper('solr')->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('solr')->__('Are you sure?')
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return false;
    }
}
