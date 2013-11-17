<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function pingAction()
    {
        $solr = Mage::helper('solr')->getSolr();
        if($solr) {
            $responce = $solr->ping();
            echo $solr->getLastPingMessage();
        }
        else {
            echo '<font color="red">'.Mage::helper('solr')->__('Solr is unavailable, please check connection settings and solr server status').'</font>';
        }
    }

    public function snycconfigAction()
    {
        $api = Mage::helper('solr/manager');
        if($api) {
            echo $api->uploadSynonyms();
        }
        else {
            echo '<font color="red">'.Mage::helper('solr')->__('Solr is unavailable, please check connection settings and solr server status').'</font>';
        }
    }

    public function clearAction()
    {
        $solr = Mage::helper('solr')->getSolr();
        if($solr) {
            $responce = $solr->deleteDocuments();
            if($responce->getHttpStatus() == '200') {
                echo '<font color="green">'.Mage::helper('solr')->__('Solr indexes are cleared').'</font>';
            }
        }
        else {
            echo '<font color="red">'.Mage::helper('solr')->__('Solr is unavailable, please check connection settings and solr server status').'</font>';
        }
    }
    
    public function partreindexAction()
    {
        $storeId = $this->getRequest()->getParam('solrStoreReindex', false);
        $error = false;
        $errorMsgs = array();
        $helper = Mage::helper('solr');
        if ($storeId === false || $storeId < 0) {
            $error = true;
            $errorMsgs[] = $helper->__('Solr: wrong request');
        } 
        if (!$error) {
            if($helper->isEnabled() && $helper->getSolr()->ping()) {
                $indexer = Mage::getModel('solr/indexer');
                if ($storeId > 0) {
                    $allowedStores = $indexer->getStoresForReindex();
                    if (in_array($storeId, $allowedStores)) {
                        $indexer->reindexStore($storeId);
                    } else {
                        $error = true;
                        $errorMsgs[] = $helper->__('Solr: wrong request');
                    }
                } else {
                    $indexer->reindexAll();
                }
            } else {
                $error = true;
                $errorMsgs[] = $helper->__('Solr is unavailable, please check connection settings and solr server status');
            }
        }
        
        $session = $this->_getSession();
        if (!$error) {
            $message = "";
            if ($storeId > 0) {
                $message = $helper->__('Solr: "%s" store successfully reindexed', Mage::getModel('core/store')->load($storeId)->getName());
            } else {
                $message = $helper->__('Solr: the all stores successfully reindexed');
            }
            $session->addSuccess($message);
        } else {
            foreach($errorMsgs as $errorMessage){
                $session->addError($errorMessage);
            }
        }
        $this->_redirect("adminhtml/system_config/edit", array('section'=>'solr'));
    }
    
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
    
}
