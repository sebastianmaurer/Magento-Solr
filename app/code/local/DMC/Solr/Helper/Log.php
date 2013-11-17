<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Helper_Log extends Mage_Core_Helper_Data
{
    const DEFAULT_LOG_FILE = 'solr.log';

    public function getLogFileName()
    {
        return self::DEFAULT_LOG_FILE;
    }

    public function addException(Exception $exception, $trace=true)
    {
        $message = sprintf('Exception message: %s', $exception->getMessage());
        if($trace) $message .= sprintf(' Trace: %s', $exception->getTraceAsString());
        $this->addMessage($message);
        return $this;
    }

    public function addMessage($message)
    {
        if(Mage::helper('solr')->isLogMode()) {
            Mage::log($message, 7, $this->getLogFileName());
        }
        return $this;
    }

    public function addDebugMessage($message)
    {
        if(Mage::helper('solr')->isDebugMode()) {
            if(Mage::helper('solr')->isLogMode()) {
                Mage::log($message, 7, $this->getLogFileName());
            }
        }
        return $this;
    }
}
