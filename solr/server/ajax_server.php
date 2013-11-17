<?php
require_once 'classes/ajax_service.php';
require_once 'classes/ajax_response.php';
require_once '../../app/Mage.php';

class Solr_Ajax_Server
{

    public $solr, $result, $fieldID, $mapping, $params, $type;

    public function __construct ($params)
    {
        $this->params = $params;
        $this->solr = new Solr_Server_Ajax_Service($this->params['solrhost'], 
                $this->params['port'], $this->params['solrpath'], 
                $this->params['collection'], $this->params['servlet']);
        $this->fieldID = $params['fieldID'];
        $this->mapping = $params['mapping'];
        self::executeQuery();
    }

    public function executeQuery ()
    {
        $query = array();
        $queryString = self::getQueryString();
        $facetString = self::getFacetString();
        $sort = "sort=" . self::getSort();

        foreach ($this->fieldID[key($this->fieldID)] as $type) {
			$this->type = $type;
            $key = key($this->fieldID);
            
            $fqString = "fq={$key}:{$type}" . self::getFq();
            
            $query['wt'] = $this->params['wt'];
            $query['start'] = $this->params['start'];
            $query['rows'] = $this->params['rows'];
            $query['limit'] = $this->params['rows'];
            $query['q'] = "{$queryString}{$this->solr->queryStringDelimiter}{$fqString}{$this->solr->queryStringDelimiter}{$facetString}{$this->solr->queryStringDelimiter}{$sort}";
            $this->result[$type] = $this->solr->search($query);
        }
    }

    public function getFq()
    {
        $fqString = '';
        
        if( isset( $this->params['filterQuery'] ) )
        {
            foreach( $this->params['filterQuery'] as $fqParts )
            {
                $fqGlue = key( $fqParts );
                $fqFields = implode(" {$fqGlue} ", $fqParts[$fqGlue]);
                $fqString .= " {$fqGlue}{$fqFields}";
            }
        }
        if( isset( $this->mapping['filterQuery'][$this->type] ) )
        {
            foreach( $this->mapping['filterQuery'][$this->type] as $key => $fqParts )
            {
                $fqGlue = $key;
                $fqFields = implode(" {$fqGlue} ", $fqParts);
                $fqString .= " {$fqGlue}{$fqFields}";
            }
        }
        return urlencode( $fqString );
    }

    public function getQueryString ()
    {
        $queryString = "{$this->params['wildcard']}{$this->params['facet']['facet.prefix']}{$this->params['wildcard']}";
        if (isset($this->params['queryString'])) {
            foreach ($this->params['queryString'] as $key => $queryParts) {
                
                $queryGlue = key($queryParts);
                $queryField = key($queryParts[$queryGlue]);
                $queryString .= " {$queryGlue} {$queryField}:{$queryParts[$queryGlue][$queryField]}";
            }
        }
        
        return urlencode($queryString);
    }

    public function getFacetString ()
    {
        $facetString = array();
        
        foreach ($this->params['facet'] as $key => $facet) {
            if ($key == 'facet.prefix') {
                $facet = urlencode($facet);
            }
            $facetString[] = "{$key}={$facet}";
        }
        return implode($this->solr->queryStringDelimiter, $facetString);
    }

    public function getSort ()
    {
        if (isset($this->params['sorting'])) {
            $sort = array();
            foreach ($this->params['sorting'] as $key => $order) {
                $sort[] = "{$key} {$order}";
            }
            return urlencode(implode(', ', $sort));
        }
    }

    public function getID ()
    {
        return key($this->fieldID);
    }

    public function mapFields ($doc, $section)
    {
        if (! empty($this->mapping['fields'][$section])) {
            foreach ($this->mapping['fields'][$section] as $key => $value) {
                $doc[$key] = $doc[$value];
            }
        }
        return $doc;
    }
    // @TODO: we need a posibility to customize this method without modify this file
    public function toHtml ()
    {
        $found = false;
        $html = '';
        $html_outer = '<div id="livesearch-box"><div id="livesearch-box-arrow">&nbsp;</div><div id="livesearch-box-background">';
        $left = 'left';
        $html_{$left} = '<div id="livesearch-box-left">';
        $right = 'right';
        $html_{$right} = '<div id="livesearch-box-right">';
        
        foreach ($this->result as $item) {
            
            $result = $item->parseData($this->getID());
            $resultDocs = $result->response->docs;
            $facetCounts = $result->facet_counts;
            $highlighting = $result->highlighting;

            $facetID = "{$this->getID()}:{$result->response->type}";
            
            if (count($resultDocs)) {
                
                $found = true;
                $position = $this->mapping['position'][$result->response->type];
                $link = isset($this->mapping['link'][$result->response->type]) ? true : false;
                
                $html_{$position} .= '<div class="livesearch-box-' . $position .
                         '-content">';
                $html_{$position} .= ($link) ? "<a class=\"livesearch-link\" href=\"{$this->mapping['link'][$result->response->type]}\">" : null;
                $html_{$position} .= '<h3>' .
                         $this->mapping['sections'][$result->response->type] .
                         ' (' . $facetCounts->facet_queries->{$facetID} . ')' .
                         '</h3>';
                $html_{$position} .= ($link) ? "</a>" : null;
                $html_{$position} .= '<ul class="result-list-' . $position . '">';
                
                foreach ($resultDocs as $doc) {
                    $doc = self::mapFields($doc, $result->response->type);
                    $title = (empty($highlighting[$doc['row_id']])) ? $doc['name'] : $highlighting[$doc['row_id']];
                    
                    if (strlen($doc['thumb']) && $position = 'right') {
                        $img = '<img class="livesearch-thumb" src="' .
                                 $this->params['baseURL'] . $doc['thumb'] . '">';
                    } else {
                        $img = '';
                    }
                    
                    $html_{$position} .= '<li class="item">' . $img .
                             '<span class="bg"></span><p class="livesearch-' . $position .
                             '-p"><a href="' . $this->params['baseURL'] .
                             $doc['url'] . '">' . $title . '</a></p></li>';
                    
                    if ($position == 'right') {
                        $html_{$position} .= '<div class="livesearch-box-spacer">&nbsp;</div>';
                    }
                }
                
                $html_{$position} .= '</ul>';
                $html_{$position} .= '</div>';
            }
        }
        if ($found) {
            $html_{$left} .= '</div>';
            $html_{$right} .= '</div>';
            $html = $html_outer . $html_{$left} . $html_{$right} .
                     '<div id="livesearch-box-clear"></div></div>';
            return $html;
        }
    }
}
/**
 * http://localhost:8983/solr/collection1/select?q=Kaffee&fq=row_type:product+AND+store_id:2&facet=true&facet.field=name_autocomplete&facet.limit=10&facet.query=row_type:product&facet.query=row_type:cms&facet.query=row_type:category&facet.prefix=kaffee
 * @TODO: Make it configurable in html data attribute...
 */
umask(0);

$solrConfig = Mage::getStoreConfig('solr');
$solrUrl = explode('/',$solrConfig['general']['server_url']);
$solrUrlParts = explode(':', $solrUrl[0]);

$params = array();
$params['solrhost'] = $solrUrlParts[0];
$params['port'] = $solrUrlParts[1];
$params['solrpath'] = $solrUrl[1];
$params['collection'] = $solrUrl[2];
$params['servlet'] = 'select';
$params['method'] = 'get';//magento query object
$params['queryID'] = 'q';
$params['rawQuery'] = ($params['method'] == 'get') ? Solr_Server_Ajax_Service::cleanString(
                $_GET[$params['queryID']]) : Solr_Ajax_Server::cleanString(
                $_POST[$params['queryID']]);
$params['term'] = Solr_Server_Ajax_Service::prepareTerm($params['rawQuery']);
$params['wt'] = 'json';
$params['start'] = 0;
$params['rows'] = 5;
$params['baseURL'] = Mage::getBaseUrl();
$params['wildcard'] = '';//@TODO: check if needed
// this is needed for the count in the Flyout but it also make it possible to
// implement a kind of suggestion during typing the search term into the field
// facet.prefix should be also be handled via setting in solr config
$params['facet'] = array(
        "facet" => "true",
        "facet.field" => "name_autocomplete",
        "facet.limit" => "10",
        "facet.query" => "row_type:product&facet.query=row_type:manufacturer&facet.query=row_type:rebsorten",
        "facet.prefix" => $params['term']
);
// needed for several&flexible mapping we have to do at runtime
// could be also be handled via setting in solr config
$params['mapping'] = array(
        'sections' => array(
                'product' => 'Produkte',
                'rebsorten' => 'Rebsorten',
                'manufacturer' => 'Winzer'
        ),
        'fields' => array(
                'rebsorten' => array(
                        'name' => 'name',
                        'url' => 'url_path'
                ),
                'product' => array(
                        'name' => 'attr_s_index_name',
                        'url' => 'rewrite_path'
                ),
                'manufacturer' => array(
                        'name' => 'name',
                        'url' => 'url_path'
                )
        ),
        'position' => array(
                'rebsorten' => 'left',
                'product' => 'right',
                'manufacturer' => 'left'
        ),
        'filterQuery' => array(
                'product' => array(
                        'AND' => array(
                                '(visibility:3 OR visibility:4)'
                        ) 
                ) 
        ),
        'link' => array(
                'product' => '/catalogsearch/result/?q='.$params['rawQuery']
        )
);
$params['fieldID'] = array(
        'row_type' => array(
                'product',
                'manufacturer',
                'rebsorten'
        )
);
$params['filterQuery'] = array(
        array(
                'AND' => array(
                        '(store_id:1)',
                ) 
        ) 
);
$params['sorting'] = array(
        'attr_s_index_dm_search_boosting' => 'desc',
        'score' => 'desc'
);

// call
$service = new Solr_Ajax_Server($params);
echo $service->toHtml();
