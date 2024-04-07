<?php

declare (strict_types=1);

namespace plugin\account;

use plugin\account\model\AccountRelation;
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
     * 插件服务注册
     * @return void
     */
    public function register(): void
    {
        // 注册用户绑定事件
        $this->app->event->listen('AccountBind', function (array $data) {
            $this->app->log->notice("Event AccountBind {$data['unid']}#{$data['usid']}");
            AccountRelation::make(intval($data['unid']));
        });
    }

    /**
     * 菜单配置
     * @return array[]
     */
    public static function menu(): array
    {
        // 设置插件菜单
        $code = self::getAppCode();
        return [
            [
                'name' => '用户管理',
                'subs' => [
                    ['name' => '数据统计报表', 'icon' => 'layui-icon layui-icon-chart', 'node' => "{$code}/portal/index"],
                    ['name' => '用户账号管理', 'icon' => 'layui-icon layui-icon-username', 'node' => "{$code}/master/index"],
                    ['name' => '终端用户管理', 'icon' => 'layui-icon layui-icon-cellphone', 'node' => "{$code}/device/index"],
                    ['name' => '用户短信管理', 'icon' => 'layui-icon layui-icon-email', 'node' => "{$code}/message/index"],
                    ['name' => '用户附件管理', 'icon' => 'layui-icon layui-icon-file', 'node' => "{$code}/file/index"],
                    ['name' => '用户关系管理', 'icon' => 'layui-icon layui-icon-file', 'node' => "{$code}/relation/index"],
                ],
            ],
        ];
    }
}