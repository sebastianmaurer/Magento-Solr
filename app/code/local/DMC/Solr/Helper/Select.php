<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Helper_Select extends Mage_Core_Helper_Data
{
    const ESCAPE_NONE = 'none';
    const ESCAPE_NORMAL = 'normal';
    const ESCAPE_WILDCARD = 'wildcard';
    const ESCAPE_DE_UMLAUTS = 'de_umlauts';

    /**
     * Escaping Special Characters
     *
     * @param string $value
     * @param string|array $types Type of escaping. For example: self::ESCAPE_NORMAL | self::ESCAPE_WILDCARD
     */
    public function escape($value, $types=self::ESCAPE_NORMAL) {
        if(!is_null(types) && (types === self::ESCAPE_NONE)) {
            if(strstr($types, ',')) {
                $types = explode(',', $types);
            }
            if(is_array($types) && count($types)) {
                foreach($types as $type) {
                    $value = self::escape($value, trim($type));
                }
            }
            else {
                switch($types) {
                    case self::ESCAPE_NORMAL:
                        $value = self::escapePhrase($value);
                        break;
                    case self::ESCAPE_WILDCARD:
                        $value = self::escapeWildcardPhrase($value);
                        break;
                    case self::ESCAPE_DE_UMLAUTS:
                        $value = self::escapeGermanUmlauts($value);
                        break;
                }
            }
        }
        return $value;
    }

    /**
     *
     * Escaping all solr Special Characters
     * @param string $value
     */
    public function escapePhrase($value)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
        return str_replace($match, $replace, $value);
    }

    /**
     *
     * Escaping all solr Special Characters exclude ? and *.
     * @param string $value
     */
    public function escapeWildcardPhrase($value)
    {
        $match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', ':', '"', ';', ' ');
        $replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\:', '\\"', '\\;', '\\ ');
        return str_replace($match, $replace, $value);
    }

    public function escapeGermanUmlauts($value)
    {
        $match = array('ö', 'ä', 'ü');
        $replace = array('o', 'a', 'u');
        return str_replace($match, $replace, $value);
    }

    public function prepareDate($date)
    {
        if(!is_numeric($date)) {
            $date = strtotime($date);
        }
        return date('Y-m-d\TH:i:s\Z', (int)$date);
    }

    /**
     *
     * A Single Term is a single word such as "test" or "hello".
     * @param string $column
     * @param string $value
     * @param string|array $escapeTypes
     * @param int $relevance Lucene provides the relevance level of matching documents based on the terms found. To boost a term use the caret, "^", symbol with a boost factor (a number) at the end of the term you are searching. The higher the boost factor, the more relevant the term will be.
     *
     * @return string
     */
    public function prepareTermCondition($column, $value, $escapeTypes=self::ESCAPE_NORMAL, $relevance=null) {

        return $column.':'.self::escape($value,$escapeTypes).(is_numeric($relevance) ? '^'.$relevance : '');
    }

    /**
     *
     * A Phrase is a group of words surrounded by double quotes such as "hello dolly".
     * @param string $column
     * @param string $value
     * @param int $relevance Lucene provides the relevance level of matching documents based on the terms found. To boost a term use the caret, "^", symbol with a boost factor (a number) at the end of the term you are searching. The higher the boost factor, the more relevant the term will be.
     *
     * @return string
     */
    public function preparePhraseCondition($column, $value, $escapeTypes=self::ESCAPE_NORMAL, $relevance=null) {

        return $column.':"'.self::escape($value, $escapeTypes).'"'.(is_numeric($relevance) ? '^'.$relevance : '');
    }

    /**
     *
     * Lucene supports single and multiple character wildcard searches within single terms (not within phrase queries).
     * To perform a single character wildcard search use the "?" symbol.
     * To perform a multiple character wildcard search use the "*" symbol.
     * You cannot use a * or ? symbol as the first character of a search.
     *
     * @param string $column
     * @param string $value
     * @param int $relevance Lucene provides the relevance level of matching documents based on the terms found. To boost a term use the caret, "^", symbol with a boost factor (a number) at the end of the term you are searching. The higher the boost factor, the more relevant the term will be.
     *
     * @return string
     */
    public function prepareWildcardCondition($column, $value, $escapeTypes=self::ESCAPE_WILDCARD, $relevance=null) {

        return $column.':'.self::escape($value, $escapeTypes).(is_numeric($relevance) ? '^'.$relevance : '');
    }

    /**
     *
     * Lucene supports fuzzy searches based on the Levenshtein Distance, or Edit Distance algorithm.
     * To do a fuzzy search use the tilde, "~", symbol at the end of a Single word Term.
     * For example to search for a term similar in spelling to "roam" use the fuzzy search:
     *
     * @param string $column
     * @param string $value
     * @param float  $similarity The value is between 0 and 1, with a value closer to 1 only terms with a higher similarity will be matched
     * @return string
     */
    public function prepareFuzzyCondition($column, $value, $escapeTypes=self::ESCAPE_NORMAL, $similarity=null) {

        return $column.':'.self::escape($value, $escapeTypes).'~'.(!is_null($similarity)?$similarity:'');
    }

    /**
     *
     * Lucene supports finding words are a within a specific distance away. To do a proximity search use the tilde, "~", symbol at the end of a Phrase.
     *
     * @param string $column
     * @param string $value
     * @param int $distance
     * @return string
     */
    public function prepareProximityCondition($column, $value, $distance, $escapeTypes=self::ESCAPE_NORMAL) {

        return $column.':"'.self::escape($value, $escapeTypes).'"~'.$distance;
    }

    /**
     *
     * Lucene supports finding words are a within a specific distance away. To do a proximity search use the tilde, "~", symbol at the end of a Phrase.
     *
     * @param string $column
     * @param string $value
     * @param int $distance
     * @return string
     */
    public function prepareRangeCondition($column, $startValue = '*', $endValue = '*', $including=true, $escapeTypes=self::ESCAPE_NORMAL) {

        if(($startValue === '*') || is_null($startValue) || !strlen($startValue)) {
            $startValue = '*';
        }
        else {
            $startValue = self::escape($startValue, $escapeTypes);
        }
        if(($endValue === '*') || is_null($endValue) || !strlen($endValue)) {
            $endValue = '*';
        }
        else {
            $endValue = self::escape($endValue, $escapeTypes);
        }

        if($including) {
            $startBracket = '[';
            $endBracket = ']';
        }
        else {
            $startBracket = '{';
            $endBracket = '}';
        }


        return $column.':'.$startBracket.$startValue.' TO '.$endValue.$endBracket;
    }

    /**
     *
     * Lucene supports finding words are a within a specific distance away. To do a proximity search use the tilde, "~", symbol at the end of a Phrase.
     *
     * @param string $column
     * @param string $value
     * @param int $distance
     * @return string
     */
    public function prepareDateRangeCondition($column, $startValue = '*', $endValue = '*', $including=true) {

        if(($startValue === '*') || is_null($startValue) || !strlen($startValue)) {
            $startValue = '*';
        }
        else {
            $startValue = self::prepareDate($startValue);
        }

        if(($endValue === '*') || is_null($endValue) || !strlen($endValue)) {
            $endValue = '*';
        }
        else {
            $endValue = self::prepareDate($endValue);
        }

        return self::prepareRangeCondition($column, $startValue, $endValue, $including, null);
    }
}
