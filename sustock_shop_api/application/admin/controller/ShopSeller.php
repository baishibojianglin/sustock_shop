<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2020/9/29
 * Time: 17:03
 */

namespace app\admin\controller;

use app\common\lib\exception\ApiException;

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
                //return show(config('code.error'), '网络忙，请重试' . $e->getMessage(), '', 500); // $e->getMessage()
                throw new ApiException('网络忙，请重试' . $e->getMessage(), 500, config('code.error')); // $e->getMessage()
            }

            return show(config('code.success'), 'OK', $data);
        } else {
            return show(config('code.error'), '请求不合法', '', 400);
        }
    }
}