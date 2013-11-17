<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Block_Catalogsearch_Tabs extends Mage_Core_Block_Template
{
    // Constants
    const XML_SOLR_ADDITIONAL_DOCUMENTS = 'global/solr_additional_documents';
    const XML_SOLR_ADDITIONAL_DOCUMENT_EXCEPTIONS = 'global/solr_additional_document_exceptions';
    // member vars
    private $_additionalDocTypeExceptions = array();
    public function getAdditionalDocTypes()
    {
        $additionalDocs = Mage::getConfig()->getNode( self::XML_SOLR_ADDITIONAL_DOCUMENTS )->children();
        $exceptions = Mage::getConfig()->getNode( self::XML_SOLR_ADDITIONAL_DOCUMENT_EXCEPTIONS )->children();
        
        foreach( $exceptions as $key => $item )
        {
            unset( $additionalDocs->$key );
        }
        $this->_additionalDocTypeExceptions = $exceptions;

        return $additionalDocs;
    }
    public function getAdditionalDocTypeExceptions()
    {
        if( ! $this->_additionalDocTypeExceptions )
        {
            $this->_additionalDocTypeExceptions = Mage::getConfig()->getNode( self::XML_SOLR_ADDITIONAL_DOCUMENT_EXCEPTIONS )->children();
        }
        
        return $this->_additionalDocTypeExceptions;
    }
}
