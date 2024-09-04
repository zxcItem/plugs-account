<?php


declare (strict_types=1);

namespace plugin\account\model;

use think\model\relation\HasOne;

/**
 * 子账号授权模型
 * @class PluginAccountAuth
 * @package plugin\account\model
 */
class PluginAccountAuth extends Abs
{
    /**
     * 关联子账号
     * @return HasOne
     */
    public function client(): HasOne
    {
        return $this->hasOne(PluginAccountBind::class, 'id', 'usid')->with(['user']);
    }
}