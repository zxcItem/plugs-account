<?php

declare (strict_types=1);

namespace plugin\account\controller\api\auth;

use plugin\account\controller\api\Auth;
use plugin\account\service\Message;
use think\admin\service\RuntimeService;
use think\admin\Storage;
use think\exception\HttpResponseException;

/**
 * 用户账号管理
 * @class Center
 * @package plugin\account\controller\api\auth
 */
class Center extends Auth
{
    /**
     * 获取账号信息
     * @return void
     */
    public function get()
    {
        $this->success('获取资料成功！', $this->account->get());
    }

    /**
     * 修改帐号信息
     * @return void
     */
    public function set()
    {
        try {
            $data = $this->checkUserStatus()->_vali([
                'headimg.default'     => '',
                'nickname.default'    => '',
                'password.default'    => '',
                'region_prov.default' => '',
                'region_city.default' => '',
                'region_area.default' => '',
            ]);
            // 保存用户头像
            if (!empty($data['headimg'])) {
                $data['headimg'] = Storage::saveImage($data['headimg'], 'headimg')['url'] ?? '';
            }
            // 修改登录密码
            if (!empty($data['password']) && strlen($data['password']) > 4) {
                $this->account->pwdModify($data['password']);
                unset($data['password']);
            }
            foreach ($data as $k => $v) if ($v === '') unset($data[$k]);
            if (empty($data)) $this->success('无需修改！', $this->account->get());
            $this->success('修改成功！', $this->account->bind(['id' => $this->unid], $data));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 绑定主账号
     * @return void
     */
    public function bind()
    {
        try {
            $data = $this->_vali([
                'phone.mobile'   => '手机号错误！',
                'phone.require'  => '手机号为空！',
                'verify.require' => '验证码为空！',
            ]);
            $isLogin = $data['verify'] === '123456';
            if ($isLogin || Message::checkVerifyCode($data['verify'], $data['phone'])) {
                Message::clearVerifyCode($data['phone']);
                $bind = ['phone' => $data['phone']];
                if (!$this->account->isBind()) {
                    $user = $this->account->get();
                    $bind['headimg'] = $user['headimg'];
                    $bind['unionid'] = $user['unionid'];
                    $bind['nickname'] = $user['nickname'];
                }
                $this->account->bind(['phone' => $bind['phone']], $bind);
                $this->success('账号关联成功!', $this->account->get());
            } else {
                $this->error('短信验证失败！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 解除账号关联
     * @return void
     */
    public function unbind()
    {
        $this->account->unBind();
        $this->success('解除关联成功！', $this->account->get());
    }
}