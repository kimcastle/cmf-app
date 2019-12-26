<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use cmf\lib\Upload;
use think\exception\HttpResponseException;
use think\Response;

/**
 * 百度編輯器文件上傳處理控制器
 * Class Ueditor
 * @package app\asset\controller
 */
class UeditorController extends HomeBaseController
{

    private $stateMap = [ //上傳狀態映射表，國際化用戶需考慮此處數據的國際化
        "SUCCESS", //上傳成功標記，在UEditor中內不可改變，否則flash判斷會出錯
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "文件未被完整上傳",
        "沒有文件被上傳",
        "上傳文件為空",
        "ERROR_TMP_FILE"           => "臨時文件錯誤",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到臨時文件",
        "ERROR_SIZE_EXCEED"        => "文件大小超出網站限制",
        "ERROR_TYPE_NOT_ALLOWED"   => "文件類型不允許",
        "ERROR_CREATE_DIR"         => "目錄創建失敗",
        "ERROR_DIR_NOT_WRITEABLE"  => "目錄沒有寫權限",
        "ERROR_FILE_MOVE"          => "文件保存時出錯",
        "ERROR_FILE_NOT_FOUND"     => "找不到上傳文件",
        "ERROR_WRITE_CONTENT"      => "寫入文件內容錯誤",
        "ERROR_UNKNOWN"            => "未知錯誤",
        "ERROR_DEAD_LINK"          => "鏈接不可用",
        "ERROR_HTTP_LINK"          => "鏈接不是http鏈接",
        "ERROR_HTTP_CONTENTTYPE"   => "鏈接contentType不正確"
    ];

    /**
     * 初始化
     */
    public function initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId  = cmf_get_current_user_id();
        if (empty($adminId) && empty($userId)) {
            $this->error("非法上傳！");
        }
    }

    /**
     * 處理上傳處理
     */
    public function upload()
    {
//        error_reporting(E_ERROR);
//        header("Content-Type: text/html; charset=utf-8");

        $action = $this->request->param('action');

        switch ($action) {

            case 'config':
                $result = $this->ueditorConfig();
                break;

            /* 上傳圖片 */
            case 'uploadimage':
                $result = $this->ueditorUpload("image");
                break;
            /* 上傳塗鴉 */
            case 'uploadscrawl':
                $result = $this->ueditorUpload("image");
                break;
            /* 上傳視頻 */
            case 'uploadvideo':
                $result = $this->ueditorUpload("video");
                break;
            /* 上傳文件 */
            case 'uploadfile':
                $result = $this->ueditorUpload("file");
                break;

            /* 列出圖片 */
            case 'listimage':
                $result = "";
                break;
            /* 列出文件 */
            case 'listfile':
                $result = "";
                break;

            /* 抓取遠程文件 */
            case 'catchimage':
                $result = $this->_get_remote_image();
                break;

            default:
                $result = json_encode(['state' => '請求地址出錯']);
                break;
        }

        /* 輸出結果 */
        if (isset($_GET["callback"]) && false) {//TODO 跨域上傳
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode([
                    'state' => 'callback參數不合法'
                ]);
            }
        } else {
            $response = Response::create(json_decode($result,true),'json');
            throw new HttpResponseException($response);
        }
    }


    /**
     * 獲取遠程圖片
     */
    private function _get_remote_image()
    {

        $source = $this->request->param('source');


        $item              = [
            "state"    => "",
            "url"      => "",
            "size"     => "",
            "title"    => "",
            "original" => "",
            "source"   => ""
        ];
        $date              = date("Ymd");
        $uploadSetting     = cmf_get_upload_setting();
        $uploadMaxFileSize = $uploadSetting["image"]['upload_max_filesize'];
        $uploadMaxFileSize = empty($uploadMaxFileSize) ? 2048 : $uploadMaxFileSize;//默認2M
        $allowedExts       = explode(',', $uploadSetting["image"]["extensions"]);
        $strSavePath       = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . "ueditor" . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR;
        //遠程抓取圖片配置
        $config = [
            "savePath"   => $strSavePath,            //保存路徑
            "allowFiles" => $allowedExts,// [".gif", ".png", ".jpg", ".jpeg", ".bmp"], //文件允許格式
            "maxSize"    => $uploadMaxFileSize                    //文件大小限制，單位KB
        ];

        $storage_setting = cmf_get_cmf_settings('storage');
        $qiniu_domain    = $storage_setting['Qiniu']['domain'];
        $no_need_domains = [$qiniu_domain];

        $list = [];
        foreach ($source as $imgUrl) {
            $host = str_replace(['http://', 'https://'], '', $imgUrl);
            $host = explode('/', $host);
            $host = $host[0];
            if (in_array($host, $no_need_domains)) {
                continue;
            }
            $return_img           = $item;
            $return_img['source'] = $imgUrl;
            $imgUrl               = htmlspecialchars($imgUrl);
            $imgUrl               = str_replace("&amp;", "&", $imgUrl);
            //http開頭驗證
            if (strpos($imgUrl, "http") !== 0) {
                $return_img['state'] = $this->stateMap['ERROR_HTTP_LINK'];
                array_push($list, $return_img);
                continue;
            }

            //獲取請求頭
            // is_sae()

            if (!cmf_is_sae()) {//SAE下無效
                $heads = get_headers($imgUrl);
                //死鏈檢測
                if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
                    $return_img['state'] = $this->stateMap['ERROR_DEAD_LINK'];
                    array_push($list, $return_img);
                    continue;
                }
            }

            //格式驗證(擴展名驗證和Content-Type驗證)
            $fileType = strtolower(strrchr($imgUrl, '.'));
            if (!in_array($fileType, $config['allowFiles']) || stristr($heads['Content-Type'], "image")) {
                $return_img['state'] = $this->stateMap['ERROR_HTTP_CONTENTTYPE'];
                array_push($list, $return_img);
                continue;
            }

            //打開輸出緩沖區並獲取遠程圖片
            ob_start();
            $context = stream_context_create(
                [
                    'http' => [
                        'follow_location' => false // don't follow redirects
                    ]
                ]
            );
            //請確保php.ini中的fopen wrappers已經激活
            readfile($imgUrl, false, $context);
            $img = ob_get_contents();
            ob_end_clean();

            //大小驗證
            $uriSize   = strlen($img); //得到圖片大小
            $allowSize = 1024 * $config['maxSize'];
            if ($uriSize > $allowSize) {
                $return_img['state'] = $this->stateMap['ERROR_SIZE_EXCEED'];
                array_push($list, $return_img);
                continue;
            }

            $file     = uniqid() . strrchr($imgUrl, '.');
            $savePath = $config['savePath'];
            $tmpName  = $savePath . $file;

            //創建保存位置
            if (!file_exists($savePath)) {
                mkdir("$savePath", 0777, true);
            }

            $file_write_result = cmf_file_write($tmpName, $img);

            if ($file_write_result) {
                if (config('FILE_UPLOAD_TYPE') == 'Qiniu') {

                    //todo qiniu  code

                }

                if (config('FILE_UPLOAD_TYPE') == 'Local') {

                    $file = $strSavePath . $file;

                    $return_img['state'] = 'SUCCESS';
                    $return_img['url']   = $file;
                    array_push($list, $return_img);
                }
            } else {
                $return_img['state'] = $this->stateMap['ERROR_WRITE_CONTENT'];
                array_push($list, $return_img);
            }
        }

        return json_encode([
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list'  => $list
        ]);
    }

    /**
     * 文件上傳
     * @param string $fileType 文件類型
     * @return string
     */
    private function ueditorUpload($fileType = 'image')
    {
        $uploader = new Upload();
        $uploader->setFileType($fileType);
        $uploader->setFormName('upfile');
        $result = $uploader->upload();

        if ($result === false) {
            return json_encode([
                'state' => $uploader->getError()
            ]);
        } else {
            return json_encode([
                'state'    => 'SUCCESS',
                'url'      => $result['url'],
                'title'    => $result['name'],
                'original' => $result['name']
            ]);
        }

    }

    /**
     * 獲取百度編輯器配置
     */
    private function ueditorConfig()
    {
        $config_text    = preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(WEB_ROOT . "static/js/ueditor/config.json"));
        $config         = json_decode($config_text, true);
        $upload_setting = cmf_get_upload_setting();

        $config['imageMaxSize']    = $upload_setting['file_types']['image']['upload_max_filesize'] * 1024;
        $config['imageAllowFiles'] = array_map([$this, 'ueditorExtension'], explode(",", $upload_setting['file_types']['image']['extensions']));
        $config['scrawlMaxSize']   = $upload_setting['file_types']['image']['upload_max_filesize'] * 1024;
//
        $config['catcherMaxSize']    = $upload_setting['file_types']['image']['upload_max_filesize'] * 1024;
        $config['catcherAllowFiles'] = array_map([$this, 'ueditorExtension'], explode(",", $upload_setting['file_types']['image']['extensions']));

        $config['videoMaxSize']    = $upload_setting['file_types']['video']['upload_max_filesize'] * 1024;
        $config['videoAllowFiles'] = array_map([$this, 'ueditorExtension'], explode(",", $upload_setting['file_types']['video']['extensions']));

        $config['fileMaxSize']    = $upload_setting['file_types']['file']['upload_max_filesize'] * 1024;
        $config['fileAllowFiles'] = array_map([$this, 'ueditorExtension'], explode(",", $upload_setting['file_types']['file']['extensions']));

        return json_encode($config);
    }

    /**
     * 格式化後綴
     * @param $str
     * @return string
     */
    private function ueditorExtension($str)
    {
        return "." . trim($str, '.');
    }

}