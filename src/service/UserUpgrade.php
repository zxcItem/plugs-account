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
    public static function withAgent(int $unid, int $puid, $relation = null): array
    {
        $relation = $relation ?: AccountRelation::make($unid)->toArray();
        // 绑定代理数据
        $puid1 = $relation['puid1'] ?? 0; // 上1级代理
        $puid2 = $relation['puid2'] ?? 0; // 上2级代理
        if (empty($relation['puids']) && $puid > 0) {
            $relation = self::bindAgent($unid, $puid, 0);
            $puid1 = $relation->getAttr('puid1') ?: 0; // 上1级代理
            $puid2 = $relation->getAttr('puid2') ?: 0; // 上2级代理
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
    public static function bindAgent(int $unid, int $puid = 0, int $mode = 1)
    {
        try {
            $relation = AccountRelation::make($unid);
            // 已经绑定代理
            $puid1 = intval($relation->getAttr('puid1'));
            if ($puid1 > 0 && $relation->getAttr('puids') > 0) {
                if ($puid1 !== $puid && $mode !== 0) throw new Exception('已绑定代理！');
            }
            // 检查代理用户
            if (empty($puid)) $puid = $puid1;
            if (empty($puid)) throw new Exception('代理不存在！');
            if ($unid === $puid) throw new Exception('不能绑定自己！');
            // 检查上级用户
            $parent = AccountRelation::make($puid);
            if (strpos($parent->getAttr('path'), ",{$unid},") !== false) throw new Exception('不能绑定下级');
            Library::$sapp->db->transaction(static function () use ($relation, $parent, $mode) {
                // 更新用户代理
                $path1 = rtrim($parent->getAttr('path') ?: ',', ',') . ",{$parent->getAttr('unid')},";
                $relation->save([
                    'pids'  => $mode > 0 ? 1 : 0,
                    'path'  => $path1,
                    'puids' => $mode > 0 ? 1 : 0,
                    'puid1' => $parent->getAttr('unid'),
                    'puid2' => $parent->getAttr('puid1'),
                    'layer' => substr_count($path1, ',')
                ]);
                // 更新下级代理
                $path2 = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
                foreach (AccountRelation::mk()->whereLike('path', "{$path2}%")->order('layer desc')->cursor() as $item) {
                    $text = arr2str(str2arr("{$path1},{$relation->getAttr('unid')}"));
                    $attr = array_reverse(str2arr($path3 = preg_replace("#^{$path2}#", $text, $item->getAttr('path'))));
                    $item->save(['path' => $path3, 'puid1' => $attr[0] ?? 0, 'puid2' => $attr[1] ?? 0]);
                }
            });
            return $relation->toarray();
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception("绑定代理失败, {$exception->getMessage()}");
        }
    }
}