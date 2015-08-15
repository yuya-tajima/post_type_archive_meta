=== PostTypeArchiveMeta ===
Contributors: tajima_taso
Tags: post types archive
Requires at least: 3.8
Tested up to: 4.2.4
Stable tag: 1.0.0
License: GPLv2 or later

You will be able to add data to the custom post type's archive page.

== Description ==

You will be able to add data to the custom post type's archive page or all post types.

= This Plugin API =

Retrieve meta data field for a post type.

<code>get_post_type_meta( $key, $post_type )</code>

= Customize =

<code>
add_filter( 'post_type_archive_meta_post_types', 'func_name' )
add_filter( 'post_type_archive_meta_update_names', 'func_name' )
add_action( 'post_type_archive_meta_update_after', 'func_name' );
</code>

= Related Links =
 * [Github](https://github.com/yuya-tajima/post_type_archive_meta)

== Installation ==

1. Upload the post_type_archive_meta directory to the plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You will find 'Meta Data' within the custom post type submenu. ( when the post type are enabled has_archive => true ). If you want to use this for all registered post types, use add_filter( 'post_type_archive_meta_post_types', 'func_name' );

== Changelog ==
= 1.0.0 =
* Initial Public Release
