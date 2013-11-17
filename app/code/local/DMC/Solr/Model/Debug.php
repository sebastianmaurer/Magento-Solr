<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
die('DMC_Solr_Model_Debug');
class DMC_Solr_Model_Debug
{
    protected $_messages = array();

    public function addMessage($message) {
        $this->_messages[] = $message;
    }

    public function getMessages() {
        return $this->_messages;
    }

    public function renderCachedTemplate($html, $attributes = array()) {
        $debug = '<div style="color:red">Attention! Debug mode is turned on for the "Solr" module</div>';
        if(is_array($attributes) && count($attributes)) {
            foreach($attributes as $name => $value) {
                $functionName = '_attribute'.$name;
                if(method_exists('DMC_Solr_Model_Debug', $functionName)) $debug = self::$functionName($debug, $value);
            }
        }
        foreach($this->_messages as $row) {
            $debug .= '<div style="color:red">'.$row.'</div>';
        }
        return str_replace('</body>', $debug.'</body>', $html);
    }

    static public function renderDynamicBlock(& $block) {
        if(!isset($block['html'])) $block['html'] = '<font color="red">content for block was not found</font>';
        $block['html'] = "<div style='height:auto; border: 2px dotted red'>".$block['html']."</div>";
    }

    static private function _attributeMode($html, $value) {
        $modeName = DMC_Extendedcache_Model_Adminhtml_Config_Source_Mode::getModeName($value);
        if($modeName) $html .= '<div style="color:red">mode is: "'.$modeName.'"</div>';
        return $html;
    }

    static private function _attributeSource($html, $value) {
        $modeName = DMC_Extendedcache_Model_Adminhtml_Config_Source_Mode::getModeName($value);
        if($modeName) $html .= '<div style="color:red">mode is: "'.$modeName.'"</div>';
        return $html;
    }
}
