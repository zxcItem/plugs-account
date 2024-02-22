<?php

declare (strict_types=1);

namespace app\account;

use think\admin\Plugin;

/**
 * 组件注册服务
 * @class Service
 * @package plugin\account
 */
class Service extends Plugin
{
    /**
     * 定义插件名称
     * @var string
     */
    protected $appName = '用户管理';

    /**
     * 定义安装包名
     * @var string
     */
    protected $package = 'xiaochao/plugs-account';


    /**
     * 菜单配置
     * @return array[]
     */
    public static function menu(): array
    {
        // 设置插件菜单
        $code = app(static::class)->appCode;
        return [
            [
                'name' => '用户管理',
                'subs' => [
                    [
                        'name' => '用户管理',
                        'subs' => [
                            ['name' => '数据统计报表', 'icon' => 'layui-icon layui-icon-chart', 'node' => "{$code}/portal/index"],
                            ['name' => '用户账号管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/master/index"],
                            ['name' => '终端用户管理', 'icon' => 'layui-icon layui-icon-cellphone', 'node' => "{$code}/device/index"],
                            ['name' => '用户短信管理', 'icon' => 'layui-icon layui-icon-email', 'node' => "{$code}/message/index"],
                            ['name' => '用户附件管理', 'icon' => 'layui-icon layui-icon-file', 'node' => "{$code}/file/index"],
                        ],
                    ],
                ],
            ]
        ];
    }
}