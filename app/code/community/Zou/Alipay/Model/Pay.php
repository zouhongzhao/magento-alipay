<?php

class Zou_Alipay_Model_Pay extends Mage_Payment_Model_Method_Abstract {
    protected $_code          = 'alipay';
    protected $_formBlockType = 'alipay/form';
     //protected $_infoBlockType = 'alipay/info';
    protected $_order;
    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund               = true;

    /**
     *
     * @return string
     */
    public function getNewOrderState()
    {
        return Mage_Sales_Model_Order::STATE_NEW;
    }
    
    /**
     *
     * @return string
     */
    public function getNewOrderStatus()
    {
        return Mage::getStoreConfig("payment/alipay/order_status");
    }
    
    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }
    /**
     * Get config payment action, do nothing if status is pending
     *
     * @return string|null
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('order_status') == 'pending' ? null : parent::getConfigPaymentAction();
    }
    
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('alipay/redirect', array('_secure' => true));
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
        return $this;
    }
    public function authorize(Varien_Object $payment, $amount) {
        if (!$this->canAuthorize()){
            $payment->setTransactionId(time());
            $payment->setIsTransactionClosed(0);
        }
        return $this;
    }
    
    public function getRepayUrl($order){
        return Mage::getUrl('alipay/redirect', array('_secure' => true,'orderId'=>$order->getRealOrderId()));
    }
    
    // public function processBeforeRefund($invoice, $payment){
    //     //before refund
    // } 
    /**
     * Mock capture transaction id in invoice
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment)
    {
        $invoice->setTransactionId(1);
        return $this;
    }

    /**
     * Set transaction ID into creditmemo for informational purposes
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId(1);
        return $this;
    }
    public function refund(Varien_Object $payment, $amount){
        $order = $payment->getOrder();
        $result = $this->callApi($payment,$amount,'refund');
        if(!$result['status']) {
            $errorMsg = $result['message']?$result['message']:'Invalid Data';
            //$errorMsg = $this->_getHelper()->__('Error Processing the request');
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    
    private function callApi(Varien_Object $payment, $amount,$type){
        $result = array('status'=>1,'message'=>'');
        $order = $payment->getOrder();
        $billingaddress = $order->getBillingAddress();
        $totals = number_format($amount, 2, '.', '');
        $orderId = $order->getIncrementId();
        $currencyDesc = $order->getBaseCurrencyCode();
        $amount = $totals;
        if($type == 'refund'){
            $refundData = array('trade_no'=>$order->getData('alipay_order_no'),'order_no'=>$orderId,'amount'=>$amount);
            $refundDataRow = Mage::helper('alipay/api')->refund($refundData);
            $insertData = array(
                'order_no'=>$orderId,
                'customer_id'=>$order->getCustomerId(),
                'customer_email'=>$order->getCustomerEmail(),
                'status'=>$refundDataRow['flag']?1:2,
                'result_code'=>$refundDataRow['sub_code'],
                'result_msg'=>$refundDataRow['sub_msg'],
                'message'=> json_encode($refundDataRow['data'])
            );

            if($refundDataRow['flag']){
                $insertData['refund_time'] = $refundDataRow['data']['gmt_refund_pay']?strtotime($refundDataRow['data']['gmt_refund_pay']):time();
                $insertData['refund_no'] = $refundDataRow['data']['trade_no'];
                $insertData['buyer_logon_id'] = $refundDataRow['data']['buyer_logon_id'];
                $insertData['amount'] = $refundDataRow['data']['refund_fee'];
                $result['transaction_id'] = $insertData['refund_no'];
                $refundModel = Mage::getModel('alipay/refund');
                $refundModel->setData($insertData);
                $refundModel->save();
            }else{
                $result['status'] = 0;
                $result['message'] = $insertData['result_msg'];
            }
        }
        return $result;
    }
    public function getOrderEmailStatus() {
        return Mage::getStoreConfig('payment/alipay/order_email_status');
    }
    public function processResponse($order){
        if ($order->canInvoice()) {
            $payment = $order->getPayment();
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            if ($this->getInvoiceEmailStatus() != 1) {
                $invoice->setEmailSent(true);
                $invoice->sendEmail();
            }
            $invoice->save();
            $newOrderStatus = 'processing';
            $notify = ($this->getOrderEmailStatus() == 1) ? true : false;
            $paymentDescription = Mage::helper('alipay')->__('Received alipay verification. %s payment method was used.', 'alipay');
            $order->setStatus(__('Processing'))->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, $paymentDescription, $notify
                )->save();
                if ($notify) {
                    $order->sendNewOrderEmail()->addStatusHistoryComment(
                        Mage::helper('svm')->__('Order confirmation sent.')
                        )
                        ->setIsCustomerNotified(true)
                        ->save();
                }
        }
        $session = Mage::getSingleton('checkout/session');
        $session->setLastSuccessQuoteId($order->getQuoteId())
        ->setLastQuoteId($order->getQuoteId())
        ->addSuccess(Mage::helper('alipay')->__('Your Payment was Successful!'))
        ->setLastOrderId($order->getId())
        ->setLastRealOrderId($order->getIncrementId());
        $session->getQuote()->setIsActive(false)->save();
    }
}
