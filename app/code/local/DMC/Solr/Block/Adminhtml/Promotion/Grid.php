<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */


class DMC_Solr_Block_Adminhtml_Promotion_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        $this->setId('promotionGrid');
        $this->setDefaultSort('promotion_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('solr/promotion')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('promotion_id', array(
            'header' => Mage::helper('solr')->__('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'promotion_id',
        ));

        $this->addColumn('query_text', array(
            'header' => Mage::helper('solr')->__('Search Term'),
            'align'  => 'left',
            'index'  => 'query_text',
        ));

        $this->addColumn('url_key', array(
            'header' => Mage::helper('solr')->__('Block Content'),
            'align'  => 'left',
            'index'  => 'snippet',
        ));

        $this->addColumn('is_active', array(
            'header' => Mage::helper('solr')->__('Is Active'),
            'align'  => 'left',
            'index'  => 'is_active',
            'type'      => 'options',
            'options'   => array(
                0 => Mage::helper('solr')->__('Disabled'),
                1 => Mage::helper('solr')->__('Enabled')
            ),
        ));


        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store', array(
                    'header'  => Mage::helper('solr')->__('Store'),
                    'align'   => 'left',
                    'width'   => '200px',
                    'index'   => 'store',
                    'type'    => 'options',
                    'options' => Mage::getSingleton('adminhtml/system_store')->getStoreOptionHash()
            ));
        }
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('promotion_id');
        $this->getMassactionBlock()->setFormFieldName('promotion');
    
        $this->getMassactionBlock()->addItem('delete', array(
                'label'   => Mage::helper('solr')->__('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('solr')->__('Are you sure?')
        ));
        return $this;
    }


    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
