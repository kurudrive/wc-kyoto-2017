<?php
	/*-------------------------------------------*/
	/*	言語ボタンを押された時に言語のクッキーの入れ替え＆再読み込み
	/*-------------------------------------------*/
	/*	リンク先に言語パラメーターを付与する
	/*-------------------------------------------*/
	/*	別言語用の meta box を作成（SCFで代用中）
	/*-------------------------------------------*/
	/*	入力フィールドの生成（SCFで代用中）
	/*-------------------------------------------*/
	/*	テキストエリアにエディタを適用（効かない）
	/*-------------------------------------------*/
	/*	入力された値の保存（SCFで代用中）
	/*-------------------------------------------*/
	/*	SCFのレイアウト調整
	/*-------------------------------------------*/
	/*	言語の切り替えを実行
	/*-------------------------------------------*/
	/*	言語によってタイトルの差し替えを実行
	/*-------------------------------------------*/
	/*	言語によって本文の差し替えを実行
	/*-------------------------------------------*/
	/*	bodyタグに言語クラスを追加
	/*-------------------------------------------*/
	/*	実行
	/*-------------------------------------------*/
/*-------------------------------------------*/
/*	タクソノミー新規追加ページでの日本語入力フォーム
/*-------------------------------------------*/
/*	タクソノミー編集ページでの日本語入力フォーム
/*-------------------------------------------*/
/*	カスタムフィールドの自動多言語対応
/*-------------------------------------------*/
/*	リンク先に言語パラメーターを付与する
/*-------------------------------------------*/
/*	Walker_Page が出力するhtmlのURL部分の多言語対応処理
/*-------------------------------------------*/
/*	日本語ページと英語ページを区別するために canonical タグを変更
/*-------------------------------------------*/
/*	プレビュー中のIDを取得
/*	※プレビューでカスタムフィールドの値が正しく取得出来ない問題への対処
/*-------------------------------------------*/

if ( ! class_exists( 'multi_language' ) ) {

	class Multi_language {

		// function load_cookie() {
		// 	// load js
		// 	wp_enqueue_script( 'jquery_cookie', get_template_directory_uri(). '/plugins/multi_language/js/jquery.cookie.js', array( 'jquery' ), '20151124', true );
		// }

	/*-------------------------------------------*/
	/*	言語ボタンを押された時に言語スイッチャーに対して、現在の表示言語にstrongタグを追加
	/*-------------------------------------------*/
		function lang_switch_action(){
			if ( !is_admin()){ ?>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){

	// var lang = jQuery.cookie("lang");
	var lang = '<?php echo get_locale(); ?>';
	if ( lang == 'ja' ){
		jQuery('#lg_bt_ja a').wrap('<strong></strong>');
	} else {
		jQuery('#lg_bt_en a').wrap('<strong></strong>');
	}

//]]>
</script>
			<?php }

		}

	/*-------------------------------------------*/
	/*	SCFのレイアウト調整
	/*-------------------------------------------*/
		function scf_layout(){ ?>
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function($){
				jQuery('.smart-cf-meta-box-table:contains("日本語本文")').addClass('editor_ja');
			});
			//]]>
			</script>
			<style type="text/css">
			.smart-cf-meta-box-table.editor_ja th,
			.smart-cf-meta-box-table.editor_ja td { display:block; }
			</style>
		<?php 
		}

	/*-------------------------------------------*/
	/*	言語の切り替えを実行
	/*-------------------------------------------*/
		function locale_custom($locale){
			if ( !is_admin() ){
				if( isset($_GET['lang']) ) {
					if ( $_GET['lang'] == 'ja' ){
						$locale = 'ja';
					} else {
						$locale = 'en-US';
					}
				}
			}
			return $locale;
		}

	/*-------------------------------------------*/
	/*	言語によってタイトルの差し替えを実行
	/*-------------------------------------------*/
		function replace_title($title,$id){
			// 言語を取得
			$locale = get_locale();

			// 日本語だったら
			if( $locale == 'ja') {
				global $post;
				$id = ( isset($id) ) ? $id : $post->ID;
				$id = ml_get_preview_id($id);
				$title_ja = get_post_meta($id,'lang_title_ja',true);
				if ( $title_ja ) {
					$title = $title_ja;
				}
			}
			return $title;
		}

	/*-------------------------------------------*/
	/*	言語によって本文の差し替えを実行
	/*-------------------------------------------*/
		function replace_content($content){
			// 言語を取得
			global $post;
			$locale = get_locale();
			// 日本語だったら
			if( $locale == 'ja') {
				$id = ml_get_preview_id($post->ID);
				$content_ja = get_post_meta($id,'lang_content_ja',true);
				if ( $content_ja ) {
					$content = wpautop($content_ja);
				}
			}
			return $content;
		}

	/*-------------------------------------------*/
	/*	bodyタグに言語クラスを追加
	/*-------------------------------------------*/
	function add_lang_class( $classes ) {
		$locale_name = get_locale();
		$classes[] = $locale_name;
		return $classes;
	}

	/*-------------------------------------------*/
	/*	実行
	/*-------------------------------------------*/
		public function __construct(){
			add_filter( 'locale', array( $this, 'locale_custom' ) );
			// add_action( 'wp_footer', array( $this, 'lang_switch_action' ) );
			// add_action( 'wp_enqueue_scripts', array( $this, 'load_cookie' ) );
			// add_action( 'admin_enqueue_scripts', array( $this, 'load_cookie' ) );

			// Smart Custom Field 用のレイアウト調整
			add_action( 'admin_footer', array( $this, 'scf_layout' ) );

			add_filter( 'the_title', array( $this, 'replace_title'),100,2 );
			add_filter( 'the_content', array( $this, 'replace_content'),1,2 );
			add_filter( 'body_class', array( $this, 'add_lang_class' ) );
		}
	} // class multi_language
} // if ( ! class_exists( 'multi_language' ) ) {

// add_filter( 'locale', 'locale_custom' );

$multi_language = new Multi_language();

/*-------------------------------------------*/
/*	タクソノミー新規追加ページでの日本語入力フォーム
/*-------------------------------------------*/
function taxonomy_add_new_meta_field_language() {
	// this will add the custom meta field to the add new term page
	?>
	<div class="form-field">
		<label for="term_title_ja"><?php _e( 'Japanese label', 'textdomain' ); ?></label>
		<input type="text" name="term_title_ja" id="term_title_ja" value="">
	</div>
<?php
}

/*-------------------------------------------*/
/*	タクソノミー編集ページでの日本語入力フォーム
/*-------------------------------------------*/
function taxonomy_add_edit_meta_field_language($term) {
	// put the term ID into a variable
	$term_title_ja = get_term_meta( $term->term_id, 'term_title_ja', true );
	?>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_title_ja"><?php _e( 'Japanese label', 'textdomain' ); ?></label></th>
		<td>
			<input type="text" name="term_title_ja" id="term_title_ja" value="<?php echo esc_attr( $term_title_ja ) ? esc_attr( $term_title_ja ) : ''; ?>">
		</td>
	</tr>
<?php
}

// Save extra taxonomy fields callback function.
function save_taxonomy_custom_meta_language( $term_id ) {
	if ( isset( $_POST['term_title_ja'] ) ) {

		$now_value = get_term_meta( $term_id, 'term_title_ja', true );
		$new_value = $_POST['term_title_ja'];
		if ( $now_value != $new_value ) {
			update_term_meta( $term_id, 'term_title_ja', $new_value );
		} else {
			add_term_meta( $term_id, 'term_title_ja', $new_value );
		}
	}
} 

// 日本語ラベルを追加するタクソノミー
$taxonomies = array('phone_book-cat','event-cat');

// 該当のタクソノミー分ループ処理する
foreach ($taxonomies as $key => $value) {
	add_action( $value.'_add_form_fields', 'taxonomy_add_new_meta_field_language', 10, 2 );
	add_action( $value.'_edit_form_fields', 'taxonomy_add_edit_meta_field_language', 10, 2 );
	add_action( 'edited_'.$value, 'save_taxonomy_custom_meta_language', 10, 2 );  
	add_action( 'create_'.$value, 'save_taxonomy_custom_meta_language', 10, 2 );
}

function term_title_lang_change( $term_id,$term_name ){
	$locale = get_locale();
	if ( $locale == 'ja' ){
		$term_title_ja = get_term_meta( $term_id, 'term_title_ja', true );
		$term_name = ( $term_title_ja ) ? $term_title_ja : $term_name;
	}
	return esc_html($term_name);
}


/*-------------------------------------------*/
/*	カスタムフィールドの自動多言語対応
/*-------------------------------------------*/
function lang_switch_custom_fields($post_id,$fieldname){
	if ( get_locale() == 'ja') {
		$value = get_post_meta( $post_id , $fieldname.'_ja' , true );
		if (!$value){
			$value = get_post_meta( $post_id , $fieldname , true );
		}
	} else {
		$value = get_post_meta( $post_id , $fieldname , true );
	}
	return $value;
}
function lang_switch_custom_fields_no_en($post_id,$fieldname){
	if ( get_locale() == 'ja') {
		$value = get_post_meta( $post_id , $fieldname.'_ja' , true );
		if (!$value){
			$value = get_post_meta( $post_id , $fieldname.'_en' , true );
		}
	} else {
		$value = get_post_meta( $post_id , $fieldname.'_en' , true );
	}
	return $value;
}

/*-------------------------------------------*/
/*	リンク先に言語パラメーターを付与する
/*-------------------------------------------*/
function lang_switch_link_url( $permalink ){

	if ( !is_admin() ){
		// 現在の言語を取得
		$locale = get_locale();
		// URLに言語パラメーターを追加
		$permalink = esc_url( add_query_arg( 'lang', $locale, $permalink ) );

	}
	
	return $permalink;
}
add_filter( 'the_permalink', 'lang_switch_link_url' ,10,2 );

/*-------------------------------------------*/
/*	Walker_Page が出力するhtmlのURL部分の多言語対応処理
/*-------------------------------------------*/
class multi_language_walker_page extends Walker_Page {
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}

		$css_class = array( 'page_item', 'page-item-' . $page->ID );

		if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
			$css_class[] = 'page_item_has_children';
		}

		if ( ! empty( $current_page ) ) {
			$_current_page = get_post( $current_page );
			if ( $_current_page && in_array( $page->ID, $_current_page->ancestors ) ) {
				$css_class[] = 'current_page_ancestor';
			}
			if ( $page->ID == $current_page ) {
				$css_class[] = 'current_page_item';
			} elseif ( $_current_page && $page->ID == $_current_page->post_parent ) {
				$css_class[] = 'current_page_parent';
			}
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_page_parent';
		}

		/**
		 * Filter the list of CSS classes to include with each page item in the list.
		 *
		 * @since 2.8.0
		 *
		 * @see wp_list_pages()
		 *
		 * @param array   $css_class    An array of CSS classes to be applied
		 *                             to each list item.
		 * @param WP_Post $page         Page data object.
		 * @param int     $depth        Depth of page, used for padding.
		 * @param array   $args         An array of arguments.
		 * @param int     $current_page ID of the current page.
		 */
		$css_classes = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		if ( '' === $page->post_title ) {
			/* translators: %d: ID of a post */
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$args['link_before'] = empty( $args['link_before'] ) ? '' : $args['link_before'];
		$args['link_after'] = empty( $args['link_after'] ) ? '' : $args['link_after'];

		/** This filter is documented in wp-includes/post-template.php */
		if ( get_locale() == 'ja' ){
			$permalink = get_permalink( $page->ID );
			$link = lang_switch_link_url( $permalink );
		} else {
			$link = get_permalink( $page->ID );
		}
		
		$output .= $indent . sprintf(
			'<li class="%s"><a href="%s">%s%s%s</a>',
			$css_classes,
			$link,
			$args['link_before'],
			apply_filters( 'the_title', $page->post_title, $page->ID ),
			$args['link_after']
		);

		if ( ! empty( $args['show_date'] ) ) {
			if ( 'modified' == $args['show_date'] ) {
				$time = $page->post_modified;
			} else {
				$time = $page->post_date;
			}

			$date_format = empty( $args['date_format'] ) ? '' : $args['date_format'];
			$output .= " " . mysql2date( $date_format, $time );
		}
	}
}

/*-------------------------------------------*/
/*	Walker_Page が出力するhtmlのURL部分の多言語対応処理
/*-------------------------------------------*/
// function ml_lang_bt_url_path($bt_locale){
// 	$now_url = $_SERVER["REQUEST_URI"];

// 	// 今のURLが日本語URLかどうかの判定
// 	$subject = $now_url;
// 	$pattern = '/([^\/]+)_ja\/$/';
// 	preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, 0);

// 	if ( is_front_page() || is_page('ja')){
// 		$ja_url = home_url('/').'ja/';
// 		$en_url = home_url('/');

// 	} else if ( $matches ){

// 		$ja_url = $now_url;

// 		$string = $now_url;
// 		$pattern = '/([^\/]+)_ja\/$/';
// 		$replacement = '${1}/';
// 		$en_url = preg_replace($pattern, $replacement, $string);

// 	} else {

// 		$en_url = $now_url;

// 		$string = $now_url;
// 		$pattern = '/([^\/]+)\/$/';
// 		$replacement = '${1}_ja/';
// 		$ja_url = preg_replace($pattern, $replacement, $string);
// 	}

// 	// Japaneseボタンの時
// 	if ( $bt_locale == 'ja' ){
// 		return $ja_url;
// 	// Englishボタンの時
// 	} else {
// 		return $en_url;
// 	}
// }

function ml_lang_bt_url_para($bt_locale){
	$now_url = $_SERVER["REQUEST_URI"];
	// 現在のURLに ?lang=url を含んでいたら削除する
	$en_url = preg_replace( '/\?lang=ja/', '', $now_url);
	// 英語版用のURLに &lang=url を含んでいたら削除する
	$en_url = preg_replace( '/&lang=ja/', '', $en_url);

	$subject = $now_url;

	// パラメーター制御の時
	$pattern = '/lang=ja/';
	preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE, 0);

	// 既にlangパラメーターがあるかどうか？
	if ( $matches ){
		// langパラメーターがある場合はそのまま戻す
		$ja_url = $now_url;

	} else {
		if ( !isset($_GET['lang']) ){
		// langパラメーターがない場合
			$subject = $now_url;
			$pattern = '/\?/';
			preg_match($pattern, $subject, $matches_hatena, PREG_OFFSET_CAPTURE, 0);
			// URLに既にパラメーターがある
			if ( $matches_hatena ){
				// &lang=ja を追加
				$ja_url = $now_url.'&lang=ja';
			} else {
			// URLにパラメーターが存在しない
				// ?lang=ja を追加	
				$ja_url = $now_url.'?lang=ja';
			}
		}
	}
	// Japaneseボタンの時
	if ( $bt_locale == 'ja' ){
		return $ja_url;
	// Englishボタンの時
	} else {
		return $en_url;
	}
}


/*-------------------------------------------*/
/*	日本語ページと英語ページを区別するために canonical タグを変更
/*-------------------------------------------*/
remove_action('wp_head', 'rel_canonical');
function ml_multi_rel_canonical(){
	if ( ! is_singular() ) {
		return;
	}

	if ( ! $id = get_queried_object_id() ) {
		return;
	}

	$url = get_permalink( $id );
	$url = lang_switch_link_url(  $url );

 	$page = get_query_var( 'page' );
	if ( $page >= 2 ) {
		if ( '' == get_option( 'permalink_structure' ) ) {
			$url = add_query_arg( 'page', $page, $url );
		} else {
			$url = trailingslashit( $url ) . user_trailingslashit( $page, 'single_paged' );
		}
	}

	$cpage = get_query_var( 'cpage' );
	if ( $cpage ) {
		$url = get_comments_pagenum_link( $cpage );
	}
	echo '<link rel="canonical" href="' . esc_url( $url ) . "\" />\n";
}
add_action('wp_head','ml_multi_rel_canonical');

/*-------------------------------------------*/
/*	プレビュー中のIDを取得
/*	※プレビューでカスタムフィールドの値が正しく取得出来ない問題への対処
/*-------------------------------------------*/
function ml_get_preview_id($id) {
	global $post;
	if (isset($id)){
		$post_id = $id;
	} else {
		$post_id = $post->ID;
	}
	
    // 一番新しいリビジョンを取得してそのIDを返す
    if ( is_preview() ){
		$rev = wp_get_post_revisions( $post_id, array( 'posts_per_page' => 1 ) );
		if ( empty( $rev ) ) {
			$preview_id = $post_id;
		} else {
			$rev = reset( $rev );
			$preview_id = $rev->ID;
		}
		return $preview_id;
    } else {
    	// プレビューじゃない時は普通に$post->IDを返す
    	$preview_id = $post_id;
    	return $preview_id;
    }
}

// function get_preview_id( $post_id ) {
//     global $post;
//     $preview_id = '';
//     if ( !empty($post)){

// 	    // $preview_id = 0;
// 	    // if ( $post->ID == $post_id && is_preview() && $preview = wp_get_post_autosave( $post->ID ) ) {
// 	    //     $preview_id = $preview->ID;
// 	    // }
// 	    // 一番新しいリビジョンを取得してそのIDを返す
// 	    if ( $post->ID == $post_id && is_preview() ){
// 			$rev = wp_get_post_revisions( $post->ID, array( 'posts_per_page' => 1 ) );
// 			if ( empty( $rev ) ) {
// 				$preview_id = $post->ID;
// 			} else {
// 				$rev = reset( $rev );
// 				// $preview_id = $rev->ID;
// 			}
// 	    } else {
// 	    	// プレビューじゃない時は普通に$post->IDを返す
// 	    	$preview_id = $post->ID;
// 	    }
//     }
//     return $preview_id;
// }
// /*	プレビュー中のカスタムフィールドの値を取得
// /*-------------------------------------------*/
// // カスタムフィールドの値は公開前はリビジョンと紐付いているのでリビジョンから取得する
// function get_preview_postmeta( $return, $post_id, $meta_key, $single ) {
//     if ( $preview_id = get_preview_id( $post_id ) ) {
//         if ( $post_id != $preview_id ) {
//             $return = get_post_meta( $preview_id, $meta_key, $single );
//         }
//     }
//     return $return;
// }
// // add_filter( 'get_post_metadata', 'get_preview_postmeta', 10, 4 );
