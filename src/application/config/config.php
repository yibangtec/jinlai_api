<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 根域名及URL
if (ENVIRONMENT !== 'production'): // 测试环境
    define('ROOT_DOMAIN', '.517ybang.com');
else: // 生产环境
    define('ROOT_DOMAIN', '.jinlaimall.com');
endif;
define('ROOT_URL', ROOT_DOMAIN.'/');

// 对AJAX请求做安全性方面的特殊处理
if (
    isset($_SERVER['HTTP_ACCEPT'])
    && (
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== FALSE
        || strpos($_SERVER['HTTP_ACCEPT'], 'text/event-stream') !== FALSE
    )
):
    header('X-Content-Type-Options:nosniff'); // 防止IE8+自行推测数据格式
    header('X-Frame-Options:deny'); // 禁止在FRAME中读取数据
    header("Content-Security-Policy:default-src 'none'"); // 检测和防御XSS（通过设置资源路径）
    header('X-XSS-Protection:1;mode=block'); // 部分旧浏览器中检测和防御XSS
    header('Strict-Transport-Security:max-age=3600;includeSubDomains'); // 只允许通过HTTPS进行访问（一小时内）

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
endif;

// 需要自定义的常量
define('SITE_NAME', '进来API'); // 站点名称

define('BASE_URL', 'https://'. $_SERVER['SERVER_NAME']); // 可对外使用的站点URL；在本地测试时须替换为类似“localhost/BasicCodeigniter”形式
define('API_URL', 'https://api'.ROOT_URL); // API URL
define('WEB_URL', 'https://www'.ROOT_URL); // 客户端 URL
define('BIZ_URL', 'https://biz'.ROOT_URL); // 商家端 URL
define('ADMIN_URL', 'https://admin'.ROOT_URL); // 管理端 URL
function api_url($api_name)
{
    return API_URL. $api_name;
}

define('COOKIE_DOMAIN', NULL); // cookie存储路径；方便起见可让所有子域共享，若需分离可自行配置
define('SESSION_COOKIE_NAME', NULL); // 用于cookie存储的session名（设置此值后，前后台session互不影响）
define('SESSION_TABLE', NULL); // 用于session存储的数据库表名
define('SESSION_PERIOD', 2592000); // session有效期秒数，此处设为30天，即60秒*60分*24小时*30天
define('ENCRYPTION_KEY', ''); // 秘钥用于加密相关功能，可为空

/**
 * 第三方功能相关配置
 */
// 高德地图服务端API key
define('AMAP_KEY_SERVER', 'b94afbbcc615ce340fc91156089eea18');

// 微信公众平台参数
// 平台版公众号
define('WECHAT_APP_ID', '');
define('WECHAT_APP_SECRET', '');
define('WECHAT_TOKEN', '');
define('AES_KEY', '');

// 微信支付
define('WEPAY_NOTIFY_URL', BASE_URL.'/wepay/notify'); // 异步回调URL
define('WEPAY_SSLCERT_PATH', '/www/web/jinlai_api/public_html/payment/wepay/cert/apiclient_cert.pem');
define('WEPAY_SSLKEY_PATH', '/www/web/jinlai_api/public_html/payment/wepay/cert/apiclient_key.pem');
// APP
define('WEPAY_MCH_ID', '1489776982'); // 商户号
define('WEPAY_APP_ID', 'wx9b19c66cc2b8bfb1'); // APP的AppId
define('WEPAY_KEY', 'OHLAt2qyVdNVHqWWoWoc5Q4UbpFycpH6');
// TODO 公众号
//define('WEPAY_MCH_ID', '1488874732'); // 商户号
//define('WEPAY_APP_ID', ''); // APP的AppId
//define('WEPAY_APP_SECRET', ''); // APP的AppSecret，不适用于APP支付
//define('WEPAY_KEY', '');

// 支付宝
define('ALIPAY_CACERT_PATH', '/www/web/jinlai_api/public_html/payment/alipay/cacert.pem');
define('ALIPAY_APP_ID', '2017092508927608');
define('ALIPAY_KEY_PUBLIC', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuA5NLDQD6swadmKSX00RbVKfxISY2fP6gl39/Pc8jxBRBMr/B9Ciy4Gm8YJ/Y8d58Km18escwTeCaEcoYPrCOk5nLJrpPSAiIdmlaD74VQRCJZoYop98XL64nOtFY4GNzCp4Hgmyp3Jd7XlZ/M9eIzHWRWsj2FWuIDdegEZ+7XPw8qU6txfIdOhD+/lufrZdoX8ElCDxXa8n9wUPk8wjX0H556lorriu0Wmy5OWzXqxN0G0ywlkbETGWZzAhbaT57l1jjultGn/4WaGsJkfXpHYlvdxLwJ8n4Mk/F58wp0ZHFOr9TuklGfVVVqBz8HvRq12mNkxCZJsvGKVP17rvuwIDAQAB'); // 公钥
define('ALIPAY_KEY_PRIVATE',
'MIIEpAIBAAKCAQEAuF8c7UM66fUOJvvq6VbZo22744X9VxSdldzXXQbzpddp/vQOtCgShybqk/ig3Eh4ZaoHzeKk/UPcUjH/5tjOlui2o5quvdJFJbjstg1s7CwcVXW/EB04baMHU8aDXMUbh2/MmtZYmGuqIMmI5YF+5KfCcB5MjGN+piPZnBvs30mC0O24ZKTfCBcLLDv5+VJPIXz4BD1lG8CZ/8+3YxDEV03PF/GgfkM6TBwkNsR64G7MVHXf+mZnEA54rc/wVVeBzBiiCVfxjNBuvAiMrY3eAFLYLgORRnNEzzm+I1LmOcCTOjzpH5NrgGtDfruxtDeYMs9BjXkD3Ic96GiizAwcZwIDAQABAoIBAHvwtGlrAHe2HMVoJAqoL7YFVoEk2aFoYmcUBlKrEa8ymDajqh7BsXLZXmgKg1iR/x2Yp5Zn/bGjpMA8jGKK7JXV6rEgksdYStOI9NeNPuOk44cvmDkk64IITiyrDjOW7WKmbUzJOtV7yuovkK931e2wOK1WMO9PExxsjSS8QQf4JpbNtFDdfNrUF5OuFAVzAm+IdO7JPSFGj6SJFmFP7tKGO8eFP95tmczV36WoU2RT3GbD/oFSA1/kkIeHlXzuhdK7CVla0swJRLRUSkF0Q1V9ZkNVzMkwcA9zo04QdGUIQMfJRBpIss5LdaDUDbmlnJJwldNJVg3JhjhYVOoaGMECgYEA3+B4pXsLWoaaug9+k933OT+56lFElcnnsF+Zcd3oIdlOblCmsXEy6LI6UZtnFuilq1/uOSYX5gS9IYRCwD8pceFQKmBzRjbKiUaiCyFVseW0XXlIAmzY8kBBGbICmR6X2uTFf8DVrfxS61Du80bJydbiJvnnZ3iEOlCeYq1vvmsCgYEA0tOElQkzKwSSSndPCsmLqhC+JYEmNEX4y/7+SP4gOE2fWOOM5sFOSJ12pU8I02o9P0uklP6DYNFxUhJOxiolTobhCPNLJb2117Zt0AeaRuqYLo6jAzYIoJAojb9XobnKLf2Mik7obn7CXlYvxJ+dXzXHB6411tQ7bNO4IwFIoPUCgYEA0jWTEs5V+soovkuOLolceQS9LKbiH0NVqOYazi/uptnEKxDPdA02IAg5eibQxVHtPNz2cfKyvef1LmNhyeGEqMlG3INzuZn40qzfulOygzeMA7i9RImvqsdqWRYsGln/fCkSyMHn4VXrBckYlJUDI+IAt1gvT5h5j8fi8ASpx8ECgYA5ayQr1wKZj7gsEcx0OqoQGlk/K6p1CC2XmY414QhzbSid8/N3EWS5wDEFGr5jngaqS3a6oYq0frZnTNcpf2cDuRZm8qQf1khFRMkppDhvYgsqeuyIvlmhKUHyQQ+j207mMazqKk2BcoKLYNvHqFUbDjFztQ2ywcChhhQbbIkUVQKBgQC1ws1/9z1ys4kXRIX/tUrYRkXMbGVs6nrHJNyOkyRjkR7ATv7RO9GvubeDmoinHlQAuV2MHDgEKQ5Vx8M7yTAeTzgYtlUCqEWVvA6lKnXkzofPFhzEx8KmoklkSctebJw4ydse13p7Kxx3gG2u9rEF0KD3eQr4b5QVxZap2cx3pg=='); // 私钥

/*
|--------------------------------------------------------------------------
| Base Site URL
|--------------------------------------------------------------------------
|
| URL to your CodeIgniter root. Typically this will be your base URL,
| WITH a trailing slash:
|
|	http://example.com/
|
| WARNING: You MUST set this value!
|
| If it is not set, then CodeIgniter will try guess the protocol and path
| your installation, but due to security concerns the hostname will be set
| to $_SERVER['SERVER_ADDR'] if available, or localhost otherwise.
| The auto-detection mechanism exists only for convenience during
| development and MUST NOT be used in production!
|
| If you need to allow multiple domains, remember that this file is still
| a PHP script and you can easily do that on your own.
|
*/
$config['base_url'] = BASE_URL;

/*
|--------------------------------------------------------------------------
| Index File
|--------------------------------------------------------------------------
|
| Typically this will be your index.php file, unless you've renamed it to
| something else. If you are using mod_rewrite to remove the page set this
| variable so that it is blank.
|
*/
$config['index_page'] = '';

/*
|--------------------------------------------------------------------------
| URI PROTOCOL
|--------------------------------------------------------------------------
|
| This item determines which server global should be used to retrieve the
| URI string.  The default setting of 'REQUEST_URI' works for most servers.
| If your links do not seem to work, try one of the other delicious flavors:
|
| 'REQUEST_URI'    Uses $_SERVER['REQUEST_URI']
| 'QUERY_STRING'   Uses $_SERVER['QUERY_STRING']
| 'PATH_INFO'      Uses $_SERVER['PATH_INFO']
|
| WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
*/
$config['uri_protocol']	= 'REQUEST_URI';

/*
|--------------------------------------------------------------------------
| URL suffix
|--------------------------------------------------------------------------
|
| This option allows you to add a suffix to all URLs generated by CodeIgniter.
| For more information please see the user guide:
|
| https://codeigniter.com/user_guide/general/urls.html
*/
$config['url_suffix'] = '';

/*
|--------------------------------------------------------------------------
| Default Language
|--------------------------------------------------------------------------
|
| This determines which set of language files should be used. Make sure
| there is an available translation if you intend to use something other
| than english.
|
*/
$config['language']	= 'chinese';

/*
|--------------------------------------------------------------------------
| Default Character Set
|--------------------------------------------------------------------------
|
| This determines which character set is used by default in various methods
| that require a character set to be provided.
|
| See http://php.net/htmlspecialchars for a list of supported charsets.
|
*/
$config['charset'] = 'UTF-8';

/*
|--------------------------------------------------------------------------
| Enable/Disable System Hooks
|--------------------------------------------------------------------------
|
| If you would like to use the 'hooks' feature you must enable it by
| setting this variable to TRUE (boolean).  See the user guide for details.
|
*/
$config['enable_hooks'] = FALSE;

/*
|--------------------------------------------------------------------------
| Class Extension Prefix
|--------------------------------------------------------------------------
|
| This item allows you to set the filename/classname prefix when extending
| native libraries.  For more information please see the user guide:
|
| https://codeigniter.com/user_guide/general/core_classes.html
| https://codeigniter.com/user_guide/general/creating_libraries.html
|
*/
$config['subclass_prefix'] = 'MY_';

/*
|--------------------------------------------------------------------------
| Composer auto-loading
|--------------------------------------------------------------------------
|
| Enabling this setting will tell CodeIgniter to look for a Composer
| package auto-loader script in application/vendor/autoload.php.
|
|	$config['composer_autoload'] = TRUE;
|
| Or if you have your vendor/ directory located somewhere else, you
| can opt to set a specific path as well:
|
|	$config['composer_autoload'] = '/path/to/vendor/autoload.php';
|
| For more information about Composer, please visit http://getcomposer.org/
|
| Note: This will NOT disable or override the CodeIgniter-specific
|	autoloading (application/config/autoload.php)
*/
$config['composer_autoload'] = FALSE;

/*
|--------------------------------------------------------------------------
| Allowed URL Characters
|--------------------------------------------------------------------------
|
| This lets you specify which characters are permitted within your URLs.
| When someone tries to submit a URL with disallowed characters they will
| get a warning message.
|
| As a security measure you are STRONGLY encouraged to restrict URLs to
| as few characters as possible.  By default only these are allowed: a-z 0-9~%.:_-
|
| Leave blank to allow all characters -- but only if you are insane.
|
| The configured value is actually a regular expression character group
| and it will be executed as: ! preg_match('/^[<permitted_uri_chars>]+$/i
|
| DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
|
*/
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';

/*
|--------------------------------------------------------------------------
| Enable Query Strings
|--------------------------------------------------------------------------
|
| By default CodeIgniter uses search-engine friendly segment based URLs:
| example.com/who/what/where/
|
| You can optionally enable standard query string based URLs:
| example.com?who=me&what=something&where=here
|
| Options are: TRUE or FALSE (boolean)
|
| The other items let you set the query string 'words' that will
| invoke your controllers and its functions:
| example.com/index.php?c=controller&m=function
|
| Please note that some of the helpers won't work as expected when
| this feature is enabled, since CodeIgniter is designed primarily to
| use segment based URLs.
|
*/
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
|
| You can enable error logging by setting a threshold over zero. The
| threshold determines what gets logged. Threshold options are:
|
|	0 = Disables logging, Error logging TURNED OFF
|	1 = Error Messages (including PHP errors)
|	2 = Debug Messages
|	3 = Informational Messages
|	4 = All Messages
|
| You can also pass an array with threshold levels to show individual error types
|
| 	array(2) = Debug Messages, without Error Messages
|
| For a live site you'll usually only enable Errors (1) to be logged otherwise
| your log files will fill up very fast.
|
*/
$config['log_threshold'] = 0;

/*
|--------------------------------------------------------------------------
| Error Logging Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| application/logs/ directory. Use a full server path.
|
*/
$config['log_path'] = '';

/*
|--------------------------------------------------------------------------
| Log File Extension
|--------------------------------------------------------------------------
|
| The default filename extension for log files. The default 'php' allows for
| protecting the log files via basic scripting, when they are to be stored
| under a publicly accessible directory.
|
| Note: Leaving it blank will default to 'php'.
|
*/
$config['log_file_extension'] = '';

/*
|--------------------------------------------------------------------------
| Log File Permissions
|--------------------------------------------------------------------------
|
| The file system permissions to be applied on newly created log files.
|
| IMPORTANT: This MUST be an integer (no quotes) and you MUST use octal
|            integer notation (i.e. 0700, 0644, etc.)
*/
$config['log_file_permissions'] = 0644;

/*
|--------------------------------------------------------------------------
| Date Format for Logs
|--------------------------------------------------------------------------
|
| Each item that is logged has an associated date. You can use PHP date
| codes to set your own date formatting
|
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| Error Views Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| application/views/errors/ directory.  Use a full server path.
|
*/
$config['error_views_path'] = '';

/*
|--------------------------------------------------------------------------
| Cache Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| application/cache/ directory.  Use a full server path.
|
*/
$config['cache_path'] = '';

/*
|--------------------------------------------------------------------------
| Cache Include Query String
|--------------------------------------------------------------------------
|
| Whether to take the URL query string into consideration when generating
| output cache files. Valid options are:
|
|	FALSE      = Disabled
|	TRUE       = Enabled, take all query parameters into account.
|	             Please be aware that this may result in numerous cache
|	             files generated for the same page over and over again.
|	array('q') = Enabled, but only take into account the specified list
|	             of query parameters.
|
*/
$config['cache_query_string'] = FALSE;

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| If you use the Encryption class, you must set an encryption key.
| See the user guide for more info.
|
| https://codeigniter.com/user_guide/libraries/encryption.html
|
*/
$config['encryption_key'] = ENCRYPTION_KEY;

/*
|--------------------------------------------------------------------------
| Session Variables
|--------------------------------------------------------------------------
|
| 'sess_driver'
|
|	The storage driver to use: files, database, redis, memcached
|
| 'sess_cookie_name'
|
|	The session cookie name, must contain only [0-9a-z_-] characters
|
| 'sess_expiration'
|
|	The number of SECONDS you want the session to last.
|	Setting to 0 (zero) means expire when the browser is closed.
|
| 'sess_save_path'
|
|	The location to save sessions to, driver dependent.
|
|	For the 'files' driver, it's a path to a writable directory.
|	WARNING: Only absolute paths are supported!
|
|	For the 'database' driver, it's a table name.
|	Please read up the manual for the format with other session drivers.
|
|	IMPORTANT: You are REQUIRED to set a valid save path!
|
| 'sess_match_ip'
|
|	Whether to match the user's IP address when reading the session data.
|
|	WARNING: If you're using the database driver, don't forget to update
|	         your session table's PRIMARY KEY when changing this setting.
|
| 'sess_time_to_update'
|
|	How many seconds between CI regenerating the session ID.
|
| 'sess_regenerate_destroy'
|
|	Whether to destroy session data associated with the old session ID
|	when auto-regenerating the session ID. When set to FALSE, the data
|	will be later deleted by the garbage collector.
|
| Other session cookie settings are shared with the rest of the application,
| except for 'cookie_prefix' and 'cookie_httponly', which are ignored here.
|
*/
$config['sess_driver'] = 'database';
$config['sess_cookie_name'] = SESSION_COOKIE_NAME;
$config['sess_expiration'] = SESSION_PERIOD;
$config['sess_save_path'] = SESSION_TABLE;
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;

/*
|--------------------------------------------------------------------------
| Cookie Related Variables
|--------------------------------------------------------------------------
|
| 'cookie_prefix'   = Set a cookie name prefix if you need to avoid collisions
| 'cookie_domain'   = Set to .your-domain.com for site-wide cookies
| 'cookie_path'     = Typically will be a forward slash
| 'cookie_secure'   = Cookie will only be set if a secure HTTPS connection exists.
| 'cookie_httponly' = Cookie will only be accessible via HTTP(S) (no javascript)
|
| Note: These settings (with the exception of 'cookie_prefix' and
|       'cookie_httponly') will also affect sessions.
|
*/
$config['cookie_prefix']	= '';
$config['cookie_domain']	= COOKIE_DOMAIN;
$config['cookie_path']		= '/';
$config['cookie_secure']	= FALSE;
$config['cookie_httponly'] 	= FALSE;

/*
|--------------------------------------------------------------------------
| Cross Site Request Forgery
|--------------------------------------------------------------------------
| Enables a CSRF cookie token to be set. When set to TRUE, token will be
| checked on a submitted form. If you are accepting user data, it is strongly
| recommended CSRF protection be enabled.
|
| 'csrf_token_name' = The token name
| 'csrf_cookie_name' = The cookie name
| 'csrf_expire' = The number in seconds the token should expire.
| 'csrf_regenerate' = Regenerate token on every submission
| 'csrf_exclude_uris' = Array of URIs which ignore CSRF checks
*/
$config['csrf_protection'] = FALSE;
$config['csrf_token_name'] = 'csrf_test_name';
$config['csrf_cookie_name'] = 'csrf_cookie_name';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;
$config['csrf_exclude_uris'] = array();

/*
|--------------------------------------------------------------------------
| Output Compression
|--------------------------------------------------------------------------
|
| Enables Gzip output compression for faster page loads.  When enabled,
| the output class will test whether your server supports Gzip.
| Even if it does, however, not all browsers support compression
| so enable only if you are reasonably sure your visitors can handle it.
|
| Only used if zlib.output_compression is turned off in your php.ini.
| Please do not use it together with httpd-level output compression.
|
| VERY IMPORTANT:  If you are getting a blank page when compression is enabled it
| means you are prematurely outputting something to your browser. It could
| even be a line of whitespace at the end of one of your scripts.  For
| compression to work, nothing can be sent before the output buffer is called
| by the output class.  Do not 'echo' any values with compression enabled.
|
*/
$config['compress_output'] = FALSE;

/*
|--------------------------------------------------------------------------
| Master Time Reference
|--------------------------------------------------------------------------
|
| Options are 'local' or any PHP supported timezone. This preference tells
| the system whether to use your server's local time as the master 'now'
| reference, or convert it to the configured one timezone. See the 'date
| helper' page of the user guide for information regarding date handling.
|
*/
$config['time_reference'] = 'Asia/Shanghai';
date_default_timezone_set('Asia/Shanghai');

/*
|--------------------------------------------------------------------------
| Reverse Proxy IPs
|--------------------------------------------------------------------------
|
| If your server is behind a reverse proxy, you must whitelist the proxy
| IP addresses from which CodeIgniter should trust headers such as
| HTTP_X_FORWARDED_FOR and HTTP_CLIENT_IP in order to properly identify
| the visitor's IP address.
|
| You can use both an array or a comma-separated list of proxy addresses,
| as well as specifying whole subnets. Here are a few examples:
|
| Comma-separated:	'10.0.1.200,192.168.5.0/24'
| Array:		array('10.0.1.200', '192.168.5.0/24')
*/
$config['proxy_ips'] = '';

/* End of file config.php */
/* Location: ./application/config/config.php */
