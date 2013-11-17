<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Helper_Manager extends Mage_Core_Helper_Data
{
    private function apiCall($method, $params = array())
    {
        try {
            
            $appConnectorUrl = Mage::getStoreConfig('solr/general/server_api_url'); //'http://solr.dev/solr/manager/api.php';
            
            if(strlen($appConnectorUrl) < 1) {
                return 'Error - API Management Url not set';
            }

            $curlHandler    = curl_init( $appConnectorUrl );
            $params         = array_merge($params, array('method' => $method));

            // set URL and other appropriate options
            curl_setopt($curlHandler, CURLOPT_POST, 1);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curlHandler, CURLOPT_TIMEOUT, 120);

            // Execute the request.
            $result = curl_exec($curlHandler);

            if (curl_exec($curlHandler) === false) {
                Mage::log('Curl error: ' . curl_error($curlHandler));
                Mage::log(curl_getinfo($curlHandler));
            }

            $http_status = curl_getinfo ($curlHandler, CURLINFO_HTTP_CODE);
            // check response
            if ($http_status != '200') {
                return '<font color="red">Error - Return status '.$http_status.'</font>';
            }

            return '<font color="green">Success</font>';

        } catch (Exception $e) {
            return '<font color="red">Error - Calling solr api on server '.$server.' failed ('.$e->getMessage().')</font>';
        }
    }

    public function uploadSynonyms()
    {
        // load from db
        $collection = Mage::getModel('solr/synonym')->getCollection();
        //echo count($collection->getData());

        ob_start();
        $df = fopen("php://output", 'w');
        foreach ($collection->getData() as $row) {
            fwrite($df, $row['word'].'=>'.$row['synonyms']."\n");
        }

        fclose($df);
        
        return $this->apiCall('synonyms/update', array('data' => ob_get_clean()));
    }

    public function uploadStopwords()
    {
        
    }

    public function reloadCore()
    {
        
    }
}
