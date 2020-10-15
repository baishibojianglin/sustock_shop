<?php


namespace Home\Controller;

/*
 * 电脑网站支付接入
 * */

use Boris\Config;
include_once './ThinkPHP/Library/Vendor/Alipay/pagepay/service/AlipayTradeService.php';
include_once './ThinkPHP/Library/Vendor/Alipay/pagepay/buildermodel/AlipayTradePagePayContentBuilder.php';

class AlipayController extends BaseController
{
    public $payment; //  具体的支付类
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

    public function pagepay(){

        $config=C('alipay');

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

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = trim($order['order_sn']);

        //订单名称，必填
        $subject = trim('店通商城');

        //付款金额，必填
        $total_amount = trim($order['order_amount']);

        //商品描述，可空
        $body = trim('');

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new \AlipayTradeService($config);

        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
         */
        $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        //输出表单
        var_dump($response);
    }

    /*
     * 异步回调
     * */
    public function notifyUrl(){
//        $this->payment->response();
//        exit();

        $config=C('alipay');

        $arr=$_POST;
        $alipaySevice = new \AlipayTradeService($config);
        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];


            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";	//请不要修改或删除
        }else {
            //验证失败
            echo "fail";

        }

    }


    /*
     * 同步跳转
     * */
    public function returnUrl(){

//        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
//        if(stripos($result['order_sn'],'recharge') !== false)
//        {
//            $order = M('recharge')->where("order_sn = '{$result['order_sn']}'")->find();
//            $this->assign('order', $order);
//            if($result['status'] == 1)
//                $this->display('recharge_success');//充值成功
//            else
//                $this->display('recharge_error');
//            exit();
//        }
//        // 先查看一下 是不是 合并支付的主订单号
//        $sum_order_amount = M('order')->where("master_order_sn = '{$result['order_sn']}'")->sum('order_amount');
//        if($sum_order_amount)
//        {
//            $this->assign('master_order_sn', $result['order_sn']); // 主订单号
//            $this->assign('sum_order_amount', $sum_order_amount); // 所有订单应付金额
//        }
//        else
//        {
//            $order = M('order')->where("order_sn = '{$result['order_sn']}'")->find();
//            $this->assign('order', $order);
//        }
//
//        if($result['status'] == 1)
//            $this->display('success');
//        else
//            $this->display('error');

        $config=C('alipay');

        $arr=$_GET;
        $alipaySevice = new \AlipayTradeService($config);
        $result = $alipaySevice->check($arr);

        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号
            $out_trade_no = htmlspecialchars($_GET['out_trade_no']);

            //支付宝交易号
            $trade_no = htmlspecialchars($_GET['trade_no']);

            echo "验证成功<br />支付宝交易号：".$trade_no;

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "验证失败";
        }

    }

}