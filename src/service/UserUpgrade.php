<?php


declare (strict_types=1);

namespace plugin\account\service;

use plugin\account\model\AccountUser;
use plugin\account\model\AccountRelation;
use think\admin\Exception;
use think\admin\Library;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户等级升级服务
 * @class UserUpgrade
 * @package plugin\account\service
 */
class UserUpgrade
{

    /**
     * 读取用户代理编号
     * @param integer $unid 会员用户
     * @param integer $puid 代理用户
     * @param array|null $relation
     * @return array
     * @throws Exception
     */
    public static function withAgent(int $unid, int $puid, ?array $relation = null): array
    {
        if (empty($relation)) {
            $relation = AccountRelation::mk()->where(['unid' => $unid])->findOrEmpty()->toArray();
            if ($relation) throw new Exception("无效的关联信息");
        }
        // 绑定代理数据
        $puid0 = $relation['puid0'] ?? 0; // 临时绑定
        $puid1 = $relation['puid1'] ?? 0; // 上1级代理
        $puid2 = $relation['puid2'] ?? 0; // 上2级代理
        if (empty($puid) && empty($puid1) && $puid0 > 0) {
            $puid1 = $puid0;
            $puid2 = intval(AccountRelation::mk()->where(['unid' => $puid0])->value('puid1'));
        } elseif ($puid > 0 && empty($puid1)) {
            $puid1 = $puid;
            $puid2 = self::bindAgent($unid, $puid1, 0)['puid1'] ?? 0;
        }
        return ['unid' => $unid, 'puid1' => $puid1, 'puid2' => $puid2];
    }

    /**
     * 尝试绑定上级代理
     * @param integer $unid 用户 UNID
     * @param integer $puid 代理 UNID
     * @param integer $mode 操作类型（0临时绑定, 1永久绑定, 2强行绑定）
     * @return array
     * @throws Exception
     */
    public static function bindAgent(int $unid, int $puid = 0, int $mode = 1): array
    {
        try {
            $user = AccountRelation::mk()->where(['unid' => $unid])->findOrEmpty();
            if ($user->isEmpty()) throw new Exception('查询用户失败');
            if ($user->getAttr('puid1') && $mode !== 2) throw new Exception('已经绑定代理');
            // 检查代理用户
            if (empty($puid)) $puid = $user->getAttr('puid0');
            if (empty($puid)) throw new Exception('代理不存在');
            if ($unid == $puid) throw new Exception('不能绑定自己');
            // 检查代理资格
            $agent = AccountRelation::mk()->where(['unid' => $puid])->findOrEmpty();
            if ($agent->isEmpty()) throw new Exception('代理无推荐资格');
            if (strpos($agent->getAttr('path'), ",{$unid},") !== false) throw new Exception('不能绑定下级');
            Library::$sapp->db->transaction(static function () use ($user, $agent, $mode) {
                // 更新用户代理
                $path1 = rtrim($agent['path'] ?: ',', ',') . ",{$agent['unid']},";
                $user->save([
                    'pids'  => $mode > 0 ? 1 : 0,
                    'path'  => $path1,
                    'puid0' => $agent['unid'],
                    'puid1' => $agent['unid'],
                    'puid2' => $agent['puid1'],
                    'layer' => substr_count($path1, ',')
                ]);
                // 更新下级代理
                $path2 = ",{$user['path']}{$user['id']},";p($path2);
                if (AccountRelation::mk()->whereLike('path', "{$path2}%")->count() > 0) {
                    foreach (AccountRelation::mk()->whereLike('path', "{$path2}%")->order('layer desc')->select() as $item) {
                        $attr = array_reverse(str2arr($path3 = preg_replace("#^{$path2}#", "{$path1}{$user['id']},", $item['path'])));
                        $item->save([
                            'path'  => $path3,
                            'puid0' => $attr[0] ?? 0,
                            'puid1' => $attr[0] ?? 0,
                            'puid2' => $attr[1] ?? 0,
                            'layer' => substr_count($path3, '-')
                        ]);
                    }
                }
            });
            return $agent->toArray();
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception("绑定代理失败, {$exception->getMessage()}");
        }
    }
}