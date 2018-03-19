<?php
class Zou_Alipay_Block_Adminhtml_Refund extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_refund';
    $this->_blockGroup = 'alipay';
    $this->_headerText = Mage::helper('alipay')->__('Refund Manager');
//     $this->_addButtonLabel = Mage::helper('supplier')->__('Add Supplier');
    parent::__construct();
    $this->_removeButton('add');
  }
}