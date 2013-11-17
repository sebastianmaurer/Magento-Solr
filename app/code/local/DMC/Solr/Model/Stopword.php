<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Stopword extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init( 'solr/stopword' );
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
        $tableName = Mage::getSingleton( 'core/resource' )->getTableName( 'solr/stopword' );
        
        $content = file_get_contents( $filePath );
        $lines = explode( "\n", $content );
        foreach( $stores as $store )
        {
            foreach( $lines as $value )
            {
                $value = strtolower( $value );
                $rows[] = array(
                        'word' => $value,
                        'store' => $store 
                );
                
                if( count( $rows ) > 1000 )
                {
                    $connection->insertArray( $tableName, array(
                            'word',
                            'store' 
                    ), $rows );
                    $rows = array();
                }
            }
            
            if( count( $rows ) > 0 )
            {
                $connection->insertArray( $tableName, array(
                        'word',
                        'store' 
                ), $rows );
            }
        }
        
        return count( $lines );
    }
    public function export($outputFile)
    {
        $file = Mage::getBaseDir('media').DS.'solr'.DS.'stopwords'.DS.'export'.DS.$outputFile;
        $collection = Mage::getModel( 'solr/stopword' )->getCollection()->load()->getData();
        $text = "";
        $i = 0;
        foreach( $collection as $item )
        {
            $text .= "{$item['word']}\n";
        }
        if(!file_exists($file))
        {
            $handle = fopen($file, 'w');
            fclose($handle);
        }
        file_put_contents($file, $text);
        return array('count' => count( $collection ), 'path' => $file);
    }
}
