<?php
/**
 * @package jltfavorites
 * @version 1.0
 */
/*
Plugin Name: JLTFavorites
Description: Permet de générer automatiquement une liste de favoris
Author: JL TRYOEN
Version: 1.0.1
Author URI: http://www.jltryoen.Fr
*/
define('_JEXEC', 1);
require_once(dirname(__FILE__) . "/lib/readsync.body.php");

// [favorites]
function favorites_func( $args, $content=null ) {
    wp_enqueue_style( 'css3treeview' );
    wp_enqueue_script( 'css3treeview' );
    $uriroot = "http://joomla.jltryoen.fr/";
    wp_add_inline_script( 'css3treeview', 'var uriroot="' . $uriroot  .'";', 'before');
    $input="";
    if (!array_key_exists('output', $args))	{
        $args['output'] = 'css3treeview';
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    $args['jsonfile'] = realpath(get_home_path() . "./joomla_5.0/files/jofavorites/bookmarks.home2.json");
    readsync($args, $input);
    return str_replace("\n", "", $input);
}

add_shortcode( 'favorites', 'favorites_func' );
wp_register_style( 'css3treeview', plugin_dir_url( __FILE__ ) . '/media/css/css3treeview.css');
wp_register_script( 'url', plugin_dir_url( __FILE__ ) . '/media/js/url.min.js', array(), '1.0', false);
wp_register_script( 'css3treeview', plugin_dir_url( __FILE__ ) . '/media/js/favorites.js', array('jquery', 'url'), '1.0', false);

