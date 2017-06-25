<?php

/*-------------------------------------------*/
/*  権限管理用投稿タイプ追加
/*-------------------------------------------*/
/*  権限グループ編集画面 _ 編集ユーザーグループ設定用のメタボックスを追加
/*-------------------------------------------*/
/*	権限グループ編集画面 _ 公開権限チェックボックスの作成
/*-------------------------------------------*/
/*  権限グループ編集画面 _ 投稿タイプ別のチェックボックスの生成
/*-------------------------------------------*/
/*  権限グループ編集画面 _ 入力された値の保存
/*-------------------------------------------*/
/*  権限書き換え _ 管理者権限 _ NEWS設定とユーザー権限グループ設定の編集権限を付与する
/*-------------------------------------------*/
/*  権限書き換え _ 管理者権限 _ NEWS投稿タイプの権限を付与する
/*-------------------------------------------*/
/*  権限書き換え _ 更新のあったユーザー権限投稿タイプに該当するroleをアップデートする
/*-------------------------------------------*/
/*  権限書き換え _ 実際にroleにcapを追加する
/*-------------------------------------------*/
/*  権限書き換え _ 権限削除用
/*-------------------------------------------*/
/*  権限書き換え _ 無くなったユーザー権限グループのroleを削除する
/*-------------------------------------------*/
/*  管理画面 _ 権限の低いユーザーからメニュー項目の削除
/*-------------------------------------------*/

/*-------------------------------------------*/
/*  権限管理用投稿タイプ追加
/*-------------------------------------------*/
add_action( 'init', 'ug_manage_add_post_type' );
function ug_manage_add_post_type() {
	// $user = wp_get_current_user();
	// print '<pre style="text-align:left">';print_r($user);print '</pre>';
    $labels = array(
        'name'               => 'ユーザー権限',
        'singular_name'      => 'ユーザー権限',
        'edit_item'          => 'ユーザー権限の編集',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'capability_type'    => array( 'admin_setting', 'admin_settings' ),
        'map_meta_cap'       => true,
        'menu_icon'          => 'dashicons-admin-generic',
        'supports'           => array( 'title', )
    );

    register_post_type( 'user_edit_group', $args );
}

/*-------------------------------------------*/
/*  権限グループ編集画面 _ 編集ユーザーグループ設定用のメタボックスを追加
/*-------------------------------------------*/
add_action( 'admin_menu', 'eam_add_custom_field_user_edit_group' );

// add meta_box
function eam_add_custom_field_user_edit_group() {
    add_meta_box( 'meta_box_user_edit_group', '保有権限', 'eam_user_group_setting_meta_box', 'user_edit_group' );
}
function eam_user_group_setting_meta_box(){
    do_action('user_group_setting_meta_box');
}

/*-------------------------------------------*/
/*	権限グループ編集画面 _ 公開権限チェックボックスの作成
/*-------------------------------------------*/
add_action('user_group_setting_meta_box','eam_user_publish_role',9);
function eam_user_publish_role(){
    global $post;

    //CSRF対策の設定（フォームにhiddenフィールドとして追加するためのnonceを「'noncename__editPostType_items'」として設定）
    wp_nonce_field(wp_create_nonce(__FILE__), 'noncename__publish_role'); 

     /*  現在の値を取得
    /*-------------------------------------------*/   
    $publish_role = get_post_meta($post->ID,'publish_role',true);

    echo '<ul>';

    $checked = '';
    // 既にチェックが保存されているものはチェックをつける
    if ( $publish_role ) $checked = " checked";
    // チェックボックス出力
    echo '<li><label>'.'<input id="publish_role" type="checkbox" name="publish_role" value="true"'.$checked.'>公開承認権限</label></li>';

    echo '</ul>';
    echo '<hr />';

}

/*-------------------------------------------*/
/*  権限グループ編集画面 _ 投稿タイプ別のチェックボックスの生成
/*-------------------------------------------*/
add_action('user_group_setting_meta_box', 'eam_user_edit_post_type_items',10);
function eam_user_edit_post_type_items(){
    global $post;

    wp_nonce_field( wp_create_nonce(__FILE__), 'noncename__edit_post_type_items' ); 

     //  現在保存されている編集可能投稿タイプの値を取得 
    $edit_post_type_items = get_post_meta( $post->ID, 'edit_post_type_items', true );

    //  NEWS投稿タイプの情報を取得
	$args = array(
		'post_type' => 'news_setting', // NEWS設定の投稿タイプ
		'posts_per_page' => -1, // 投稿を全件取得
	);
	$news_settings = get_posts($args);

	// NEWS投稿タイプをループしてチェックボックスを生成
	if ( $news_settings ){
		echo '<h4>編集可能NEWS</h4>';
		echo '<ul>';
		foreach ($news_settings as $key => $value) {
            $news_post_type = esc_attr( $value->post_name );
            $news_label = esc_html( $value->post_title );
            // 既にチェックが保存されているものはチェックをつける
            $checked = '';
            if ( $edit_post_type_items && in_array($value->post_name, $edit_post_type_items) ) {
            	$checked = " checked";
            }
            // チェックボックス出力
            echo '<li><label><input type="checkbox" name="edit_post_type_items[]" value="'.$news_post_type.'"'.$checked.'>'.$news_label.'</label></li>';
		} // foreach ($news_settings as $key => $value) {
		echo '</ul>';
	} // if ( $news_settings ){
}

/*-------------------------------------------*/
/*  権限グループ編集画面 _ 入力された値の保存
/*-------------------------------------------*/
add_action('save_post', 'eam_save_cf_edit_post_type_items');

function eam_save_cf_edit_post_type_items($post_id){
    global $post;

    //設定したnonce を取得（CSRF対策）
    $noncename__edit_post_type_items = isset($_POST['noncename__edit_post_type_items']) ? $_POST['noncename__edit_post_type_items'] : null;

    //nonce を確認し、値が書き換えられていれば、何もしない（CSRF対策）
    if( !wp_verify_nonce($noncename__edit_post_type_items, wp_create_nonce(__FILE__))) {  
        return $post_id;
    }

    //自動保存ルーチンかどうかチェック。そうだった場合は何もしない（記事の自動保存処理として呼び出された場合の対策）
    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { 
    	return $post_id;
    }
    
    $fields = array('edit_post_type_items', 'publish_role');
    foreach ($fields as $key => $field) {
    	// 入力がなかったら消す
    	if ( ! isset( $_POST[$field] ) ) {
    		delete_post_meta($post_id, $field , get_post_meta($post_id, $field , true));
    	} else {
			$field_value = $_POST[$field];
			// データが空だったら入れる
			if(get_post_meta($post_id, $field ) == ""){
				add_post_meta($post_id, $field , $field_value, true);
			// 今入ってる値と違ってたらアップデートする
			}elseif($field_value != get_post_meta($post_id, $field , true)){
				update_post_meta($post_id, $field , $field_value);
			// 入力がなかったら消す
			}elseif($field_value == ""){
				delete_post_meta($post_id, $field , get_post_meta($post_id, $field , true));
			}
    	}

    } // foreach ($fields as $key => $field) {
    eam_update_role( $post_id );
}

/*-------------------------------------------*/
/*  権限書き換え _ 管理者権限 _ NEWS設定とユーザー権限グループ設定の編集権限を付与する
/*-------------------------------------------*/
add_action('admin_init','eam_update_admin_role_admin_setting');
function eam_update_admin_role_admin_setting(){
	$role = get_role( 'administrator' );
    // 公開権限を持つかどうか
    $publish_role = true;
	$edit_post_type_items = 'admin_setting';
    eam_add_caps( $role, $edit_post_type_items, $publish_role );
}


/*-------------------------------------------*/
/*  権限書き換え _ 管理者権限 _ NEWS投稿タイプの権限を付与する
/*-------------------------------------------*/
add_action('save_post','eam_update_admin_role_news_setting');
function eam_update_admin_role_news_setting(){
	if ( get_post_type() != 'news_setting' ){
		return;
	}
	$role = get_role( 'administrator' );

	// 追加されたNEWS投稿タイプ
	$news_settings = news_manage_news_settings();
    foreach ($news_settings as $key => $news_setting ) {
    	$edit_post_type_items[] = $news_setting->post_name.'_news';
    }

    // 公開権限を持つかどうか
    $publish_role = true;

	if ( is_array($edit_post_type_items) ){
		// 編集可能にチェックされている項目をループして権限を付与する
		foreach ( $edit_post_type_items as $key => $edit_post_type ) {
			eam_add_caps( $role, $edit_post_type, $publish_role );
		}
	}
}

/*-------------------------------------------*/
/*  権限書き換え _ 更新のあったユーザー権限投稿タイプに該当するroleをアップデートする
/*-------------------------------------------*/
function eam_update_role( $post_id ){
	// 新規登録あるいはアップデートのあったユーザー権限グループの投稿
	$user_group_post = get_post( $post_id );
	$role_slug = $user_group_post->post_name;
	$role_label = $user_group_post->post_title;

	// 変更のあったロールだけ一旦削除
	// （編集可能のNEWS投稿タイプからチェックが外された時用にロールごと一旦削除）
    remove_role( $role_slug );
    // ロールを再度作成
	add_role( $role_slug, $role_label, array('read'=> true) );

	/*-------------------------------------------*/
	/* ユーザー権限グループに権限を設定
	/*-------------------------------------------*/
    // 作成した各権限グループのロールを取得
    $role = get_role( $role_slug );

    // グループが編集可能に設定されている情報を取得
    $edit_post_type_items = get_post_meta( $user_group_post->ID, 'edit_post_type_items', true );
    // 公開権限を持つかどうかを取得
    $publish_role = get_post_meta( $user_group_post->ID, 'publish_role', true );
    // 編集可能コンテンツがチェックされていたら
	if ( is_array( $edit_post_type_items ) ){
		// 編集可能にチェックされている項目をループして権限を付与する
		foreach ( $edit_post_type_items as $key => $edit_post_type ) {
			$edit_post_type = $edit_post_type.'_news';
			eam_add_caps( $role, $edit_post_type, $publish_role );
		}
	}
}

/*-------------------------------------------*/
/*  権限書き換え _ 実際にroleにcapを追加する
/*-------------------------------------------*/
function eam_add_caps( $role, $edit_post_type, $publish_role ){
    $role->add_cap( 'upload_files' );
    $role->add_cap( 'edit_files' );
    // posts関連はメディアの編集・削除のために必要
    $role->add_cap( 'edit_posts' );
    $role->add_cap( 'delete_posts' );
    $role->add_cap( 'edit_others_posts' );
    $role->add_cap( 'unfiltered_html' );

    // 編集可能にチェックされているコンテンツの編集権限をループ中のグループに付与する
    $role->add_cap( 'add_'.$edit_post_type );
    $role->add_cap( 'add_'.$edit_post_type.'s' );
    $role->add_cap( 'edit_'.$edit_post_type );
    $role->add_cap( 'edit_'.$edit_post_type.'s' );
    $role->add_cap( 'edit_others_'.$edit_post_type.'s' );
    $role->add_cap( 'delete_private_'.$edit_post_type.'s' );
    $role->add_cap( 'delete_'.$edit_post_type );
    $role->add_cap( 'delete_'.$edit_post_type.'s' );
    $role->add_cap( 'manage_tax_'.$edit_post_type );

    // 公開権限があるユーザー
    if ( $publish_role ){
        $role->add_cap( 'publish_'.$edit_post_type.'s' );
        $role->add_cap( 'publish_others_'.$edit_post_type.'s' ); 
        $role->add_cap( 'edit_published_'.$edit_post_type.'s' );
        $role->add_cap( 'delete_others_'.$edit_post_type.'s' );
        $role->add_cap( 'delete_published_'.$edit_post_type.'s' );
    } //  if ($publish_role){
}
/*-------------------------------------------*/
/*  権限書き換え _ 権限削除用
/*-------------------------------------------*/
// add_action('init', 'eam_delete_caps');
function eam_delete_caps(){
	$role = get_role( 'administrator' );
	$edit_post_type = 'sk8_news';
    // 編集可能にチェックされているコンテンツの編集権限をループ中のグループに付与する
    $role->remove_cap( 'add_'.$edit_post_type );
    $role->remove_cap( 'add_'.$edit_post_type.'s' );
    $role->remove_cap( 'edit_'.$edit_post_type );
    $role->remove_cap( 'edit_'.$edit_post_type.'s' );
    $role->remove_cap( 'edit_others_'.$edit_post_type.'s' );
    $role->remove_cap( 'delete_private_'.$edit_post_type.'s' );
    $role->remove_cap( 'delete_'.$edit_post_type );
    $role->remove_cap( 'delete_'.$edit_post_type.'s' );
    $role->remove_cap( 'manage_tax_'.$edit_post_type );
    $role->remove_cap( 'publish_'.$edit_post_type.'s' );
    $role->remove_cap( 'publish_others_'.$edit_post_type.'s' ); 
    $role->remove_cap( 'edit_published_'.$edit_post_type.'s' );
    $role->remove_cap( 'delete_others_'.$edit_post_type.'s' );
    $role->remove_cap( 'delete_published_'.$edit_post_type.'s' );
}

/*-------------------------------------------*/
/*  権限書き換え _ 無くなったユーザー権限グループのroleを削除する
/*-------------------------------------------*/
add_action('save_post','eam_delete_role');
function eam_delete_role(){
	if ( get_post_type() != 'user_edit_group' ){
		return;
	}
    // 権限グループをすべて取得
    $roles = new WP_Roles();
    // 初期の権限グループ
    $roles_default = array('administrator','editor','author','contributor','subscriber');
    // 有効な権限グループ
    $roles_live = $roles_default;
    // 作成した編集権限グループ情報を取得
    $args = array(
        'posts_per_page'   => -1,
        'post_type'        => 'user_edit_group',
        'post_status'      => 'publish',
    );
    $groups = get_posts($args);

    // 作成されている編集権限グループを配列に追加
    foreach ( $groups as $key => $group ) {
        $role_slug = $group->post_name;
        // 有効なロールの配列に追加
        $roles_live[] = $role_slug;
    }

    // 有効じゃないロールを削除（ユーザーグループで削除されたものを自動で削除する）
    foreach ( $roles->roles as $key => $value ) {
        // 有効なロールじゃない場合は削除
        if ( !in_array( $key,$roles_live) ){
            remove_role($key);
        }
    }
}

/*-------------------------------------------*/
/*  管理画面 _ 権限の低いユーザーからメニュー項目の削除
/*-------------------------------------------*/
function remove_menus () {
	$user = wp_get_current_user();
	$role = current( $user->roles );
	// 管理画面から作成したユーザーグループを取得
	    $args = array(
        'posts_per_page'   => -1,
        'post_type'        => 'user_edit_group',
        'post_status'      => 'publish',
    );
    $groups = get_posts($args);
    foreach ($groups as $key => $value) {
    	$maked_groups[] = $value->post_name;
    }

    // 独自作成したユーザーグループの場合
    if ( in_array( $role, $maked_groups) ) {
    global $menu;
		// unset($menu[2]);  // ダッシュボード
		// unset($menu[4]);  // メニューの線1
		unset($menu[5]);  // 投稿
		// unset($menu[10]); // メディア
		// unset($menu[15]); // リンク
		// unset($menu[20]); // ページ
		unset($menu[25]); // コメント
		// unset($menu[59]); // メニューの線2
		// unset($menu[60]); // テーマ
		// unset($menu[65]); // プラグイン
		// unset($menu[70]); // プロフィール
		unset($menu[75]); // ツール
		// unset($menu[80]); // 設定
		// unset($menu[90]); // メニューの線3
    }

}
add_action('admin_menu', 'remove_menus');

