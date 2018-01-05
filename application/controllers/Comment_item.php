<?php
defined('BASEPATH') OR exit('此文件不可被直接访问');

/**
 * Comment_item 商品评论类
 *
 * @version 1.0.0
 * @author Kamas 'Iceberg' Lau <kamaslau@outlook.com>
 * @copyright ICBG <www.bingshankeji.com>
 */
class Comment_item extends MY_Controller
{
    /**
     * 可作为列表筛选条件的字段名；可在具体方法中根据需要删除不需要的字段并转换为字符串进行应用，下同
     */
    protected $names_to_sort = array(
        'order_id', 'user_id', 'biz_id', 'item_id', 'score', 'content', 'image_urls', 'time_create', 'time_delete', 'time_edit', 'creator_id', 'operator_id',
    );

    /**
     * 创建时必要的字段名
     */
    protected $names_create_required = array(
        'order_id', 'user_id', 'biz_id', 'item_id',
    );

    /**
     * 编辑多行特定字段时必要的字段名
     */
    protected $names_edit_bulk_required = array(
        'user_id', 'ids', 'operation', 'password',
    );

    // 商品评价信息（批量创建评价）
    protected $comment_item = array();

    public function __construct()
    {
        parent::__construct();

        // 设置主要数据库信息
        $this->table_name = 'comment_item'; // 这里……
        $this->id_name = 'comment_id'; // 这里……

        // 主要数据库信息到基础模型类
        $this->basic_model->table_name = $this->table_name;
        $this->basic_model->id_name = $this->id_name;
    }

    /**
     * 0 计数
     */
    public function count()
    {
        // 筛选条件
        $condition = NULL;
        // 遍历筛选条件
        foreach ($this->names_to_sort as $sorter):
            if (!empty($this->input->post($sorter))):
                // 对时间范围做限制
                if ($sorter === 'time_create'):
                    $condition['time_create >'] = $this->input->post($sorter);
                elseif ($sorter === 'time_create_end'):
                    $condition['time_create <'] = $this->input->post($sorter);
                else:
                    $condition[$sorter] = $this->input->post($sorter);
                endif;
            endif;
        endforeach;

        // 获取列表；默认可获取已删除项
        $count = $this->basic_model->count($condition);

        if ($count !== FALSE):
            $this->result['status'] = 200;
            $this->result['content']['count'] = $count;

        else:
            $this->result['status'] = 414;
            $this->result['content']['error']['message'] = '没有符合条件的数据';

        endif;
    } // end count

    /**
     * 1 列表/基本搜索
     */
    public function index()
    {
        // 检查必要参数是否已传入
        $required_params = array();
        foreach ($required_params as $param):
            ${$param} = $this->input->post($param);
            if (!isset(${$param})):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                exit();
            endif;
        endforeach;

        // 筛选条件
        $condition = NULL;
        // 遍历筛选条件
        foreach ($this->names_to_sort as $sorter):
            if (!empty($this->input->post($sorter))):
                // 对时间范围做限制
                if ($sorter === 'time_create'):
                    $condition['time_create >'] = $this->input->post($sorter);
                elseif ($sorter === 'time_create_end'):
                    $condition['time_create <'] = $this->input->post($sorter);
                else:
                    $condition[$sorter] = $this->input->post($sorter);
                endif;
            endif;
        endforeach;

        // 商家端若未请求特定状态的退款，则不返回部分状态的退款
        if ($this->app_type === 'biz' && empty($this->input->post('status')))
            $this->db->where_not_in($this->table_name.'.status', array('冻结',));

        // 排序条件
        $order_by = NULL;

        // 获取列表；默认可获取已删除项
        $this->load->model('comment_item_model');
        $items = $this->comment_item_model->select($condition, $order_by);
        if (!empty($items)):
            $this->result['status'] = 200;
            $this->result['content'] = $items;

        else:
            $this->result['status'] = 414;
            $this->result['content']['error']['message'] = '没有符合条件的数据';

        endif;
    } // end index

    /**
     * 2 详情
     */
    public function detail()
    {
        // 检查必要参数是否已传入
        $id = $this->input->post('id');
        if (!isset($id)):
            $this->result['status'] = 400;
            $this->result['content']['error']['message'] = '必要的请求参数未传入';
            exit();
        endif;

        // 获取特定项；默认可获取已删除项
        $this->load->model('comment_item_model');
        $item = $this->comment_item_model->select_by_id($id);
        if (!empty($item)):
            $this->result['status'] = 200;
            $this->result['content'] = $item;

        else:
            $this->result['status'] = 414;
            $this->result['content']['error']['message'] = '没有符合条件的数据';

        endif;
    } // end detail

    /**
     * 3 创建
     */
    public function create()
    {
        // 操作可能需要检查客户端及设备信息
        $type_allowed = array('client'); // 客户端类型
        $this->client_check($type_allowed);

        // 检查必要参数是否已传入
        $required_params = $this->names_create_required;
        foreach ($required_params as $param):
            ${$param} = $this->input->post($param);
            if (!isset(${$param})):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                exit();
            endif;
        endforeach;

        // 初始化并配置表单验证库
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        // 验证规则 https://www.codeigniter.com/user_guide/libraries/form_validation.html#rule-reference
        $this->form_validation->set_rules('biz_id', '相关商家ID', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('order_id', '所属订单ID', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('item_id', '相关商品ID', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('score', '描述相符', 'trim|is_natural_no_zero|greater_than[0]|less_than[6]');
        $this->form_validation->set_rules('content', '评价内容', 'trim|max_length[255]');
        $this->form_validation->set_rules('image_urls', '图片URL们', 'trim|max_length[255]');

        // 若表单提交不成功
        if ($this->form_validation->run() === FALSE):
            $this->result['status'] = 401;
            $this->result['content']['error']['message'] = validation_errors();

        else:
            // 需要创建的数据；逐一赋值需特别处理的字段
            $data_to_create = array(
                'creator_id' => $user_id,
                'score' => !empty($this->input->post('score')) ? $this->input->post('score') : 4,
                'content' => !empty($this->input->post('content')) ? $this->input->post('content') : '默认好评',
            );
            // 自动生成无需特别处理的数据
            $data_need_no_prepare = array(
                'order_id', 'user_id', 'biz_id', 'item_id', 'content', 'image_urls',
            );
            foreach ($data_need_no_prepare as $name)
                $data_to_create[$name] = $this->input->post($name);

            $result = $this->basic_model->create($data_to_create, TRUE);
            if ($result !== FALSE):
                $this->result['status'] = 200;
                $this->result['content']['id'] = $result;
                $this->result['content']['message'] = '创建成功';

            else:
                $this->result['status'] = 424;
                $this->result['content']['error']['message'] = '创建失败';

            endif;
        endif;
    } // end create

    /**
     * 7 批量创建
     *
     * 为单一订单的商家及全部相关商品创建评价
     */
    public function create_bulk()
    {
        // 操作可能需要检查客户端及设备信息
        $type_allowed = array('client'); // 客户端类型
        $this->client_check($type_allowed);

        // 检查必要参数是否已传入
        $required_params = array('order_id', 'user_id',);
        foreach ($required_params as $param):
            ${$param} = $this->input->post($param);
            if (!isset(${$param})):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                exit();
            endif;
        endforeach;

        // 获取商品评价信息
        $this->comment_item = json_decode($this->input->post('comment_item'), TRUE);
        if ($this->input->post('test_mode') === 'on') var_dump($this->comment_item);

        // 初始化并配置表单验证库
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('order_id', '所属订单ID', 'trim|required|is_natural_no_zero');
        // 商家相关评论内容
        $this->form_validation->set_rules('score_service', '服务态度', 'trim|is_natural_no_zero|greater_than[0]|less_than[6]');
        $this->form_validation->set_rules('score_deliver', '物流配送', 'trim|is_natural_no_zero|greater_than[0]|less_than[6]');

        // 若表单提交不成功
        if ($this->form_validation->run() === FALSE):
            $this->result['status'] = 401;
            $this->result['content']['error']['message'] = validation_errors();

        else:
            // 获取订单信息，必须是待评价，所属用户为当前用户的订单
            $this->switch_model('order', 'order_id');
            $data_to_search = array(
                'order_id' => $order_id,
                'user_id' => $user_id,
                'status' => '待评价',
            );
            $order = $this->basic_model->match($data_to_search);

            if ( empty($order) ):
                $this->result['status'] = 444;
                $this->result['content']['error']['message'] = '该订单已评价过，或订单号与用户ID不匹配。';

            else:
                // 为相关商家创建商家评论
                // 获取商家信息
                $this->switch_model('biz', 'biz_id');
                $this->db->select('biz_id, brief_name');
                $biz = $this->basic_model->find('biz_id', $order['biz_id']);
                // 创建商家评论
                $data_to_create = array(
                    'creator_id' => $user_id,

                    'biz_id' => $biz['biz_id'],
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'score_service' => !empty($this->input->post('score_service')) ? $this->input->post('score_service') : 4,
                    'score_deliver' => !empty($this->input->post('score_deliver')) ? $this->input->post('score_deliver') : 4,
                );
                $this->create_comment_biz($data_to_create);

                // 为所有未退款的相关订单商品创建商品评论
                // 获取订单商品信息
                $this->switch_model('order_items', 'record_id');
                $this->db->select('item_id, name');
                $conditions = array(
                    'order_id' => $order['order_id'],
                );
                $order_items = $this->basic_model->select($conditions);

                // 为每个商品创建评价
                // 各商品评价通用值
                $this->switch_model('comment_item', 'comment_id');
                $data_to_create = array(
                    'creator_id' => $user_id,

                    'biz_id' => $biz['biz_id'],
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                );
                foreach ($order_items as $item):
                    $item_score = $this->comment_item[$item['item_id']]['score'];
                    $item_content = $this->comment_item[$item['item_id']]['content'];
                    $item_image_urls = $this->comment_item[$item['item_id']]['image_urls'];

                    $comment_item = array(
                        'item_id' => $item['item_id'],
                        'score' => !empty($item_score) ? $item_score : 4,
                        'content' => !empty($item_content) ? $item_content : '满意，好评！',
                        'image_urls' => !empty($item_image_urls) ? $item_image_urls : NULL,
                    );
                    $this->create_comment_item( array_merge($data_to_create, $comment_item) );
                endforeach;

                // 若评论创建成功，标记相应订单为已评价状态
                if ($this->result['status'] === 200):

                    $this->switch_model('order', 'order_id');
                    $data_to_edit = array('status' => '已完成');
                    $result = $this->basic_model->edit($order_id, $data_to_edit);
                    if ($result !== FALSE):
                        $this->result['status'] = 200;
                        $this->result['content']['id'] = $order_id;
                        $this->result['content']['message'] = trim($this->result['content']['message'], '、').'；订单状态已更新。';

                    else:
                        $this->result['status'] = 434;
                        $this->result['content']['error']['message'] = '订单评价失败';

                    endif;
                endif;

            endif;

        endif;
    } // end create_bulk

    /**
     * 创建单条商家评论
     *
     * @param $data_to_create 待创建的评价内容
     */
    protected function create_comment_biz($data_to_create)
    {
        $this->switch_model('comment_biz', 'comment_id');
        $result = $this->basic_model->create($data_to_create, TRUE);
        if ($result !== FALSE):
            $this->result['status'] = 200;
            $this->result['content']['comment_biz_id'] = $result;
            $this->result['content']['message'] = '商家评论创建成功；';

        else:
            $this->result['status'] = 424;
            $this->result['content']['error']['message'] = '商家评论创建失败';
            exit();

        endif;
    } // end create_comment_biz

    /**
     * 创建单条商品评价
     *
     * @param $data_to_create 待创建的评价内容
     */
    protected function create_comment_item($data_to_create)
    {
        $result = $this->basic_model->create($data_to_create, TRUE);
        if ($result !== FALSE):
            $this->result['status'] = 200;
            $this->result['content']['comment_item_ids'][] = $result;
            $this->result['content']['message'] .= '商品ID['.$data_to_create['item_id'].']评论创建成功、';

        else:
            $this->result['status'] = 424;
            $this->result['content']['error']['message'] .= '部分商品评论创建失败';
            exit();

        endif;
    } // end create_comment_item

    /**
     * 6 编辑多行数据特定字段
     *
     * 修改多行数据的单一字段值
     */
    public function edit_bulk()
    {
        // 操作可能需要检查客户端及设备信息
        $type_allowed = array('admin', 'client'); // 客户端类型
        $this->client_check($type_allowed);

        // 管理类客户端操作可能需要检查操作权限
        //$role_allowed = array('管理员', '经理'); // 角色要求
        //$min_level = 10; // 级别要求
        //$this->permission_check($role_allowed, $min_level);

        // 检查必要参数是否已传入
        $required_params = $this->names_edit_bulk_required;
        foreach ($required_params as $param):
            ${$param} = $this->input->post($param);
            if (!isset(${$param})):
                $this->result['status'] = 400;
                $this->result['content']['error']['message'] = '必要的请求参数未全部传入';
                exit();
            endif;
        endforeach;

        // 初始化并配置表单验证库
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('', '');
        $this->form_validation->set_rules('ids', '待操作数据ID们', 'trim|required|regex_match[/^(\d|\d,?)+$/]'); // 仅允许非零整数和半角逗号
        $this->form_validation->set_rules('operation', '待执行操作', 'trim|required|in_list[delete,restore]');
        $this->form_validation->set_rules('user_id', '操作者ID', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('password', '密码', 'trim|required|min_length[6]|max_length[20]');

        // 验证表单值格式
        if ($this->form_validation->run() === FALSE):
            $this->result['status'] = 401;
            $this->result['content']['error']['message'] = validation_errors();
            exit();

        elseif ($this->operator_check() !== TRUE):
            $this->result['status'] = 453;
            $this->result['content']['error']['message'] = '与该ID及类型对应的操作者不存在，或操作密码错误';
            exit();

        else:
            // 需要编辑的数据；逐一赋值需特别处理的字段
            $data_to_edit['operator_id'] = $user_id;

            // 根据待执行的操作赋值待编辑数据
            switch ($operation):
                case 'delete':
                    $data_to_edit['time_delete'] = date('Y-m-d H:i:s');
                    break;
                case 'restore':
                    $data_to_edit['time_delete'] = NULL;
                    break;
            endswitch;

            // 依次操作数据并输出操作结果
            // 将待操作行ID们的CSV格式字符串，转换为待操作行的ID数组
            $ids = explode(',', $ids);

            // 默认批量处理全部成功，若有任一处理失败则将处理失败行进行记录
            $this->result['status'] = 200;
            foreach ($ids as $id):
                $result = $this->basic_model->edit($id, $data_to_edit);
                if ($result === FALSE):
                    $this->result['status'] = 434;
                    $this->result['content']['row_failed'][] = $id;
                endif;

            endforeach;

            // 添加全部操作成功后的提示
            if ($this->result['status'] = 200)
                $this->result['content']['message'] = '全部操作成功';

        endif;
    } // end edit_bulk


} // end class Comment_item

/* End of file Comment_item.php */
/* Location: ./application/controllers/Comment_item.php */
