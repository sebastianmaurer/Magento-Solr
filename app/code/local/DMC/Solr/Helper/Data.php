<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Helper_Data extends Mage_CatalogSearch_Helper_Data
{
    private $_solr = null;
    private $_solrServerUrl = null;
    private $_debug = null;
    private $_enabled = null;
    private $_cacheVersion = null;

    private $_enabledOnCatalog = null;
    private $_enabledOnSearchResult = null;
    protected $_suggest;

    const PRODUCTS_BY_PERIOD = 100;

    public function getSolr()
    {
        return Mage::getSingleton('DMC_Solr_Model_SolrServer_Adapter');
    }

    public function isEnabled()
    {
        if (is_null($this->_enabled)) {
            $this->_enabled = false;
            if (Mage::getStoreConfig('solr/general/enable')) {
                $this->_enabled = $this->getSolr()->ping();
            }
        }
        return $this->_enabled;
    }

    public function isEnabledOnCatalog()
    {
        if (is_null($this->_enabledOnCatalog)) {
            $this->_enabledOnCatalog = Mage::getStoreConfig('solr/general/enable_on_catalog') && $this->isEnabled();
        }
        return $this->_enabledOnCatalog;
    }

    public function isEnabledOnSearchResult()
    {
        if (is_null($this->_enabledOnSearchResult)) {
            $this->_enabledOnSearchResult = Mage::getStoreConfig('solr/general/enable_on_search_result') && $this->isEnabled();
        }
        return $this->_enabledOnSearchResult;
    }

    public function getDebug() 
    {
        if(is_null($this->_debug)) {
            if(strlen(Mage::getStoreConfig('solr/general/server_url'))) {
                $this->_debug = Mage::getSingleton('DMC_Solr_Model_Debug');
            }
        }
        return $this->_debug;
    }

    public function isCurrentVersionMore($version)
    {
        if(is_null($this->_cacheVersion)) {
            $serverVer = Mage::getVersionInfo();
            $tmp = sprintf('%02s%02s%02s%02s', $serverVer['major'], $serverVer['minor'], $serverVer['revision'], $serverVer['patch']);
            $this->_cacheVersion = (int)$tmp;
        }
        $tmp = explode('.', $version);
        $version = '';
        for($i=0;$i<4;$i++) {
            if(isset($tmp[$i])) {
                $version .= sprintf('%02s',$tmp[$i]);
            }
            else {
                $version .= '00';
            }
        }
        return ($this->_cacheVersion >= $version) ? true : false;
    }

    public function isDebugMode()
    {
        return Mage::getStoreConfig('solr/general/debug') ? true : false;
    }

    public function isLogMode()
    {
        return Mage::getStoreConfig('solr/general/log') ? true : false;
    }

    public function reindexProductIds($ids)
    {
        $solr = $this->getSolr();
        $responce = $solr->ping();
        if(!$responce) {
            return;
        }

        $product = Mage::getModel('catalog/product');
                $solrProduct = new DMC_Solr_Model_SolrServer_Adapter_Product;

        foreach ($ids as $one) {
            $product->load($one);
            if ($product->getId()) {
                $document = $solrProduct->getSolrDocument();
                if($document->setObject($product)) {
                    $solr->addDocument($document);
                }
            }
        }
        $solr->addDocuments();
    }

    public function getStoresForReindex()
    {
        $storeIds = array();
        $collections = Mage::getModel('core/store')->getCollection();
        $collections->addFieldToFilter('store_id', array('neq' => 0));
        $collections->load();
        foreach($collections as $store) {
            if(Mage::getStoreConfig('solr/general/enable', $store->getId())) {
                $storeIds[] = $store->getId();
            }
        }

        return $storeIds;
    }

    /** Proximity search
     * (To search for "apache" and "jakarta" within N words of each other in a document)
     * @return N (count of words) or false (if not proximity search)
     */
    public function getProximitySearch() {
        return 10;
    }


    public function catalogCategoryMultiselectEnabled()
    {
        $result = false;
        if ($this->isEnabled() && Mage::getStoreConfig('solr/catalog_navigation/allow_multiselect')) $result = true;
        return $result;
    }

    public function displayFilterItemCount()
    {
        $result = false;
        if (Mage::getStoreConfig('solr/catalog_navigation/display_filter_item_count')) $result = true;
        return $result;
    }

    public function showCategories()
    {
        return Mage::getStoreConfigFlag('solr/catalog_navigation/show_categories');
    }
    
    public function setQueryText ($query)
    {
        $this->_queryText = $query;
    }
    
    public function setSpellcheckNotes ($note)
    {
        $this->addNoteMessage($note);
    }
    
    public function setSuggest ($suggest)
    {
        $this->_suggest = $suggest;
    }
    
    public function getSuggest ()
    {
        return $this->_suggest;
    }
    
    public function getTranslation ($string)
    {
        return $this->__($string);
    }
    
    public function checkRedirect ()
    {
        $helper = Mage::helper('solr')->getSolr()->checkIfRedirect(Mage::helper('solr')->_query);
        
        if($helper)
        {
            return $helper->getRedirectURL();
        }
    
    }
    /*
    public function getPromoContent ()
    {
        $data = new DMC_Solr_Block_View();
        return $data->preparePromotion();
    }
    */
}
