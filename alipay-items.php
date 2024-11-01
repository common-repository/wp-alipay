<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Alipay_List_Table extends WP_List_Table {

    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'item', 
            'plural'    => 'items',
            'ajax'      => true
        ) );
        
    }
    
    function column_default($item, $column_name){
            return print_r($item[$column_name],true);
    }
    

    function column_title($item){
        
        //Build row actions
        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&item=%s">删除</a>',$_REQUEST['page'],'delete',$item['alipay_num']),
        );
        
        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $item['alipay_num'],
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'], 
            /*$2%s*/ $item['alipay_num']
        );
    }
    
    
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'alipay_num'     => '订单号',
            'alipay_title'    => '商品名',
            'alipay_price'    => '价格',
            'alipay_email'    => '支付宝',
            'alipay_qq'    => 'QQ',
            'alipay_site'    => '网站',
            'alipay_time'    => '时间',
            'alipay_status'  => '状态'
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
            'alipay_num'     => array('alipay_num',true),     //true means it's already sorted
            'alipay_price'    => array('alipay_price',false),
            'alipay_status'    => array('alipay_status',false),
            'alipay_title'    => array('alipay_title',false),
            'alipay_time'  => array('alipay_time',false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => '删除'
        );
        return $actions;
    }
    
    function process_bulk_action() {
        global $wpdb;
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action()) {
            $items = $_GET['item'];
            if($items){
                foreach($items as $i){
                    $wpdb->query("DELETE  FROM $wpdb->alipay WHERE alipay_num = '$i'");
                }
            }
        }
    }
    

    function prepare_items() {
        global $wpdb;
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;
        
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
        $data =$wpdb->get_results("SELECT alipay_num,alipay_title,alipay_price,alipay_email,alipay_qq,alipay_site,alipay_time,alipay_status FROM $wpdb->alipay",ARRAY_A);

        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'alipay_num'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'DESC'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
        $current_page = $this->get_pagenum();
        
        $total_items = count($data);
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
    
}


function alipay_render_list_page(){
    
    //Create an instance of our package class...
    $alipayListTable = new Alipay_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $alipayListTable->prepare_items();
    
    ?>
    <div class="wrap">
        
  <style>
#icon-wp-alipay {
    background: transparent url(<?php echo plugins_url( 'images/admin_icon.png', __FILE__ ); ?>) no-repeat;
    }
    .ali_icon{
        float: left;
        height: 45px;
        margin: 14px 6px 0 0;
        width: 125px;
        }
    .pages{
        display:block;
        text-align:center;
        margin-top:10px;
    }
    .pages span{
        margin-right:3px;
    }
    .wrap h2{padding-top:30px;}
</style>
    <div id="icon-wp-alipay" class="ali_icon"></div>
    <h2>订单查询</h2>
    <p><?php 
        global $wpdb;
        $total_trade   = $wpdb->get_var("SELECT COUNT(alipay_id)     FROM $wpdb->alipay");
        $total_success = $wpdb->get_var("SELECT COUNT(alipay_status) FROM $wpdb->alipay WHERE alipay_status = '交易成功'");
        $total_money   = $wpdb->get_var("SELECT SUM(alipay_price)    FROM $wpdb->alipay WHERE alipay_status = '交易成功'");

    printf(('共有<strong>%s</strong>笔交易, 其中<strong>%s</strong>笔交易完成了付款, 收入￥<strong>%s</strong>'), number_format_i18n($total_trade), number_format_i18n($total_success),$total_money); ?></p>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $alipayListTable->display() ?>
        </form>
        
    </div>
    <br />
                        <div style="text-align:center;background:#eee;margin:10px;padding:5px;">
                        <a style="padding:5px;" target="_blank" href="http://www.iztwp.com/">爱主题</a> >
                        <a style="padding:5px;" target="_blank" href="http://www.iztwp.com/wordpress-alipay.html">插件主页</a> >
                        <a style="padding:5px;" target="_blank" href="http://wordpress.org/extend/plugins/wp-alipay/">WordPress官方目录</a> >
                        <a style="padding:5px;" target="_blank" href="http://www.iztwp.com/">WordPress主题</a> >
                        <a style="padding:5px;" target="_blank" href="http://weibo.com/iztme">爱主题官方微博</a> >
                        <a style="padding:5px;" target="_blank" href="http://www.iztwp.com/wordpress-alipay.html">报告BUG</a> >
                        <a style="padding:5px;" target="_blank" href="https://me.alipay.com/iztme">捐赠我们</a>
                        </div>
    <?php
}