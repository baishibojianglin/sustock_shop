<?php

namespace Mobile\Controller;
/*
 * 手机网站支付接入
 * */



class WithdrawController extends MobileBaseController
{
    protected $mch_appid;//商户账号appid
    protected $mchid;//商户号
    protected $nonce_str;//随机字符串
    protected $partner_trade_no;//商户订单号
    protected $openid;//用户openid
    protected $amount;//金额（单位为分）
    protected $desc;//企业付款备注
    protected $key;//支付密钥
    protected $f1;//apiclient_cert
    protected $f2;//apiclient_key

    /**
     * 析构流函数
     */
    public function  __construct($partner_trade_no,$openid,$amount,$desc) {
        parent::__construct();
        //获取微信配置
        $paymentPlugin = M('Plugin')->where("code='weixin' and  type = 'payment' ")->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化

        $this->mch_appid = $config_value['appid'];
        $this->mchid = $config_value['mchid'];
        $this->partner_trade_no = $partner_trade_no;
        $this->openid = $openid;
        $this->amount = $amount*100;
        $this->desc = $desc;
        $this->key = $config_value['key'];
        $this->f1 = './plugins/payment/weixin/cert/apiclient_cert.pem';
        $this->f2 = './plugins/payment/weixin/cert/apiclient_key.pem';
    }

    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /*
     * 申请提现
     * */

    public function getWithdraw(){
        $return = $this ->transfers();
        return $return;
    }

    private function transfers() {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $parameters = array(
            'mch_appid' => $this->mch_appid,
            'mchid' => $this->mchid,
            'nonce_str' => $this->getNonceStr(),
            'partner_trade_no' => $this->partner_trade_no,
            'openid' => $this->openid,
            'check_name'=>'NO_CHECK',
            'amount' => $this->amount,
            'desc' => $this->desc,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
        );
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters,$this->key);
        $xmlData = $this->arrayToXml($parameters);
        $return = $this->xmlToArray($this->postXmlCurl($xmlData, $url,true,$this->f1,$this->f2));
        return $return;
    }

    private static function postXmlCurl($xml, $url,$useCert=false,$f1="",$f2="", $second = 60){
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $f1);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $f2);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);

        //运行curl
        $data = curl_exec($ch);
        // echo json_encode($data);
        // exit;
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            exit("curl出错，错误码:$error");
        }
    }

    public function getSign($Obj,$key) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    ///作用：格式化参数，签名过程需要使用
    public function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    //数组转换成xml
    public function arrayToXml($arr) {
        $xml = "<root>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</root>";
        return $xml;
    }

    //xml转换成数组
    public function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }
}