<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
require_once '../../lib/DMC/Solr/Service.php';
require_once '../../lib/DMC/Solr/Response.php';

class Solr_Server_Ajax_Service extends Apache_Solr_Service
{

    public $queryStringDelimiter = '&';

    public $servlet;
    
    const ICONV_CHARSET = 'UTF-8';

    /**
     * Constructor.
     * All parameters are optional and will take on default values
     * if not specified.
     *
     * @param string $host            
     * @param string $port            
     * @param string $path            
     * @param string $collection            
     */
    public function __construct ($host = 'localhost', $port = 8983, $path = 'solr', 
            $collection = 'collection1', $servlet = self::SEARCH_SERVLET)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setPath($path, $collection);
        $this->servlet = $servlet;
        $this->_initUrls();
        
        // set up the stream context for posting with file_get_contents
        $contextOpts = array(
                'http' => array(
                        'method' => 'POST',
                        'header' => "Content-Type: text/xml; charset=UTF-8\r\n"
                )
        );
        
        $this->_postContext = stream_context_create($contextOpts);
    }

    /**
     * Set the path and collection.
     *
     * @param string $path            
     * @param string $collection            
     */
    public function setPath ($path, $collection)
    {
        $this->_path = '/' . $path . '/' . $collection . '/';
    }

    /**
     * Construct the Full URLs for the three servlets we reference
     */
    protected function _initUrls ()
    {
        // Initialize our full servlet URLs now that we have server information
        $this->_updateUrl = $this->_constructUrl(self::UPDATE_SERVLET, 
                array(
                        'wt' => self::SOLR_WRITER
                ));
        $this->_searchUrl = $this->_constructUrl($this->servlet);
        $this->_threadsUrl = $this->_constructUrl(self::THREADS_SERVLET, 
                array(
                        'wt' => self::SOLR_WRITER
                ));
        
        $this->_urlsInited = true;
    }

    public static function prepareTerm ($term)
    {
        $words = preg_split("/\s+/", $term);
        
        if (count($words) > 1) {
            $words = urlencode(implode(' ', $words));
            $term = '(' . $words . ')';
        }
        return self::escape($term);
    }

    /**
     * Clean non UTF-8 characters
     *
     * @param string $string            
     * @return string
     */
    public static function cleanString ($string)
    {
        return '"libiconv"' == ICONV_IMPL ? iconv(self::ICONV_CHARSET, 
                self::ICONV_CHARSET . '//IGNORE', $string) : $string;
    }

    /**
     * Escape a value for special query characters such as ':', '(', ')', '*',
     * '?', etc.
     *
     * NOTE: inside a phrase fewer characters need escaped, use {@link
     * Apache_Solr_Service::escapePhrase()} instead
     *
     * @param string $value            
     * @return string
     */
    static public function escape ($value)
    {
        return parent::escape($value);
    }

    /**
     * Escape a value meant to be contained in a phrase for special query
     * characters
     *
     * @param string $value            
     * @return string
     */
    static public function escapePhrase ($value)
    {
        return parent::escapePhrase($value);
    }

    /**
     * Simple Search interface
     *
     * @param array $params
     *            key / value pairs for query parameters, use arrays for
     *            multivalued parameters
     * @return Apache_Solr_Response
     *
     * @throws Exception If an error occurs during the service call
     */
    public function search (array $params)
    {
        $escapedParams = array();
        do {
            foreach ($params as $key => &$value) {
                if (is_array($value)) {
                    // parameter has multiple values that need passed
                    // array_shift pops off the first value in the array and
                    // also removes it
                    $escapedParams[] = $key . '=' . array_shift($value);
                    
                    if (empty($value)) {
                        unset($params[$key]);
                    }
                } else {
                    // simple, single value case
                    $escapedParams[] = $key . '=' . $value;
                    unset($params[$key]);
                }
            }
        } while (! empty($params));
        return $this->_sendRawGet(
                $this->_searchUrl . $this->_queryDelimiter .
                         implode($this->_queryStringDelimiter, $escapedParams));
    }

    /**
     * Central method for making a get operation against this Solr Server
     *
     * @param string $url            
     * @return Apache_Solr_Response
     *
     * @throws Exception If a non 200 response status is returned
     */
    protected function _sendRawGet ($url)
    {
        // $http_response_header is set by file_get_contents
        $response = new Solr_Server_Ajax_Response(@file_get_contents($url), 
                null);
        
        if ($response->getHttpStatus() != 200) {
            throw new Exception(
                    '"' . $response->getHttpStatus() . '" Status: ' .
                             $response->getHttpStatusMessage());
        }
        return $response;
    }
}
