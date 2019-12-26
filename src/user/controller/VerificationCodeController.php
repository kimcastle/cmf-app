<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use think\facade\Validate;

class VerificationCodeController extends HomeBaseController
{
    public function send()
    {
        $validate = new \think\Validate([
            'username' => 'require',
            'captcha'  => 'require',
        ]);

        $validate->message([
            'username.require' => '請輸入手機號或郵箱!',
            'captcha.require'  => '圖片驗證碼不能為空',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $captchaId = empty($data['captcha_id']) ? '' : $data['captcha_id'];
        if (!cmf_captcha_check($data['captcha'], $captchaId, false)) {
            $this->error('圖片驗證碼錯誤!');
        }

        $registerCaptcha = session('register_captcha');

        session('register_captcha', $data['captcha']);

        if ($registerCaptcha == $data['captcha']) {
            cmf_captcha_check($data['captcha'], $captchaId, true);
            $this->error('請輸入新圖片驗證碼!');
        }

        $accountType = '';

        if (Validate::is($data['username'], 'email')) {
            $accountType = 'email';
        } else if (cmf_check_mobile($data['username'])) {
            $accountType = 'mobile';
        } else {
            $this->error("請輸入正確的手機或者郵箱格式!");
        }

        if (isset($data['type']) && $data['type'] == 'register') {
            if ($accountType == 'email') {
                $findUserCount = db('user')->where('user_email', $data['username'])->count();
            } else if ($accountType == 'mobile') {
                $findUserCount = db('user')->where('mobile', $data['username'])->count();
            }

            if ($findUserCount > 0) {
                $this->error('賬號已註冊！');
            }
        }

        //TODO 限制 每個ip 的發送次數

        $code = cmf_get_verification_code($data['username']);
        if (empty($code)) {
            $this->error("驗證碼發送過多,請明天再試!");
        }

        if ($accountType == 'email') {

            $emailTemplate = cmf_get_option('email_template_verification_code');

            $user     = cmf_get_current_user();
            $username = empty($user['user_nickname']) ? $user['user_login'] : $user['user_nickname'];

            $message = htmlspecialchars_decode($emailTemplate['template']);
            $message = $this->view->display($message, ['code' => $code, 'username' => $username]);
            $subject = empty($emailTemplate['subject']) ? 'ThinkCMF驗證碼' : $emailTemplate['subject'];
            $result  = cmf_send_email($data['username'], $subject, $message);

            if (empty($result['error'])) {
                cmf_verification_code_log($data['username'], $code);
                $this->success("驗證碼已經發送成功!");
            } else {
                $this->error("郵箱驗證碼發送失敗:" . $result['message']);
            }

        } else if ($accountType == 'mobile') {

            $param  = ['mobile' => $data['username'], 'code' => $code];
            $result = hook_one("send_mobile_verification_code", $param);

            if ($result !== false && !empty($result['error'])) {
                $this->error($result['message']);
            }

            if ($result === false) {
                $this->error('未安裝驗證碼發送插件,請聯系管理員!');
            }

            $expireTime = empty($result['expire_time']) ? 0 : $result['expire_time'];

            cmf_verification_code_log($data['username'], $code, $expireTime);

            if (!empty($result['message'])) {
                $this->success($result['message']);
            } else {
                $this->success('驗證碼已經發送成功!');
            }

        }


    }

}
