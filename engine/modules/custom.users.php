<?php
/*
=====================================================
 MWS Custom Users v1.3 - Mehmet Hanoğlu
-----------------------------------------------------
 http://dle.net.tr/ -  Copyright (c) 2015
-----------------------------------------------------
 Mail: mehmethanoglu@dle.net.tr
-----------------------------------------------------
 Lisans : MIT License
=====================================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

$user_conf = array(
	'sel_news_info' => "1",
	'sel_xfields'   => "1",
);

if ( $user_conf['sel_news_info'] ) {

	function user_fulllink( $id, $category, $alt_name, $date ) {
		global $config;
		if ( $config['allow_alt_url'] ) {
			if ( $config['seo_type'] == 1 OR $config['seo_type'] == 2 ) {
				if ( $category and $config['seo_type'] == 2 ) {
					$full_link = $config['http_home_url'] . get_url( $category ) . "/" . $id . "-" . $alt_name . ".html";
				} else {
					$full_link = $config['http_home_url'] . $id . "-" . $alt_name . ".html";
				}
			} else {
				$full_link = $config['http_home_url'] . date( 'Y/m/d/', $date ) . $alt_name . ".html";
			}
		} else {
			$full_link = $config['http_home_url'] . "index.php?newsid=" . $id;
		}
		return $full_link;
	}

	function user_title( $count, $title ) {
		global $config;
		if ( $count AND dle_strlen( $title, $config['charset'] ) > $count ) {
			$title = dle_substr( $title, 0, $count, $config['charset'] );
			if ( ($temp_dmax = dle_strrpos( $title, ' ', $config['charset'] )) ) $title = dle_substr( $title, 0, $temp_dmax, $config['charset'] );
		}
		return $title;
	}
}

function user_formdate( $matches = array() ) {
	global $news_date;
	return date( $matches[1], $news_date );
}

function custom_users( $matches = array() ) {
	global $db, $_TIME, $config, $lang, $user_group, $user_conf, $news_date, $member_id, $nav;

	if ( ! count( $matches ) ) return "";
	$yes_no_map = array( "yes" => "1", "no" => "0" );

	$param_str = trim( $matches[1] );
	$thisdate = strtotime( date( "Y-m-d H:i:s", $_TIME ) );
	$where = array();

	if ( preg_match( "#template=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$comm_tpl = trim( $match[1] );
	} else return "";

	if ( preg_match( "#id=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$temp_array = array();
		$where_id = array();
		$match[1] = explode( ',', trim( $match[1] ) );
		foreach ( $match[1] as $value ) {
			if ( count( explode( '-', $value ) ) == 2 ) {
				$value = explode( '-', $value );
				$where_id[] = "u.user_id >= '" . intval( $value[0] ) . "' AND u.user_id <= '" . intval( $value[1] ) . "'";
			} else $temp_array[] = intval($value);
		}
		if ( count( $temp_array ) ) {
			$where_id[] = "u.user_id IN ('" . implode( "','", $temp_array ) . "')";
		}
		if ( count( $where_id ) ) {
			$custom_id = implode( ' OR ', $where_id );
			$where[] = $custom_id;
		}
	}

	if ( preg_match( "#group=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$temp_array = array();
		$where_id = array();
		$match[1] = explode( ',', trim( $match[1] ) );
		foreach ( $match[1] as $value ) {
			if ( count( explode( '-', $value ) ) == 2 ) {
				$value = explode( '-', $value );
				$where_id[] = "u.user_group >= '" . intval( $value[0] ) . "' AND u.user_group <= '" . intval( $value[1] ) . "'";
			} else $temp_array[] = intval($value);
		}
		if ( count( $temp_array ) ) {
			$where_id[] = "u.user_group IN ('" . implode( "','", $temp_array ) . "')";
		}
		if ( count( $where_id ) ) {
			$custom_id = implode( ' OR ', $where_id );
			$where[] = $custom_id;
		}
	}
	if ( preg_match( "#online=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		if ( $match[1] == "yes" ) {
			$where[] = "u.lastdate+1200 > {$_TIME} ";
		} else {
			$where[] = "u.lastdate+1200 <= {$_TIME} ";
		}
	}
	if ( preg_match( "#news-count=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$where[] = "u.news_num > " . intval( $match[1] ) . " ";
	}
	if ( preg_match( "#comment-count=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$where[] = "u.comm_num > " . intval( $match[1] ) . " ";
	}
	if ( preg_match( "#from=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$user_from = intval( $match[1] ); $custom_all = $custom_from;
	} else {
		$user_from = 0; $custom_all = 0;
	}
	if ( preg_match( "#limit=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$user_limit = intval( $match[1] );
	} else {
		$user_limit = $config['comm_nummers'];
	}
	if ( preg_match( "#order=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_order = array ( 'news' => 'news_num', 'comment' => 'comm_num', 'group' => 'user_group', 'lastdate' => 'lastdate', 'regdate' => 'reg_date', 'nick' => 'name', 'rand' => 'RAND()' );
		if ( $allowed_order[ $match[1] ] ) $user_order = $allowed_order[ $match[1] ];
	}
	if ( ! $user_order ) $user_order = "reg_date";

	if ( preg_match( "#sort=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$allowed_sort = array ( 'asc' => 'ASC', 'desc' => 'DESC' );
		if ( $allowed_sort[ $match[1] ] ) $user_sort = $allowed_sort[ $match[1] ];
	}
	if ( ! $user_sort ) $user_order = "ASC";

	if ( preg_match( "#cache=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$user_cache = $yes_no_map[ $match[1] ];
	} else {
		$user_cache = "0";
	}

	if ( preg_match( "#not=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$not_found = $db->safesql( $match[1] );
	} else {
		$not_found = "";
	}

	if ( preg_match( "#friends=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$fids = array();
		if ( $match[1] == "current" ) {
			$friend_que = $db->query( "SELECT friend_id FROM " . PREFIX . "_users_friends WHERE user_id = '{$member_id['user_id']}' AND approve = '1'" );
			while( $row = $db->get_row( $friend_que ) ) { $fids[] = $row['friend_id']; }
		} else if ( $match[1] == "profile" ) {
			$friend_que = $db->query( "SELECT f.friend_id FROM " . PREFIX . "_users_friends f LEFT JOIN " . PREFIX . "_users u ON ( u.user_id = f.user_id ) WHERE u.name = '{$_REQUEST['user']}' AND f.approve = '1'" );
			while( $row = $db->get_row( $friend_que ) ) { $fids[] = $row['friend_id']; }
		}
		if ( count( $fids ) > 0 ) {
			$where[] = "u.user_id IN ('" . implode( "','", $fids ) . "')";
		} else {
			$where[] = "u.user_id = '0'";
		}
	} else {
		$user_friends = false;
	}

	if ( preg_match( "#xfield=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		$_temp = explode( ",", $match[1] ); $_rules = array();
		foreach ( $_temp as $_temp2 ) {
			if ( strpos( $_temp2, "this." ) !== False && isset( $member_id ) ) {
				$_temp3 = explode( ":", $_temp2 );
				$_temp4 = trim( str_replace( "this.", "", $_temp3[1] ) ); unset( $_temp3 );
				$_thisxf = xfieldsdataload( $member_id['xfields'] );
				if ( array_key_exists( $_temp4, $_thisxf ) ) {
					$_rules[] = "u.xfields LIKE '%" . $_temp4 . "|" . $_thisxf[ $_temp4 ] . "%'";
				}
			} else {
				$_rules[] = "u.xfields LIKE '%" . str_replace( ":", "|", $_temp2 ) . "%'";
			}
		}
		if ( count( $_rules ) > 0 ) {
			$where[] = "( " . implode( " AND ", $_rules ) . " )";
			$use_xfield = True;
		}
	} else {
		$use_xfield = False;
	}

	$user_yes = false;
	$user_cols = array( "email", "name", "user_id", "news_num", "comm_num", "user_group", "lastdate", "reg_date", "signature", "foto", "fullname", "land", "logged_ip" );
	if ( $user_conf['sel_xfields'] ) $user_cols[] = "xfields";
	$_WHERE = ( count( $where ) > 0 ) ? " WHERE " . implode( ' AND ', $where ) : "";

	$build_navigation = false;
	if ( isset( $_GET['cstart'] ) ) $cstart = intval( $_GET['cstart'] ); else $cstart = 0;
	if ( preg_match( "#navigation=['\"](.+?)['\"]#i", $param_str, $match ) ) {
		if ( $match[1] == "yes" AND $url_page !== false ) {
			$build_navigation = true;
			$custom_limit = $user_limit;
			//if ( $cstart > 10 ) $config['allow_cache'] = false;
			if ( $cstart ) {
				$cstart = $cstart - 1;
				$cstart = ( $cstart * $custom_limit ) + $user_from;
				$user_from = $cstart;
			}
			$count_que = $db->super_query( "SELECT COUNT(u.name) as total FROM " . PREFIX . "_users u{$_WHERE} ORDER BY {$user_order} {$user_sort}" );
			$count_all = $count_que['total'];
		} else $build_navigation = false;
	} else $build_navigation = false;

	$user_sql = "SELECT u." . implode( ", u.", $user_cols ) . " FROM " . PREFIX . "_users u{$_WHERE} ORDER BY {$user_order} {$user_sort} LIMIT {$user_from},{$user_limit}";
	$user_que = $db->query( $user_sql );

	if ( $user_cache ) {
		$user_cacheid = $param_str . $user_sql . $member_id['user_group'] . $build_navigation . $cstart . implode( "|", $user_conf );
		$cache_content = dle_cache( "news_ucustom", $user_cacheid, true );
	} else $cache_content = false;

	if ( ! $cache_content ) {

		$tpl = new dle_template();
		$tpl->dir = TEMPLATE_DIR;

		// Build navigation - start
		$url_page = &$nav['url_page'];
		$user_query = &$nav['user_query'];
		if ( $build_navigation AND $count_all ) {
			$tpl->load_template( 'navigation.tpl' );
			$no_prev = false;
			$no_next = false;
			if ( isset( $_GET['cstart'] ) ) $cstart = intval ( $_GET['cstart'] ); else $cstart = 1;
			if ( isset( $cstart ) and $cstart != "" and $cstart > 1 ) {
				$prev = $cstart - 1;
				if ( $config['allow_alt_url'] ) {
					$prev_page = ( $prev == 1 ) ? $url_page . "/" : $url_page . "/page/" . $prev . "/";
					$tpl->set_block( "'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"" . $prev_page . "\">\\1</a>" );
				} else {
					$prev_page = ( $prev == 1 ) ? $PHP_SELF . "?" . $user_query : $PHP_SELF . "?cstart=" . $prev . "&amp;" . $user_query;
					$tpl->set_block( "'\[prev-link\](.*?)\[/prev-link\]'si", "<a href=\"" . $prev_page . "\">\\1</a>" );
				}
			} else {
				$tpl->set_block( "'\[prev-link\](.*?)\[/prev-link\]'si", "<span>\\1</span>" );
				$no_prev = TRUE;
			}
			if ( $custom_limit ) {
				$pages = "";
				if ( $count_all > $custom_limit ) {
					$enpages_count = @ceil( $count_all / $custom_limit );
					if ( $enpages_count <= 10 ) {
						for( $j = 1; $j <= $enpages_count; $j++ ) {
							if ( $j != $cstart ) {
								if ( $config['allow_alt_url'] ) {
									$pages .= ( $j == 1 ) ? "<a href=\"" . $url_page . "/\">$j</a> " : "<a href=\"" . $url_page . "/page/" . $j . "/\">$j</a>";
								} else {
									$pages .= ( $j == 1 ) ? "<a href=\"$PHP_SELF?{$user_query}\">$j</a> " : "<a href=\"$PHP_SELF?$user_query&amp;cstart=$j\">$j</a>";
								}
							} else {
								$pages .= "<span>$j</span> ";
							}
						}
					} else {
						$start = 1;
						$end = 10;
						$nav_prefix = "<span class=\"nav_ext\">{$lang['nav_trennen']}</span> ";
						if ( $cstart > 0 ) {
							if ( $cstart > 6 ) {
								$start = $cstart - 4;
								$end = $start + 8;
								if ( $end >= $enpages_count ) {
									$start = $enpages_count - 9;
									$end = $enpages_count - 1;
									$nav_prefix = "";
								} else {
									$nav_prefix = "<span class=\"nav_ext\">{$lang['nav_trennen']}</span> ";
								}
							}
						}
						if ( $start >= 2 ) {
							$pages .= ( $config['allow_alt_url'] ) ? "<a href=\"" . $url_page . "/\">1</a> <span class=\"nav_ext\">{$lang['nav_trennen']}</span> " : "<a href=\"$PHP_SELF?{$user_query}\">1</a> <span class=\"nav_ext\">{$lang['nav_trennen']}</span> ";
						}
						for( $j = $start; $j <= $end; $j++ ) {
							if ( $j != $cstart ) {
								if ( $config['allow_alt_url'] ) {
									$pages .= ( $j == 1 ) ? "<a href=\"" . $url_page . "/\">$j</a> " : "<a href=\"" . $url_page . "/page/" . $j . "/\">$j</a> ";
								} else {
									$pages .= ( $j == 1 ) ? "<a href=\"$PHP_SELF?{$user_query}\">$j</a> " : "<a href=\"$PHP_SELF?$user_query&amp;cstart=$j\">$j</a> ";
								}
							} else {
								$pages .= "<span>$j</span> ";
							}
						}
						if ( $cstart != $enpages_count ) {
							$pages .= ( $config['allow_alt_url'] ) ? $nav_prefix . "<a href=\"" . $url_page . "/page/{$enpages_count}/\">{$enpages_count}</a>" : $nav_prefix . "<a href=\"$PHP_SELF?$user_query&amp;cstart={$enpages_count}\">{$enpages_count}</a>";
						} else {
							$pages .= "<span>{$enpages_count}</span> ";
						}
					}
				}
				$tpl->set( '{pages}', $pages );
			}
			if ( $custom_limit AND $custom_limit < $count_all AND $cstart < $enpages_count ) {
				$next_page = $cstart + 1;
				$next = ( $config['allow_alt_url'] ) ? $url_page . '/page/' . $next_page . '/' : $PHP_SELF . "?" . $user_query . "&amp;cstart=" . $next_page;
				$tpl->set_block( "'\[next-link\](.*?)\[/next-link\]'si", "<a href=\"" . $next . "\">\\1</a>" );
			} else {
				$tpl->set_block( "'\[next-link\](.*?)\[/next-link\]'si", "<span>\\1</span>" );
				$no_next = TRUE;
			}
			if ( !$no_prev OR !$no_next ) {
				$tpl->compile( 'navi' );
			}
			$tpl->clear();
		}
		// Build navigation - end

		$tpl->load_template( $comm_tpl . '.tpl' );

		while( $user_row = $db->get_row( $user_que ) ) {
			$user_yes = true;
			$news_row = false;

			if ( $user_conf['sel_news_info'] ) {
				$news_row = $db->super_query( "SELECT id, title, category, alt_name, date FROM " . PREFIX . "_post WHERE autor = '{$user_row['name']}' ORDER BY date DESC LIMIT 0,1" );
				if ( $news_row ) {
					if ( preg_match( "#\\{news-title limit=['\"](.+?)['\"]\\}#i", $tpl->copy_template, $matches ) ) { $count = intval( $matches[1] ); $tpl->set( $matches[0], user_title( $count, $news_row['title'] ) ); }
					else $tpl->set( '{news-title}', strip_tags( stripslashes( $news_row['title'] ) ) );
					$tpl->set( '{news-link}', user_fulllink( $news_row['post_id'], $news_row['category'], $news_row['alt_name'], $news_row['pdate'] ) );
					$tpl->set( '{news-cat}', get_categories( $news_row['category'] ) );
					$news_date = strtotime( $news_row['date'] );
					$tpl->copy_template = preg_replace_callback( "#\{news-date=(.+?)\}#i", "user_formdate", $tpl->copy_template );
					$tpl->set( '{news-date}', $news_row['date'] );
					$tpl->set( '{news-id}', $news_row['id'] );
				}
			}

			if ( ( $user_row['lastdate'] + 1200 ) > $_TIME ) {
				$tpl->set( '[online]', "" );
				$tpl->set( '[/online]', "" );
				$tpl->set_block( "'\\[offline\\](.*?)\\[/offline\\]'si", "" );
			} else {
				$tpl->set( '[offline]', "" );
				$tpl->set( '[/offline]', "" );
				$tpl->set_block( "'\\[online\\](.*?)\\[/online\\]'si", "" );
			}

			$news_date = $user_row['reg_date'];
			$tpl->copy_template = preg_replace_callback( "#\{reg-date=(.+?)\}#i", "user_formdate", $tpl->copy_template );
			$tpl->set( '{reg-date}', date( "d.m.Y H:i:s", $news_date ) );

			$news_date = $user_row['lastdate'];
			$tpl->copy_template = preg_replace_callback( "#\{last-date=(.+?)\}#i", "user_formdate", $tpl->copy_template );
			$tpl->set( '{last-date}', date( "d.m.Y H:i:s", $news_date ) );

			if ( count( explode( "@", $user_row['foto'] ) ) == 2 ) {
				$tpl->set( '{foto}', 'http://www.gravatar.com/avatar/' . md5( trim( $user_row['foto'] ) ) . '?s=' . intval( $user_group[$user_row['user_group']]['max_foto'] ) );
			} else {
				if ( $user_row['foto'] && $config['version_id'] < "10.5" ) {
					if ( ( file_exists( ROOT_DIR . "/uploads/fotos/" . $user_row['foto'] ) ) ) {
						$tpl->set( '{foto}', $config['http_home_url'] . "uploads/fotos/" . $comm_row['foto'] );
					} else {
						$tpl->set( '{foto}', "{THEME}/dleimages/noavatar.png" );
					}
				} else if ( $user_row['foto'] && $config['version_id'] >= "10.5" ) {
					$tpl->set( '{foto}', $user_row['foto'] );
				} else {
					$tpl->set( '{foto}', "{THEME}/dleimages/noavatar.png" );
				}
			}

			if ( $user_conf['sel_xfields'] ) {
				$xf = xfieldsdataload( $user_row['xfields'] );
				foreach ( $xf as $xf_key => $xf_val ) {
					$xf_key = preg_quote( $xf_key, "'" );
					$tpl->set( "{xfield-" . $xf_key . "}", $xf_val );
				}
			} else {
				$tpl->set_block( "'{xfield-(.*?)}'si", "" );
			}

			$tpl->set( "{name}", $user_row['name'] );
			$tpl->set( "{name-colored}", $user_group[ $user_row['user_group'] ]['group_prefix'] . $user_row['name'] . $user_group[ $user_row['user_group'] ]['group_suffix'] );
			$tpl->set( "{name-url}", ( $config['allow_alt_url'] ) ? $config['http_home_url'] . "user/" . urlencode( $user_row['name'] ) : $config['http_home_url'] . "index.php?subaction=userinfo&amp;user=" . urlencode( $user_row['name'] ) );
			$tpl->set( "{name-popup}", ( $config['allow_alt_url'] ) ? "ShowProfile('" . urlencode( $user_row['name'] ) . "', '" . $config['http_home_url'] . "user/" . urlencode( $user_row['name'] ) . "/', '1'); return false;" : "ShowProfile('" . urlencode( $user_row['name'] ) . "', '" . $config['http_home_url'] . "index.php?subaction=userinfo&amp;user=" . urlencode( $user_row['name'] ) . "', '0'); return false;" );
			$tpl->set( "{allnews-url}", ( $config['allow_alt_url'] ) ? $config['http_home_url'] . "user/" . urlencode( $user_row['name'] ) . "/news/" : $config['http_home_url'] . "index.php?subaction=allnews&amp;user=" . urlencode( $user_row['name'] ) );
			$tpl->set( "{allcomm-url}", $config['http_home_url'] . "index.php?do=lastcomments&amp;userid=" . urlencode( $user_row['user_id'] ) );
			$tpl->set( "{pm-url}", $config['http_home_url'] . "index.php?do=pm&amp;doaction=newpm&amp;user=" . urlencode( $user_row['user_id'] ) );
			$tpl->set( "{email-url}", $config['http_home_url'] . "index.php?do=feedback&amp;user=" . urlencode( $user_row['user_id'] ) );
			$tpl->set( "{news-num}", intval( $user_row['news_num'] ) );
			$tpl->set( "{comm-num}", intval( $user_row['comm_num'] ) );
			$tpl->set( "{email}", $user_row['email'] );
			$tpl->set( "{ip}", $user_row['logged_ip'] );
			$tpl->set( "{id}", $user_row['user_id'] );
			$tpl->set( "{land}", $user_row['land'] );
			$tpl->set( '{info}', $user_row['info'] );
			$tpl->set( '{sign}', $user_row['signature'] );
			$tpl->set( "{full-name}", $user_row['fullname'] );
			$tpl->set( "{group}", $user_group[ $user_row['user_group'] ]['group_name'] );
			$tpl->set( "{group-id}", $user_group[ $user_row['user_group'] ]['id'] );
			$tpl->set( "{group-colored}", $user_group[ $user_row['user_group'] ]['group_prefix'] . $user_group[ $user_row['user_group'] ]['group_name'] . $user_group[ $user_row['user_group'] ]['group_suffix'] );
			$tpl->set( "{group-icon}", $user_group[ $user_row['user_group'] ]['icon'] );
			$tpl->compile( "content" );

			$tpl->result['content'] = preg_replace( "#\\{xfield-(.*?)\\}#is", "", $tpl->result['content'] );
			$tpl->result['content'] = preg_replace( "#\\[user-group=" . $user_row['user_group'] . "\\](.*?)\\[/user-group\\]#is", "\\1", $tpl->result['content'] );
			$tpl->result['content'] = preg_replace( "#\\[user-group=([0-9])\\](.*?)\\[/user-group\\]#is", "", $tpl->result['content'] );
			$tpl->result['content'] = preg_replace( "#\\[news\\](.*?)\\[/news\\]#is", ( $news_row != false ) ? "\\1" : "", $tpl->result['content'] );
		}

		if ( ! $user_yes ) {
			$tpl->result['content'] = $not_found;
		}

		if ( $build_navigation ) {
			$tpl->result['content'] = $tpl->result['content'] . $tpl->result['navi'];
		}

		$tpl->result['content'] = str_replace( "{THEME}", $config['http_home_url'] . "templates/" . $config['skin'] . "/", $tpl->result['content'] );

		if ( $user_cache ) {
			create_cache( "news_ucustom", $tpl->result['content'], $user_cacheid, true );
		}
		return $tpl->result['content'];

	} else return $cache_content;

}

?>
