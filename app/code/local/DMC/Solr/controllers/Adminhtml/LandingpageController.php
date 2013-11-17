<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Adminhtml_LandingpageController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu( 'dmc_solr' );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
    
        return $this;
    }
    
    public function indexAction()
    {
        $this->_title($this->__('Manage Landingpages'));
        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('solr/adminhtml_landingpage'));
        $this->renderLayout();
    }
    
    public function newAction()
    {
        $this->_getModel();
    
        $this->_title(Mage::helper('solr')->__('Add Landing Page'));
        $this->_initAction()
        ->_addContent($this->getLayout()->createBlock('solr/adminhtml_landingpage_edit'))
        ->renderLayout();
    }
    
    public function editAction()
    {
        $model = $this->_getModel();
    
        if ($model->getId()) {
            $this->_title(Mage::helper('solr')->__('Edit Landing Page for "%s"', $model->getQueryText()));
    
            $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('solr/adminhtml_landingpage_edit'))
            ->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('action')->__('The Landing Page does not exist.'));
            $this->_redirect('*/*/');
        }
    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
    
            try {
                $model = $this->_getModel();
                $model->addData($data)
                ->save();
                
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('solr')->__('Landing Page for "%s" saved', $model->getData('query_text')));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                
                $observer = new DMC_Solr_Model_Observer();
                $observer->solr_landingpage_save_after($model);
                
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
    
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
    
                $this->_redirect('*/*/');
            }
        }
    }
    
    public function deleteAction()
    {
        try {
            $model = $this->_getModel();
            $observer = new DMC_Solr_Model_Observer();
            $observer->solr_landingpage_delete_before($model);
            $model->delete();
    
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('solr')->__('Landing Page was successfully deleted'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    
        $this->_redirect('*/*/');
    }
    
    protected function _getModel()
    {
        $model = Mage::getModel('solr/landingpage');
    
        if ($id = $this->getRequest()->getParam('id')) {
            $model->load($id);
        }
    
        Mage::register('current_model', $model);
    
        return $model;
    }
    public function massDeleteAction()
    {
        ini_set("memory_limit","512M");//@TODO: Check if needed in production env.
        ini_set("max_execution_time","120");//@TODO: Check if needed in production env.
        $ids = $this->getRequest()->getParam( 'landingpage' );
    
        if( ! is_array( $ids ) )
        {
            Mage::getSingleton( 'adminhtml/session' )->addError( Mage::helper( 'solr' )->__( 'Please select pages' ) );
        }
        else
        {
            try
            {
                foreach( $ids as $itemId )
                {
                    $model = Mage::getModel( 'solr/landingpage' )->setIsMassDelete( true )->load( $itemId );
                    $observer = new DMC_Solr_Model_Observer();
                    $observer->solr_landingpage_delete_before($model);
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
    
}
