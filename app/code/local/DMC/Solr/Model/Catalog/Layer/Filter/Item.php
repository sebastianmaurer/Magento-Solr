<?php
/**
 * Apache Solr Search Engine for Magento
 *
 * @category  DMC
 * @package   DMC_Solr
 * @author    Team Magento <magento@digitalmanufaktur.com>
 * @version   0.1.6
 */
class DMC_Solr_Model_Catalog_Layer_Filter_Item extends Mage_Catalog_Model_Layer_Filter_Item
{
    protected $_isActive = null;

    public function getUrl()
    {
        if ($this->isActive()) {
            return $this->getRemoveUrl();
        }

        $currentAttrCode = $this->getFilter()->getRequestVar();
        $currentVal = $this->getValue();
        $model = Mage::getModel('core/url');
        $currentQuery = $model->getRequest()->getQuery();
        if (isset($currentQuery[$currentAttrCode])) {
            $currentQuery[$currentAttrCode] .= ','.$currentVal;
        } else {
            $currentQuery[$currentAttrCode] = $currentVal;
        }

        return Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$currentQuery));
    }

    public function getRemoveUrl()
    {
        $currentAttrCode = $this->getFilter()->getRequestVar();
        $currentVal = $this->getValue();
        $model = Mage::getModel('core/url');
        $currentQuery = $model->getRequest()->getQuery();
        if (isset($currentQuery[$currentAttrCode])) {
            $ta = explode(',',$currentQuery[$currentAttrCode]);
            $newTa = array();
            if ($currentAttrCode == 'price' && is_array($currentVal)) {
                $currentVal = implode('-', $currentVal);
            }
            foreach ($ta as $v) {
                if ($v != $currentVal) $newTa[] = $v;
            }

            if (count($newTa)) {
                $currentQuery[$currentAttrCode] = implode(',', $newTa);
            } else {
                $currentQuery[$currentAttrCode] = $this->getFilter()->getResetValue();
            }

        }

        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $currentQuery;
        $params['_escape']      = false;
        return Mage::getUrl('*/*/*', $params);
    }

    public function isActive()
    {
        if (is_null($this->_isActive)) {
            $this->_isActive = false;
            $currentQuery = Mage::getModel('core/url')->getRequest()->getQuery();
            $currentAttrCode = $this->getFilter()->getRequestVar();
            if (isset($currentQuery[$currentAttrCode])) {
                $currentVal = $this->getValue();
                //if ($currentAttrCode == 'price') $currentVal = implode('-', $currentVal);
                $t = explode(',', $currentQuery[$currentAttrCode]);
                if (in_array($currentVal, explode(',', $currentQuery[$currentAttrCode]))) $this->_isActive = true;
            }
        }
        return $this->_isActive;

    }
}
