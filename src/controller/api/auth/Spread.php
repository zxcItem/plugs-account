<?php


declare (strict_types=1);

namespace plugin\account\controller\api\auth;

use plugin\account\controller\api\Auth;
use plugin\account\model\AccountRelation;
use plugin\account\service\UserUpgrade;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 推广用户管理
 * @class Spread
 * @package plugin\wemall\controller\api\auth
 */
class Spread extends Auth
{
    /**
     * 获取我推广的用户
     * @return void
     */
    public function get()
    {
        AccountRelation::mQuery(null, function (QueryHelper $query) {
            $query->with(['user'])->where(['puid1' => $this->unid])->order('id desc');
            $this->success('获取数据成功！', $query->page(intval(input('page', 1)), false, false, 10));
        });
    }

    /**
     * 临时绑定推荐人
     * @return void
     */
    public function spread()
    {
        try {
            $input = $this->_vali(['from.require' => '推荐人不能为空！']);
            $this->success('绑定推荐人成功！', UserUpgrade::bindAgent($this->unid, intval($input['from'])));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}