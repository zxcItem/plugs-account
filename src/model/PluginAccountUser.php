<?php

declare (strict_types=1);

namespace plugin\account\model;

use think\model\relation\HasMany;

/**
 * 用户账号模型
 * @class PluginAccountUser
 * @package plugin\account\model
 */
class PluginAccountUser extends Abs
{
    /**
     * 关联子账号
     * @return HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(PluginAccountBind::class, 'unid', 'id');
    }

    /**
     * 获取扩展数据
     * @param int $unid
     * @param array $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function extraItem(int $unid, array $data)
    {
        $user = static::mk()->where('id',$unid)->find()->toArray();
        foreach ($data as &$datum) $datum['amount'] = $user['extra'][$datum['name']] ?? 0;
        $user['extra_arry'] = $data;
        return $user;
    }
}