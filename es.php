<?php
// 根域名及URL
define('ROOT_DOMAIN', '.517ybang.com');
//define('ROOT_DOMAIN', '.jinlaimall.com'); // 生产环境
define('ROOT_URL', ROOT_DOMAIN.'/');

// 允许响应指定URL的跨域请求
$origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN']: NULL;
$allow_origin = array(
    'https://www'.ROOT_DOMAIN,
    'https://biz'.ROOT_DOMAIN,
    'https://admin'.ROOT_DOMAIN,
);
if ( in_array($origin, $allow_origin) ):
    header('Access-Control-Allow-Origin:'.$origin);
    header('Access-Control-Allow-Methods:POST');
    header('Access-Control-Allow-Credentials:TRUE'); // 允许将Cookie包含在请求中
endif;

header("Content-Type:text/event-stream;charset=utf-8");
header('Cache-Control:no-cache');

$content = array(
    array('type' => 'text', 'content' => '测试一下测试一下测试一下测试一下前端是否可以正常接收并解析JSON格式返回的EventStream信息'), // 文字
    array('type' => 'image', 'content' => 'image/201806/0607/1202018064.jpg'), // 图片
    array('type' => 'location', 'content' => '120.44208,36.06894'), // 位置
    //array('type' => 'url', 'url_page' => 'https://www.517ybang.com/', 'title' => '进来商城', 'url_image' => NULL), // 网页
    //array('type' => 'item', 'ids' => 3), // 商品

    //array('type' => 'order', 'order_id' => 1, 'content' => array()), // 订单
);

$data = array(
    'stuff_id' => 3,
);

// 间隔多少秒后继续运行
$second_to_sleep = 4;

while (TRUE)
{
    $timestamp = time();

    // 生成一个0-1之间的随机数，根据随机数是否大于0.5决定是客户类消息还是商家类消息（即相应字段是否有值）
    $random = rand(0,1);

    $extra_data = array(
        'message_id' => substr($timestamp, 7),

        'sender_type' => ($random > 0.5)? 'client': 'biz',
        'receiver_type' => ($random > 0.5)? 'biz': 'client',

        'user_id' => ($random > 0.5)? NULL: 1,
        'biz_id' => $_GET['biz_id'],

        'time_create' => $timestamp,
        'creator_id' => 1,
    );
    $data = array_merge($data, $extra_data);
    $data = array_merge($data, $content[ round( rand(0, count($content)) ) ]);
    //$data = array_merge($data, $content[0]);

    // 发送内容
    try {
        output($data);
    } catch(Exception $e) {
        print $e->getMessage();
        exit();
    }

    // 稍作间隔以节省数据库连接数
    sleep($second_to_sleep);
}

// 输出待发送内容
function output($data)
{
    echo "id:". $data['id']. "\n"; // 可选
    // echo "event:message". "\n"; // 可选，默认为message
    echo "retry:5000". "\n"; // 可选
    echo "data:". json_encode($data). "\n";
    echo "\n";

    @ob_flush();@flush();

    // 若上述语句无效，需禁用Nginx的buffering，即在nginx.conf文件中添加/替换配置项（若有则修改值），并重启Nginx。
    //proxy_buffering off;
    //fastcgi_keep_conn on;
}