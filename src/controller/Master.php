<?php

declare (strict_types=1);

namespace plugin\account\controller;

use plugin\account\model\PluginAccountUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 用户账号管理
 * @class Master
 * @package plugin\account\controller
 */
class Master extends Controller
{
    /**
     * 用户账号管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginAccountUser::mQuery()->layTable(function () {
            $this->title = '用户账号管理';
        }, function (QueryHelper $query) {
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
            $query->like('code,phone,email,username,nickname')->dateBetween('create_time');
        });
    }

    /**
     * 修改主账号状态
     * @auth true
     */
    public function state()
    {
        PluginAccountUser::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除主账号
     * @auth true
     */
    public function remove()
    {
        PluginAccountUser::mDelete();
    }
}