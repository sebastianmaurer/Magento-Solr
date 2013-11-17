<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */

class DMC_Solr_Block_Catalogsearch_Result extends Mage_Core_Block_Template
{
    // member vars
    private $_resultCollection = false;
    private $_resultCount = false;
    private $_additionalDocTypes = array();
    private $_additionalDocTypeExceptions = array();
    public function init()
    {
        self::_prepareLayout();
        $query = $this->helper('solr')->getQueryText();
        if( strlen( $query ) )
        {
            $docType = $_GET['type'];
            
            if( ! $this->_additionalDocTypes )
            {
                $tabBlock = $this->getLayout()->getBlockSingleton( 'solr/catalogsearch_tabs' );
                $this->_additionalDocTypes = $tabBlock->getAdditionalDocTypes();
            }
            
            try
            { // @TODO: check if obsolete...because of check in tabs Block
                $this->_additionalDocTypeExceptions = $tabBlock->getAdditionalDocTypeExceptions();
                if( $this->_additionalDocTypeExceptions->$docType->asArray() )
                {
                    return array();
                }
            }
            catch( Exception $e )
            {}
            $this->setSearchFilter( Mage::app()->getRequest()->getQuery( 'q' ) );
            
            $this->_resultCollection = Mage::getModel( $this->_additionalDocTypes->$docType->collection_class->asArray() );
            $this->_resultCollection->addStoreFilter();
            $this->_resultCollection->addSearchFilter( $this->getSearchFilter() );
            
            $typeConverter = $this->_additionalDocTypes->$docType->type_converter->asArray();
            $tcObject = new $typeConverter();
            $this->_resultCollection->mapping = $tcObject::$staticMapping;
            
            $this->_resultCount = $this->_resultCollection->count();
            
            return $this->_resultCollection;
        }
        else
        {
            return array();
        }
    }
    public function getResultCount()
    {
        if( ! $this->_resultCollection )
        {
            self::init();
        }
        return $this->_resultCount;
    }
    public function getResultCollection()
    {
        if( ! $this->_resultCollection )
        {
            self::init();
        }
        return $this->_resultCollection;
    }
    public function getAdditionalDocs()
    {
        if( ! $this->_additionalDocTypes )
        {
            $tabBlock = $this->getLayout()->createBlock( 'solr/catalogsearch_tabs' );
            $this->_additionalDocTypes = $tabBlock->getAdditionalDocTypes();
        }
        return $this->_additionalDocTypes;
    }
    /**
     * Prepare layout
     *
     * @return Mage_CatalogSearch_Block_Result
     */
    protected function _prepareLayout()
    {
        // add Home breadcrumb
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $title = $this->__("Search results for: '%s'", $this->helper('solr')->getQueryText());

            $breadcrumbs->addCrumb('home', array(
                'label' => $this->__('Home'),
                'title' => $this->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ))->addCrumb('search', array(
                'label' => $title,
                'title' => $title
            ));
        }
    }
}