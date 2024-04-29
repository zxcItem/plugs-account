<?php


declare (strict_types=1);

namespace plugin\account\model;

use plugin\account\service\Message;

/**
 * 账号短信验证模型
 * @class AccountMsms
 * @package plugin\account\model
 */
class AccountMsms extends Abs
{
    /**
     * 格式化数据
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['scene'])) {
            $data['scene_name'] = Message::$scenes[$data['scene']] ?? $data['scene'];
        }
        return $data;
    }
}