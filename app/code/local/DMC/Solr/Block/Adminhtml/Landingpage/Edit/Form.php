<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Adminhtml_Landingpage_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry( 'current_model' );
        
        $form = new Varien_Data_Form( array(
                'id' => 'edit_form',
                'action' => $this->getData( 'action' ),
                'method' => 'post',
                'enctype' => 'multipart/form-data' 
        ) );
        
        $general = $form->addFieldset( 'general', array(
                'legend' => Mage::helper( 'solr' )->__( 'General Information' ) 
        ) );
        
        if( $model->getId() )
        {
            $general->addField( 'landingpage_id', 'hidden', array(
                    'name' => 'landingpage_id',
                    'value' => $model->getId() 
            ) );
        }
        
        $general->addField( 'query_text', 'text', array(
                'name' => 'query_text',
                'label' => Mage::helper( 'solr' )->__( 'Search Term' ),
                'required' => true,
                'value' => $model->getQueryText() 
        ) );
        
        $general->addField( 'redirect', 'text', array(
                'name' => 'redirect',
                'label' => Mage::helper( 'solr' )->__( 'Redirect Url' ),
                'required' => true,
                'value' => $model->getRedirect(),
                'note' => 'http://digitalmanufaktur.com' 
        ) );
        
        $general->addField( 'is_active', 'select', array(
                'name' => 'is_active',
                'label' => Mage::helper( 'solr' )->__( 'Active' ),
                'required' => true,
                'value' => $model->getIsActive(),
                'values' => Mage::getSingleton( 'adminhtml/system_config_source_yesno' )->toOptionArray() 
        ) );
        
        if( ! Mage::app()->isSingleStoreMode() )
        {
            $general->addField( 'store_id', 'select', array(
                    'label' => Mage::helper( 'solr' )->__( 'Store View' ),
                    'required' => true,
                    'name' => 'store_id',
                    'values' => Mage::getSingleton( 'adminhtml/system_store' )->getStoreValuesForForm() 
            ) );
        }
        else
        {
            $general->addField( 'store_id', 'hidden', array(
                    'name' => 'store_id',
                    'value' => Mage::app()->getStore( true )->getId() 
            ) );
        }
        
        $form->setAction( $this->getUrl( '*/*/save' ) );
        $form->setUseContainer( true );
        $this->setForm( $form );
        
        return parent::_prepareForm();
    }
}
