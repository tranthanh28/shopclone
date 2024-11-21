<?php

    define("IN_SITE", true);
    require_once(__DIR__."/../libs/db.php");
    require_once(__DIR__.'/../config.php');
    require_once(__DIR__."/../libs/lang.php");
    require_once(__DIR__."/../libs/helper.php");
    $CMSNT = new DB();


    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($CMSNT->site('status') != 1 && !isset($_SESSION['admin_login'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Hệ thống đang bảo trì')]));
        }
        if (empty($_POST['username']) || empty($_POST['password'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng điền thông tin đăng nhập')]));
        }
        if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '".check_string($_POST['username'])."' AND `admin` = 1  ")) {
            die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
        }
        if (empty($_POST['product'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập ID sản phẩm')]));
        }
        $password = check_string($_POST['password']);
        if ($CMSNT->site('type_password') == 'bcrypt') {
            if (!password_verify($password, $getUser['password'])) {
                die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
            }
        } else {
            if ($getUser['password'] != TypePassword($password)) {
                die(json_encode(['status' => 'error','msg' => __('Thông tin đăng nhập không chính xác')]));
            }
        }
        // kiểm tra ip có trong whitelist
        if($CMSNT->site('status_security') == 1){
            if(!$CMSNT->get_row("SELECT * FROM `ip_white` WHERE `ip` = '".myip()."' ")){
                die(json_encode(['status' => 'error','msg' => __('IP của bạn không được phép truy cập')]));
            }
        }
        if(!$row = $CMSNT->get_row(" SELECT * FROM `products` WHERE `id` = '".check_string($_POST['product'])."' ")){
            die(json_encode(['status' => 'error','msg' => __('ID sản phẩm không tồn tại trong hệ thống')]));
        }
        if(isset($_POST['product']) && isset($_POST['account'])){  
            $isAdd = $CMSNT->insert("accounts", [
                    'product_id'    => check_string($_POST['product']),
                    'seller'        => $getUser['id'],
                    'account'       => $_POST['account'],
                    'status'        => 'LIVE',
                    'create_date'   => gettime(),
                    'create_time'   => time(),
                    'update_date'   => gettime(),
                    'update_time'   => time()
                ]);
                
            if($isAdd){
                /** SEND NOTI TELEGRAM */
                $my_text = $CMSNT->site('noti_import_telegram');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{name}', $row['name'], $my_text);
                $my_text = str_replace('{amount}', 1, $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessTelegram($my_text, $CMSNT->site('group_id_import_telegram'));
                die('Thêm tài khoản thành công!');
            }
        }else{
            die(json_encode(['status' => 'error', 'msg' => __('Thiếu product và account')]));
        }
    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if ($CMSNT->site('status') != 1 && !isset($_SESSION['admin_login'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Hệ thống đang bảo trì')]));
        }
        if (empty($_GET['username']) || empty($_GET['password'])) {
            die(json_encode(['status' => 'error', 'msg' => __('Vui lòng điền thông tin đăng nhập')]));
        }
        if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '".check_string($_GET['username'])."' AND `admin` = 1  ")) {
            die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
        }
        $password = check_string($_GET['password']);
        if ($CMSNT->site('type_password') == 'bcrypt') {
            if (!password_verify($password, $getUser['password'])) {
                die(json_encode(['status' => 'error', 'msg' => __('Thông tin đăng nhập không chính xác')]));
            }
        } else {
            if ($getUser['password'] != TypePassword($password)) {
                die(json_encode(['status' => 'error','msg' => __('Thông tin đăng nhập không chính xác')]));
            }
        }

        if(isset($_GET['account'])){
            $dataAccount = explode("|",$_GET['account']);
            $length = count($dataAccount);
            if($length< 2) {
                die(json_encode(['status' => 'error','msg' => __('incorrect account format')]));
            }
            $product_area = strtolower(check_string($dataAccount[$length - 1]));
            $mappingProduct = [
                'vn' => 2,
                'us' => 4,
                'in' => 3,
//                'id' => 3,
            ];
            $product_id = $mappingProduct[$product_area] ?? 1;
            $clone_id = check_string($dataAccount[0]);
            if($row = $CMSNT->get_row(" SELECT * FROM `accounts` WHERE `clone_id` = '".$clone_id."'  ")){
                die(json_encode(['status' => 'error','msg' => __('Clone Id Is Unique!!')]));
            }
            try {
                $data = [
                    'product_id'    => $product_id,
                    'product_area'    => $product_area,
                    'seller'        => $getUser['id'],
                    'account'       => $_GET['account'],
                    'clone_id'       => $clone_id,
                    'status'        => 'LIVE',
                    'create_date'   => gettime(),
                    'create_time'   => time(),
                    'update_date'   => gettime(),
                    'update_time'   => time()
                ];
                $isAdd = $CMSNT->insert("accounts", $data);

                if($isAdd){
                    /** SEND NOTI TELEGRAM */
//                $my_text = $CMSNT->site('noti_import_telegram');
//                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
//                $my_text = str_replace('{name}', $row['name'], $my_text);
//                $my_text = str_replace('{amount}', 1, $my_text);
//                $my_text = str_replace('{time}', gettime(), $my_text);
//                sendMessTelegram($my_text, $CMSNT->site('group_id_import_telegram'));
                    die('Thêm tài khoản thành công!');
                } else {
                    die('Thêm tài khoản thất bại');
                }
            } catch (Exception $e) {
                die($e->getMessage());
            }

        }else{
            die(json_encode(['status' => 'error', 'msg' => __('Thiếu product và account')]));
        }
    }