<?php
class Zou_Alipay_Helper_Api extends Zou_Alipay_Helper_Data
{
    public function http_post($url, $data)
    {
        if (! function_exists('curl_init')) {
            throw new Exception('php未安装curl组件', 500);
        }
//         if(is_array($data)){
//             $data = json_encode($data);
//         }
        $protocol = (! empty ( $_SERVER ['HTTPS'] ) && $_SERVER ['HTTPS'] !== 'off' || $_SERVER ['SERVER_PORT'] == 443) ? "https://" : "http://";
        $website = $protocol.$_SERVER['HTTP_HOST'];
    
        $ch = curl_init();
//         $separator = '';
//         $fields_string = '';
//         foreach($data as $key=>$value) {
//             $fields_string .= $separator . $key . '=' . $value;
//             $separator = '&';
//         }
        
        $url = $url . '?' .http_build_query($data);
        //file_put_contents(Mage::getBaseDir('media').'/zou.txt', $url.PHP_EOL,FILE_APPEND);
        //echo $url;
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_REFERER, $website);
        curl_setopt( $ch ,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        /*
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        */
        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($httpStatusCode != 200) {
            throw new Exception("invalid httpstatus:{$httpStatusCode} ,response:$response,detail_error:" . $error, $httpStatusCode);
        }
        
        file_put_contents(Mage::getBaseDir('media').'/zou.txt', print_r($response,true).PHP_EOL,FILE_APPEND);
        return $response;
    }
    
    /*
     * 查询退款状态
     * 商户申请退款成功之后，可以根据 Omipay 返回的退款单号查询退款状态。
     */
    public function queryRefund($sendData=array()){
        $gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $requestConfigs = array(
            //'trade_no'=>'2014112611001004680073956707', //支付宝交易号，和商户订单号不能同时为空
            'out_trade_no'=>$refundData['order_no'],//订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'out_request_no'=>$refundData['refund_no'],  //请求退款接口时，传入的退款请求号，如果在退款请求时未传入，则该值为创建交易时的外部交易号
        );
        $appInfo = $this->getAppInfo();
        $commonConfigs = array(
            //公共参数
            'app_id' => $appInfo['app_id'],
            'method' => 'alipay.trade.fastpay.refund.query',//接口名称
            'format' => $appInfo['format'],
            'charset'=> $appInfo['charset'],
            'sign_type'=> 'RSA2',//$appInfo['sign_type'],
            'timestamp'=> $appInfo['timestamp'],
            'version'=> $appInfo['version'],
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $reponseData = $this->curlPost($gatewayUrl,$commonConfigs);
        $reponseData = json_decode($reponseData,true);
        $result = array(
                    'flag'=>false,
                    'sub_code'=>$reponseData['sub_code'],
                    'sub_msg'=>$reponseData['sub_msg'],
                    'data'=>$reponseData
                );
        if($reponseData['code'] && $reponseData['code'] == 10000){
            $result['flag'] = true;
        }
        return $result;
    }

    public function refund($refundData = array()){
        $gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $requestConfigs = array(
            //'trade_no'=>'2014112611001004680073956707', //支付宝交易号，和商户订单号不能同时为空
            'out_trade_no'=>$refundData['order_no'],//订单支付时传入的商户订单号,不能和 trade_no同时为空。
            'refund_amount'=>$refundData['amount'],  //需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
            'refund_reason'=>'正常退款',//退款的原因说明
            //'out_request_no'=>'HZ01RF001',//标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传
        );
        $appInfo = $this->getAppInfo();
        $commonConfigs = array(
            //公共参数
            'app_id' => $appInfo['app_id'],
            'method' => 'alipay.trade.refund',//接口名称
            'format' => $appInfo['format'],
            'charset'=> $appInfo['charset'],
            'sign_type'=> 'RSA2',//$appInfo['sign_type'],
            'timestamp'=> $appInfo['timestamp'],
            'version'=> $appInfo['version'],
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $reponseData = $this->curlPost($gatewayUrl,$commonConfigs);
        $reponseData = json_decode($reponseData,true);
        $reponseData = $reponseData['alipay_trade_refund_response'];
        /*
            ["alipay_trade_refund_response"]=>
              array(10) {
                ["code"]=>
                string(5) "10000"
                ["msg"]=>
                string(7) "Success"
                ["buyer_logon_id"]=>
                string(11) "186****6286"
                ["buyer_user_id"]=>
                string(16) "2088122143044775"
                ["fund_change"]=>
                string(1) "N"
                ["gmt_refund_pay"]=>
                string(19) "2018-03-03 22:50:10"
                ["out_trade_no"]=>
                string(9) "100000027"
                ["refund_fee"]=>
                string(4) "0.02"
                ["send_back_fee"]=>
                string(4) "0.00"
                ["trade_no"]=>
                string(28) "2018020921001004770539739772"
              }
              ["sign"]=>
  string(344) "MHPOffyNHxy3q/sRtb7fwasBL4GA9Qgvf229c4vK0MvFOK5AfnApoKHR1GUeEGtmi
         */
        $result = array(
                    'flag'=>false,
                    'sub_code'=>$reponseData['code'],
                    'sub_msg'=>$reponseData['msg'],
                    'data'=>$reponseData
                );
        if($reponseData['code'] && $reponseData['code'] == 10000){
            $result['flag'] = true;
        }
        return $result;
    }
    public function sendErrorEmail($subject, $message) {
        $from = Mage::getStoreConfig('trans_email/ident_sales/name') . ' <' . Mage::getStoreConfig('trans_email/ident_sales/email') . '>';
        $headers = "From: $from";
        $toArray = $this->getConfigData('error_report_receivers');
        if ($toArray) {
            $toArray = explode(',', $toArray);
            foreach ((array) $toArray as $to) {
                mail($to, $subject, $message, $headers);
            }
        }
    }

    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function doPcPay($data)
    {
        $totalFee = $data['amount'];
        $outTradeNo = $data['out_order_no'];
        $orderName = $data['order_name'];
        $returnUrl = $data['redirect_url'];
        $notifyUrl = $data['notify_url'];
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outTradeNo,
            'product_code'=>'FAST_INSTANT_TRADE_PAY',
            'total_amount'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
        );
        $appInfo = $this->getAppInfo();
        $commonConfigs = array(
            //公共参数
            'app_id' => $appInfo['app_id'],
            'method' => 'alipay.trade.page.pay',//接口名称
            'format' => $appInfo['format'],
            'return_url' => $returnUrl,
            'charset'=> $appInfo['charset'],
            'sign_type'=> 'RSA2',//$appInfo['sign_type'],
            'timestamp'=> $appInfo['timestamp'],
            'version'=> $appInfo['version'],
            'notify_url' => $notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        // print_r($commonConfigs);die;
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        //print_r($commonConfigs);die;
        return $this->buildRequestForm($commonConfigs);
    }
    //手机网站支付
    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function doWapPay($data)
    {
        $totalFee = $data['amount'];
        $outTradeNo = $data['out_order_no'];
        $orderName = $data['order_name'];
        $returnUrl = $data['redirect_url'];
        $notifyUrl = $data['notify_url'];
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outTradeNo,
            'product_code'=>'QUICK_WAP_WAY',
            'total_amount'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
        );
        $appInfo = $this->getAppInfo();
        $commonConfigs = array(
            //公共参数
            'app_id' => $appInfo['app_id'],
            'method' => 'alipay.trade.wap.pay',             //接口名称
            'format' => 'JSON',
            'return_url' => $returnUrl,
            'charset'=> $appInfo['charset'],
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        return $this->buildRequestForm($commonConfigs);
    }

    //当面付（扫码支付）
    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @return array
     */
    public function doScanPay($totalFee, $outTradeNo, $orderName, $notifyUrl)
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outTradeNo,
            'total_amount'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.precreate',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        return json_decode($result,true);
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
