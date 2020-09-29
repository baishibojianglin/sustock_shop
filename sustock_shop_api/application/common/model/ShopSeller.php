<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2020/9/29
 * Time: 17:28
 */

namespace app\common\model;

/**
 * �̼�ģ����
 * Class ShopSeller
 * @package app\common\model
 */
class ShopSeller extends Base
{
    /**
     * ��ȡ�̼��б����ݣ�����paginate()�Զ�����ҳ��
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

    /**
     * ͨ�û���ȡ�����������ֶ�
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