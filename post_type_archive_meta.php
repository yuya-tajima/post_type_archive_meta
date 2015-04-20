<?php
/*
Plugin Name: PostTypeArchiveMeta
Description: ex. You will be able to add data to the custom post type's archive page.
Version:0.2
Author:Yuya Tajima
*/

class PostTypeArchiveMeta
{
	protected static $plugin_name;

	protected $plugin_title;

	protected $plugin_data;

	protected $main_menu_slug;
	protected $main_page_nonce;

	protected $post_types = '';
	protected $updates;

	const CAPABILITY         = 'level_7';
	const MAIN_INIT_CALLBACK = 'addSettingMenu';

	const MAIN_PAGE_SLUG    = 'main';
	const MAIN_PAGE_TITLE   = 'ディスクリプション';
	const MAIN_PAGE_DESC    = 'のアーカイブにディスクリプションを設定します。';
	const MAIN_CALLBACK     = 'setPage';
	const MAIN_NONCE_NAME   = 'main-nonce-name';
	const MAIN_NONCE_ACTION = 'main-nonce-action';
	const MAIN_PAGE_SUBMIT  = 'Save';
	const MAIN_EXEC_FUNC    = 'execAction';
	const MAIN_ADMIN_STYLE  = 'adminPrintStyle';
	const THIS_PAGE         = 'thispage';

	const NONCE_NAME   = 'nonce_name';
	const NONCE_ACTION = 'nonce_action';

	const SUBMIT_NAME = 'submit';

	const DESC         = 'description';
	const META_DESC    = 'meta_description';
	const META_KEYWORD = 'meta_keyword';

	const POST_TYPE = 'post_type';

	public function __construct()
	{
		self::$plugin_name = strtolower(get_called_class());
		$this->plugin_data = get_file_data(__FILE__, array('pluginname' => 'Plugin Name', 'version' => 'Version'));

		$this->mainPageInit();
	}

	protected function mainPageInit()
	{
		add_action('init', array($this, 'init'), 1000);
		$this->main_menu_slug = self::$plugin_name . '-' . self::MAIN_PAGE_SLUG;
		$this->plugin_title = __(self::MAIN_PAGE_TITLE);
		$this->main_page_nonce = $this->createNonce(self::MAIN_NONCE_ACTION, self::MAIN_NONCE_NAME);
		add_action('admin_menu', array($this, self::MAIN_INIT_CALLBACK));
		add_action('admin_init', array($this, self::MAIN_EXEC_FUNC));
		add_action('admin_print_scripts', array($this, self::MAIN_ADMIN_STYLE));
	}

	public function init()
	{
		$this->post_types = get_post_types(array('has_archive' => true));
		$this->updates    = array(self::DESC, self::META_DESC, self::META_KEYWORD);
	}

	public function addSettingMenu()
	{
		foreach($this->post_types as $post_type){
			add_submenu_page( 'edit.php?post_type=' . $post_type, $this->plugin_title, $this->plugin_title, self::CAPABILITY, $this->setPageSlug($post_type), array($this, self::MAIN_CALLBACK));
		}
	}

	protected function isSetPost()
	{
		foreach($this->updates as $v){
			if(! isset($_POST[$v])){
				return false;
			}
		}
		return true;
	}

	public function execAction()
	{
		$_POST = stripslashes_deep( $_POST );

		if(is_admin() && $this->isSetPost()){

			$this->check_admin_referer($this->createNonce($_POST[self::THIS_PAGE], $_POST[self::POST_TYPE]));
			foreach($this->updates as $v){
				$result = add_option(self::getOptionKey($v, $_POST[self::POST_TYPE]), $_POST[$v], '', 'no');
				if(! $result){
					update_option(self::getOptionKey($v, $_POST[self::POST_TYPE]), $_POST[$v]);
				}
			}
		}
	}

	public function setPage()
	{
		global $plugin_page;

		if(isset($_GET['post_type'])){
			$post_type = $_GET['post_type'];
			$title = $this->setPagetitle($_GET['post_type']);
		}else{
			wp_die('Don\'t allow');
		}
?>
		<div class="wrap">
			<h2><?php echo __($title . self::MAIN_PAGE_TITLE); ?></h2>
			<form action="" method="post">
			<h3><?php echo __($title . self::MAIN_PAGE_DESC); ?></h3>
			<div class="form-field">
				<h4>説明文</h4>
				<textarea cols="40" rows="5" name="<?php echo esc_attr(self::DESC); ?>"><?php echo $this->getOption(self::DESC, $post_type); ?></textarea>
				<h4>メタディスクリプション</h4>
				<textarea cols="40" rows="5" name="<?php echo esc_attr(self::META_DESC); ?>"><?php echo $this->getOption(self::META_DESC, $post_type); ?></textarea>
				<h4>メタキーワード</h4>
				<textarea cols="40" rows="5" name="<?php echo esc_attr(self::META_KEYWORD); ?>"><?php echo $this->getOption(self::META_KEYWORD, $post_type); ?></textarea>
			</div>
			<input type="hidden" name="<?php echo self::THIS_PAGE; ?>" value="<?php echo esc_attr($plugin_page); ?>" />
			<input type="hidden" name="<?php echo self::POST_TYPE; ?>" value="<?php echo esc_attr($post_type); ?>" />
			<?php $this->createNonceField($this->createNonce($plugin_page, $post_type)); ?>
			<?php submit_button(__(self::MAIN_PAGE_SUBMIT)); ?>
			</form>
		<div><!-- .wrap -->
<?php
	}


	public static function getOption($type, $post_type)
	{
		return get_option(self::getOptionKey($type, $post_type));
	}

	protected static function getOptionKey($type, $post_type)
	{
		return self::$plugin_name . '-' . $type . '-' . $post_type;
	}

	protected function echoMessage($status, $msg)
	{
		printf('<div class="%1$s fade"><p><strong>%2$s</strong></p></div>', $status, $msg);
	}

	protected function createNonce($action, $name)
	{
		$_action = self::$plugin_name . '-' . $action;
		$_name = self::$plugin_name . '-' . $name;

		return array( self::NONCE_ACTION =>  $_action, self::NONCE_NAME => $_name);
	}

	protected function check_admin_referer($nonce)
	{
		check_admin_referer($nonce[self::NONCE_ACTION], $nonce[self::NONCE_NAME]);
	}

	protected function createNonceField($nonce)
	{
		wp_nonce_field($nonce[self::NONCE_ACTION], $nonce[self::NONCE_NAME]);
	}

	protected function isThisPage($page)
	{
		global $plugin_page;

		if( !isset($plugin_page) || ($plugin_page !== $page)){
			return false;
		}else{
			return true;
		}
	}

	protected function setFormName($arr)
	{
		array_unshift($arr, self::$plugin_name);
		$str = implode('-', $arr);

		return $str;
	}

	protected function setPageSlug($slug)
	{
		return self::$plugin_name . '-' . $slug;
	}

	protected function setPagetitle($title)
	{
		return get_post_type_object($title)->label;
	}

	public function adminPrintStyle()
	{
		if(! $this->isThisPage($this->main_menu_slug)){
			return;
		}
?>
		<style type="text/css">
			h3 {
				color:#424242;
			}
		</style>
<?php
	}
}

new PostTypeArchiveMeta;


function get_post_type_info($type, $post_type){
	return PostTypeArchiveMeta::getOption($type, $post_type);
}
