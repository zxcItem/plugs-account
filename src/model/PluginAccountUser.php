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
}