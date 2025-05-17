<?php
/*
Plugin Name: 友链RSS聚合
Description: 友链RSS聚合 正文中输入短代码[yaya-links-rss]即可 使用WP自带的链接管理器（Links Manager）管理RSS地址
Author: 学游渊、crowya
Author URI: https://github.com/Rao-Ruixian/argon-rss-links
Version: 1.2
*/

// 注册插件激活和停用时的钩子
register_activation_hook(__FILE__, 'yaya_links_rss_activate');
register_deactivation_hook(__FILE__, 'yaya_links_rss_deactivate');

// 添加管理菜单
add_action('admin_menu', 'yaya_links_rss_add_admin_menu');
// 注册设置
add_action('admin_init', 'yaya_links_rss_settings_init');
// 在插件列表添加设置链接
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yaya_links_rss_add_action_links');

function yaya_links_rss_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=yaya-links-rss') . '">设置</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// 插件激活时设置定时任务
function yaya_links_rss_activate() {
    $settings = yaya_links_rss_get_settings();
    // 使用设置中的更新频率
    if (!wp_next_scheduled('yaya_links_rss_update_event')) {
        wp_schedule_event(time(), $settings['update_frequency'], 'yaya_links_rss_update_event');
    }
}

// 插件停用时清除定时任务
function yaya_links_rss_deactivate() {
    wp_clear_scheduled_hook('yaya_links_rss_update_event');
}

// 添加定时任务处理函数
add_action('yaya_links_rss_update_event', 'yaya_links_rss_update_cache');

// 定时更新缓存函数
function yaya_links_rss_update_cache() {
    // 直接调用现有的load_links_rss函数更新缓存
    load_links_rss();
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
                    if (rss_btn) {
                        rss_btn.disabled = true;
                        // 显示加载提示信息
                        rss_btn.textContent = 'RSS加载中，请稍候...';
                    }

                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', '" . esc_url(admin_url('admin-ajax.php?action=load_links_rss')) . "', true);
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 400) {
                            // 请求成功时将返回的内容插入到容器中
                            document.getElementById('links-rss-container').innerHTML = xhr.responseText;
                
                            // 隐藏按钮
                            if (rss_btn) {
                                rss_btn.style.display = 'none';
                            }
                            
                            // 检查是否启用了自动展开
                            if (settings.auto_expand && settings.auto_expand == 1) {
                                // 延迟一点执行，确保DOM完全加载
                                setTimeout(function() {
                                    // 查找所有折叠块
                                    var collapseBlocks = document.querySelectorAll('.collapse-block');
                                    // 自动展开折叠块
                                    collapseBlocks.forEach(function(block) {
                                        // 在Argon主题中，移除'collapsed'类表示展开
                                        if (block.classList.contains('collapsed')) {
                                            block.classList.remove('collapsed');
                                        }
                                        // 确保折叠块内容可见
                                        var blockBody = block.querySelector('.collapse-block-body');
                                        if (blockBody) {
                                            blockBody.style.display = 'block';
                                        }
                                    });
                                }, 100);
                            }
                        } else {
                            // 处理请求失败的情况
                            console.error('请求失败：' + xhr.statusText);
                            // 恢复按钮状态
                            if (rss_btn) {
                                rss_btn.disabled = false;
                                rss_btn.textContent = '点击查看友友们的最新文章';
                            }
                        }
                    };
                    xhr.onerror = function() {
                        // 处理请求错误的情况
                        console.error('请求错误');
                        // 恢复按钮状态
                        if (rss_btn) {
                            rss_btn.disabled = false;
                            rss_btn.textContent = '点击查看友友们的最新文章';
                        }
                    };
                    xhr.send();
                }
    
                if (rss_btn) {
                    rss_btn.addEventListener('click', rss_btn_click);
                }
                
                // 根据设置决定是否自动更新和自动展开
                var settings = " . json_encode(yaya_links_rss_get_settings()) . ";
                
                // 检查是否启用了自动加载
                if (settings.auto_load && settings.auto_load == 1) {
                    // 页面加载完成后自动触发RSS加载
                    document.addEventListener('DOMContentLoaded', function() {
                        rss_btn_click();
                    });
                }
                
                // 自动展开功能已在xhr.onload回调中实现
            </script>";

    return $html;
}

// 注册短代码
add_shortcode('yaya-links-rss', 'add_rss_button');

// 添加用于处理 AJAX 请求的函数
add_action('wp_ajax_load_links_rss', 'load_links_rss');
add_action('wp_ajax_nopriv_load_links_rss', 'load_links_rss');
add_action('wp_ajax_yaya_links_rss_clear_cache', 'yaya_links_rss_clear_cache');
add_action('wp_ajax_nopriv_yaya_links_rss_clear_cache', 'yaya_links_rss_clear_cache');

// 获取插件设置
function yaya_links_rss_get_settings() {
    // 获取设置并确保所有字段都有默认值
    $settings = get_option('yaya_links_rss_settings');
    
    // 如果设置不存在或为空，初始化默认值
    if (empty($settings)) {
        $settings = array();
    }
    
    // 合并默认值确保所有字段都存在
    return wp_parse_args($settings, array(
        'update_frequency' => 'hourly',
        'display_count' => 50,
        'auto_load' => 1,
        'auto_expand' => 1
    ));
}

// 设置数据清理回调函数
function yaya_links_rss_sanitize_settings($input) {
    // 确保复选框值被正确处理
    $input['auto_load'] = isset($input['auto_load']) ? 1 : 0;
    $input['auto_expand'] = isset($input['auto_expand']) ? 1 : 0;
    
    return $input;
}

// 添加管理菜单
function yaya_links_rss_add_admin_menu() {
    add_options_page(
        '友链RSS聚合设置', 
        '友链RSS聚合', 
        'manage_options', 
        'yaya-links-rss', 
        'yaya_links_rss_options_page'
    );
}

// 初始化设置
function yaya_links_rss_settings_init() {
    // 注册设置并指定sanitize回调函数
    register_setting(
        'yaya_links_rss', 
        'yaya_links_rss_settings',
        array(
            'sanitize_callback' => 'yaya_links_rss_sanitize_settings'
        )
    );

    // 添加基本设置部分
    add_settings_section(
        'yaya_links_rss_basic_section', 
        '基本设置', 
        'yaya_links_rss_basic_section_callback', 
        'yaya_links_rss'
    );

    // 添加更新频率字段
    add_settings_field(
        'yaya_links_rss_update_frequency', 
        '更新频率', 
        'yaya_links_rss_update_frequency_render', 
        'yaya_links_rss', 
        'yaya_links_rss_basic_section'
    );

    // 添加显示数量字段
    add_settings_field(
        'yaya_links_rss_display_count', 
        '显示数量', 
        'yaya_links_rss_display_count_render', 
        'yaya_links_rss', 
        'yaya_links_rss_basic_section'
    );

    // 添加自动更新字段
    add_settings_field(
        'yaya_links_rss_auto_load', 
        '自动更新', 
        'yaya_links_rss_auto_load_render', 
        'yaya_links_rss', 
        'yaya_links_rss_basic_section'
    );
    
    // 添加自动展开字段
    add_settings_field(
        'yaya_links_rss_auto_expand', 
        '自动展开', 
        'yaya_links_rss_auto_expand_render', 
        'yaya_links_rss', 
        'yaya_links_rss_basic_section'
    );
    
    // 添加清除缓存字段
    add_settings_field(
        'yaya_links_rss_clear_cache', 
        '清除缓存', 
        'yaya_links_rss_clear_cache_render', 
        'yaya_links_rss', 
        'yaya_links_rss_basic_section'
    );
}

// 更新频率字段渲染
function yaya_links_rss_update_frequency_render() {
    $options = yaya_links_rss_get_settings();
    ?>
    <select name='yaya_links_rss_settings[update_frequency]'>
        <option value='15min' <?php selected($options['update_frequency'], '15min'); ?>>每15分钟</option>
        <option value='30min' <?php selected($options['update_frequency'], '30min'); ?>>每30分钟</option>
        <option value='hourly' <?php selected($options['update_frequency'], 'hourly'); ?>>每小时</option>
        <option value='twicedaily' <?php selected($options['update_frequency'], 'twicedaily'); ?>>每天两次</option>
        <option value='daily' <?php selected($options['update_frequency'], 'daily'); ?>>每天</option>
    </select>
    <?php
}

// 显示数量字段渲染
function yaya_links_rss_display_count_render() {
    $options = yaya_links_rss_get_settings();
    ?>
    <input type='number' name='yaya_links_rss_settings[display_count]' value='<?php echo $options['display_count']; ?>' min='1' max='100'>
    <?php
}

// 自动更新字段渲染
function yaya_links_rss_auto_load_render() {
    $options = yaya_links_rss_get_settings();
    ?>
    <label><input type='checkbox' name='yaya_links_rss_settings[auto_load]' value='1' <?php checked($options['auto_load'], 1); ?>> 启用自动更新</label>
    <p class="description">启用后页面加载时会自动获取RSS内容，无需点击按钮</p>
    <?php
}



// 清除缓存字段渲染
function yaya_links_rss_clear_cache_render() {
    ?>
    <button id="clear-cache-btn" class="button button-secondary">清除缓存</button>
    <p class="description">点击此按钮清除RSS缓存数据</p>
    <?php
}

// 自动展开字段渲染
function yaya_links_rss_auto_expand_render() {
    $options = yaya_links_rss_get_settings();
    ?>
    <label><input type='checkbox' name='yaya_links_rss_settings[auto_expand]' value='1' <?php checked($options['auto_expand'], 1); ?>> 启用自动展开</label>
    <p class="description">启用后RSS内容加载时会自动展开所有折叠块</p>
    <?php
}

// 基本设置部分回调
function yaya_links_rss_basic_section_callback() {
    echo '<p>配置友链RSS聚合插件的基本参数</p>';
}

// 设置页面
function yaya_links_rss_options_page() {
    ?>
    <div class="wrap">
        <h1>友链RSS聚合设置</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('yaya_links_rss');
            do_settings_sections('yaya_links_rss');
            submit_button();
            ?>
        </form>
        <script>
jQuery(document).ready(function($) {
    $('#clear-cache-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).text('清除中...');
        
        $.post(ajaxurl, {
            action: 'yaya_links_rss_clear_cache'
        }, function(response) {
            if (response.success) {
                alert('缓存已清除，将自动刷新内容');
                // 触发前端重新加载内容
                if (typeof rss_btn_click === 'function') {
                    rss_btn_click();
                }
            } else {
                alert('清除缓存失败: ' + response.data);
            }
            btn.prop('disabled', false).text('清除缓存');
        });
    });
});
</script>
    </div>
    <?php
}

function load_links_rss() {
    // 确保SimplePie类已加载
    if (!class_exists('SimplePie')) {
        require_once ABSPATH . WPINC . '/class-simplepie.php';
    }
    
    $settings = yaya_links_rss_get_settings();
    // 检查是否有缓存
    $cache_key = 'yaya_links_rss_cache';
    $cached_html = get_transient($cache_key);
    
    if(false !== $cached_html) {
        echo $cached_html;
        wp_die();
    }
    
    // 生成内容的代码
    $category = 0;  // 链接分类
    $settings = yaya_links_rss_get_settings();
    $limit = $settings['display_count'];    // 使用设置中的显示数量
    include_once WP_PLUGIN_DIR.'/yaya-links-rss/links.php';
    $BFCLinks = new BFCLinks();
    $rssItems = $BFCLinks->getRss($category, $limit);
    
    $html = '<div class="wp-block-argon-collapse collapse-block shadow-sm" style="border-left-color:#4fd69c"><div class="collapse-block-title" style="background-color:#4fd69c33"><span><i class="fa fa-rss"></i> </span><span class="collapse-block-title-inner">以下是友友们的最新文章~</span><i class="collapse-icon fa fa-angle-down"></i></div><div class="collapse-block-body" style="">';
    $html .= '<div class="links-rss">';
    
    if (!empty($rssItems)) {
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

            // 获取更新时间并格式化
            $updateTimeUnix = (int) $item->get_updated_date('U');
            $updateTimeOriginal = $item->get_updated_date('');
            // 添加非空检查避免传递null给explode()
            $updateZone = $updateTimeOriginal ? explode('+', $updateTimeOriginal) : [];
            if (isset($updateZone[1])) {
                $updateZone = $updateZone[1];
                if ($updateZone != "0800") {
                    $updateZone = (int) $updateZone[1];
                    $updateZone = (800 - $updateZone) * 36;
                    $updateTimeUnix = $updateTimeUnix + $updateZone;
                }
                if ($updateZone == "0800") {
                    $updateTimeUnix = $updateTimeUnix + 28800;
                }
            }
            // 使用WordPress时区设置格式化时间
$updateTime = date_i18n($format, $updateTimeUnix);


            $html .= '
            <p>
                <span>'. esc_attr($dateTime) . '</span>
                <a target="_blank" href="' . esc_attr($item->get_permalink()) .'" data-popover="' . wp_trim_words(sanitize_textarea_field($item->get_description()), 140, '...') . '">' . esc_attr($item->get_title()) . '</a>
                <span style="float:right;">' . esc_attr($feed->get_title()) . '</span>
            </p>';
        }
    } else {
        $html .= '<p>暂时没有获取到友链的最新文章</p>';
    }

    $html .= '</div>';
    $html .= '<p style="text-align: center; margin-bottom: 0; opacity: 0.3;">Fork from yaya, modified by <a href="https://github.com/Rao-Ruixian/argon-rss-links">学游渊</a></p>';
    $html .= '<p style="text-align: center; margin-bottom: 0; opacity: 0.3;">缓存更新时间: ' . date_i18n('Y-m-d H:i:s', current_time('timestamp')) . '</p>';
    $html .= '</div></div>';

    // 设置缓存，过期时间为1小时
    set_transient($cache_key, $html, HOUR_IN_SECONDS);

    // 输出 HTML 内容
    echo $html;

    // 结束响应
    wp_die();
}

// 清除缓存函数
function yaya_links_rss_clear_cache() {
    $cache_key = 'yaya_links_rss_cache';
    
    // 检查缓存是否存在
    if (false === get_transient($cache_key)) {
        wp_send_json_error('缓存不存在或已过期');
        return;
    }
    
    // 尝试删除缓存
    if (delete_transient($cache_key)) {
        wp_send_json_success('缓存已清除');
    } else {
        // 获取更详细的错误信息
        global $wpdb;
        $error = $wpdb->last_error;
        wp_send_json_error('清除缓存失败: ' . ($error ? $error : '未知错误'));
    }
}
?>