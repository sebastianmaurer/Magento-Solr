<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Adminhtml_SynonymController extends Mage_Adminhtml_Controller_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
        
        return $this;
    }
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu( 'dmc_solr' );
        $this->getLayout()->getBlock( 'head' )->setCanLoadExtJs( true );
        
        return $this;
    }
    public function indexAction()
    {
        $this->_title( $this->__( 'Dictionary of synonyms' ) );
        $this->_initAction();
        $this->_addContent( $this->getLayout()->createBlock( 'solr/adminhtml_synonym' ) );
        $this->renderLayout();
    }
    public function newAction()
    {
        $this->_getModel();
        
        $this->_title( Mage::helper( 'solr' )->__( 'Add Stopword' ) );
        $this->_initAction()->_addContent( $this->getLayout()->createBlock( 'solr/adminhtml_synonym_edit' ) )->renderLayout();
    }
    public function editAction()
    {
        $model = $this->_getModel();
        
        if( $model->getId() )
        {
            $this->_title( Mage::helper( 'solr' )->__( 'Edit Synonym "%s"', $model->getTitle() ) );
            
            $this->_initAction()->_addContent( $this->getLayout()->createBlock( 'solr/adminhtml_synonym_edit' ) )->renderLayout();
        }
        else
        {
            Mage::getSingleton( 'adminhtml/session' )->addError( Mage::helper( 'action' )->__( 'The Synonym does not exist.' ) );
            $this->_redirect( '*/*/' );
        }
    }
    public function saveAction()
    {
        if( $data = $this->getRequest()->getPost() )
        {
            
            try
            {
                $model = $this->_getModel();
                $model->addData( $data )->save();
                
                Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'solr' )->__( 'Synonyms for "%s" saved', $model->getData( 'word' ) ) );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( false );
                
                if( $this->getRequest()->getParam( 'back' ) )
                {
                    $this->_redirect( '*/*/edit', array(
                            'id' => $model->getId() 
                    ) );
                    return;
                }
                
                $this->_redirect( '*/*/' );
            }
            catch( Exception $e )
            {
                Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( $data );
                
                $this->_redirect( '*/*/' );
            }
        }
    }
    public function importAction()
    {
        $this->_initAction();
        
        $this->_addContent( $this->getLayout()->createBlock( 'solr/adminhtml_synonym_import' ) );
        
        $this->renderLayout();
    }
    public function saveImportAction()
    {
        if( $data = $this->getRequest()->getPost() )
        {
            try
            {
                $result = Mage::getSingleton( 'solr/synonym' )->import( $data['file'], $data['store'] );
                
                Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'solr' )->__( 'Imported %s synonyms', $result ) );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( false );
                
                $this->_redirect( '*/*/' );
            }
            catch( Exception $e )
            {
                Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( $data );
                
                $this->_redirect( '*/*/import' );
            }
        }
    }
    public function exportAction()
    {
        $this->_initAction();
        
        $this->_addContent( $this->getLayout()->createBlock( 'solr/adminhtml_synonym_export' ) );
        
        $this->renderLayout();
    }
    public function saveExportAction()
    {
        if( $data = $this->getRequest()->getPost() )
        {
            try
            {
                $result = Mage::getSingleton( 'solr/synonym' )->export( $data['outputfile'], ( isset( $data['exportMGSynonyms'] ) ? true : null ) );
                Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'solr' )->__( '%s synonyms', $result['count'] ) . Mage::helper( 'solr' )->__( ' exported to %s ', $result['path'] ) );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( false );
                
                $this->_redirect( '*/*/' );
            }
            catch( Exception $e )
            {
                Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
                Mage::getSingleton( 'adminhtml/session' )->setFormData( $data );
                
                $this->_redirect( '*/*/export' );
            }
        }
    }
    public function massDeleteAction()
    {
        ini_set( "memory_limit", "512M" ); // @TODO: Check if needed in production env.
        ini_set( "max_execution_time", "120" ); // @TODO: Check if needed in production env.
        $ids = $this->getRequest()->getParam( 'synonym' );
        
        if( ! is_array( $ids ) )
        {
            Mage::getSingleton( 'adminhtml/session' )->addError( Mage::helper( 'solr' )->__( 'Please select synonym(s)' ) );
        }
        else
        {
            try
            {
                foreach( $ids as $itemId )
                {
                    $model = Mage::getModel( 'solr/synonym' )->setIsMassDelete( true )->load( $itemId );
                    $model->delete();
                }
                Mage::getSingleton( 'adminhtml/session' )->addSuccess( Mage::helper( 'solr' )->__( 'Total of %d record(s) were successfully deleted', count( $ids ) ) );
            }
            catch( Exception $e )
            {
                Mage::getSingleton( 'adminhtml/session' )->addError( $e->getMessage() );
            }
        }
        
        $this->_redirect( '*/*/index' );
    }
    protected function _getModel()
    {
        $model = Mage::getModel( 'solr/synonym' );
        
        if( $id = $this->getRequest()->getParam( 'id' ) )
        {
            $model->load( $id );
        }
        
        Mage::register( 'current_model', $model );
        
        return $model;
    }
}
