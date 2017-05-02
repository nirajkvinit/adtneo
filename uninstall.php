<?php
//Reference http://codex.wordpress.org/Function_Reference/register_uninstall_hook

//if uninstall has not been called from WordPress then exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

//delete Adore Datatable Settings option
$option_name = 'adt_admin_config';
delete_option( $option_name );

//delete Adore Datatable Custom CSS version option
$option_name = 'adt_css_version';
delete_option( $option_name );

//delete Adore Datatable Custom JS version option
$option_name = 'adt_js_version';
delete_option( $option_name );

//drop Adore Datatables custom database table
global $wpdb;

$demo_table_prefix=$wpdb->prefix;

$wpdb->query( "DROP TABLE IF EXISTS ".$demo_table_prefix."adt_demo_table" );

//delete table adore_datatable_settings
$wpdb->query( "DROP TABLE IF EXISTS adore_datatable_settings" );