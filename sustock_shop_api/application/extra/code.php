<?php
/**
 * Created by PhpStorm.
 * User: Yan
 * Date: 2019/7/22
 * Time: 17:52
 */

// 状态码配置
return [
    // API接口状态码
    'error' => 0,
    'success' => 1,

    // 审核与启用状态
    'status' => [0 => '禁用', 1 => '启用', 2 => '待审核', 3 => '驳回'],
    'status_disable' => 0, // 禁用
    'status_enable' => 1, // 启用
    'status_pending' => 2, // 待审核
    'status_reject' => 3, // 驳回

    // 是否删除
    'not_delete' => 0, // 未删除
    'is_delete' => 1, // 删除

    // 性别：0保密，1男，2女
    'gender' => [0 => '保密', 1 => '男', 2 => '女'],

    // 是否上架：0下架，1上架
    'is_on_sale' => [0 => '下架', 1 => '上架'],

    // 授权状态
    'is_auth' => [0 => '禁止', 1 => '允许'],

    // 订单状态：0未付款，1已付款完成，2已取消
    'order_status' => [0 => '未付款', 1 => '已完成', 2 => '已取消'],
    // 支付状态
    'pay_status' => [0 => '未支付', 1 => '已支付'],
];