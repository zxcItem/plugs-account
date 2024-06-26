<?php

use plugin\account\Service;
use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

/**
 * 用户模块初始化数据表
 */
class InstallAccount extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->_create_menu();
        $this->_create_account_auth();
        $this->_create_account_bind();
        $this->_create_account_msms();
        $this->_create_account_user();
        $this->_create_account_file();
        $this->_create_account_relation();
    }

    /**
     * 初始化系统菜单
     * @return void
     */
    private function _create_menu()
    {
        // 初始化菜单数据
        PhinxExtend::write2menu([
            [
                'name' => '用户管理',
                'subs' => Service::menu(),
            ],
        ], ['url|node' => 'plugin-account/portal/index']);
    }

    /**
     * 插件-账号-授权
     * @class AccountAuth
     * @table account_auth
     * @return void
     */
    private function _create_account_auth()
    {

        // 当前数据表
        $table = 'account_auth';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-授权',
        ])
            ->addColumn('usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '终端账号'])
            ->addColumn('time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '有效时间'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '授权类型'])
            ->addColumn('token', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '授权令牌'])
            ->addColumn('tokenv', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '授权验证'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('usid', ['name' => 'idx_account_auth_usid'])
            ->addIndex('type', ['name' => 'idx_account_auth_type'])
            ->addIndex('time', ['name' => 'idx_account_auth_time'])
            ->addIndex('token', ['name' => 'idx_account_auth_token'])
            ->addIndex('create_time', ['name' => 'idx_account_auth_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 插件-账号-终端
     * @class AccountBind
     * @table account_bind
     * @return void
     */
    private function _create_account_bind()
    {

        // 当前数据表
        $table = 'account_bind';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-终端',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员编号'])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '终端类型'])
            ->addColumn('phone', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => '绑定手机'])
            ->addColumn('appid', 'string', ['limit' => 30, 'default' => '', 'null' => true, 'comment' => 'APPID'])
            ->addColumn('openid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'OPENID'])
            ->addColumn('unionid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'UnionID'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('nickname', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '账号状态'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '注册时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'idx_account_bind_type'])
            ->addIndex('unid', ['name' => 'idx_account_bind_unid'])
            ->addIndex('sort', ['name' => 'idx_account_bind_sort'])
            ->addIndex('phone', ['name' => 'idx_account_bind_phone'])
            ->addIndex('appid', ['name' => 'idx_account_bind_appid'])
            ->addIndex('status', ['name' => 'idx_account_bind_status'])
            ->addIndex('openid', ['name' => 'idx_account_bind_openid'])
            ->addIndex('unionid', ['name' => 'idx_account_bind_unionid'])
            ->addIndex('deleted', ['name' => 'idx_account_bind_deleted'])
            ->addIndex('create_time', ['name' => 'idx_account_bind_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 插件-账号-短信
     * @class AccountMsms
     * @table account_msms
     * @return void
     */
    private function _create_account_msms()
    {

        // 当前数据表
        $table = 'account_msms';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-短信',
        ])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '账号编号'])
            ->addColumn('usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '终端编号'])
            ->addColumn('type', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '短信类型'])
            ->addColumn('scene', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '业务场景'])
            ->addColumn('smsid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '消息编号'])
            ->addColumn('phone', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '目标手机'])
            ->addColumn('result', 'string', ['limit' => 512, 'default' => '', 'null' => true, 'comment' => '返回结果'])
            ->addColumn('params', 'string', ['limit' => 512, 'default' => '', 'null' => true, 'comment' => '短信内容'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '短信状态(0失败,1成功)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'idx_account_msms_type'])
            ->addIndex('unid', ['name' => 'idx_account_msms_unid'])
            ->addIndex('usid', ['name' => 'idx_account_msms_usid'])
            ->addIndex('phone', ['name' => 'idx_account_msms_phone'])
            ->addIndex('smsid', ['name' => 'idx_account_msms_smsid'])
            ->addIndex('scene', ['name' => 'idx_account_msms_scene'])
            ->addIndex('status', ['name' => 'idx_account_msms_status'])
            ->addIndex('create_time', ['name' => 'idx_account_msms_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 插件-账号-资料
     * @class AccountUser
     * @table account_user
     * @return void
     */
    private function _create_account_user()
    {

        // 当前数据表
        $table = 'account_user';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-账号-资料',
        ])
            ->addColumn('code', 'string', ['limit' => 16, 'default' => '', 'null' => true, 'comment' => '用户编号'])
            ->addColumn('phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '用户手机'])
            ->addColumn('email', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户邮箱'])
            ->addColumn('unionid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => 'UnionID'])
            ->addColumn('username', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '用户姓名'])
            ->addColumn('nickname', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '用户昵称'])
            ->addColumn('headimg', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '用户头像'])
            ->addColumn('password', 'string', ['limit' => 32, 'default' => '', 'null' => true, 'comment' => '登录密码'])
            ->addColumn('region_prov', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在省份'])
            ->addColumn('region_city', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在城市'])
            ->addColumn('region_area', 'string', ['limit' => 99, 'default' => '', 'null' => true, 'comment' => '所在区域'])
            ->addColumn('remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '备注(内部使用)'])
            ->addColumn('extra', 'text', ['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '用户状态(0拉黑,1正常)'])
            ->addColumn('deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删,1已删)'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '注册时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('code', ['name' => 'idx_account_user_code'])
            ->addIndex('phone', ['name' => 'idx_account_user_phone'])
            ->addIndex('email', ['name' => 'idx_account_user_email'])
            ->addIndex('unionid', ['name' => 'idx_account_user_unionid'])
            ->addIndex('username', ['name' => 'idx_account_user_username'])
            ->addIndex('nickname', ['name' => 'idx_account_user_nickname'])
            ->addIndex('region_prov', ['name' => 'idx_account_user_region_prov'])
            ->addIndex('region_city', ['name' => 'idx_account_user_region_city'])
            ->addIndex('region_area', ['name' => 'idx_account_user_region_area'])
            ->addIndex('sort', ['name' => 'idx_account_user_sort'])
            ->addIndex('status', ['name' => 'idx_account_user_status'])
            ->addIndex('deleted', ['name' => 'idx_account_user_deleted'])
            ->addIndex('create_time', ['name' => 'idx_account_user_create_time'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 创建数据对象
     * @class AccountRelation
     * @table account_relation
     * @return void
     */
    private function _create_account_relation() {

        // 当前数据表
        $table = 'account_relation';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '用户-关系',
        ])
            ->addColumn('unid','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '当前用户'])
            ->addColumn('puids', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '绑定状态'])
            ->addColumn('puid1','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上1级代理'])
            ->addColumn('puid2','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '上2级代理'])
            ->addColumn('path','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '关系路径'])
            ->addColumn('layer','biginteger',['limit' => 20, 'default' => 1, 'null' => true, 'comment' => '所属层级'])
            ->addColumn('extra','text',['default' => NULL, 'null' => true, 'comment' => '扩展数据'])
            ->addColumn('level_code','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '等级编号'])
            ->addColumn('level_name','string',['limit' => 180, 'default' => '', 'null' => true, 'comment' => '等级名称'])
            ->addColumn('buy_vip_entry','integer',['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '入会礼包'])
            ->addColumn('buy_last_date','datetime',['default' => NULL, 'null' => true, 'comment' => '最后购买'])
            ->addColumn('sort','biginteger',['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重'])
            ->addColumn('create_time','datetime',['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time','datetime',['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('unid', ['name' => 'idx_account_relation_unid'])
            ->addIndex('path', ['name' => 'idx_account_relation_path'])
            ->addIndex('puid1', ['name' => 'idx_account_relation_puid1'])
            ->addIndex('puid2', ['name' => 'idx_account_relation_puid2'])
            ->addIndex('level_code', ['name' => 'idx_account_relation_level_code'])
            ->addIndex('create_time', ['name' => 'idx_account_relation_create_time'])
            ->addIndex('buy_vip_entry', ['name' => 'idx_account_relation_buy_vip_entry'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }

    /**
     * 用户附件
     * @class AccountFile
     * @table account_file
     * @return void
     */
    private function _create_account_file()
    {

        // 当前数据表
        $table = 'account_file';

        // 存在则跳过
        if ($this->hasTable($table)) return;

        // 创建数据表
        $this->table($table, [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '用户-附件',
        ])
            ->addColumn('type', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '上传类型'])
            ->addColumn('name', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '文件名称'])
            ->addColumn('xext', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '文件后缀'])
            ->addColumn('xurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '访问链接'])
            ->addColumn('xkey', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '文件路径'])
            ->addColumn('mime', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '文件类型'])
            ->addColumn('size', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '文件大小'])
            ->addColumn('unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员编号'])
            ->addColumn('isfast', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否秒传'])
            ->addColumn('issafe', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '安全模式'])
            ->addColumn('create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间'])
            ->addColumn('update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间'])
            ->addIndex('type', ['name' => 'idx_account_file_type'])
            ->addIndex('xext', ['name' => 'idx_account_file_xext'])
            ->addIndex('xkey', ['name' => 'idx_account_file_xkey'])
            ->addIndex('unid', ['name' => 'idx_account_file_unid'])
            ->addIndex('name', ['name' => 'idx_account_file_name'])
            ->addIndex('issafe', ['name' => 'idx_account_file_issafe'])
            ->addIndex('isfast', ['name' => 'idx_account_file_isfast'])
            ->create();

        // 修改主键长度
        $this->table($table)->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
    }
}
