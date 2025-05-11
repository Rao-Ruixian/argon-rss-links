<?php
/*
Plugin Name: 学游渊的友链RSS聚合
Description: 友链RSS聚合 正文中输入短代码[yaya-links-rss]即可 使用WP自带的链接管理器（Links Manager）管理RSS地址
Author: 学游渊 | ryugu
Author URI: https://blog.rrxweb.top/
Version: 1.2
*/

// 缓存设置
define('YAYA_RSS_CACHE_TIME_DEFAULT', 3600); // 默认缓存时间，1小时

// 插件激活时执行
register_activation_hook(__FILE__, 'yaya_rss_activate');
function yaya_rss_activate() {
    // 创建缓存目录
    $cache_dir = WP_PLUGIN_DIR.'/yaya-links-rss/cache';
    if (!file_exists($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }
    
    // 设置默认选项
    if (get_option('yaya_rss_cache_time') === false) {
        add_option('yaya_rss_cache_time', YAYA_RSS_CACHE_TIME_DEFAULT);
    }
    
    // 清除缓存
    yaya_rss_clear_cache();
}

// 获取当前缓存时间设置
function yaya_rss_get_cache_time() {
    return get_option('yaya_rss_cache_time', YAYA_RSS_CACHE_TIME_DEFAULT);
}

// 插件卸载时执行
register_deactivation_hook(__FILE__, 'yaya_rss_deactivate');
function yaya_rss_deactivate() {
    // 清除缓存
    yaya_rss_clear_cache();
}

// 当短代码被调用时运行的函数
function add_rss_button() {
    $html = '<link rel="stylesheet" type="text/css" href="' . plugins_url('yaya-links-rss/rss.css') . '" />';
    // 添加一个按钮
    $html .= '<button id="load-rss-btn" class="btn btn-primary">点击查看友友们的最新文章</button>';
    // 返回按钮和容器
    $html .= '<div id="links-rss-container"></div>';

    // 添加事件监听器
    $html .= "<script>
    
                var rss_btn = document.getElementById('load-rss-btn');
                
                function rss_btn_click() {
                    // 禁用按钮
                    rss_btn.disabled = true;
                
                    // 显示加载提示信息
                    rss_btn.textContent = 'RSS加载中，请稍候...';
                
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '" . esc_url(admin_url('admin-ajax.php?action=load_links_rss')) . "', true); // 设置 AJAX 请求地址
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            // 请求成功时将返回的内容插入到容器中
                            document.getElementById('links-rss-container').innerHTML = xhr.responseText;
                
                            // 隐藏按钮
                            rss_btn.style.display = 'none';
                        } else {
                            // 处理请求失败的情况
                            console.error('请求失败：' + xhr.statusText);
                            // 恢复按钮状态
                            rss_btn.disabled = false;
                            rss_btn.textContent = '点击查看友友们的最新文章';
                        }
                    };
                    xhr.onerror = function() {
                        // 处理请求错误的情况
                        console.error('请求错误');
                        // 恢复按钮状态
                        rss_btn.disabled = false;
                        rss_btn.textContent = '点击查看友友们的最新文章';
                    };
                    xhr.send();
                }
    
                if (rss_btn) {
                    rss_btn.addEventListener('click', rss_btn_click);
                }
                
                rss_btn_click();    //自动加载
                
            </script>";

    return $html;
}

// 注册短代码
add_shortcode('yaya-links-rss', 'add_rss_button');

// 添加用于处理 AJAX 请求的函数
add_action('wp_ajax_load_links_rss', 'load_links_rss');
add_action('wp_ajax_nopriv_load_links_rss', 'load_links_rss');

// 获取RSS缓存的函数
function get_rss_cache($category, $limit) {
    $cache_key = 'yaya_links_rss_' . $category . '_' . $limit;
    $cached_content = get_transient($cache_key);
    
    if ($cached_content !== false) {
        return $cached_content;
    }
    
    return false;
}

// 设置RSS缓存的函数
function set_rss_cache($category, $limit, $content) {
    $cache_key = 'yaya_links_rss_' . $category . '_' . $limit;
    $cache_time = yaya_rss_get_cache_time();
    set_transient($cache_key, $content, $cache_time);
}

function load_links_rss() {
    // 获取参数
    $category = isset($_GET['category']) ? intval($_GET['category']) : 0;  // 链接分类
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;     // 显示条数
    
    // 检查缓存
    $cached_content = get_rss_cache($category, $limit);
    if ($cached_content !== false) {
        echo $cached_content;
        wp_die();
    }
    
    // 没有缓存，生成内容
    include_once WP_PLUGIN_DIR.'/yaya-links-rss/links.php';
    $BFCLinks = new BFCLinks();
    $rssItems = $BFCLinks->getRss($category, $limit);

    $html = '<div class="wp-block-argon-collapse collapse-block shadow-sm" style="border-left-color:#4fd69c"><div class="collapse-block-title" style="background-color:#4fd69c33"><span><i class="fa fa-rss"></i> </span><span class="collapse-block-title-inner">以下是友友们的最新文章~</span><i class="collapse-icon fa fa-angle-down"></i></div><div class="collapse-block-body" style="">';
    $html .= '<div class="links-rss">';
    
    foreach ($rssItems as $item) {
        $feed = $item->get_feed();
        $author = $item->get_author();
        $format = 'Y-m-d';
        $dateTimeUnix = (int) $item->get_date('U');
        $dateTimeOriginal = $item->get_date('');
        $dateZone = explode('+', $dateTimeOriginal);
        if (isset($dateZone[1])) {
            $dateZone = $dateZone[1];
            if ($dateZone != "0800") {
                $dateZone = (int) $dateZone[1];
                $dateZone = (800 - $dateZone) * 36;
                $dateTimeUnix = $dateTimeUnix + $dateZone;
            }
            if ($dateZone == "0800") {
                $dateTimeUnix = $dateTimeUnix + 28800;
            }
        }
        $dateTime = date($format, $dateTimeUnix);

        $html .= '
        <p>
            <span>'. esc_attr($dateTime) . '</span> &nbsp &nbsp 
            <a target="_blank" href="' . esc_attr($item->get_permalink()) .'" data-popover="' . wp_trim_words(sanitize_textarea_field($item->get_description()), 140, '...') . '">' . esc_attr($item->get_title()) . '</a>
            <span style="float:right;">' . esc_attr($feed->get_title()) . '</span>
        </p>';
    }

    $html .= '</div>';
    $html .= '<p style="text-align: center; margin-bottom: 0; opacity: 0.3;">友链RSS聚合插件由<a href="https://github.com/crowya/yaya-plugins-for-argon">鸦鸦</a>制作</p>';
    // 设置为中国上海时间
    date_default_timezone_set('Asia/Shanghai');
    $html .= '<p style="text-align: center; margin-bottom: 0; opacity: 0.3;">缓存更新时间：'.date('Y-m-d H:i:s').'</p>';
    $html .= '</div></div>';

    // 保存到缓存
    set_rss_cache($category, $limit, $html);
    
    // 输出 HTML 内容
    echo $html;

    // 结束响应
    wp_die();
}

// 添加清除缓存的功能
function yaya_rss_clear_cache() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_yaya_links_rss_%'");
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_timeout_yaya_links_rss_%'");
}

// 添加后台设置页面
add_action('admin_menu', 'yaya_rss_admin_menu');

// 添加设置链接
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yaya_rss_add_settings_link');
function yaya_rss_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=yaya-links-rss">设置</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// 注册设置
add_action('admin_init', 'yaya_rss_register_settings');
function yaya_rss_register_settings() {
    register_setting('yaya_rss_options', 'yaya_rss_cache_time', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => YAYA_RSS_CACHE_TIME_DEFAULT
    ));
}

function yaya_rss_admin_menu() {
    // 确保把菜单添加到"设置"下面
    add_options_page(
        '友链RSS聚合设置',  // 页面标题
        '友链RSS聚合',      // 菜单标题
        'manage_options',   // 需要的权限
        'yaya-links-rss',   // 菜单slug
        'yaya_rss_settings_page' // 回调函数
    );
}

// 添加管理页面样式
function yaya_rss_admin_styles() {
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_yaya-links-rss') {
        ?>
        <style type="text/css">
            .yaya-rss-settings-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 15px;
                margin: 15px 0;
                border-radius: 3px;
            }
            .yaya-rss-usage-box {
                background: #f8f9fa;
                border-left: 4px solid #4fd69c;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'yaya_rss_admin_styles');

function yaya_rss_settings_page() {
    // 检查用户权限
    if (!current_user_can('manage_options')) {
        wp_die('您没有足够权限访问此页面');
    }
    
    // 保存设置
    if (isset($_POST['submit'])) {
        // 验证nonce
        check_admin_referer('yaya_rss_options');
        
        // 更新缓存时间设置
        $cache_time = absint($_POST['yaya_rss_cache_time']);
        if ($cache_time < 300) { // 最低5分钟
            $cache_time = 300;
        }
        update_option('yaya_rss_cache_time', $cache_time);
        
        echo '<div class="notice notice-success is-dismissible"><p>设置已保存！</p></div>';
    }
    
    // 处理清除缓存
    if (isset($_POST['yaya_rss_clear_cache'])) {
        check_admin_referer('yaya_rss_clear_cache');
        yaya_rss_clear_cache();
        echo '<div class="notice notice-success is-dismissible"><p>RSS缓存已清除！</p></div>';
    }
    
    // 获取当前设置
    $cache_time = yaya_rss_get_cache_time();
    
    // 显示设置页面
    ?>
    <div class="wrap">
        <h1>友链RSS聚合设置</h1>
        
        <div class="yaya-rss-settings-box">
            <h2><span class="dashicons dashicons-admin-generic"></span> 缓存设置</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('yaya_rss_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="yaya_rss_cache_time">缓存时间（秒）</label></th>
                        <td>
                            <input type="number" id="yaya_rss_cache_time" name="yaya_rss_cache_time" value="<?php echo esc_attr($cache_time); ?>" min="300" step="300" class="regular-text">
                            <p class="description">设置RSS缓存的有效时间，单位为秒。建议设置为至少5分钟（300秒）。<br>
                            当前设置: <strong><?php echo $cache_time; ?></strong> 秒 (<?php echo round($cache_time/60, 1); ?> 分钟)</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="保存设置">
                </p>
            </form>
            
            <hr>
            
            <form method="post" action="">
                <?php wp_nonce_field('yaya_rss_clear_cache'); ?>
                <p>如果您更新了友链，可以点击下面的按钮清除缓存，以便立即生效</p>
                <p class="submit">
                    <input type="submit" name="yaya_rss_clear_cache" class="button button-secondary" value="清除RSS缓存">
                </p>
            </form>
        </div>
        
        <div class="yaya-rss-settings-box yaya-rss-usage-box">
            <h2><span class="dashicons dashicons-book"></span> 使用方法</h2>
            <p>在文章或页面中插入短代码 <code>[yaya-links-rss]</code> 即可显示友链RSS聚合</p>
            <p>您可以在 WordPress 后台的"链接"菜单中管理友情链接，确保在添加友链时填写了正确的RSS地址</p>
        </div>
        
        <div class="yaya-rss-settings-box">
            <h2><span class="dashicons dashicons-info"></span> 关于插件</h2>
            <p>版本: <?php echo '1.2'; ?></p>
            <p>作者: <a href="https://blog.rrxweb.top/" target="_blank">学游渊</a> | <a href="https://github.com/crowya/yaya-plugins-for-argon" target="_blank">yaya</a></p>
            <p>插件优化增加了缓存功能，提高了性能和用户体验</p>
        </div>
    </div>
    <?php
}
?>
