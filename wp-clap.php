<?php
/*
Plugin Name: WP-Clap
Plugin URI: http://blog.lolily.com/wordpress-plugin-wp-clap.html
Plugin Description: Allow your visitors to leave messages easily without typing a comment.
Version: 1.0
Author: Ariagle
Author URI: http://blog.lolily.com/
*/

// 载入语言包
load_plugin_textdomain('wp-clap', "/wp-content/plugins/wp-clap/languages/");

/**
 * 主函数
 */
function wp_clap($echo = true) {
	
	global $post;
	
	//读取设定
	$args = clp_parse_args($args);
	
	$html  = '<!-- WP-Clap -->';
	$html .= '<div id="wp_clap_' . $post->ID .'" class="wp_clap">';
	
		if ($args['title_text'] != '') {
			if ($args['title_container'] != '') {
				$html .= '<' . $args['title_container'] . ' class="wp_clap_title" >';
				$html .= $args['title_text'];
				$html .= '</' . $args['title_container'] . '>';
			} else {
				$html .= $args['title_text'];
			}
		}
	
	$html .= create_clap($args, $post->ID);
	$html .= '</div>';
	
	if ($echo) {
			echo $html;
	} else {
		return $html;
	}

}

/**
 * 显示拍手数
 * $zero		没有拍手
 * $one			一次拍手
 * $more		%次拍手
 * $mode		0 = 总拍手数， 1 = 总拍手人数， 2 = 平均拍手次数
 * $link		0 = 无超链接， 1 = 有超链接
 * $echo		1 = 打印， 0 = 返回值
 */
function get_wp_claps($zero = 'No Claps', $one = 'One Clap', $more = '% Claps', $mode = 0, $link = 1, $echo = 1, $post_id = 0) {

	if ($post_id == 0) {
		global $post;
		$post_id = $post->ID;
	}
	
	switch ($mode) {
	
		case 1:
					
			global $wpdb;	
			$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'wp_clap';";
			$clapper = $wpdb->get_var($query);
			$clapper = maybe_unserialize( $clapper );			
			$claps = count( $clapper );
			break;
		
		case 2:
		
			global $wpdb;	
			$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'wp_clap';";
			$clapper = $wpdb->get_var($query);
			$clapper = maybe_unserialize( $clapper );
					
			$clappers = count( $clapper );
			$all_claps = get_claps($post_id);
			$claps = round($all_claps / $clappers, 0);
			break;
		
		default:
		
			$claps = get_claps($post_id);
		
	}
	
	// 生成HTML
	if ($claps == 0) {
		$html = $zero;
	} elseif ($claps == 1) {
		$html = $one;
	} else {
		$html = str_replace('%', $claps, $more);
	}
	
	if ($link == 1) {
		$html = '<a href="' . get_permalink($post_id) . '#wp_clap_' . $post_id . '" title="' . __('Clap', 'wp-clap') . ' on ' . get_the_title($post_id) . '">' . $html . '</a>';
	}
	
	if ($echo == 1) {
		echo $html;
	} else {
		return $html;
	}
	
}

/**
 * Ajax 方法
 */
function clp_ajax(){

	if($_GET['action'] == 'clp_ajax') {
		
		update_clap($_GET['post_id'], $_GET['name'], $_GET['email'], $_GET['site']);
		
		echo create_clap('', $_GET['post_id']);
		die();
	}
	
}
add_action('init', 'clp_ajax');

/**
 * 生成拍手HTML
 */
function create_clap($args = '', $post_id = 0) {
	
	// 获取参数
	$args = clp_parse_args($args);
	
	// 读取对应日志的拍手数据
	global $wpdb;
	
	$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'wp_clap';";
	$claps = $wpdb->get_var($query);
	$claps = maybe_unserialize( $claps );
	
	// 生成HTML
	$html  = '<div id="wp_clap_do_' . $post_id . '" class="wp_clap_do">';
	$html .= '<a href="javascript:void(0);" onclick="ClpJS.clap(\'' . get_bloginfo('siteurl') . '/index.php\',\'' . $post_id . '\',\'' . $args['text_loading'] . '\');">';
	if ($args['text_img'] != '') {
		$html .= '<img class="wp_clap_img" alt="' . $args['text'] . '" src="' . $args['text_img'] . '" />';
	}
	if ($_GET['clapped'] == 'done') {
		$html .= $args['text_done'];
	} else {
		$html .= $args['text'];
	}
	$html .= '</a>';
	$html .= '</div>';
	
	if ($args['text_notice'] != '') {
		$html .= '<div class="wp_clap_notice">';
		$html .= $args['text_notice'];
		
		if ( strstr($html, '%clap_f%') ) {
			$html = str_replace('%clap_f%', get_wp_claps('0', '1', '%', 0, 0, 0, $post_id), $html);
		}
		if ( strstr($html, '%clap_f_s%') ) {
			$html = str_replace('%clap_f_s%', get_wp_claps('0', '1', '%', 1, 0, 0, $post_id), $html);
		}
		if ( strstr($html, '%clap_f_ave%') ) {
			$html = str_replace('%clap_f_ave%', get_wp_claps('0', '1', '%', 2, 0, 0, $post_id), $html);
		}
		
		$html .= '</div>';
	}
	
	if ($claps) {
	
		$html .= '<div class="wp_clap_clappers">';
		
		foreach( $claps as $key => $clap ) {
		
			$html .= '<span class="wp_clap_single_clapper">';		
			
			// 输出头像。若使用get_avatar函数，则无法使用title属性。
			if ($args['avatar'] == true) {		
				$html .= '<span class="wp_clap_avatar">';
				//if (function_exists('get_avatar')) {
					//$html .= get_avatar($clap['email'], $args['avatar_size'], $args['avatar_file'], $clap['name']);
				//}
				$html .= '<img alt="' . $clap['name'] . '" title="' . $clap['name'] . '" src="http://www.gravatar.com/avatar.php?gravatar_id=' . md5($clap['email']) . '&amp;size=' . $args['avatar_size'] . '&amp;default=' . urlencode($args['avatar_file']) . '" width="' . $args['avatar_size'] . '" height="' . $args['avatar_size'] . '"/>';

				$html .= '</span>';
			}
			
			if ($args['name'] == true) {	
				$html .= '<span class="wp_clap_name">';
				
				if ($clap['site'] != '') {
					$html .= '<a href="' . $clap['site'] . '">' . $clap['name'] . '</a>';
				} else {
					$html .= $clap['name'];
				}
			}
			
			if ($clap['claps'] >1) {
				if ($args['frequency'] == true) {
					$html .= '<span class="wp_clap_frequency">';
					$html .= $args['before_f'] . $clap['claps'] . $args['after_f'];
					$html .= '</span>';
				}
			}
			
			$html .= '</span>';		
			$html .= '</span>';
			
		}
		
		$html .= '</div>';
		
	} else {
		//$html .= 'N/A';
	}
	
	return $html;
}

/**
 * 添加拍手自定义域
 */
function add_claps_field($post_id) {
	
	global $post;

	if(!wp_is_post_revision($post_id)) {

		add_post_meta($post_id, 'wp_claps', 0, true);

	}
	
}
add_action('publish_post', 'add_claps_field');
add_action('publish_page', 'add_claps_field');

/**
 * 更新拍手数据
 */
function update_clap($post_id = 0) {
	
	if ($post_id > 0) {
	
		global $wpdb;
		
		$query = "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = $post_id AND meta_key = 'wp_clap';";
		$claps = $wpdb->get_var($query);
		$claps = maybe_unserialize( $claps );
		$options = clp_getoptions();
		
		// 获取用户资料
		if (is_user_logged_in()) {
			
			// 已登录用户从数据库中读取资料
			global $user_ID;
			
			$user_info = get_userdata($user_ID);
			$name = $user_info -> display_name;
			$email = $user_info -> user_email;
			$site = $user_info -> user_url;
			
		} else {
		
			// 未登录用户从Cookie中读取资料
			$name = '';
			if ( isset($_COOKIE['comment_author_'.COOKIEHASH]) )
				$name = $_COOKIE['comment_author_'.COOKIEHASH];
		
			$email = '';
			if ( isset($_COOKIE['comment_author_email_'.COOKIEHASH]) )
				$email = $_COOKIE['comment_author_email_'.COOKIEHASH];
		
			$site = '';
			if ( isset($_COOKIE['comment_author_url_'.COOKIEHASH]) )
				$site = $_COOKIE['comment_author_url_'.COOKIEHASH];
				
		}		
		
		if ($name == '') { $name = $options['uname']; }
		
		// 写入拍手人资料			
		$claps[$name] = array(
			'name'  => $name,
			'email' => $email,
			'site'  => $site,
			'date'  => date("Y-m-d H:i:s"),
			'claps' => $claps[$name]['claps'] + 1
		);
		update_post_meta($post_id, 'wp_clap', $claps);
		
		// 更新总拍手数
		$clap_n = 0;
		foreach ( $claps as $key => $value ) {
			$clap_n = $clap_n + $value['claps'];
		}
		update_post_meta($post_id, 'wp_claps', $clap_n);
		
	}
}

/**
 * 初始化参数
 * @param args			参数字符串
 */
function clp_parse_args($args) {
	
	$defaults = clp_getoptions();
	
	// 替换参数
	$args = wp_parse_args($args, $defaults);
	
	return $args;
}

function clp_getoptions() {
	$options = get_option('wp_clap');
	//$options['text'] = '';
	if (!is_array($options)) {
		$options['name']            = true;
		$options['uname']           = __('Anonymous', 'wp-clap');
		$options['avatar']          = true;
		$options['avatar_file']     = '';
		$options['avatar_size']     = 32;
		$options['frequency']       = true;
		$options['before_f']        = '(';
		$options['after_f']         = ')';
		$options['title_container'] = 'h3';
		$options['title_text']      = __('Clap', 'wp-clap');
		$options['text']            = __('Clap', 'wp-clap');
		$options['text_loading']    = __('Clapping', 'wp-clap');
		$options['text_done']       = __('Clapped', 'wp-clap');
		$options['text_img']        = plugins_url('wp-clap/images/clap_32x32.gif');
		$options['text_notice']     = '';
		$options['css']             = false;
		update_option('wp_clap', $options);
	}
	return $options;
}

/**
 * 获取拍手次数
 * 处理无拍手数自定义域的情况
 */
function get_claps($post_id) {

	$claps = get_post_meta($post_id, 'wp_claps', true);
	if ($claps =='') {
		$claps = 0;
	}
	return $claps;
	
}


/**
 * 加载后台选项
 */
 function clp_options() {
	if (function_exists('add_options_page')) {
		add_options_page(__('WP Clap Options', 'wp-clap'), 'WP-Clap', 0, 'wp-clap/options.php');
	}
}
add_action('admin_menu', 'clp_options');

function wp_clap_head() {

	$options = clp_getoptions();
	
	echo "<!-- START WP-Clap -->\n";
	
	if ($options['css'] == false) {
		echo "<link href=\"" . plugins_url('wp-clap/wp-clap-style.css') . "\" rel=\"stylesheet\" type=\"text/css\" />\n";
	}
	
	echo "<script type=\"text/javascript\" src=\"" . plugins_url('wp-clap/wp-clap-jquery.js') . "\"></script>\n";
	echo "<!-- END WP-Clap -->\n";
}
add_filter('wp_head', 'wp_clap_head');
wp_enqueue_script('jquery');

?>