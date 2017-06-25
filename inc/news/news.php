<?php
add_action( 'init', 'news_manage_add_setting_post_type' );
function news_manage_add_setting_post_type() {
    $labels = array(
        'name'               => 'NEWS設定',
        'singular_name'      => 'NEWS設定',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'news_setting' ),
        'capability_type'    => array( 'admin_setting', 'admin_settings' ),
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_icon'          => 'dashicons-admin-generic',
        'supports'           => array( 'title', )
    );

    register_post_type( 'news_setting', $args );
}

// NEWS設定に投稿されている情報を取得する関数
function news_manage_news_settings(){
	// NEWS設定に投稿されている情報を取得
    $args = array(
        'posts_per_page'   => -1,
        'post_type'        => 'news_setting',
    );
    $news_settings = get_posts($args);
    return $news_settings;
}

add_action( 'init', 'news_manage_add_post_type_news' );
function news_manage_add_post_type_news() {

	$news_settings = news_manage_news_settings();

    // NEWS設定の情報をループしてカスタム投稿タイプを作る
    foreach ($news_settings as $key => $news_setting) {

        $labels = array(
            'name'               => esc_html__( $news_setting->post_title, 'textdomain' ),
            'singular_name'      => esc_html__( $news_setting->post_title, 'textdomain' ),
            'menu_name'          => 'NEWS [ '.esc_html__( $news_setting->post_title, 'textdomain' ).' ]',
        );
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'rewrite'            => array( 'slug' => $news_setting->post_name.'_news' ),
            'capability_type'    => array( $news_setting->post_name.'_news',$news_setting->post_name.'_newss' ),
            'map_meta_cap'       => true,
            'has_archive'		 => true,
            'supports'           => array( 'title', 'editor', 'author','thumbnail' )
        );

        register_post_type( $news_setting->post_name.'_news', $args );

    } // foreach ($news_settings as $key => $news_setting) {

} // function news_manage_add_post_type_news() {