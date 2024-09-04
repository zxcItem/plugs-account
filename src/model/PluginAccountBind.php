<?php

declare (strict_types=1);

namespace plugin\account\model;

use plugin\account\service\Account;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 用户子账号模型
 * @class PluginAccountBind
 * @package plugin\account\model
 */
class PluginAccountBind extends Abs
{
    /**
     * 关联主账号
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联授权数据
     * @return HasMany
     */
    public function auths(): HasMany
    {
        return $this->hasMany(PluginAccountAuth::class, 'usid', 'id');
    }

    /**
     * 增加通道名称显示
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = Account::get($data['type'])['name'] ?? $data['type'];
        }
        return $data;
    }
}