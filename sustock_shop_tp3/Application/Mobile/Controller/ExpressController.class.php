<?php


namespace Mobile\Controller;


class ExpressController extends MobileBaseController
{
    //调用查询物流轨迹
//---------------------------------------------

    public function express(){

        $shippingcode = I("get.shippingcode");
        $logisticcode = I("get.invoiceno");
        $logisticResult= $this -> getOrderTracesByJson($shippingcode,$logisticcode);
        $data = json_decode($logisticResult,true);
        $state = array_reverse($data['Traces']);

        $this -> assign('express',$data);
        $this -> assign('traces',$state);
        $this -> display();
    }

//---------------------------------------------

    /**
     * Json方式 查询订单物流轨迹
     */
    private function getOrderTracesByJson($shippingcode,$logisticcode){

        $shippingcode = strtoupper($shippingcode);
        $requestData= "{'OrderCode':'','ShipperCode':'".$shippingcode."','LogisticCode':'".$logisticcode."'}";

        $datas = array(
            'EBusinessID' => C('EBUSINESSID'),
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this -> signEncrypt($requestData, C('APPKEY'));

        $result = $this -> sendPost(C('REQURL'), $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    private function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    private function signEncrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
}