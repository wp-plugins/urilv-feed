<?php
/*
Plugin Name: URI.LV Feed
Plugin URI: http://uri.lv/wordpress
Description: Redirects all feeds to an URI.LV feed and enables realtime feed updates.
Author: Maxime VALETTE
Author URI: http://maxime.sh
Version: 1.2.1
*/

define('URILV_TEXTDOMAIN', 'urilv');

if (function_exists('load_plugin_textdomain')) {
	load_plugin_textdomain(URILV_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages' );
}

add_action('admin_menu', 'urilv_config_page');

function urilv_config_page() {

	if (function_exists('add_submenu_page')) {

        add_submenu_page('options-general.php',
            __('URI.LV', URILV_TEXTDOMAIN),
            __('URI.LV', URILV_TEXTDOMAIN),
            'manage_options', __FILE__, 'urilv_conf');

    }

}

function urilv_urlcompliant($nompage, $lowercase=true) {

    $a = array('À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','Ð','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','','','J','j','K','k','L','l','L','l','L','l','','','L','l','N','n','N','n','N','n','','O','o','O','o','O','o','Œ','œ','R','r','R','r','R','r','S','s','S','s','S','s','Š','š','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Ÿ','Z','z','Z','z','Ž','ž','','ƒ','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','','','','','','','€','@','Š','¡');

    $b = array('A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o','e','a','s','i');

    $string = preg_replace(array('/[^a-zA-Z0-9 -]/','/[ -]+/','/^-|-$/'),array('','-',''),str_replace($a,$b,$nompage));

    if ($lowercase) {
        $string = strtolower($string);
    }

    return $string;

}

function urilv_locale() {

    $langs = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

    $locale = 'en';

    foreach ($langs as $lang) {

        if (preg_match('/fr/', $lang)) {

            $locale = 'fr';
            break;

        }

    }

    return $locale;

}

function urilv_api_call($url, $params = array(), $type='GET') {

    $options = get_option('urilv');
    $json = array();

    $url .= '.json';

    $params['key'] = '50d45a6bef51d';
    $params['token'] = $options['urilv_token'];

    $qs = http_build_query($params, '', '&');

    if ($type == 'GET') {

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, 'http://api.uri.lv/'.$url.'?'.$qs);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($data);

    } elseif ($type == 'POST') {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.uri.lv/'.$url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'uri.lv/1.0 (http://uri.lv)');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);

        $data = curl_exec($ch);
        $json = json_decode($data);

        curl_close($ch);

    }

    return $json;

}

function urilv_conf() {

	$options = get_option('urilv');

    if (!isset($options['urilv_token'])) $options['urilv_token'] = null;
    if (!isset($options['urilv_feed_id'])) $options['urilv_feed_id'] = null;
    if (!isset($options['urilv_feed_url'])) $options['urilv_feed_url'] = null;
    if (!isset($options['urilv_comment_id'])) $options['urilv_comment_id'] = null;
    if (!isset($options['urilv_comment_url'])) $options['urilv_comment_url'] = null;
    if (!isset($options['urilv_no_redirect'])) $options['urilv_no_redirect'] = 0;
    if (!isset($options['urilv_no_cats'])) $options['urilv_no_cats'] = 0;
    if (!isset($options['urilv_no_search'])) $options['urilv_no_search'] = 0;
    if (!isset($options['urilv_no_author'])) $options['urilv_no_author'] = 0;
    if (!isset($options['urilv_no_ping'])) $options['urilv_no_ping'] = 0;

	$updated = false;

    if ($_GET['token']) {

        $options['urilv_token'] = ($_GET['token'] == 'reset') ? null : $_GET['token'];

        if ($_GET['token'] == 'reset') {

            $options['urilv_token'] = null;
            $options['urilv_feed_id'] = null;
            $options['urilv_feed_url'] = null;
            $options['urilv_comment_id'] = null;
            $options['urilv_comment_url'] = null;
            $options['urilv_no_redirect'] = 0;
            $options['urilv_no_cats'] = 0;
            $options['urilv_no_search'] = 0;
            $options['urilv_no_author'] = 0;
            $options['urilv_no_ping'] = 0;

        }

        update_option('urilv', $options);

        $updated = true;

    }

	if (isset($_POST['submit'])) {

		check_admin_referer('urilv', 'urilv-admin');

		if (isset($_POST['urilv_feed_url'])) {
			$urilv_url = $_POST['urilv_feed_url'];
			$urilv_id = urilv_get_feed_name($urilv_url);
		} else {
            $urilv_url = null;
            $urilv_id = null;
		}

		if (isset($_POST['urilv_comment_url'])) {
			$urilv_comment_url = $_POST['urilv_comment_url'];
            $urilv_comment_id = urilv_get_feed_name($urilv_comment_url);
		} else {
            $urilv_comment_url = null;
            $urilv_comment_id = null;
		}

		if (isset($_POST['urilv_append_cats'])) {
            $urilv_append_cats = $_POST['urilv_append_cats'];
		} else {
            $urilv_append_cats = 0;
		}

        if (isset($_POST['urilv_no_redirect'])) {
            $urilv_no_redirect = $_POST['urilv_no_redirect'];
        } else {
            $urilv_no_redirect = 0;
        }

		if (isset($_POST['urilv_no_cats'])) {
            $urilv_no_cats = $_POST['urilv_no_cats'];
		} else {
            $urilv_no_cats = 0;
		}

		if (isset($_POST['urilv_no_search'])) {
            $urilv_no_search = $_POST['urilv_no_search'];
		} else {
            $urilv_no_search = 0;
		}

		if (isset($_POST['urilv_no_author'])) {
            $urilv_no_author = $_POST['urilv_no_author'];
		} else {
            $urilv_no_author = 0;
		}

		$options['urilv_feed_url'] = $urilv_url;
        $options['urilv_feed_id'] = $urilv_id;
		$options['urilv_comment_url'] = $urilv_comment_url;
        $options['urilv_comment_id'] = $urilv_comment_id;
		$options['urilv_append_cats'] = $urilv_append_cats;
        $options['urilv_no_redirect'] = $urilv_no_redirect;
		$options['urilv_no_cats'] = $urilv_no_cats;
		$options['urilv_no_search'] = $urilv_no_search;
		$options['urilv_no_author'] = $urilv_no_author;

		update_option('urilv', $options);

		$updated = true;

	} elseif (isset($_POST['create'])) {

        $json = urilv_api_call('feeds/create', array('url' => $_POST['urilv_url'], 'alias' => $_POST['urilv_alias'], 'locale' => urilv_locale()), 'POST');

        if (is_array($json->errors) && count($json->errors)) {

            echo '<div id="message" class="error"><p>';
            _e('There was something wrong with the feed creation:', URILV_TEXTDOMAIN);
            echo ' '.$json->errors[0];
            echo "</p></div>";

        } else {

            $options['urilv_feed_id'] = $_POST['urilv_alias'];
            $options['urilv_feed_url'] = 'http://feeds.uri.lv/'.$_POST['urilv_alias'];

            update_option('urilv', $options);

            $updated = true;

        }

    }

    echo '<div class="wrap">';

    if ($updated) {

        echo '<div id="message" class="updated fade"><p>';
        _e('Configuration updated.', URILV_TEXTDOMAIN);
        echo '</p></div>';

    }

    if ($options['urilv_token']) {

        $json = urilv_api_call('account');

        if (is_array($json->errors) && count($json->errors)) {

            echo '<div id="message" class="error"><p>';
            _e('There was something wrong with your URI.LV authentication. Please retry.', URILV_TEXTDOMAIN);
            echo "</p></div>";

            $options['urilv_token'] = null;
            $options['urilv_feed_url'] = null;
            $options['urilv_feed_id'] = null;
            $options['urilv_comment_url'] = null;
            $options['urilv_comment_id'] = null;

            update_option('urilv', $options);

        }

    }

    echo '<div id="form-conf"';

    if ((is_array($json->feeds) && count($json->feeds) == 0)) {
        echo ' style="display: none;"';
    }

    echo '>';

    echo '<h2>'.__('URI.LV Configuration', URILV_TEXTDOMAIN).'</h2>';

    echo '<div style="float: right; width: 350px">';

    echo '<h3>'.__('How does this work?', URILV_TEXTDOMAIN).'</h3>';
    echo '<p>'.__('This plugin automatically redirects all or parts of your existing feeds to URI.LV.', URILV_TEXTDOMAIN).'</p>';
    echo '<p>'.__('You just have to connect to URI.LV on this page. You will now be able to create and select the feeds you want to redirect to. You may optionally redirect your comments feed using the same procedure.', URILV_TEXTDOMAIN).'</p>';
    echo '<p>'.__('Once you enter URLs your feeds will be redirected automatically and you do not need to take any further action.', URILV_TEXTDOMAIN).'</p>';
    echo '<p>'.__('Additionally, when you publish a new article on your blog, URI.LV will be pinged by the plugin and your feed will be updated in realtime.', URILV_TEXTDOMAIN).'</p>';

    echo '</div>';

    if (empty($options['urilv_token'])) {

        echo '<p><a href="http://api.uri.lv/login.json?key=50d45a6bef51d&callback='.admin_url('options-general.php?page=urilv-feed/urilv-feed.php').'">'.__('Connect to URI.LV', URILV_TEXTDOMAIN).'</a></p>';

    } else {

        echo '<p>'.__('You are authenticated on URI.LV with the username:', URILV_TEXTDOMAIN).' '.$json->login.'</p>';
        echo '<p><a href="'.admin_url('options-general.php?page=urilv-feed/urilv-feed.php').'&token=reset">'.__('Disconnect from URI.LV', URILV_TEXTDOMAIN).'</a></p>';

        echo '<form action="'.admin_url('options-general.php?page=urilv-feed/urilv-feed.php').'" method="post" id="urilv-conf">';

        echo '<h3><label for="urilv_feed_url">'.__('Redirect my feeds here:', URILV_TEXTDOMAIN).'</label></h3>';
        echo '<p><select id="urilv_feed_url" name="urilv_feed_url" style="width: 400px;" />';

        echo '<option value=""';
        if (empty($options['urilv_token'])) echo ' SELECTED';
        echo '>'.__('None', URILV_TEXTDOMAIN).'</option>';

        if (is_array($json->feeds)) {

            foreach ($json->feeds as $feed) {

                echo '<option value="'.$feed->url.'"';
                if ($options['urilv_feed_id'] == $feed->name) echo ' SELECTED';
                echo '>'.$feed->url.'</option>';

            }

        }

        echo '</select></p>';

        echo '<p><a href="javascript:;" onclick="document.getElementById(\'form-create\').style.display=\'block\';document.getElementById(\'form-conf\').style.display=\'none\';">'.__('Create a new feed on URI.LV', URILV_TEXTDOMAIN).' &raquo;</a></p>';

        echo '<h3><label for="urilv_comment_url">'.__('Redirect my comments feed here:', URILV_TEXTDOMAIN).'</label></h3>';
        echo '<p><select id="urilv_comment_url" name="urilv_comment_url" style="width: 400px;" />';

        echo '<option value=""';
        if (empty($options['urilv_token'])) echo ' SELECTED';
        echo '>'.__('None', URILV_TEXTDOMAIN).'</option>';

        if (is_array($json->feeds)) {

            foreach ($json->feeds as $feed) {

                echo '<option value="'.$feed->url.'"';
                if ($options['urilv_comment_id'] == $feed->name) echo ' SELECTED';
                echo '>'.$feed->url.'</option>';

            }

        }

        echo '</select></p>';

        echo '<p><a href="javascript:;" onclick="document.getElementById(\'form-create\').style.display=\'block\';document.getElementById(\'form-conf\').style.display=\'none\';">'.__('Create a new feed on URI.LV', URILV_TEXTDOMAIN).' &raquo;</a></p>';

        echo '<h3>'.__('Advanced Options', URILV_TEXTDOMAIN).'</h3>';

        echo '<p><input id="urilv_no_cats" name="urilv_no_cats" type="checkbox" value="1"';
        if ($options['urilv_no_cats'] == 1) echo ' checked';
        echo '/> <label for="urilv_no_cats">'.__('Do not redirect category or tag feeds.', URILV_TEXTDOMAIN).'</label></p>';

        echo '<p><input id="urilv_append_cats" name="urilv_append_cats" type="checkbox" value="1"';
        if ($options['urilv_append_cats'] == 1) echo ' checked';
        echo '/> <label for="urilv_append_cats">'.__('Append category/tag to URL for category/tag feeds.', URILV_TEXTDOMAIN).' (<i>http://feeds.uri.lv/MyFeed<b>/category</b></i>)</label></p>';

        echo '<p><input id="urilv_no_search" name="urilv_no_search" type="checkbox" value="1"';
        if ($options['urilv_no_search'] == 1) echo ' checked';
        echo '/> <label for="urilv_no_search">'.__('Do not redirect search result feeds.', URILV_TEXTDOMAIN).'</label></p>';

        echo '<p><input id="urilv_no_author" name="urilv_no_author" type="checkbox" value="1"';
        if ($options['urilv_no_author'] == 1) echo ' checked';
        echo '/> <label for="urilv_no_author">'.__('Do not redirect author feeds.', URILV_TEXTDOMAIN).'</label></p>';

        echo '<p><input id="urilv_no_redirect" name="urilv_no_redirect" type="checkbox" value="1"';
        if ($options['urilv_no_redirect'] == 1) echo ' checked';
        echo '/> <label for="urilv_no_redirect">'.__('Do not redirect ANY feeds (useful if you just want the plugin for realtime updates).', URILV_TEXTDOMAIN).'</label></p>';

        echo '<p><input id="urilv_no_ping" name="urilv_no_ping" type="checkbox" value="1"';
        if ($options['urilv_no_ping'] == 1) echo ' checked';
        echo '/> <label for="urilv_no_ping">'.__('Do not ping URI.LV when a new article is published.', URILV_TEXTDOMAIN).'</label></p>';

        echo '<p class="submit" style="text-align: left">';
        wp_nonce_field('urilv', 'urilv-admin');
        echo '<input type="submit" name="submit" value="'.__('Save', URILV_TEXTDOMAIN).' &raquo;" /></p></form>';

        echo '</div>';

        echo '<div id="form-create"';

        if ((is_array($json->feeds) && count($json->feeds) > 0) || !is_array($json->feeds)) {
            echo ' style="display: none;"';
        }

        echo '>';

        echo '<h2>'.__('Create a new URI.LV feed', URILV_TEXTDOMAIN).'</h2>';

        echo '<p>'.__('Fill the form below to create your feed on URI.LV.', URILV_TEXTDOMAIN).'</p>';

        echo '<form action="'.admin_url('options-general.php?page=urilv-feed/urilv-feed.php').'" method="post" id="urilv-create">';

        echo '<h3><label for="urilv_url">'.__('Original feed URL:', URILV_TEXTDOMAIN).'</label></h3>';
        echo '<p><input type="text" id="urilv_url" name="urilv_url" value="'.get_bloginfo('rss2_url').'" style="width: 400px;" /></p>';

        echo '<h3><label for="urilv_alias">'.__('Alias name for the feed:', URILV_TEXTDOMAIN).'</label></h3>';
        echo '<p>http://feeds.uri.lv/ <input type="text" id="urilv_alias" name="urilv_alias" value="'.urilv_urlcompliant(get_bloginfo('name')).'" style="width: 150px;" /></p>';

        echo '<p class="submit" style="text-align: left">';
        wp_nonce_field('urilv', 'urilv-admin');
        echo '<input type="submit" name="create" value="'.__('Create', URILV_TEXTDOMAIN).' &raquo;" /></p></form>';

        echo '</div>';

        echo '</div>';

    }

}

// Feed redirection
function urilv_redirect() {

	global $feed, $withcomments, $wp, $wpdb, $wp_version, $wp_db_version;

	// Do nothing if not a feed
	if (!is_feed()) return;
	
	// Do nothing if uri.lv is the user-agent
	if (preg_match('/uri\.lv/i', $_SERVER['HTTP_USER_AGENT'])) return;

    // Do nothing if feedvalidator is the user-agent
    if (preg_match('/feedvalidator/i', $_SERVER['HTTP_USER_AGENT'])) return;
	
	// Avoid redirecting Googlebot to avoid sitemap feeds issues
	if (preg_match('/googlebot/i', $_SERVER['HTTP_USER_AGENT'])) return;
	
	// Do nothing if not configured
	$options = get_option('urilv');
    if (!isset($options['urilv_token'])) $options['urilv_token'] = null;
	if (!isset($options['urilv_feed_url'])) $options['urilv_feed_url'] = null;
    if (!isset($options['urilv_feed_id'])) $options['urilv_feed_id'] = null;
    if (!isset($options['urilv_comment_url'])) $options['urilv_comment_url'] = null;
    if (!isset($options['urilv_comment_id'])) $options['urilv_comment_id'] = null;
    if (!isset($options['urilv_no_redirect'])) $options['urilv_no_redirect'] = 0;
    if (!isset($options['urilv_no_cats'])) $options['urilv_no_cats'] = 0;
    if (!isset($options['urilv_no_search'])) $options['urilv_no_search'] = 0;
    if (!isset($options['urilv_no_author'])) $options['urilv_no_author'] = 0;
    if (!isset($options['urilv_no_ping'])) $options['urilv_no_ping'] = 0;

    $feed_url = null;
    $comment_url = null;

	if (!empty($options['urilv_feed_url'])) {
        $feed_url = $options['urilv_feed_url'];
    }

    if (!empty($options['urilv_comment_url'])) {
        $comment_url = $options['urilv_comment_url'];
    }

    if ($options['urilv_no_redirect'] == 1 || ($feed_url == null && $comment_url == null)) return;

	// Get category

    $cat = null;

	if ($wp->query_vars['category_name'] != null) {
		$cat = $wp->query_vars['category_name'];
	}

	if ($wp->query_vars['cat'] != null) {
		if ($wp_db_version >= 6124) {
			// 6124 = WP 2.3
			$cat = $wpdb->get_var("SELECT slug FROM $wpdb->terms WHERE term_id = '".$wp->query_vars['cat']."' LIMIT 1");
		} else {
			$cat = $wpdb->get_var("SELECT category_nicename FROM $wpdb->categories WHERE cat_ID = '".$wp->query_vars['cat']."' LIMIT 1");
		}
	}

	if ($options['urilv_append_cats'] == 1 && $cat) {
		$feed_url .= '/'.$cat;
	}
	
	// Get tag
	$tag = null;
	if ($wp->query_vars['tag'] != null) {
		$tag = $wp->query_vars['tag'];
	}
	if ($options['urilv_append_cats'] == 1 && $tag) {
		$feed_url .= '/'.$tag;
	}

	// Get search terms
	$search = null;
	if ($wp->query_vars['s'] != null) {
		$search = $wp->query_vars['s'];
	}

	// Get author name
	$author_name = null;
	if ($wp->query_vars['author_name'] != null) {
		$author_name = $wp->query_vars['author_name'];
	}

	// Redirect comment feed
	if ($feed == 'comments-rss2' || is_single() || $withcomments) {
		if ($comment_url != null) {
			header("Location: ".$comment_url);
			exit;
		}
	} else {
		// Other feeds
		switch($feed) {
			case 'feed':
			case 'rdf':
			case 'rss':
			case 'rss2':
			case 'atom':
				if (($cat || $tag) && $options['urilv_no_cats'] == 1) {
					// If this is a category/tag feed and redirect is disabled, do nothing
				} else if ($search && $options['urilv_no_search'] == 1) {
					// If this is a search result feed and redirect is disabled, do nothing
				} else if ($author_name && $options['urilv_no_author'] == 1) {
					// If this is an author feed and redirect is disabled, do nothing
				} else {
					if ($feed_url != null) {
						// Redirect the feed
						header("Location: ".$feed_url);
                        exit;
					}
				}
		}
	}

}

// Handle feed redirections
add_action('template_redirect', 'urilv_redirect');

// Ping URI.LV when there is a new post
function urilv_publish_post() {

    $options = get_option('urilv');

    if ($options['urilv_no_ping'] == 0) {

        urilv_api_call('feeds/ping', array('feed' => $options['urilv_feed_id']));

    }

}

// Action when a post is published
add_action('publish_post', 'urilv_publish_post');

function urilv_get_feed_name($url) {

    $infos = parse_url($url);

    return substr($infos['path'], 1);

}

function urilv_admin_notice() {

    $options = get_option('urilv');

    if (current_user_can('manage_options') &&
        ((!empty($options['urilv_feed_url']) && !preg_match('/^http/', $options['urilv_feed_url'])) ||
        (!empty($options['urilv_comment_url']) && !preg_match('/^http/', $options['urilv_comment_url'])))) {

        echo '<div class="error"><p>'.__('Warning: The options have changed. You have to update your URI.LV settings.', URILV_TEXTDOMAIN).' <a href="'.admin_url('options-general.php?page=urilv-feed/urilv-feed.php').'">'.__('Update settings', URILV_TEXTDOMAIN).' &rarr;</a></p></div>';

    }

}

// Admin notice
add_action('admin_notices', 'urilv_admin_notice');