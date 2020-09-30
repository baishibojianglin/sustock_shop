<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2020/9/29
 * Time: 17:28
 */

namespace app\common\model;

/**
 * 商家模型类
 * Class ShopSeller
 * @package app\common\model
 */
class ShopSeller extends Base
{
    /**
     * 获取商家列表数据（基于paginate()自动化分页）
     * @param array $map
     * @param int $size
     * @return \think\Paginator
     */
    public function getShopSeller($map = [], $size = 5)
    {
        $order = ['seller_id' => 'asc'];

        $result = $this
            ->field($this->_getListField())
            ->where($map)
            ->order($order)
            ->cache(true, 10)
            ->paginate($size);
        return $result;
    }

    /*
     *  商家新增
     */
    public function addShopSeller($data)
    {
        $result=$this->insert($data);

        return $result;
    }

    /*
     * 商家修改
     * */
    public function updShopSeller($seller_id,$data){
        $result=$this->where("seller_id",$seller_id)->update($data);

        return $result;
    }

    /**
     * 通用化获取参数的数据字段
     * @return array
     */
    private function _getListField()
    {
        return [
            'seller_id',
            'seller_name',
            'address'
        ];
    }
}