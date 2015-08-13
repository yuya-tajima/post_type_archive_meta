<?php
/*
Plugin Name: PostTypeArchiveMeta
Description: You will be able to add data to the custom post type's archive page.
Version:1.0.0
Author:Yuya Tajima ( Prime Strategy Co.,Ltd. )
Author URI: http://tajima-taso.jp
Plugin URI: https://github.com/yuya-tajima/post_type_archive_meta
Text Domain: post_type_archive_meta
Domain Path: /languages
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'PostTypeArchiveMeta' ) ) {
  return;
}

require __DIR__ . '/includes/class-plugin-helper.php';

class PostTypeArchiveMeta extends DtPluginHelper
{
  private $plugin_title;
  private $post_types = array();
  private $is_update  = false;

  const TEXT_DOMAIN      = 'post_type_archive_meta';
  const CAPABILITY       = 'level_7';

  const MAIN_PAGE_TITLE  = 'Meta Data';
  const MAIN_PAGE_DESC   = 'Addition data for %s';
  const MAIN_CALLBACK    = 'setAdminFormPage';

  const THIS_PAGE_KEY    = 'ptam-thispluginpage';
  const H1_WORDS_KEY     = 'h1';
  const DESC_KEY         = 'description';
  const CONT_KEY         = 'content';
  const META_TITLE_KEY   = 'meta_title';
  const META_DESC_KEY    = 'meta_description';
  const META_KEYWORD_KEY = 'meta_keyword';
  const IMAGE_KEY        = 'image';
  const POST_TYPE_KEY    = 'ptam_post_type';

  protected function __construct()
  {
    parent::__construct();

    load_plugin_textdomain( static::TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    $this->plugin_title = __( static::MAIN_PAGE_TITLE, static::TEXT_DOMAIN );

    add_action( 'init',                  array( $this, 'postTypesInit' ), 99999 );
    add_action( 'admin_menu',            array( $this, 'menuInit' ) );
  }

  public function postTypesInit ()
  {
    $post_types       = get_post_types( array( 'has_archive' => true ) );
    $this->post_types = apply_filters( 'post_type_archive_meta_post_types', $post_types );
  }

  public function menuInit()
  {
    if ( ! $this->post_types ) {
      return;
    }

    foreach( $this->post_types as $post_type ){

      $parent_slug = 'edit.php?post_type=' . $post_type;
      $menu_slug   = $this->getPageSlug( $post_type );

      add_submenu_page(
        $parent_slug,
        $this->plugin_title,
        $this->plugin_title,
        static::CAPABILITY,
        $menu_slug,
        array( $this, static::MAIN_CALLBACK )
      );

      add_action( 'load-' . get_plugin_page_hookname( $menu_slug, $parent_slug ), array( $this, 'enqueueResource' ) );
      add_action( 'load-' . get_plugin_page_hookname( $menu_slug, $parent_slug ), array( $this, 'update' ) );
    }
  }

  public function enqueueResource ()
  {
    add_action( 'admin_print_scripts',   array( $this, 'adminPrintStyle' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
  }

  public function update()
  {
    if( ! is_admin() ){
      return;
    }

    if ( empty( $_POST ) ) {
      return;
    }

    $update_names = array(
      static::H1_WORDS_KEY,
      static::DESC_KEY,
      static::CONT_KEY,
      static::META_DESC_KEY,
      static::META_KEYWORD_KEY,
      static::META_TITLE_KEY,
      static::IMAGE_KEY,
      static::THIS_PAGE_KEY,
      static::POST_TYPE_KEY,
    );

    $this->setUpdateNames( apply_filters( 'post_type_archive_meta_update_names', $update_names ) );

    if ( $this->isSetPostData() ) {

      $_POST = stripslashes_deep( $_POST );

      $this->check_admin_referer( $this->createNonce( $_POST[static::THIS_PAGE_KEY], $_POST[static::POST_TYPE_KEY] ) );

      foreach( $this->getUpdateNames() as $v ){

        $_POST[$v] = trim( $_POST[$v] );

        if ( $_POST[$v] === '' ) {
          delete_option( $this->getDataKey( $v, $_POST[static::POST_TYPE_KEY] ) );
        } else {
          $result = add_option( $this->getDataKey( $v, $_POST[static::POST_TYPE_KEY] ), $_POST[$v], '', 'no' );
          if( ! $result ){
            update_option( $this->getDataKey( $v, $_POST[static::POST_TYPE_KEY] ), $_POST[$v] );
          }
        }
      }
      do_action( 'post_type_archive_meta_update_after' );

      $this->is_update = true;
    }

    add_action( 'ptam_update_msg', array( $this, 'updateMsg' ) );
  }

  public function updateMsg ()
  {
    if ( $this->is_update ) {
      $status = 'updated';
      $msg    = 'Success!';
    } else {
      $status = 'error';
      $msg    = 'Failure!';
    }

    $this->echoMessage( $status, $msg );
  }

  protected function getDataKey( $key, $post_type )
  {
    return static::getRootSlug() . '-' . $key . '-' . $post_type;
  }

  public function getData( $key, $post_type )
  {
    return get_option( $this->getDataKey( $key, $post_type ) );
  }

  protected function getPageTitle( $post_type )
  {
    return get_post_type_object( $post_type )->label;
  }

  public function adminPrintStyle()
  {
    ?>
      <style type="text/css">
      h3 {
        color:#424242;
      }
      .ptam-image-area {
        margin-bottom:10px;
      }
    </style>
      <?php
  }

  public function getImage ( $key, $post_type, $size = 'full', $icon = false, $attr = array() )
  {
    $image_id = $this->getData( $key, $post_type );

    if ( ! $image_id ) {
      return '';
    }

    $image_html = wp_get_attachment_image( $image_id, $size, $icon, $attr );

    return $image_html;
  }

  public function enqueueScripts ()
  {
    wp_enqueue_script( 'ptam-js', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), '', true );
  }

  public function setAdminFormPage()
  {
    global $plugin_page;

    // exception may not be thrown. just making sure.
    try {
      if( is_admin() && ! empty( $_GET['post_type'] ) ){
        if ( ! in_array( $_GET['post_type'], $this->post_types, true ) ) {
          throw new Exception( 'You don\'t have permission to access this page.' );
        }
        $post_type  = $_GET['post_type'];
        $page_title = $this->getPageTitle( $post_type );
      } else {
        throw new Exception( 'You don\'t have permission to access this page.' );
      }
    } catch ( Exception $e ) {
      wp_die( esc_html( $e->getMessage() ) );
    }

    wp_enqueue_media();

    ?>
      <div class="wrap ptam-wrap">
        <h2><?php echo esc_html( $page_title . ' ' . __( static::MAIN_PAGE_TITLE, static::TEXT_DOMAIN ) ); ?></h2>
        <?php do_action( 'ptam_update_msg', array( $this, 'updateMsg' ) ); ?>

        <form action="" method="post">
          <h3><?php echo esc_html( sprintf( __( static::MAIN_PAGE_DESC, static::TEXT_DOMAIN ), $page_title ) ); ?></h3>
          <p><?php echo __( 'Usage', static::TEXT_DOMAIN ); ?>: get_post_type_meta( $key, $post_type ). <?php echo __( 'use the following key nemes.', static::TEXT_DOMAIN );  ?></p>
          <div class="form-field">
            <h4><?php echo __( 'H1', static::TEXT_DOMAIN );  ?> ( key name is <?php echo esc_html( static::H1_WORDS_KEY ); ?>)</h4>
            <textarea cols="40" rows="5" name="<?php echo esc_attr( static::H1_WORDS_KEY ); ?>"><?php echo esc_textarea( $this->getData( static::H1_WORDS_KEY, $post_type ) ); ?></textarea>
              <h4><?php echo __( 'Description', static::TEXT_DOMAIN ); ?> ( key name is <?php echo esc_html( static::DESC_KEY ); ?>)</h4>
            <textarea cols="40" rows="5" name="<?php echo esc_attr( static::DESC_KEY ); ?>"><?php echo esc_textarea( $this->getData( static::DESC_KEY, $post_type ) ); ?></textarea>
            <h4><?php echo __( 'Content', static::TEXT_DOMAIN );  ?> ( key name is <?php echo esc_html( static::CONT_KEY ); ?>)</h4>
            <?php wp_editor( $this->getData( static::CONT_KEY, $post_type ), static::CONT_KEY, array( 'editor_class' => 'ptam-editor' , 'textarea_rows' => 20 ) ); ?>
            <h4><?php echo __( 'Meta Title', static::TEXT_DOMAIN ); ?> ( key name is <?php echo esc_html( static::META_TITLE_KEY); ?>)</h4>
            <textarea cols="40" rows="5" name="<?php echo esc_attr( static::META_TITLE_KEY ); ?>"><?php echo esc_textarea( $this->getData( static::META_TITLE_KEY, $post_type ) ); ?></textarea>
            <h4><?php echo __( 'Meta Description', static::TEXT_DOMAIN ); ?> ( key name is <?php echo esc_html( static::META_DESC_KEY ); ?>)</h4>
            <textarea cols="40" rows="5" name="<?php echo esc_attr( static::META_DESC_KEY ); ?>"><?php echo esc_textarea( $this->getData( static::META_DESC_KEY, $post_type ) ); ?></textarea>
            <h4><?php echo __( 'Meta Keyword', static::TEXT_DOMAIN ); ?> ( key name is <?php echo esc_html( static::META_KEYWORD_KEY ); ?>)</h4>
            <textarea cols="40" rows="5" name="<?php echo esc_attr( static::META_KEYWORD_KEY ); ?>"><?php echo esc_textarea( $this->getData( static::META_KEYWORD_KEY, $post_type ) ); ?></textarea>
            <div>
              <h4><?php echo __( 'Image', static::TEXT_DOMAIN ); ?> ( key name is <?php echo esc_html( static::IMAGE_KEY );  ?>) Note. get attachment id.</h4>
              <div class="ptam-image-area"><?php echo $this->getImage( static::IMAGE_KEY, $post_type );  ?></div>
              <input type="button" class="ptam-upload-btn button-secondary" value="<?php echo __( 'Upload Image', static::TEXT_DOMAIN ); ?>" />
              <input type="button" class="ptam-delete-btn button-secondary" value="<?php echo __( 'Delete Image', static::TEXT_DOMAIN ); ?>" />
              <input type="hidden" class="ptam-image-id" name="<?php echo esc_attr( static::IMAGE_KEY ); ?>" value="<?php echo esc_attr( $this->getData( static::IMAGE_KEY, $post_type ) ); ?>" />
            </div>

            <?php do_action( 'post_type_archive_meta_form_after' ); ?>

          </div>

          <?php $this->createNonceField( $this->createNonce( $plugin_page, $post_type ), array( static::THIS_PAGE_KEY => $plugin_page, static::POST_TYPE_KEY => $post_type) ); ?>
          <?php submit_button( __( static::PAGE_SUBMIT ) ); ?>

        </form>
      <div>
      <?php
  }
}

PostTypeArchiveMeta::getInstance();

if ( ! function_exists( 'get_post_type_meta' ) ) {
  function get_post_type_meta( $key, $post_type ){
    return PostTypeArchiveMeta::getInstance()->getData( $key, $post_type );
  }
}
