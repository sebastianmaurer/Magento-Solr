<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_AdditionalController extends Mage_Core_Controller_Front_Action
{
    /**
     * Display additional Content instaed of common SearchResults
     */
    public function indexAction()
    {
        $query = Mage::helper('solr')->getQuery();

        $query->setStoreId(Mage::app()->getStore()->getId());
        
        if ($query->getQueryText()) {
            if (Mage::helper('solr')->isMinQueryLength()) {
                $query->setId(0)
                ->setIsActive(1)
                ->setIsProcessed(1);
            }
            else {
                if ($query->getId()) {
                    $query->setPopularity($query->getPopularity()+1);
                }
                else {
                    $query->setPopularity(1);
                }
                if (($redirectURL=Mage::helper('solr')->checkRedirect())){
                    $query->save();
                    $this->getResponse()->setRedirect($redirectURL);
                    return;
                }
                else {
                    $query->prepare();
                }
            }
            
            Mage::helper('solr')->checkNotes();
    
            $this->loadLayout();
            
            $this->_initLayoutMessages('catalog/session');
            $this->_initLayoutMessages('checkout/session');
            $this->renderLayout();
    
            if (!Mage::helper('solr')->isMinQueryLength()) {
                $query->save();
            }
        }
        else {
            $this->_redirectReferer();
        }
    }
}
