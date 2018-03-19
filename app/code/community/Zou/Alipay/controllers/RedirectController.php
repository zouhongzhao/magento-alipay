<?php
class Zou_Alipay_RedirectController extends Mage_Core_Controller_Front_Action {

//     protected function _expireAjax() {
//         if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
//             $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
//             exit;
//         }
//     }
    
//     protected function _getCheckout()
//     {
//         return Mage::getSingleton('checkout/session');
//     }

    public function indexAction() {
        $orderId = $this->getRequest()->get('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        } else {
            $order = Mage::helper('alipay')->getOrder();
        }
        $order_id = $order->getRealOrderId();
//         var_dump($order_id);
//         var_dump($order->getState());die;
    	if(!in_array($order->getState(), array(
    	    Mage_Sales_Model_Order::STATE_NEW,
    	    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
    	))){
    	    $this->_redirectUrl(Mage::getUrl('alipay/redirect/success', array('orderId' => $order_id)));
    	    return;
    	}
    	if(!($order && $order instanceof Mage_Sales_Model_Order)){
    	   throw new Exception('unknow order');
    	}
    	
    	$payment = $order->getPayment();
    	$orderIncrementId = $order->getIncrementId();
    	if( $payment->getMethod() !='alipay'){
    	    throw new Exception('unknow order payment method');
    	}
    	
    	$protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$website = $protocol.$_SERVER['HTTP_HOST'];
    	
    	$total_amount     = round($order->getGrandTotal(),2);
    	
    	$helper = Mage::helper('alipay');
    	//$helper->log('test');die;
    	$apiHelper = Mage::helper('alipay/api');
    	//$appId = $helper->getConfigData('api_m_number');
    	try {
    	    if($helper->isWebApp()){
    	        $data = array(
    	            "order_name" => $helper->get_order_title($order),
    	            "currency" => $order->getOrderCurrencyCode(),
    	            "amount" => $total_amount,
    	            "redirect_url"=>Mage::getUrl('alipay/redirect/success', array('orderId' => $order_id)),
    	            "notify_url" => Mage::getUrl('alipay/notify'),
    	            "out_order_no" => $orderIncrementId
    	        );
    	        $result = $apiHelper->doWapPay($data);
    	        // $order->setOmipayOrderNo($result['order_no'])->save();
//     	        $hash = $helper->generate_xh_hash($result,$hashkey);
//     	        if(!isset( $result['hash'])|| $hash!=$result['hash']){
//     	            throw new Exception($helper->__('Invalid sign!'),40028);
//     	        }
//     	        if("{$result['errcode']}"!=0){
//     	            throw new Exception($result['errmsg'],$result['errcode']);
//     	        }
    	        	
    	        $session = Mage::getSingleton('checkout/session');
    	        $session->setQuoteId($order->getRealOrderId());
    	        $session->getQuote()
    	        ->setIsActive(false)
    	        ->save();
    	        	
    	        ?>
    	        <!DOCTYPE html>
    	        	<html>
    	        		<head>
    	        			<title><?php print $helper->__('Redirect to alipay ...')?></title>
    	                </head>
    	           	<body>
    	                <?php print $helper->__('Redirect to alipay ...');?>
    	            	<?php echo $result;?>
    	            </body>
    	           </html>
    	        <?php
    	    }else{
                //pc支付
                $data = array(
                    "order_name" => $helper->get_order_title($order),
                    "currency" => $order->getOrderCurrencyCode(),
                    "amount" => $total_amount,
                    "redirect_url"=>Mage::getUrl('alipay/redirect/success', array('orderId' => $order_id)),
                    "notify_url" => Mage::getUrl('alipay/notify'),
                    "out_order_no" => $orderIncrementId
                );
                $result = $apiHelper->doPcPay($data);
                $session = Mage::getSingleton('checkout/session');
                $session->setQuoteId($order->getRealOrderId());
                $session->getQuote()
                ->setIsActive(false)
                ->save();
                ?>
                <!DOCTYPE html>
                    <html>
                        <head>
                            <title><?php print $helper->__('Redirect to alipay ...')?></title>
                        </head>
                    <body>
                        <?php print $helper->__('Redirect to alipay ...');?>
                        <?php echo $result;?>
                    </body>
                   </html>
                <?php
    	    }
    	    
    	} catch (Exception $e) {
    	    ?>
    	    <html>
    	    <meta charset="utf-8" />
        	<title><?php print $helper->__('System error!')?></title>
        	<meta http-equiv="X-UA-Compatible" content="IE=edge">
        	<meta content="width=device-width, initial-scale=1.0" name="viewport" />
    	    
    	    <head>
    	    	<title><?php print $helper->__('Ops!Something is wrong.')?></title>
    	    </head>
    	    <body>
            <?php 
    	       echo "errcode:{$e->getCode()},errmsg:{$e->getMessage()}";
    	   ?>
    	   </body>
    	   </html>
    	   <?php
    	}
    	
    	exit;
    }
    
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    public function successAction() {
        $orderId = $this->getRequest()->get('orderId');
        if ($orderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if($order){
                Mage::getModel('alipay/pay')->processResponse($order);
            }
        }
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        
    }
    public function queryAction() {
        $orderNo = $this->getRequest()->get('order_no');
        $orderId = $this->getRequest()->get('order_id');
        $result = array('status'=>false);
        if ($orderNo) {
            $apiHelper = Mage::helper('alipay/api');
            $data = array(
                'order_no'=>$orderNo
            );
            $res = $apiHelper->queryOrder($data);
            //var_dump($res);
            if($res['flag'] && $res['result_code'] == 'PAID'){
                $result['status'] = 'paid';
                $result['message'] = Mage::getUrl('alipay/redirect/success', array('orderId' => $orderId));
            }
            if(!$res['flag'] && $res['error_code']){
                $result['status'] = $res['error_code'];
            }
        }
        echo json_encode($result);die;
    }
}
?>