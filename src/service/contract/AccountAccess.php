<?php


declare (strict_types=1);

namespace plugin\account\service\contract;

use plugin\account\model\AccountAuth;
use plugin\account\model\AccountBind;
use plugin\account\model\AccountUser;
use plugin\account\service\Account;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\extend\JwtExtend;
use think\App;

/**
 * 用户账号通用类
 * @class AccountAccess
 * @package plugin\account\service\contract
 */
class AccountAccess implements AccountInterface
{
    /**
     * 当前应用实例
     * @var App
     */
    protected $app;

    /**
     * 当前认证对象
     * @var AccountAuth
     */
    protected $access;

    /**
     * 当前终端对象
     * @var AccountBind
     */
    protected $client;

    /**
     * 当前通道类型
     * @var string
     */
    protected $type;

    /**
     * 授权检查字段
     * @var string
     */
    protected $field;

    /**
     * 是否JWT模式
     * @var boolean
     */
    protected $isjwt;

    /**
     * 令牌有效时间
     * @var integer
     */
    protected $expire = 3600;

    /**
     * 测试专用 TOKEN
     * 主要用于接口文档演示
     * @var string
     */
    public const tester = 'tester';

    /**
     * 通道构造方法
     * @param App $app
     * @param string $type 通道类型
     * @param string $field 授权字段
     * @throws Exception
     */
    public function __construct(App $app, string $type, string $field)
    {
        $this->app = $app;
        $this->type = $type;
        $this->field = $field;
        $this->expire = Account::expire();
    }

    /**
     * 初始化通道
     * @param string|array $token 令牌或条件
     * @param boolean $isjwt 是否返回令牌
     * @return AccountInterface
     */
    public function init($token = '', bool $isjwt = true): AccountInterface
    {
        if (empty($token)) {
            $this->access = AccountAuth::mk();
            $this->client = AccountBind::mk();
        } elseif (is_array($token)) {
            $map = ['deleted' => 0];
            if ($this->type) $map['type'] = $this->type;
            $this->client = AccountBind::mk()->where($map)->where($token)->findOrEmpty();
            $map = ['usid' => intval($this->client->getAttr('id')), 'type' => $this->type];
            $this->access = AccountAuth::mk()->where($map)->findOrEmpty();
        } else {
            $map = ['type' => $this->type, 'token' => $token];
            $this->access = AccountAuth::mk()->where($map)->findOrEmpty();
            $this->client = $this->access->client()->findOrEmpty();
        }
        $this->isjwt = $isjwt;
        return $this;
    }

    /**
     * 设置子账号资料
     * @param array $data 用户资料
     * @param boolean $rejwt 返回令牌
     * @return array
     * @throws Exception
     */
    public function set(array $data = [], bool $rejwt = false): array
    {
        // 如果传入授权验证字段
        if (isset($data[$this->field])) {
            if ($this->client->isExists()) {
                if ($data[$this->field] !== $this->client->getAttr($this->field)) {
                    throw new Exception('禁止强行关联！');
                }
            } else {
                $map = [$this->field => $data[$this->field]];
                if ($this->type) $map['type'] = $this->type;
                $this->client = AccountBind::mk()->where($map)->findOrEmpty();
            }
        } elseif ($this->client->isEmpty()) {
            throw new Exception("字段 {$this->field} 为空！");
        }
        $this->client = $this->save(array_merge($data, ['type' => $this->type]));
        if ($this->client->isEmpty()) throw new Exception('更新资料失败！');
        return $this->token()->get($rejwt);
    }

    /**
     * 获取用户数据
     * @param boolean $rejwt 返回令牌
     * @return array
     */
    public function get(bool $rejwt = false): array
    {
        $data = $this->client->hidden(['sort', 'password'])->toArray();
        if ($this->client->isExists()) {
            $data['user'] = $this->client->user()->findOrEmpty()->hidden(['sort', 'password'])->toArray();
            if ($rejwt) $data['token'] = $this->isjwt ? JwtExtend::token([
                'type' => $this->access->getAttr('type'), 'token' => $this->access->getAttr('token')
            ]) : $this->access->getAttr('token');
        }
        return $data;
    }

    /**
     * 验证终端密码
     * @param string $pwd
     * @return boolean
     */
    public function pwdVerify(string $pwd): bool
    {
        $pass = md5("Think{$pwd}Admin");
        if (($user = $this->client->user()->findOrEmpty())->isExists()) {
            if ($user->getAttr('password') === $pass && $this->expire()) return true;
        }
        return $this->client->getAttr('password') === $pass && $this->expire();
    }

    /**
     * 修改终端密码
     * @param string $pwd
     * @return boolean
     */
    public function pwdModify(string $pwd): bool
    {
        if ($this->client->isEmpty()) return false;
        $data = ['password' => md5("Think{$pwd}Admin")];
        $user = $this->client->user()->findOrEmpty();
        $user->isExists() && $user->save($data);
        return $this->client->save($data);
    }

    /**
     * 绑定主账号
     * @param array $map 主账号条件
     * @param array $data 主账号资料
     * @return array
     * @throws Exception
     */
    public function bind(array $map, array $data = []): array
    {
        if ($this->client->isEmpty()) throw new Exception('终端账号异常！');
        $user = AccountUser::mk()->where(['deleted' => 0])->where($map)->findOrEmpty();
        if (!empty($data['extra'])) $user->setAttr('extra', array_merge($user->getAttr('extra'), $data['extra']));
        unset($data['id'], $data['code'], $data['extra']);
        // 生成新的用户编号
        if ($user->isEmpty()) do $check = ['code' => $data['code'] = $this->userCode()];
        while (AccountUser::mk()->master()->where($check)->findOrEmpty()->isExists());
        // 自动绑定默认头像
        if (empty($data['headimg']) && $user->isEmpty() || empty($user->getAttr('headimg'))) {
            if (empty($data['headimg'] = $this->client->getAttr('headimg'))) $data['headimg'] = Account::headimg();
        }
        // 自动生成用户昵称
        if (empty($data['nickname']) && empty($user->getAttr('nickname'))) {
            if (empty($data['nickname'] = $this->client->getAttr('nickname'))) {
                $name = Account::get($this->type)['name'] ?? $this->type;
                $data['nickname'] = "{$name}{$this->client->getAttr('id')}";
            }
        }
        // 同步用户登录密码
        if (!empty($this->client->getAttr('password'))) {
            $data['password'] = $this->client->getAttr('password');
        }
        // 保存更新用户数据
        if ($user->save($data + $map)) {
            $this->client->save(['unid' => $user['id']]);
            $this->app->event->trigger('AccountBind', [
                'type' => $this->type,
                'unid' => intval($user['id']),
                'usid' => intval($this->client->getAttr('id')),
            ]);
            return $this->get();
        } else {
            throw new Exception('绑定用户失败！');
        }
    }

    /**
     * 解绑主账号
     * @return array
     * @throws Exception
     */
    public function unBind(): array
    {
        if ($this->client->isEmpty()) {
            throw new Exception('终端账号异常！');
        }
        if (($unid = $this->client->getAttr('unid')) > 0) {
            $this->client->save(['unid' => 0]);
            $this->app->event->trigger('AccountUnbind', [
                'unid' => intval($unid),
                'usid' => intval($this->client->getAttr('id')),
                'type' => $this->type
            ]);
        }
        return $this->get();
    }

    /**
     * 判断绑定主账号
     * @return boolean
     */
    public function isBind(): bool
    {
        return $this->client->isExists() && $this->client->user()->findOrEmpty()->isExists();
    }

    /**
     * 判断是否空账号
     * @return boolean
     */
    public function isNull(): bool
    {
        return $this->client->isEmpty();
    }

    /**
     * 获取关联终端
     * @return array
     */
    public function allBind(): array
    {
        try {
            if ($this->isNull()) return [];
            if ($this->isBind() && ($unid = $this->client->getAttr('unid'))) {
                $map = ['unid' => $unid, 'deleted' => 0];
                return AccountBind::mk()->where($map)->select()->toArray();
            } else {
                return [$this->client->refresh()->toArray()];
            }
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * 解除终端关联
     * @param integer $usid 终端编号
     * @return array
     */
    public function delBind(int $usid): array
    {
        if ($this->isBind() && ($unid = $this->client->getAttr('unid'))) {
            $map = ['id' => $usid, 'unid' => $unid];
            AccountBind::mk()->where($map)->update(['unid' => 0]);
        }
        return $this->allBind();
    }

    /**
     * 刷新账号序号
     * @return array
     */
    public function recode(): array
    {
        if ($this->client->isEmpty()) return $this->get();
        if (($user = $this->client->user()->findOrEmpty())->isExists()) {
            do $check = ['code' => $this->userCode()];
            while (AccountUser::mk()->master()->where($check)->findOrEmpty()->isExists());
            $user->save($check);
        }
        return $this->get();
    }

    /**
     * 检查是否有效
     * @return array
     * @throws Exception
     */
    public function check(): array
    {
        if ($this->client->isEmpty()) {
            throw new Exception('需要重新登录！', 401);
        }
        if ($this->expire > 0 && $this->access->getAttr('time') < time()) {
            throw new Exception('登录认证超时！', 403);
        }
        return static::expire()->get();
    }

    /**
     * 获取终端类型
     * @return string
     */
    public function type(): string
    {
        if ($this->client->isEmpty()) return '';
        return $this->client->getAttr('type');
    }

    /**
     * 获取用户编号
     * @return integer
     */
    public function unid(): int
    {
        if ($this->client->isEmpty()) return 0;
        return intval($this->client->getAttr('unid'));
    }

    /**
     * 获取终端编号
     * @return integer
     */
    public function usid(): int
    {
        if ($this->client->isEmpty()) return 0;
        return intval($this->client->getAttr('id'));
    }

    /**
     * 生成授权令牌
     * @return AccountInterface
     */
    public function token(): AccountInterface
    {
        // 十分之一概率清理令牌
        if (mt_rand(1, 100) < 10) {
            AccountAuth::mk()->whereBetween('time', [1, time()])->delete();
        }
        $usid = $this->client->getAttr('id');
        // 查询该通道历史授权记录
        if ($this->access->isEmpty()) {
            $where = ['usid' => $usid, 'type' => $this->type];
            $this->access = AccountAuth::mk()->where($where)->findOrEmpty();
        }
        // 生成新令牌数据
        if ($this->access->isEmpty()) {
            do $check = ['type' => $this->type, 'token' => md5(uniqid(strval(rand(0, 999))))];
            while (AccountAuth::mk()->master()->where($check)->findOrEmpty()->isExists());
            $this->access->save($check + ['usid' => $usid]);
        }
        return $this->expire();
    }

    /**
     * 延期令牌时间
     * @return AccountInterface
     */
    public function expire(): AccountInterface
    {
        $time = $this->expire > 0 ? $this->expire + time() : 0;
        $this->access->isExists() && $this->access->save([
            'type' => $this->type, 'time' => $time
        ]);
        return $this;
    }

    /**
     * 更新用户资料
     * @param array $data
     * @return AccountBind
     * @throws Exception
     */
    private function save(array $data): AccountBind
    {
        if (empty($data)) throw new Exception('资料不能为空！');
        $data['extra'] = array_merge($this->client->getAttr('extra'), $data['extra'] ?? []);
        // 写入默认头像内容
        if (empty($data['headimg']) && empty($this->client->getAttr('headimg'))) {
            $data['headimg'] = Account::headimg();
        }
        // 自动生成账号昵称
        if (empty($data['nickname']) && $this->client->getAttr('nickname')) {
            $name = Account::get($this->type)['name'] ?? $this->type;
            $data['nickname'] = "{$name}{$this->client->getAttr('id')}";
        }
        // 更新写入终端账号
        if ($this->client->save($data) && $this->client->isExists()) {
            return $this->client->refresh();
        } else {
            throw new Exception('资料保存失败！');
        }
    }

    /**
     * 生成用户随机编号
     * @return string
     */
    private function userCode(): string
    {
        return CodeExtend::uniqidNumber(16, 'U');
    }
}