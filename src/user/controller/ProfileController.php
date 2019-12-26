<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\lib\Storage;
use think\Validate;
use think\Image;
use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use think\Db;

class ProfileController extends UserBaseController
{

    /**
     * 會員中心首頁
     */
    public function center()
    {
        $user = cmf_get_current_user();
        $this->assign($user);

        $userId = cmf_get_current_user_id();

        $userModel = new UserModel();
        $user      = $userModel->where('id', $userId)->find();
        $this->assign('user', $user);
        return $this->fetch();
    }

    /**
     * 編輯用戶資料
     */
    public function edit()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch('edit');
    }

    /**
     * 編輯用戶資料提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'user_nickname' => 'max:32',
                'sex'           => 'between:0,2',
                'birthday'      => 'dateFormat:Y-m-d|after:-88 year|before:-1 day',
                'user_url'      => 'url|max:64',
                'signature'     => 'max:128',
            ]);
            $validate->message([
                'user_nickname.max'   => lang('NICKNAME_IS_TO0_LONG'),
                'sex.between'         => lang('SEX_IS_INVALID'),
                'birthday.dateFormat' => lang('BIRTHDAY_IS_INVALID'),
                'birthday.after'      => lang('BIRTHDAY_IS_TOO_EARLY'),
                'birthday.before'     => lang('BIRTHDAY_IS_TOO_LATE'),
                'user_url.url'        => lang('URL_FORMAT_IS_WRONG'),
                'user_url.max'        => lang('URL_IS_TO0_LONG'),
                'signature.max'       => lang('SIGNATURE_IS_TO0_LONG'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $editData = new UserModel();
            if ($editData->editData($data)) {
                $this->success(lang('EDIT_SUCCESS'), "user/profile/center");
            } else {
                $this->error(lang('NO_NEW_INFORMATION'));
            }
        } else {
            $this->error(lang('ERROR'));
        }
    }

    /**
     * 個人中心修改密碼
     */
    public function password()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 個人中心修改密碼提交
     */
    public function passwordPost()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'old_password' => 'require|min:6|max:32',
                'password'     => 'require|min:6|max:32',
                'repassword'   => 'require|min:6|max:32',
            ]);
            $validate->message([
                'old_password.require' => lang('old_password_is_required'),
                'old_password.max'     => lang('old_password_is_too_long'),
                'old_password.min'     => lang('old_password_is_too_short'),
                'password.require'     => lang('password_is_required'),
                'password.max'         => lang('password_is_too_long'),
                'password.min'         => lang('password_is_too_short'),
                'repassword.require'   => lang('repeat_password_is_required'),
                'repassword.max'       => lang('repeat_password_is_too_long'),
                'repassword.min'       => lang('repeat_password_is_too_short'),
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $login = new UserModel();
            $log   = $login->editPassword($data);
            switch ($log) {
                case 0:
                    $this->success(lang('change_success'));
                    break;
                case 1:
                    $this->error(lang('password_repeat_wrong'));
                    break;
                case 2:
                    $this->error(lang('old_password_is_wrong'));
                    break;
                default :
                    $this->error(lang('ERROR'));
            }
        } else {
            $this->error(lang('ERROR'));
        }

    }

    // 用戶頭像編輯
    public function avatar()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    // 用戶頭像上傳
    public function avatarUpload()
    {
        $file   = $this->request->file('file');
        $result = $file->validate([
            'ext'  => 'jpg,jpeg,png',
            'size' => 1024 * 1024
        ])->move(WEB_ROOT . 'upload' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR);

        if ($result) {
            $avatarSaveName = str_replace('//', '/', str_replace('\\', '/', $result->getSaveName()));
            $avatar         = 'avatar/' . $avatarSaveName;
            session('avatar', $avatar);

            return json_encode([
                'code' => 1,
                "msg"  => "上傳成功",
                "data" => ['file' => $avatar],
                "url"  => ''
            ]);
        } else {
            return json_encode([
                'code' => 0,
                "msg"  => $file->getError(),
                "data" => "",
                "url"  => ''
            ]);
        }
    }

    // 用戶頭像裁剪
    public function avatarUpdate()
    {
        $avatar = session('avatar');
        if (!empty($avatar)) {
            $w = $this->request->param('w', 0, 'intval');
            $h = $this->request->param('h', 0, 'intval');
            $x = $this->request->param('x', 0, 'intval');
            $y = $this->request->param('y', 0, 'intval');

            $avatarPath = WEB_ROOT . "upload/" . $avatar;

            $avatarImg = Image::open($avatarPath);
            $avatarImg->crop($w, $h, $x, $y)->save($avatarPath);

            $result = true;
            if ($result === true) {
                $storage = new Storage();
                $result  = $storage->upload($avatar, $avatarPath, 'image');

                $userId = cmf_get_current_user_id();
                Db::name("user")->where("id", $userId)->update(["avatar" => $avatar]);
                session('user.avatar', $avatar);
                $this->success("頭像更新成功！");
            } else {
                $this->error("頭像保存失敗！");
            }

        }
    }

    /**
     * 綁定手機號或郵箱
     */
    public function binding()
    {
        $user = cmf_get_current_user();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 綁定手機號
     */
    public function bindingMobile()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|number|unique:user,mobile',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require'          => '手機號不能為空',
                'username.number'           => '手機號只能為數字',
                'username.unique'           => '手機號已存在',
                'verification_code.require' => '驗證碼不能為空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log       = $userModel->bindingMobile($data);
            switch ($log) {
                case 0:
                    $this->success('手機號綁定成功');
                    break;
                default :
                    $this->error('未受理的請求');
            }
        } else {
            $this->error("請求錯誤");
        }
    }

    /**
     * 綁定郵箱
     */
    public function bindingEmail()
    {
        if ($this->request->isPost()) {
            $validate = new Validate([
                'username'          => 'require|email|unique:user,user_email',
                'verification_code' => 'require',
            ]);
            $validate->message([
                'username.require'          => '郵箱地址不能為空',
                'username.email'            => '郵箱地址不正確',
                'username.unique'           => '郵箱地址已存在',
                'verification_code.require' => '驗證碼不能為空',
            ]);

            $data = $this->request->post();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $errMsg = cmf_check_verification_code($data['username'], $data['verification_code']);
            if (!empty($errMsg)) {
                $this->error($errMsg);
            }
            $userModel = new UserModel();
            $log       = $userModel->bindingEmail($data);
            switch ($log) {
                case 0:
                    $this->success('郵箱綁定成功');
                    break;
                default :
                    $this->error('未受理的請求');
            }
        } else {
            $this->error("請求錯誤");
        }
    }

}