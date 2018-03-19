<?php

class Zou_Alipay_Block_Adminhtml_Refund_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('refundGrid');
      $this->setDefaultSort('id');
      $this->setDefaultDir('desc');
      $this->setUseAjax(true);
      $this->setVarNameFilter('refund_filter');
      
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('alipay/refund')->getCollection();
      $collection->getSelect()->order('id desc');
      $this->setCollection($collection);
      
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('customer_email', array(
          'header'    => Mage::helper('alipay')->__('Customer email'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'customer_email',
      ));
      $this->addColumn('order_no', array(
          'header'    => Mage::helper('alipay')->__('Order no'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'order_no',
      ));
      
      $this->addColumn('refund_no', array(
      		'header'    => Mage::helper('alipay')->__('Refund no'),
      		'align'     =>'right',
//       		'width'     => '50px',
      		'index'     => 'refund_no',
      ));

      $this->addColumn('buyer_logon_id', array(
      		'header'    => Mage::helper('alipay')->__('Buyer logon id'),
      		'align'     =>'right',
      		'width'     => '50px',
      		'index'     => 'buyer_logon_id',
      ));
      $this->addColumn('amount', array(
          'header'    => Mage::helper('alipay')->__('Refund amount'),
          'align'     =>'right',
          //       		'width'     => '50px',
          'index'     => 'amount',
      ));
      $this->addColumn('refund_time', array(
      		'header'    => Mage::helper('alipay')->__('Refund time'),
      		'align'     =>'right',
          'type' => 'datetime',
//       		'width'     => '50px',
      		'index'     => 'refund_time',
      ));
      $this->addColumn('status', array(
          'header'    => Mage::helper('alipay')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
              1 => 'Yes',
              2 => 'No',
          ),
      ));
	  
//         $this->addColumn('action',
//             array(
//                 'header'    =>  Mage::helper('alipay')->__('Action'),
//                 'width'     => '100',
//                 'type'      => 'action',
//                 'getter'    => 'getId',
//                 'actions'   => array(
//                     array(
//                         'caption'   => Mage::helper('alipay')->__('Edit'),
//                         'url'       => array('base'=> '*/*/edit'),
//                         'field'     => 'id'
//                     )
//                 ),
//                 'filter'    => false,
//                 'sortable'  => false,
//                 'index'     => 'stores',
//                 'is_system' => true,
//         ));
		
// 		$this->addExportType('*/*/exportCsv', Mage::helper('alipay')->__('CSV'));
// 		$this->addExportType('*/*/exportXml', Mage::helper('alipay')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        return $this;
    }

  public function getRowUrl($row)
  {
  	 	return false;
//       return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}