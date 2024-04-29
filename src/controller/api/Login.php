<?php

declare (strict_types=1);

namespace plugin\account\controller\api;

use plugin\account\model\AccountRelation;
use plugin\account\service\Account;
use plugin\account\service\Message;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\ImageVerify;
use think\admin\service\RuntimeService;
use think\exception\HttpResponseException;

/**
 * 手机号登录入口
 * @class Login
 * @package plugin\account\controller\api
 */
class Login extends Controller
{
    /**
     * 通过手机号登录
     * @return void
     */
    public function in()
    {
        try {
            $data = $this->_vali([
                'type.require'   => '类型为空！',
                'phone.mobile'   => '手机号错误！',
                'phone.require'  => '手机号为空！',
                'verify.require' => '验证码为空！'
            ]);
            if (Account::field($data['type']) !== 'phone') {
                $this->error('不支持登录！');
            }
            $isLogin = $data['verify'] === '123456';
            if ($isLogin || Message::checkVerifyCode($data['verify'], $data['phone'])) {
                Message::clearVerifyCode($data['phone']);
                $account = Account::mk($data['type']);
                $account->set($inset = ['phone' => $data['phone']]);
                $account->isBind() || $account->bind($inset, $inset);
                AccountRelation::sync($account->get()['id']);
                $this->success('关联账号成功！', $account->get(true));
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
     * 通过密码登录
     * @return void
     */
    public function pass()
    {
        try {
            $data = $this->_vali([
                'type.require'     => '接口类型为空！',
                'phone.mobile'     => '登录手机错误！',
                'phone.require'    => '登录手机为空！',
                'uniqid.require'   => '拼图编号为空！',
                'verify.require'   => '拼图位置为空！',
                'password.require' => '登录密码为空！',
            ]);
            if (Account::field($data['type']) !== 'phone') {
                $this->error('不支持登录！');
            }
            if (ImageVerify::verify($data['uniqid'], $data['verify'], true) !== 1) {
                $this->error('拼图验证失败！');
            }
            $account = Account::mk($data['type'], $inset = ['phone' => $data['phone']]);
            if ($account->isNull()) $this->error('该手机未注册！');
            if ($account->pwdVerify($data['password'])) {
                $account->isBind() || $account->bind($inset, $inset);
                $this->success('登录成功！', $account->get(true));
            } else {
                $this->error('密码错误！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 通过短信找回密码
     * @return void
     * @throws Exception
     */
    public function forget()
    {
        $data = $this->_vali([
            'type.require'   => '接口类型为空！',
            'phone.mobile'   => '登录手机错误！',
            'phone.require'  => '登录手机为空！',
            'verify.require' => '短信验证为空！',
            'passwd.require' => '密码不能为空！',
        ]);
        if (Message::checkVerifyCode($data['verify'], $data['phone'], Message::tForget)) {
            Message::clearVerifyCode($data['phone'], Message::tForget);
            $account = Account::mk($data['type'], ['phone' => $data['phone']]);
            if ($account->isNull()) $this->error('该账号不存在！');
            $account->pwdModify($data['passwd']);
            $this->success('密码重置成功！', $account->get(true));
        } else {
            $this->error('短信验证失败！');
        }
    }

    /**
     * 发送短信验证码
     * @return void
     */
    public function send()
    {
        $data = $this->_vali([
            'type.default'   => 'login',
            'phone.mobile'   => '手机号错误！',
            'phone.require'  => '手机号为空！',
            'uniqid.require' => '拼图编号为空！',
            'verify.require' => '拼图位置为空！',
        ]);
        // 发送手机短信验证码
        if (ImageVerify::verify($data['uniqid'], $data['verify'], true) === 1) {
            if ($data['type'] === 'login') {
                [$state, $info, $result] = Message::sendVerifyCode($data['phone']);
            } else {
                [$state, $info, $result] = Message::sendVerifyCode($data['phone'], 120, Message::tForget);
            }
            $state ? $this->success($info, $result) : $this->error($info);
        } else {
            $this->error('拼图验证失败！');
        }
    }

    /**
     * 生成拼图验证码
     * @return void
     */
    public function image()
    {
        $images = [
            syspath('public/static/theme/img/login/bg1.jpg'),
            syspath('public/static/theme/img/login/bg2.jpg'),
        ];
        $image = ImageVerify::render($images[array_rand($images)]);
        $this->success('生成拼图成功！', [
            'bgimg'  => $image['bgimg'],
            'water'  => $image['water'],
            'uniqid' => $image['code'],
        ]);
    }

    /**
     * 实时验证结果
     * @return void
     */
    public function verify()
    {
        $data = $this->_vali([
            'uniqid.require' => '拼图验证为空！',
            'verify.require' => '拼图数值为空！'
        ]);
        // state: [ -1:需要刷新, 0:验证失败, 1:验证成功 ]
        $this->success('获取验证结果！', [
            'state' => ImageVerify::verify($data['uniqid'], $data['verify'])
        ]);
    }
}