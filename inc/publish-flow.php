<?php
/*-------------------------------------------*/
/*  ログインしているユーザーが公開権限を持っているかどうか
/*-------------------------------------------*/
/*  新規複製 _ 保存する関数
/*-------------------------------------------*/
/*  記事リスト _ 複製して編集へのリンクを追加
/*-------------------------------------------*/
/*  新規複製 _ 複製を実行
/*-------------------------------------------*/
/*  記事内容の差し替えトリガー
/*-------------------------------------------*/
/*	記事内容の差し替え処理
/*-------------------------------------------*/
/*  レビュー送信metabox
/*-------------------------------------------*/
/*  レビュー送信metabox _ 非承認メッセージのUIを非表示処理
/*-------------------------------------------*/
/*  レビュー送信metabox _ 邪魔な項目を削除
/*-------------------------------------------*/
/*  レビュー依頼メール送信処理
/*-------------------------------------------*/
/*  公開通知メール _ 送信処理
/*-------------------------------------------*/
/*  公開通知メール _ 記事の公開時に処理を実行
/*-------------------------------------------*/
/*  メール送信 _ Fromを強制的に固定する
/*-------------------------------------------*/

/*-------------------------------------------*/
/*  ログインしているユーザーが公開権限を持っているかどうか
/*-------------------------------------------*/
function edit_flow__has_publish_role(){
	// ログインしているユーザーデータ
	$userdata = wp_get_current_user();
	// 現在の投稿タイプを公開するのに必要なcap
	$publish_cap = 'publish_others_'.get_post_type().'s';
	if ( isset( $userdata->allcaps[$publish_cap] ) && $userdata->allcaps[$publish_cap] ) {
		return true;
	} else {
		return false;
	}
}

/*-------------------------------------------*/
/*  新規複製 _ 保存する関数
/*-------------------------------------------*/
function edit_flow__copy_post( $post_id, $post_status='draft' ){

    $post = get_post($post_id);

    if( empty($post) ) return null;
 
    $taxonomys = get_object_taxonomies( $post );
    // var_dump($taxonomys);
    $set_terms = array();
    foreach( $taxonomys as $taxonomy ){
        $tm = wp_get_object_terms( $post_id, $taxonomy );
        if( empty($tm) ) continue;
        $set_terms[$taxonomy] = array();
        foreach( $tm as $t ){
            $set_terms[$taxonomy][] = $t->term_taxonomy_id;
        }
    }
    // var_dump($set_terms);

    $metas = get_post_custom($post_id);
    $metas['copy_master_id'][0] = $post_id;

    // var_dump($metas);

    $copy_metas = array();
    while( list($k,$v) = each( $metas ) ){
        if( $k == '_wp_page_template' ) $copy_metas[$k] = $v;
        if( $k == '_thumbnail_id' ) $copy_metas[$k] = $v;

        if( ! preg_match('/^_/', $k) ) $copy_metas[$k] = $v;
    }

    // var_dump($copy_metas);

    // 複製したユーザー情報
	$userdata = wp_get_current_user();
	$author_id = $userdata->data->ID;

    $post_var = array(
        'post_content'   => $post->post_content,
        'post_name'      => $post->post_name,
        'post_title'     => $post->post_title,
        'post_status'    => $post_status,
        'post_type'      => $post->post_type,
        'post_author'    => $author_id,
        'ping_status'    => $post->ping_status,
        'post_parent'    => $post->post_parent,
        'menu_order'     => 0,
        'to_ping'        => $post->to_ping,
        'pinged'         => $post->pinged,
        'post_password'  => $post->post_password,
        'post_excerpt'   => $post->post_excerpt,
        'post_date'      => $post->post_date,
        'post_date_gmt'  => $post->post_date_gmt,
        'comment_status' => $post->comment_status,
        'tax_input'      => $set_terms,
    );
    // echo "post_var\n";
    // var_dump($post_var);

    // return;
    $new_post = wp_insert_post($post_var);
    // var_dump($new_post);
    if( is_wp_error( $new_post ) ) return false;

    while( list($k,$v) = each($copy_metas) ){
        foreach( $v as $vv ) add_post_meta( $new_post, $k, $vv );
    }

    // var_dump(get_post_custom($new_post));
    return $new_post;
}


/*-------------------------------------------*/
/*  記事リスト _ 複製して編集へのリンクを追加
/*-------------------------------------------*/

add_action( 'admin_init', 'edit_flow__post_list__add_edit_link_action' );

function edit_flow__post_list__add_edit_link_action() {
	if ( !edit_flow__has_publish_role() ){
	    add_filter( 'post_row_actions', 'edit_flow__post_row_actions', 10, 2 );
		add_filter( 'page_row_actions', 'edit_flow__post_row_actions', 10, 2 );
	}
}

function edit_flow__post_row_actions( $actions, $post ) {

	// tinymcetemplateのエントリーを削除する。
	if (array_key_exists('copy_to_template', $actions)) { 
		unset($actions['copy_to_template']); 
	}  
	/* 「複製して編集」を追加 */
	$post_type = get_post_type();
	$links = admin_url().'post-new.php?post_type='.$post_type.'&master_id='.$post->ID;
	$actions['newlink'] =  '<a href="'.$links.'">複製して編集</a>';
	return $actions;
}

/*-------------------------------------------*/
/*  新規複製 _ 複製を実行
/*-------------------------------------------*/
function edit_flow__create_copy(){
	// 管理画面のURLに複製識別用のURLが含まれていたら
	if ( isset($_GET['master_id']) ){
		$master_id = esc_html($_GET['master_id']);
		// 記事の複製を実行
		$copy_post_id = edit_flow__copy_post( $master_id );
		// 複製した記事の編集画面へリダイレクト
		$url = admin_url().'post.php?post='.$copy_post_id.'&action=edit';
		header("Location: {$url}");
	}
}
add_action('admin_init','edit_flow__create_copy');

/*-------------------------------------------*/
/*  記事内容の差し替えトリガー
/*-------------------------------------------*/
function edit_flow__pub_update_action(){
	// 公開承認ボタンが押された時の処理だった場合
	if ( isset($_POST['pub_update']) ){

		/*  	公開権限を持つユーザーの場合
		/*-------------------------------------------*/
		global $post;
		$edit_copy_id = esc_html($_POST['post_ID']);
		$copy_master_id = get_post_meta($edit_copy_id, 'copy_master_id',true );
		// 編集した記事IDのパラメーターをつけて元記事の編集画面に移動
		$update_url = admin_url().'post.php?post_type='.get_post_type().'&action=edit&post='.$copy_master_id.'&edit_copy_id='.$edit_copy_id;
		wp_safe_redirect($update_url);
		die();
	}
	remove_action('post_updated','edit_flow__pub_update_action',9999);
}
add_action('post_updated','edit_flow__pub_update_action',9999);

/*-------------------------------------------*/
/*	記事内容の差し替え処理
/*-------------------------------------------*/

function edit_flow__update_post_get(){
	global $post;
	// 管理画面のURLに複製識別用のURLが含まれていたら
	// (承認して公開ボタンを押された時のURLだったら)
	if ( isset($_GET['edit_copy_id']) && isset($_GET['post']) ){
		$edit_copy_id = esc_html($_GET['edit_copy_id']);
		$master_id = esc_html($_GET['post']);

		/*  メタ情報のアップデート
		/*-------------------------------------------*/
	    $metas = get_post_custom($edit_copy_id);
	    $metas['copy_master_id'][0] = null;

	    $edited_metas = array();
	    while( list($k,$v) = each( $metas ) ){
	        if( $k == '_wp_page_template' ) $edited_metas[$k] = $v;
	        if( $k == '_thumbnail_id' ) $edited_metas[$k] = $v;

	        if( ! preg_match('/^_/', $k) ) $edited_metas[$k] = $v;
	    } 

	    while( list($k,$v) = each($edited_metas) ){
	        foreach( $v as $vv ) update_post_meta( $master_id, $k, $vv );
	    }

		/*  投稿情報のアップデート
		/*-------------------------------------------*/
		$edit_copy_post = get_post($edit_copy_id,'ARRAY_A');

		/*  taaxonomy情報のアップデート
		/*-------------------------------------------*/
		$taxonomys = get_object_taxonomies( $edit_copy_post );
		$set_terms = array();
		if ( isset( $taxonomys ) && $taxonomys ) {
			foreach( $taxonomys as $taxonomy ){
			    $tm = wp_get_object_terms( $edit_copy_id, $taxonomy );
			    if( empty($tm) ) continue;
			    $set_terms[$taxonomy] = array();
			    foreach( $tm as $t ){
			        $set_terms[$taxonomy][] = $t->term_taxonomy_id;
			    }
			} // foreach( $taxonomys as $taxonomy ){
		} // if ( isset( $taxonomys ) && $taxonomys ) {

		$edit_copy_post['ID'] = esc_html($_GET['post']);
		// 元記事のスラッグを書き換えないように元記事の post_name を取得
		$master_post = get_post($master_id,'ARRAY_A');
		$edit_copy_post['post_name'] = $master_post['post_name'];
		$edit_copy_post['post_status'] = 'publish';
		$edit_copy_post['tax_input'] = $set_terms;

		$delete_post = wp_delete_post( $edit_copy_id, true );
		$new_post = wp_update_post($edit_copy_post);
		// 承認通知を送信
		edit_flow__send_approved_mail($edit_copy_post['ID']);
		if( is_wp_error( $new_post ) ) return false;

	} // if ( isset($_GET['edit_copy_id']) && isset($_GET['post']) ){

}
add_action('admin_init','edit_flow__update_post_get');


/*-------------------------------------------*/
/*  レビュー送信metabox
/*-------------------------------------------*/
add_action( 'post_submitbox_start','edit_flow__send_review' );
function edit_flow__send_review(){
	// if ( get_post_type() != 'sk8_news' ) return;
	echo '<div id="review_section">';

	if ( edit_flow__has_publish_role() ) {
		/*  	公開権限を持つユーザーの場合
		/*-------------------------------------------*/
		global $post;
		$copy_master_id = get_post_meta($post->ID, 'copy_master_id',true );
		$update_url = admin_url().'post.php?post_type='.get_post_type().'&action=edit&post='.$copy_master_id.'&edit_copy_id='.$post->ID;

		if ( $post->post_status != 'publish' ){

			// 公開ボタン
			if ( $copy_master_id ) {
				// 複製された記事の場合
				echo '<p><input type="submit" name="pub_update" id="publish_review_post" class="button button-primary button-large button-block" value="承認して公開"></p>';
			} else {
				// 新規投稿の下書き記事の場合
				echo '<p><input type="submit" name="publish" id="publish" class="button button-primary button-large button-block" value="公開"></p>';
			} // if ( $copy_master_id ) {

			// 非承認の入力欄とボタン
			$not_app_ui_html = '<hr><p>'."\n";
			if ( get_locale() == 'ja') {
				$not_app_ui_html .= '内容に問題がある場合は、修正指示を入力してメッセージを送信してください。';
			} else {
				$not_app_ui_html .= 'If there is a problem with the contents, please enter the correction instructions and send message.';
			}
			$not_app_ui_html .= '</p>'."\n";
			$not_app_ui_html .= '<p><textarea name="non_app_message" style="width:100%;" rows="3"></textarea>
			<input type="submit" name="non_approved" id="non_approved_post" class="button button-danger button-large button-block" value="メッセージを送信（非承認）" style="width:100%"></p>'."\n";

			echo apply_filters('edit_flow__not_app_ui_custom',$not_app_ui_html);

		}

	} else {
		/*  	公開権限を持たないユーザーの場合
		/*-------------------------------------------*/
		$pub_users = edit_flow__has_publish_users();
		echo "承認依頼先<br>";
		echo '<select name="review_target" id="review_target">';
		foreach ($pub_users as $key => $value) {
			echo '<option value="'.$value->ID.'">';
			echo $value->display_name;
			echo '</option>';
		}
		echo '</select>';
		?>

		<input type="submit" name="send_review_mail" id="send_review_mail" class="button button-primary" value="承認依頼">
		<?php 
	}

	echo '</div><!-- [ / #review_section ] -->';
}

/*-------------------------------------------*/
/*  レビュー送信metabox _ 非承認メッセージのUIを非表示処理
/*-------------------------------------------*/
function edit_flow__not_app_ui_hidden($not_app_ui_html){

	// 公開権限のあるユーザーのみこの関数は実行されている
	global $hook_suffix;
	if ( $hook_suffix == 'post-new.php' ) {
		//承認権限のあるユーザーが新規投稿の場合は非承認のUIは表示しない
		$not_app_ui_html = '';
	}
	if ( $hook_suffix == 'post.php' ) {

		// 記事の作成者とログイン中のユーザーが同じだったら非承認のUIを表示しない
		global $post;
		$author_id = $post->post_author;

		$userdata = wp_get_current_user();
		$current_login_user_id = $userdata->data->ID;

		if ( $author_id == $current_login_user_id ){
			$not_app_ui_html = '';
		}
	}
	return $not_app_ui_html;
}
add_filter('edit_flow__not_app_ui_custom','edit_flow__not_app_ui_hidden');

/*  	公開権限を持つユーザー情報
/*-------------------------------------------*/
function edit_flow__has_publish_users(){
	// 管理者も承認依頼のリストに含める場合
	$pub_groups = array('administrator','editor');

	// 公開に必要なcap
	$publish_cap = 'publish_others_'.get_post_type().'s';

    // 権限グループを取得
	$roles = wp_roles()->roles;
	// 存在する権限グループをループ
	$pub_users = '';
    foreach ($roles as $role => $value) {
    	if ( isset( $value['capabilities'][$publish_cap] ) && $value['capabilities'][$publish_cap] ){

    		// 権限グループに登録されているユーザー情報を取得
			$group_users = get_users( array( 'role' => $role ) );

	 		foreach ($group_users as $key => $user) {
	 			$pub_users[] = $user->data;
	 		}
    	} // if ( in_array( $publish_role, $role->capabilities ) ){
    }
	return $pub_users;
}

/*-------------------------------------------*/
/*  レビュー送信metabox _ 邪魔な項目を削除
/*-------------------------------------------*/
add_action('admin_footer','edit_flow__metabox_item_tuning');
function edit_flow__metabox_item_tuning(){
	if ( get_post_type() != 'sk8_news' ) return;
	global $hook_suffix;
	if ( $hook_suffix == 'post.php' || $hook_suffix == 'post-new.php' ){

	global $post;
	// $copy_master_id = get_post_meta($post->ID, 'copy_master_id',true );
	// UI統一のためにデフォルトの公開ボタンは非表示
	if ( $post->post_status != 'publish' ) { 
		?>
		<style type="text/css">
			<?php // 公開ボタンを非表示に ?>
			#publishing-action { display: none; }
		</style>
	<?php 
}

	// ログインしているユーザーが編集権限を持っているかどうか
	$userdata = wp_get_current_user();
	$publish_role = 'publish_'.get_post_type().'s';
	if ( ! isset( $userdata->allcaps[$publish_role] ) || ! $userdata->allcaps[$publish_role] ) {
	// 公開権限を持っていない場合 ?>
		<style type="text/css">
		<?php // レビュー待ちとして送信ボタンを非表示に ?>
			#publishing-action { display: none; }
		</style>
<?php
	} // if ( !$userdata->allcaps[$publish_role] ) {

	} // if ( $hook_suffix == 'post.php' || $hook_suffix == 'post-new.php' ){
}

/*-------------------------------------------*/
/*  レビュー依頼メール送信処理
/*-------------------------------------------*/
function edit_flow__send_review_mail(){

	// 複数送信しないようにremoveする
	remove_action('save_post', 'edit_flow__send_review_mail');

	if( isset($_POST['send_review_mail']) ){

	    //自動保存ルーチンかどうかチェック。そうだった場合は何もしない（記事の自動保存処理として呼び出された場合の対策）
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }

		$review_target = $_POST['review_target'];

		if ($review_target){

			$review_target_user = get_userdata($review_target);
			$userdata = wp_get_current_user();

			$to = $review_target_user->data->user_email;
			$to_name = $review_target_user->data->display_name;

			global $post;
			$author_id = $post->post_author;
			$author_user = get_userdata($author_id);
			$from_name = $author_user->data->display_name;

			// $to = 'ishikawa@vektor-inc.co.jp';
		    $subject = '[ '.get_bloginfo('name').' ] 承認依頼メール';
		    $message = '';
		    $message .= '[ To ] '.$to_name."\n\n";
		    $message .= __('The following article has been updated. Confirmation thank you.','textdomain')."\n\n";
		    $message .= admin_url().'post.php?post='.$_POST['ID'].'&action=edit'."\n\n";
		    $message .= '[ From ] '.$from_name."\n\n";
		    $message .= "----------------------------"."\n";
		    $message .= get_bloginfo('name')."\n";
		    $message .= home_url()."\n";
		    $message .= "----------------------------"."\n";
		    // $headers[] = 'From: '.esc_html($userdata->data->display_name).' <'.$userdata->data->user_email.'>';
		    $headers[] = 'From: <gtgn@toyoda-gosei.co.jp>';
		    wp_mail( $to, $subject, $message, $headers );	
		}
	}
}
add_action('save_post', 'edit_flow__send_review_mail');


/*-------------------------------------------*/
/*  非承認メール _ 保存時に処理を実行
/*-------------------------------------------*/
function edit_flow__send_not_app_mail_hook(){

	// 複数送信しないようにremoveする
	remove_action('save_post','edit_flow__send_not_app_mail_hook');

	if ( isset($_POST['non_approved']) ){

	    //自動保存ルーチンかどうかチェック。そうだった場合は何もしない（記事の自動保存処理として呼び出された場合の対策）
	    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }

		global $post;
		$author_id = $post->post_author;
		$author_user = get_userdata($author_id);
		$to = $author_user->data->user_email;
		$to_name = $author_user->data->display_name;

		global $userdata;
		get_currentuserinfo();
		$from_name = $userdata->data->display_name;

		$post_id = $post->ID;

		$subject = '[ '.get_bloginfo('name').' ] '. __('Your article has not approved','textdomain');
		$message = '';
		$message .= '[ To ] '.$to_name."\n\n";
		$message .= __('The following article has not approved.','textdomain')."\n\n";
		$message .= get_the_title($post_id )."\n";
		$message .= admin_url().'post.php?post='.$post_id.'&action=edit'."\n\n";
		$message .= esc_html($_POST['non_app_message'])."\n\n";
		$message .= '[ From ] '.$from_name."\n\n";
		$message .= "----------------------------"."\n";
		$message .= get_bloginfo('name')."\n";
		$message .= home_url()."\n";
		$message .= "----------------------------"."\n";
		$headers[] = 'From: '.esc_html($userdata->data->display_name).' <'.$userdata->data->user_email.'>';
		$return = wp_mail( $to, $subject, $message, $headers );	
		return $return;
	}
}
add_action('save_post','edit_flow__send_not_app_mail_hook');

/*-------------------------------------------*/
/*  非承認メール _ 完了後のメッセージ
/*-------------------------------------------*/
// function echo_message() {
	
// 	echo '<div class="message updated"><p>'.$_POST['non_app_message'].'</p></div>';
// 	if ( isset($_POST['non_approved']) ){
//     	echo '<div class="message updated"><p>'.__('Made a notification of non-approval.','textdomain').'</p></div>';
// 	}
// }
// add_action( 'admin_notices','echo_message',100,2 );

/*-------------------------------------------*/
/*  公開通知メール _ 送信処理
/*-------------------------------------------*/
function edit_flow__send_approved_mail($post_id){
	global $post;
	// $post_id = get_post_meta($post->ID, 'copy_master_id',true );
	$edit_post = get_post($post_id,'ARRAY_A');
	$author_id = $edit_post['post_author'];
	$author_user = get_userdata($author_id);
	$to = $author_user->data->user_email;
	$to_name = $author_user->data->display_name;

	$userdata = wp_get_current_user();
	$from_name = $userdata->data->display_name;

	$post_id = ($post_id) ? $post_id : $post->ID;

	$subject = '[ '.get_bloginfo('name').' ] '. __('Article has been approved and released','textdomain');
	$message = '';
	$message .= '[ To ] '.$to_name."\n\n";
	$message .= __('The following article has been approved and released.','textdomain')."\n\n";
	$message .= get_the_title($post_id )."\n";
	$message .= get_permalink($post_id )."\n\n";
	$message .= '[ From ] '.$from_name."\n\n";
	$message .= "----------------------------"."\n";
	$message .= get_bloginfo('name')."\n";
	$message .= home_url()."\n";
	$message .= "----------------------------"."\n";
	$headers[] = 'From: '.esc_html($userdata->data->display_name).' <'.$userdata->data->user_email.'>';
	$return = wp_mail( $to, $subject, $message, $headers );	
	return $return;
	// print '<pre style="text-align:left">';print_r($to);print '</pre>';
	// print '<pre style="text-align:left">';print_r($subject);print '</pre>';
	// print '<pre style="text-align:left">';print_r($message);print '</pre>';
}

/*-------------------------------------------*/
/*  公開通知メール _ 記事の公開時に処理を実行
/*-------------------------------------------*/
function edit_flow__send_publish_mail_hook(){
	global $post;
	edit_flow__send_approved_mail($post->ID);
}
add_action('draft_to_publish','edit_flow__send_publish_mail_hook');

/*-------------------------------------------*/
/*  メール送信 _ Fromを強制的に固定する
/*-------------------------------------------*/
add_action('phpmailer_init', 'add_mail_sender');
function add_mail_sender($phpmailer){
	$phpmailer->FromName = get_bloginfo('name');
    // $phpmailer->Sender = 'sample@example.com';
    return $phpmailer;
}