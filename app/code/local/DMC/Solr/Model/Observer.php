<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Observer extends Mage_Core_Model_Abstract
{
    // commit moved to DMC_Solr_Model_Indexer
    //public function catalog_product_save_commit_after( $observer )
    //{
    //    if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
    //    {
    //        $solr = Mage::helper( 'solr' )->getSolr();
    //        $solr->commit();
    //    }
    //}

    public function catalog_product_delete_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $object = $observer->getEvent()->getDataObject();
            if( is_object( $object ) && $object->getId() )
            {
                $adapter = new DMC_Solr_Model_SolrServer_Adapter_Product();
                $solr = Mage::helper( 'solr' )->getSolr();
                $solr->deleteByQuery( 'id:' . $object->getId() . ' AND row_type:' . $adapter->getType() );
                $solr->commit();
            }
        }
    }
    public function catalog_entity_attribute_save_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $indexProcessObject = Mage::getSingleton( 'index/indexer' )->getProcessByCode( 'solr_indexer' );
            if( $indexProcessObject->getStatus() != Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX )
            {
                $attribute = $observer->getEvent()->getAttribute();
                $obj = new Varien_Object();
                $obj->setData( $attribute->getOrigData() );
                Mage::register( "solr_attribute_save_before_object", $obj );
            }
        }
    }
    public function catalog_entity_attribute_save_after( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $indexProcessObject = Mage::getSingleton( 'index/indexer' )->getProcessByCode( 'solr_indexer' );
            if( $indexProcessObject->getStatus() != Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX )
            {
                $newStateObject = $observer->getEvent()->getAttribute();
                $typeConverter = new DMC_Solr_Model_SolrServer_Adapter_Product_TypeConverter( $newStateObject->getFrontendInput() );
                if( $typeConverter && isset( $typeConverter->solr_index ) && $typeConverter->isSearchable() && $oldStateObject = Mage::registry( 'solr_attribute_save_before_object' ) )
                {
                    
                    if( $oldStateObject->getAttributeId() )
                    {
                        $prevState = ( boolean )$oldStateObject->getData( 'is_searchable' ) || ( boolean )$oldStateObject->getData( 'used_in_product_listing' ) || ( boolean )$oldStateObject->getData( 'is_visible_in_advanced_search' );
                        $newState = ( boolean )$newStateObject->getData( 'is_searchable' ) || ( boolean )$newStateObject->getData( 'used_in_product_listing' ) || ( boolean )$newStateObject->getData( 'is_visible_in_advanced_search' );
                        if( $prevState != $newState )
                        {
                            /*
                             * check whether this attribute belongs to any
                             * product attribute set
                             */
                            $attributeSets = Mage::getModel( 'eav/entity_attribute_set' )->getCollection()->setEntityTypeFilter( Mage::getModel( 'eav/entity_type' )->loadByCode( 'catalog_product' )->getId() );
                            $isInWorkingAttributeSet = false;
                            foreach( $attributeSets as $attributeSet )
                            {
                                $attributes = Mage::getModel( 'catalog/product_attribute_api' )->items( $attributeSet->getId() );
                                foreach( $attributes as $attribute )
                                {
                                    if( $attribute['attribute_id'] == $newStateObject->getAttributeId() )
                                    {
                                        $isInWorkingAttributeSet = true;
                                        break;
                                    }
                                }
                                if( $isInWorkingAttributeSet )
                                    break;
                            }
                            
                            if( $isInWorkingAttributeSet )
                            {
                                $indexProcessObject->changeStatus( Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX );
                            }
                        }
                    }
                }
            }
        }
    }
    public function catalog_entity_attribute_delete_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $indexProcessObject = Mage::getSingleton( 'index/indexer' )->getProcessByCode( 'solr_indexer' );
            if( $indexProcessObject->getStatus() != Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX )
            {
                $attribute = $observer->getEvent()->getAttribute();
                if( $attribute->getData( 'is_searchable' ) || $attribute->getData( 'used_in_product_listing' ) || $attribute->getData( 'is_visible_in_advanced_search' ) )
                {
                    
                    $attributeSets = Mage::getModel( 'eav/entity_attribute_set' )->getCollection();
                    $attributeSets->setEntityTypeFilter( Mage::getModel( 'eav/entity_type' )->loadByCode( 'catalog_product' )->getId() );
                    $isInWorkingAttributeSet = false;
                    foreach( $attributeSets as $attributeSet )
                    {
                        $attributes = Mage::getModel( 'catalog/product_attribute_api' )->items( $attributeSet->getId() );
                        foreach( $attributes as $attr )
                        {
                            if( $attr['attribute_id'] == $attribute->getId() )
                            {
                                $isInWorkingAttributeSet = true;
                                break;
                            }
                        }
                        if( $isInWorkingAttributeSet )
                            break;
                    }
                    
                    if( $isInWorkingAttributeSet )
                    {
                        $indexProcessObject->changeStatus( Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX );
                    }
                }
            }
        }
    }
    public function eav_entity_attribute_set_save_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $indexProcessObject = Mage::getSingleton( 'index/indexer' )->getProcessByCode( 'solr_indexer' );
            if( $indexProcessObject->getStatus() != Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX )
            {
                $attributeSet = $observer->getEvent()->getDataObject();
                $collection = Mage::getModel( 'eav/entity_attribute' )->getCollection();
                $collection->setEntityTypeFilter( Mage::getModel( 'eav/entity_type' )->loadByCode( 'catalog_product' )->getId() );
                $collection->setAttributeSetFilter( $attributeSet->getId() );
                $attributes = array();
                foreach( $collection as $attr )
                {
                    $attributes[] = $attr->getId();
                }
                Mage::register( 'solr_eav_attribute_set_save_before_object', $attributes );
            }
        }
    }
    public function eav_entity_attribute_set_save_after( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/product_update' ) )
        {
            $indexProcessObject = Mage::getSingleton( 'index/indexer' )->getProcessByCode( 'solr_indexer' );
            if( $indexProcessObject->getStatus() != Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX )
            {
                $oldList = Mage::registry( 'solr_eav_attribute_set_save_before_object' );
                if( ! is_null( $oldList ) )
                {
                    $attributeSet = $observer->getEvent()->getDataObject();
                    $collection = Mage::getModel( 'eav/entity_attribute' )->getCollection();
                    $collection->setEntityTypeFilter( Mage::getModel( 'eav/entity_type' )->loadByCode( 'catalog_product' )->getId() );
                    $collection->setAttributeSetFilter( $attributeSet->getId() );
                    $attributes = array();
                    foreach( $collection as $attr )
                    {
                        $attributes[$attr->getId()] = $attr;
                    }
                    $newList = array_keys( $attributes );
                    $changedAttributeIds = array_diff( $newList, $oldList );
                    $changedAttributeIds = array_merge( $changedAttributeIds, array_diff( $oldList, $newList ) );
                    $changedAttributeIds = array_unique( $changedAttributeIds );
                    foreach( $changedAttributeIds as $attributeId )
                    {
                        $attribute = null;
                        if( isset( $attributes[$attributeId] ) )
                        {
                            $attribute = $attributes[$attributeId];
                        }
                        else
                        {
                            $attribute = Mage::getModel( 'eav/entity_attribute' )->load( $attributeId );
                        }
                        if( $attribute->getData( 'is_searchable' ) || $attribute->getData( 'used_in_product_listing' ) || $attribute->getData( 'is_visible_in_advanced_search' ) )
                        {
                            $indexProcessObject->changeStatus( Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX );
                            break;
                        }
                    }
                }
            }
        }
    }
    public function cms_page_save_after( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/cms_update' ) )
        {
            $object = $observer->getEvent()->getDataObject();
            $adapter = new DMC_Solr_Model_SolrServer_Adapter_Cms();
            $document = $adapter->getSolrDocument();
            $solr = Mage::helper( 'solr' )->getSolr();
            
            if( $object->getData( 'is_active' ) == 0 )
            {
                $solr->deleteByQuery( "id:{$object->getId()}" );
                $solr->commit();
                Mage::helper( 'solr/log' )->addDebugMessage( 'The object #' . $object->getId() . ' is not active and was therefore deleted.' );
                return;
            }
            if( $document->setObject( $object ) )
            {
                $solr->addDocument( $document );
                $solr->addDocuments();
                $solr->commit();
            }
        }
    }
    public function cms_page_delete_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/cms_update' ) )
        {
            $object = $observer->getEvent()->getDataObject();
            if( is_object( $object ) && $object->getId() )
            {
                $adapter = new DMC_Solr_Model_SolrServer_Adapter_Cms();
                $solr = Mage::helper( 'solr' )->getSolr();
                $solr->deleteByQuery( 'id:' . $object->getId() . ' AND row_type:' . $adapter->getType() );
                $solr->commit();
            }
        }
    }
    public function catalog_category_save_after( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/category_update' ) )
        {
            $object = $observer->getEvent()->getDataObject();
            
            $adapter = new DMC_Solr_Model_SolrServer_Adapter_Category();
            $document = $adapter->getSolrDocument();
            $solr = Mage::helper( 'solr' )->getSolr();
            
            if( $object->getData( 'is_active' ) == 0 )
            {
                $solr->deleteByQuery( "id:{$object->getId()}" );
                Mage::helper( 'solr/log' )->addDebugMessage( 'The object #' . $object->getId() . ' is not active and was therefore deleted.' );
                $solr->commit();
                return;
            }
            if( $document->setObject( $object ) )
            {
                $solr->addDocument( $document );
                $solr->addDocuments();
                $solr->commit();
            }
        }
    }
    public function catalog_category_delete_before( $observer )
    {
        if( ( int )Mage::getStoreConfig( 'solr/indexer/category_update' ) )
        {
            $object = $observer->getEvent()->getDataObject();
            if( is_object( $object ) && $object->getId() )
            {
                $adapter = new DMC_Solr_Model_SolrServer_Adapter_Category();
                $solr = Mage::helper( 'solr' )->getSolr();
                $solr->deleteByQuery( 'id:' . $object->getId() . ' AND row_type:' . $adapter->getType() );
                $solr->commit();
            }
        }
    }
    public function solr_landingpage_save_after( $object )
    {
        $adapter = new DMC_Solr_Model_SolrServer_Adapter_Landingpage();
        $document = $adapter->getSolrDocument();
        
        $solr = Mage::helper( 'solr' )->getSolr();
        
        if( $object->getData( 'is_active' ) == 0 )
        {
            $solr->deleteByQuery( "id:{$object->getId()}" );
            Mage::helper( 'solr/log' )->addDebugMessage( 'The object #' . $object->getId() . ' is not active and was therefore deleted.' );
            $solr->commit();
            return;
        }
        if( $document->setObject( $object ) )
        {
            $solr->addDocument( $document );
            $solr->addDocuments();
            $solr->commit();
        }
    }
    public function solr_landingpage_delete_before( $object )
    {
        if( is_object( $object ) && $object->getId() )
        {
            $adapter = new DMC_Solr_Model_SolrServer_Adapter_Landingpage();
            $solr = Mage::helper( 'solr' )->getSolr();
            $solr->deleteByQuery( 'id:' . $object->getId() . ' AND row_type:' . $adapter->getType() );
            $solr->commit();
        }
    }
    public function solr_promotion_save_after( $object )
    {
        $adapter = new DMC_Solr_Model_SolrServer_Adapter_Promotion();
        $document = $adapter->getSolrDocument();
    
        $solr = Mage::helper( 'solr' )->getSolr();
    
        if( $object->getData( 'is_active' ) == 0 )
        {
            $solr->deleteByQuery( "id:{$object->getId()}" );
            Mage::helper( 'solr/log' )->addDebugMessage( 'The object #' . $object->getId() . ' is not active and was therefore deleted.' );
            $solr->commit();
            return;
        }
        if( $document->setObject( $object ) )
        {
            $solr->addDocument( $document );
            $solr->addDocuments();
            $solr->commit();
        }
    }
    public function solr_promotion_delete_before( $object )
    {
        if( is_object( $object ) && $object->getId() )
        {
            $adapter = new DMC_Solr_Model_SolrServer_Adapter_Promotion();
            $solr = Mage::helper( 'solr' )->getSolr();
            $solr->deleteByQuery( 'id:' . $object->getId() . ' AND row_type:' . $adapter->getType() );
            $solr->commit();
        }
    }
    public function addAttributeOptions( $observer )
    {
        $form = $observer->getEvent()->getForm();
        
        $fieldset = $form->addFieldset( 'Solr_fieldset', array(
                'legend' => Mage::helper( 'catalog' )->__( 'Solr Properties' ) 
        ) );
        
        $yesnoSource = Mage::getModel( 'adminhtml/system_config_source_yesno' )->toOptionArray();
        $fieldset->addField( 'is_filterableBySolr', 'select', array(
                'name' => 'is_filterableBySolr',
                'label' => Mage::helper( 'catalog' )->__( "Filterable with Solr" ),
                'title' => Mage::helper( 'catalog' )->__( 'Solr searcher should be enabled. Can be used only with catalog input type Dropdown, Multiple Select, Price, Text Field and Yes/No' ),
                'note' => Mage::helper( 'catalog' )->__( 'Solr searcher should be enabled. Can be used only with catalog input type Dropdown, Multiple Select, Price, Text Field and Yes/No' ),
                'values' => $yesnoSource 
        ) );
    }
}
