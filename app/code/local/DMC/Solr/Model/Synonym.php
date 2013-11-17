<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Synonym extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init( 'solr/synonym' );
    }
    public function import( $filePath, $stores )
    {
        if( ! is_array( $stores ) )
        {
            $stores = array(
                    $stores 
            );
        }
        
        $resource = Mage::getSingleton( 'core/resource' );
        $connection = $resource->getConnection( 'core_write' );
        $tableName = Mage::getSingleton( 'core/resource' )->getTableName( 'solr/synonym' );
        
        $content = file_get_contents( $filePath );
        $lines = explode( "\n", $content );
        
        foreach( $stores as $store )
        {
            foreach( $lines as $value )
            {
                $value = explode( '=>', strtolower( $value ) );
                
                if( count( $value ) != 2 )
                {
                    continue;
                }
                
                $rows[] = array(
                        'word' => $value[0],
                        'synonyms' => $value[1],
                        'store' => $store 
                );
                
                if( count( $rows ) > 1000 )
                {
                    $connection->insertArray( $tableName, array(
                            'word',
                            'synonyms',
                            'store' 
                    ), $rows );
                    $rows = array();
                }
            }
            
            if( count( $rows ) > 0 )
            {
                $connection->insertArray( $tableName, array(
                        'word',
                        'synonyms',
                        'store' 
                ), $rows );
            }
        }
        
        return count( $lines );
    }
    public function export( $outputFile, $MGSynonyms = null )
    {
        ini_set("memory_limit","512M");//@TODO: Check if needed in production env.
        $file = Mage::getBaseDir( 'media' ) . DS . 'solr' . DS . 'synonyms' . DS . 'export' . DS . $outputFile;
        $collection = Mage::getModel( 'solr/synonym' )->getCollection()->load()->getData();
        
        $data = ( $MGSynonyms ) ? self::exportMGSynonyms() : array(
                'text' => '',
                'count' => 0 
        );
//         die(print_R($data));
        $text = $data['text'];
        $count = (int) $data['count'] + count($collection);
        
        foreach( $collection as $item )
        {
            $text .= "{$item['word']} => {$item['synonyms']}\n";
        }
        
        if( ! file_exists( $file ) )
        {
            $handle = fopen( $file, 'w' );
            fclose( $handle );
        }
        
        file_put_contents( $file, $text );
        
        return array(
                'count' => $count,
                'path' => $file 
        );
    }
    public function exportMGSynonyms()
    {
        $collection = Mage::getModel( 'catalogsearch/query' )->getCollection();
        $collection->addFieldToFilter( 'synonym_for', array(
                'neq' => '' 
        ) );
        
        $data = $collection->getData();
        $result = '';
        
        foreach( $data as $item )
        {
            $result .= "{$item['query_text']} => {$item['synonym_for']}\n";
        }
        
        return array(
                'count' => count( $collection ),
                'text' => $result 
        );
    }
}
