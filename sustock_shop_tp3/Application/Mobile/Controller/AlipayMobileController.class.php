<?php

namespace Mobile\Controller;
/*
 * 手机网站支付接入
 * */

use Boris\Config;
include_once './ThinkPHP/Library/Vendor/AlipayMobile/wappay/service/AlipayTradeService.php';
include_once './ThinkPHP/Library/Vendor/AlipayMobile/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php';
include_once './plugins/payment/weixin/weixin.class.php';
include_once './plugins/payment/weixin/example/notify.php';

//初始化日志
$logHandler= new \CLogFileHandler("./logs/".date('Y-m-d').'.log');
$log = \Log::Init($logHandler, 15);

class AlipayMobileController extends MobileBaseController
{
    public $pay_code; //  具体的支付code

    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        // tpshop 订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        if(!empty($pay_radio))
        {
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {
            $_GET = I('get.');
            //file_put_contents('./a.html',$_GET,FILE_APPEND);
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }

    }

    /*
     * 订单支付
     * */

    public function wappay(){

        $config=C('alipay');
        $config['notify_url'] = C('alipaymobile.notify_url');
        $config['return_url'] = C('alipaymobile.return_url');

        // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        $order_id = I('order_id',0); // 订单id
        $master_order_sn = I('master_order_sn','');// 多单付款的 主单号
        // 如果是主订单号过来的, 说明可能是合并付款的
        if($master_order_sn)
        {
            M('order')->where("master_order_sn = '$master_order_sn'")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            $order = M('order')->where("master_order_sn = '$master_order_sn'")->find();
            $order['order_sn'] = $master_order_sn; // 临时修改 给支付宝那边去支付
            $order['order_amount'] = M('order')->where("master_order_sn = '$master_order_sn'")->sum('order_amount'); // 临时修改 给支付宝那边去支付
        }else{
            M('order')->where("order_id = $order_id")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            $order = M('order')->where("order_id = $order_id")->find();
        }
        if($order['pay_status'] == 1){
            $this->error('此订单，已完成支付!');
        }

        //微信JS支付
        $pay_radio = $_REQUEST['pay_radio'];
        $config_value = parse_url_param($pay_radio);
        if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            // 引入微信支付类文件
            $weixin= new \weixin();
            $code_str = $weixin->getJSAPI($order);
            exit($code_str);
        }

        /*提交支付宝支付 start*/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($order['order_sn']);

        //订单名称，必填
        $subject = trim('店通商城');

        //付款金额，必填
        $total_amount = trim($order['order_amount']);

        //商品描述，可空
        $body = trim('');

        //超时时间
        $timeout_express="1m";

        //构造参数
        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTimeExpress($timeout_express);


        $payResponse = new \AlipayTradeService($config);
        $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        return ;
        /*提交支付宝支付 end*/
    }

    /*
     * 微信回调
     * */
    public function notifyUrlWx(){

//        \Log::DEBUG("begin notify");
        $notify = new \PayNotifyCallBack();
        $notify->Handle(false);
        exit();

    }

    /*
     * 异步回调
     * */
    public function notifyUrl(){

        $config=C('alipay');

        $arr=$_POST;
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);

        if($result) {//验证成功

            //商户订单号
            $order_sn = $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            if($trade_status == 'TRADE_FINISHED') {

                update_pay_status($order_sn);
            }
            else if ($trade_status == 'TRADE_SUCCESS') {

                update_pay_status($order_sn); // 修改订单支付状态
            }

            echo "success";	//告诉支付宝处理成功
        }else {
            //验证失败
            echo "fail";

        }

        exit();
    }


    /*
     * 同步跳转
     * */
    public function returnUrl(){

        $config=C('alipay');

        $arr=$_GET;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);


        if($result) {//验证成功

            //商户订单号
            $order_sn = $out_trade_no = htmlspecialchars($_GET['out_trade_no']);

            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);

            $resultarr = array('status'=>1,'order_sn'=>$order_sn);//存储成功状态信息

        }
        else {
            //验证失败
            $resultarr = array('status'=>0,'order_sn'=>htmlspecialchars($_GET['out_trade_no']));//存储失败状态信息
        }

        // 先查看一下 是不是 合并支付的主订单号
        $sum_order_amount = M('order')->where("master_order_sn = '{$resultarr['order_sn']}'")->sum('order_amount');
        if($sum_order_amount)
        {
            $this->assign('master_order_sn', $resultarr['order_sn']); // 主订单号
            $this->assign('sum_order_amount', $sum_order_amount); // 所有订单应付金额
        }
        else
        {
            $order = M('order')->where("order_sn = '{$resultarr['order_sn']}'")->find();
            $this->assign('order', $order);
        }

        if($resultarr['status'] == 1)
            $this->display('Payment/success');//跳转至成功页面
        else
            $this->display('Payment/error');//跳转至失败页面


    }
}