<?php
class Omipay_Payment_Model_System_Config_Source_Supportpays
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'ALIPAY', 'label'=>Mage::helper('adminhtml')->__('ALIPAY')),
            array('value' => 'WECHATPAY', 'label'=>Mage::helper('adminhtml')->__('WECHATPAY')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'ALIPAY' => Mage::helper('adminhtml')->__('ALIPAY'),
            'WECHATPAY' => Mage::helper('adminhtml')->__('WECHATPAY'),
        );
    }

}
