<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2020/9/29
 * Time: 17:03
 */

namespace app\admin\controller;

use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;

/**
 * admin模块商家管理控制器类
 * Class ShopSeller
 * @package app\admin\controller
 */
class ShopSeller extends AuthBase
{
    /**
     * 显示商家资源列表
     */
    public function index()
    {
        // 判断为GET请求
        if (request()->isGet()) {
            // 传入的数据
            $param = input('param.');

            // 查询条件
            $map = [];

            // 获取分页page、size
            $this->getPageAndSize($param);

            // 获取分页列表数据 模式一：基于paginate()自动化分页
            try {
                $data = model('ShopSeller')->getShopSeller($map, (int)$this->size);
            } catch (\Exception $e) {
                return show(config('code.error'), '网络忙，请重试' . $e->getMessage(), '', 500); // $e->getMessage()
//                throw new ApiException('网络忙，请重试' . $e->getMessage(), 500, config('code.error')); // $e->getMessage()
            }

            return show(config('code.success'), 'OK', $data);
        } else {
            return show(config('code.error'), '请求不合法', '', 400);
        }
    }

    /**
     * 商家新增
     * @return \think\response\Json
     * @throws ApiException
     */
    public function save()
    {
        //判断为POST请求
        if(request()->isPost()){
            // 获取前端数据
            $data = input();

            // 封装需要新增的数据
            $add_data = [
                "seller_name" => $data["seller_name"],
                "pwd" => IAuth::encrypt(trim($data["phone"])),
                "phone" => trim($data["phone"]),
                "address" => trim($data["address"])
            ];

            // 新增数据
            try {
                $res = model("ShopSeller")->add($add_data, "seller_id");
            } catch (\Exception $e) {
                throw new ApiException('请求异常' . $e->getMessage(), 500, config('code.error'));
            }

            if ($res) {
                return show(config('code.success'), '新增成功', $res, 201);
            } else {
                return show(config('code.error'), '新增失败', $res, 403);
            }
        } else {
            return show(config('code.error'), '请求不合法', '', 400);
        }
    }

    /**
     * 显示指定商家信息
     * @param $id
     * @return \think\response\Json
     * @throws ApiException
     */
    public function read($id)
    {
        //判断请求类型
        if(request()->isGet()){
            try {
                //不查询密码等字段
                $data=model("ShopSeller")->field("pwd,token,token_time",true)->find($id);
            } catch (\Exception $e) {
                throw new ApiException($e->getMessage(), 500, config('code.error'));
            }

            if ($data) {
                return show(config('code.success'), 'ok', $data);
            } else {
                return show(config('code.error'), '数据不存在', '', 404);
            }
        } else {
            return show(config('code.error'), '请求不合法', '', 400);
        }
    }

    /**
     * 商家修改
     * @param $id
     * @return \think\response\Json
     */
    public function update($id)
    {
        //判断请求类型
        if(request()->isPut()){
            // 判断数据是否存在
            $shopSellerData = model("ShopSeller")->field("pwd,token,token_time", true)->find($id);
            if (empty($shopSellerData)) {
                return show(config('code.error'), '数据不存在', '', 404);
            }
            
            //获取前端数据
            $data=input();

            $upd_data=[];
            //数据判断
            if (isset($data["seller_name"]) && !empty($data["seller_name"])) {
                $upd_data['seller_name'] = trim($data["seller_name"]);
            }
            if (isset($data["phone"]) && !empty($data["phone"])) {
                $upd_data['phone'] = trim($data["phone"]);
            }
            if (isset($data["address"]) && !empty($data["address"])) {
                $upd_data['address'] = trim($data["address"]);
            }

//            //封装需要修改的数据
//            $upd_data=[
////                "seller_name" =>$data["seller_name"],
////                "phone"=>trim($data["phone"]),
////                "address"=>$data["address"],
//                "utime"=>date('Y-m-d H:i:s',time())
//            ];

            //执行修改
            $res=model("ShopSeller")->updShopSeller($id,$upd_data);

            if ($res === false) {
                return show(config('code.error'), '修改失败', $res);
            } else {
                return show(config('code.success'), '修改成功', $res);
            }
        } else {
            return show(config('code.error'), '请求不合法', '', 400);
        }
    }

    /**
     * 删除指定商家
     * @param $id
     * @return \think\response\Json
     * @throws ApiException
     */
    public function delete($id)
    {
        // 判断为DELETE请求
        if (!request()->isDelete()) {
            return show(config('code.error'), '请求不合法', '', 400);
        }

        // 显示指定的商家资源
        try {
            $data = model('ShopSeller')->find($id);
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage(), 500, config('code.error'));
        }

        // 判断数据是否存在
        if ($data['seller_id'] != $id) {
            return show(config('code.error'), '数据不存在');
        }

        // 执行删除
        // 软删除
        if ($data['is_delete'] != config('code.is_delete')) {
            $res = model("ShopSeller")->softDelete("seller_id", $id);

            if($res){
                return show(config('code.success'), '删除成功', $res);
            }else{
                return show(config('code.error'), '删除失败', $res);
            }
        }
    }
}