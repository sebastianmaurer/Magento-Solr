<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
require_once '../../lib/DMC/Solr/Response.php';

class Solr_Server_Ajax_Response extends Apache_Solr_Response
{
    // Constants for better parsing the suggestion Object delivered by solr
    const SPELLCHECK_COLLATION_ID = 'collation';

    const SPELLCHECK_SUGGEST = 1;

    const SPELLCHECK_HITS = 3;

    const SPELLCHECK_CORRECTION = 5;

    /**
     * Constructor.
     * Takes the raw HTTP response body and the exploded HTTP headers
     *
     * @param string $rawResponse            
     * @param array $httpHeaders
     *            TODO: __construct is called twice i guess...
     *            
     */
    public function __construct ($rawResponse, $httpHeaders = array())
    {
        $status = 0;
        $statusMessage = 'Communication Error';
        $type = 'text/plain';
        $encoding = 'UTF-8';
        
        // iterate through headers for real status, type, and encoding
        if (is_array($httpHeaders) && count($httpHeaders) > 0) {
            if (substr($httpHeaders[0], 0, 4) == 'HTTP') {
                $parts = explode(' ', substr($httpHeaders[0], 9), 2);
                
                $status = $parts[0];
                $statusMessage = trim($parts[1]);
                
                array_shift($httpHeaders);
            }
            foreach ($httpHeaders as $header) {
                if (substr($header, 0, 13) == 'Content-Type:') {
                    $parts = explode(';', substr($header, 13), 2);
                    
                    $type = trim($parts[0]);
                    
                    if ($parts[1]) {
                        $parts = explode('=', $parts[1], 2);
                        
                        if ($parts[1]) {
                            $encoding = trim($parts[1]);
                        }
                    }
                    
                    break;
                }
            }
        }
        
        $this->_rawResponse = $rawResponse;
        $this->_type = $type;
        $this->_encoding = $encoding;
        $this->_httpStatus = $status;
        $this->_httpStatusMessage = $statusMessage;
    }

    public function parseData ($identifier)
    {
        $data = json_decode($this->_rawResponse);
        
        if (isset($data->response) && is_array($data->response->docs)) {
            $documents = array();
            foreach ($data->response->docs as $doc) {
                $docItem = array();
                foreach ($doc as $key => $value) {
                    if ($key == $identifier) {
                        $data->response->type = $value;
                    }
                    if (is_array($value) && count($value) <= 1) {
                        $value = array_shift($value);
                    }
                    $docItem[$key] = $value;
                }
                $documents[] = $docItem;
            }
            $data->response->docs = $documents;
        }
        if (isset($data->facet_counts) &&
                 isset($data->facet_counts->facet_fields)) {
            foreach ($data->facet_counts->facet_fields as $key => $facet_array) {
                $new_facet_array = array();
                
                while (count($facet_array) > 0) {
                    $new_facet_array[array_shift($facet_array)] = array_shift(
                            $facet_array);
                }
                
                $data->facet_counts->facet_fields->$key = $new_facet_array;
            }
        }
        // TODO:name_show should be dynamic as well
        if (isset($data->highlighting)) {
            $highlighting = array();
            foreach ($data->highlighting as $key => $highlighting_array) {
                $highlighting[$key] = $highlighting_array->name_show{0};
            }
            $data->highlighting = $highlighting;
        }
        if (! empty($data->spellcheck->suggestions)) {
            
            $spellcheck = array();
            $suggestions = array_reverse($data->spellcheck->suggestions);
            
            foreach ($suggestions as $key => $item) {
                $x = $key + $key;
                $y = $key + $key + 1;
                if (! empty($suggestions[$y])) {
                    if ($suggestions[$y] == self::SPELLCHECK_COLLATION_ID) {
                        $spellcheck[$suggestions[$y]][] = $suggestions[$x];
                    } else {
                        $spellcheck['misspelled'][] = $suggestions[$y];
                    }
                    unset($suggestions[$key + 1]);
                    unset($suggestions[$key]);
                }
            }
            $data->spellcheck = $spellcheck;
        }
        return $data;
    }
}
