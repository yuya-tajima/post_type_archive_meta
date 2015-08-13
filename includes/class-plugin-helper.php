<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'DtPluginHelper' ) ) {

  abstract class DtPluginHelper
  {
    const VERSION = 1.1;

    protected static $instance = null;

    protected static $plugin_root_slug = NULL;
    protected $update_names = array();

    const NONCE_NAME   = 'nonce_name';
    const NONCE_ACTION = 'nonce_action';
    const SUBMIT_NAME  = 'submit';
    const PAGE_SUBMIT  = 'Save';

    protected function __construct()
    {
      static::$plugin_root_slug = strtolower( get_called_class() );
    }

    protected static function getRootSlug ()
    {
      return static::$plugin_root_slug;
    }

    protected function maybeThisPluginPage( )
    {
      global $plugin_page;

      if( ! isset( $plugin_page ) || ( strpos( $plugin_page, static::getRootSlug() ) !== 0 ) ){
        return false;
      } else {
        return true;
      }
    }

    protected function setUpdateNames( $names )
    {
      $this->update_names = $names;
    }

    protected function getUpdateNames ()
    {
      return $this->update_names;
    }

    protected function isSetPostData( $prefix = '' )
    {
      $prefix = ( $prefix ) ? $prefix . '-' : '';
      $this->setUpdateNames( array_merge( $this->getUpdateNames(), apply_filters( $prefix . static::getRootSlug() .'-isSetPostData', array() ) ) );
      foreach( $this->getUpdateNames() as $v ){
        if( ! isset( $_POST[$v] ) ){
          return false;
        }
      }
      return true;
    }

    protected function check_admin_referer( array $nonce )
    {
      check_admin_referer( $nonce[static::NONCE_ACTION], $nonce[static::NONCE_NAME] );
    }

    protected function createNonce( $action = '', $name = '_wpnonce' )
    {
      $_action = ( $action ) ? static::getRootSlug() . '-' . $action : static::getRootSlug();
      $_name   = static::getRootSlug() . '-' . $name;

      return array( static::NONCE_ACTION =>  $_action, static::NONCE_NAME => $_name);
    }

    protected function createNonceField( $nonce, array $check_hidden = array() )
    {
      if ( $check_hidden && is_array( $check_hidden ) ) {
        foreach ( $check_hidden as $k => $v) {
?>
          <input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>" />
<?php
        }
      }
      wp_nonce_field( $nonce[static::NONCE_ACTION], $nonce[static::NONCE_NAME] );
    }

    protected function echoMessage( $status, $msg )
    {
      printf( '<div class="%1$s fade"><p><strong>%2$s</strong></p></div>', esc_attr( $status ), esc_html( $msg ) );
    }

    protected function getPageSlug( $slug )
    {
      return static::getRootSlug() . '-' . $slug;
    }

    public static function getInstance()
    {
      if ( ! isset( static::$instance ) ) {
        static::$instance = new static();
      }
      return static::$instance;
    }
  }
}
