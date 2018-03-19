<?php
class Zou_Alipay_NotifyController extends Mage_Core_Controller_Front_Action
{
    /**
     * Instantiate notify model and pass notify request to it
     */
    public function indexAction()
    {
//         if (!$this->getRequest()->isPost()) {
//             return;
//         }
        $data = $this->getRequest()->getPost();
        if(!isset($data['sign']) ||!isset($data['out_trade_no'])){
            return;
        }
        $helper = Mage::helper('alipay');
        $result = $helper->rsaCheck($data);
        if($result !==true){
            return;
        }
        //处理你的逻辑，例如获取订单号$_POST['out_trade_no']，订单金额$_POST['total_amount']等
        //程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，直到超过24小时22分钟。一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）；
        
        $helper->log( $data);
        
        $order_id = $data['out_order_no'];
        $transaction_id = isset($data['order_no'])?$data['order_no']:'';
        
        try{
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
            if (! $order || ! $order->getId() || ! $order instanceof Mage_Sales_Model_Order) {
                throw new Exception('unknow order');
            }
            
            if (!in_array($order->getState(), array(
                Mage_Sales_Model_Order::STATE_NEW,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
            
            ))) {
                $params = array(
                    'action'=>'success',
                    'm_number'=>$app_id,
                );
                $params['hash'] = $hash;
                ob_clean();
                print json_encode($params);
                exit;
            }
             
            $payment = $order->getPayment();
            if( $payment->getMethod() != 'alipay'){
                throw new Exception('unknow order payment method');
            }
            
            $payment->setTransactionId($transaction_id)
            ->registerCaptureNotification($order->getGrandTotal(), true);
            $order->save();
            
            // notify customer
            $invoice = $payment->getCreatedInvoice();
            if ($invoice && ! $order->getEmailSent()) {
                $order->sendNewOrderEmail()
                ->addStatusHistoryComment(Mage::helper('omipay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($order->getQuoteId());
            $session->getQuote()->setIsActive(false)->save();
            
        }catch(Exception $e){
            //looger
            $helper->log( $e->getMessage());
            $params = array(
                'action'=>'fail',
                'appid'=>$app_id,
                'errcode'=>$e->getCode(),
                'errmsg'=>$e->getMessage()
            );
        
            $params['hash'] = $hash;//$helper->generate_xh_hash($params, $hashkey);
            ob_clean();
            print json_encode($params);
            exit;
        }
        
        $params = array(
            'action'=>'success',
            'appid'=>$app_id
        );
        
        $params['hash']= $hash;//$helper->generate_xh_hash($params, $hashkey);
        ob_clean();
        print json_encode($params);
        exit;
    }
}
