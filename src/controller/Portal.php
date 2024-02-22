<?php

namespace plugin\account\controller;

use plugin\account\model\AccountUser;
use plugin\account\service\Source;
use think\admin\Controller;
use think\db\exception\DbException;

/**
 * 用户数据统计表
 * @class Portal
 * @package plugin\account\controller
 */
class Portal extends Controller
{

    /**
     * 用户数据统计表
     * @auth true
     * @menu true
     * @return void
     * @throws DbException
     */
    public function index()
    {
        $this->title = '用户数据统计';
        $this->provs = $this->app->cache->get('provs', []);
        if (empty($this->provs)) {
            $this->provs = Source::userToProv();
            $this->app->cache->set('provs', $this->provs, 60);
        }
        $this->ranking = Source::ranking($this->provs);

        $this->userHours = $this->app->cache->get('userHours', []);
        if (empty($this->userHours)) {
            for ($i = 0; $i < 24; $i++) {
                $date = date('Y-m-d H',strtotime(date('Y-m-d')) + $i * 3600);
                $this->userHours[] = [
                    '当天时间' => date('H:i', strtotime(date('Y-m-d')) + $i * 3600),
                    '今日统计' => AccountUser::mk()->whereLike('create_time', "{$date}%")->count()
                ];
            }
            $this->app->cache->set('userHours', $this->userHours, 60);
        }

        $this->userMonth = $this->app->cache->get('userMonth', []);
        if (empty($this->userMonth)) {
            $field = ['count(1)' => 'count', 'left(create_time,10)' => 'mday'];
            $model = AccountUser::mk()->field($field);
            $users = $model->whereTime('create_time', '-30 days')->where(['deleted' => 0])->group('mday')->select()->column(null, 'mday');
            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i}days"));
                $this->userMonth[] = [
                    '当天日期' => date('m-d', strtotime("-{$i}days")),
                    '本月统计' => ($users[$date] ?? [])['count'] ?? 0,
                ];
            }
            $this->app->cache->set('userMonth', $this->userMonth, 60);
        }
        $this->fetch();
    }
}