<?php 
class Zou_Alipay_Model_Observer{
    public function sales_model_service_quote_submit_success($evt ){
        $order = $evt ->getOrder();
        $quote = $evt ->getQuote(); 

        if(!($order&&$order instanceof Mage_Sales_Model_Order)){
            return;
        }
        
        $payment = $order->getPayment();
        if( $payment->getMethod() != 'alipay'){
           return;
        }
       
        //设置不删除购物车
        $quote->setIsActive(true);
    }
    
}