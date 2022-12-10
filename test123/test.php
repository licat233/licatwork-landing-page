<?php
    $host = 'https://dwz.cn';
    $path = '/admin/v2/create';
    $url = $host . $path;
    $method = 'POST';
    $content_type = 'application/json';
    
        $time = time();
    $header = array(
        'typ' => 'JWT'
    );
    $array = array(
        'iss' => 'auth', // 权限验证作者
        'iat' => $time, // 时间戳
        'exp' => 3600, // token有效期，1小时
        'sub' => 'demo', // 案例
        'user_name' => $user_name
    ) // 用户名
    ;
    $str = base64_encode(json_encode($header)) . '.' . base64_encode(json_encode($array)); // 数组转成字符
    $str = urlencode($str); // 通过url转码
    
    // TODO: 设置Token
    $token = $str;
    
    // TODO：设置待注册长网址
    $bodys = array('url'=>'https://www.planttitle.com'); 

    // 配置headers
    $headers = array('Content-Type:'.$content_type, 'Token:'.$token);

    // 创建连接
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($bodys));
    
    // 发送请求
    $response = curl_exec($curl);
    curl_close($curl);
    
    // 读取响应
    var_dump($response);
    ?>