<?php


declare (strict_types=1);

namespace plugin\account\model;


use think\admin\Exception;
use think\model\relation\HasOne;

/**
 * 用户关系数据
 * @class AccountRelation
 * @package plugin\account\model
 */
class AccountRelation extends Abs
{
    /**
     * 关联当前用户
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'unid');
    }

    /**
     * 关联临时上级
     * @return HasOne
     */
    public function user0(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid0');
    }

    /**
     * 关联上1级用户
     * @return HasOne
     */
    public function user1(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid1');
    }

    /**
     * 关联上2级用户
     * @return HasOne
     */
    public function user2(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'id', 'puid2');
    }

    /**
     * 关联临时上级关系
     * @return HasOne
     */
    public function relation0(): HasOne
    {
        return $this->hasOne(AccountRelation::class, 'unid', 'puid0')->with('user');
    }

    /**
     * 关联上1级关系
     * @return HasOne
     */
    public function relation1(): HasOne
    {
        return $this->hasOne(AccountRelation::class, 'unid', 'puid1')->with('user');
    }

    /**
     * 关联上2级关系
     * @return HasOne
     */
    public function relation2(): HasOne
    {
        return $this->hasOne(AccountRelation::class, 'unid', 'puid2')->with('user');
    }

    /**
     * 更新用户推荐关系
     * @param integer $unid 用户编号
     * @return $this
     * @throws Exception
     */
    public static function make(int $unid): AccountRelation
    {
        $user = AccountUser::mk()->findOrEmpty($unid);
        if ($user->isEmpty()) throw new Exception("无效的用户！");
        $rela = static::mk()->where(['unid' => $unid])->findOrEmpty();
        if ($rela->isEmpty() || empty($rela->getAttr('path')) || empty($rela->getAttr('level_name'))) {
            $rela->save(['unid' => $unid, 'path' => ',', 'level_name' => '普通用户']);
        }
        return $rela;
    }
}