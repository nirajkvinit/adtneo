<?php

add_action('wp_ajax_fn_adt_ajax' , 'fn_adt_ajax');
add_action('wp_ajax_nopriv_fn_adt_ajax' , 'fn_adt_ajax');

add_shortcode('adore-datatables' , 'fn_adore_datatables_shortcode');

/**
 * Shortcode tags are:
 * [adore-datatables id="1"]
 * Or
 * [adore-datatables table="adore-demo"]
 */
function fn_adore_datatables_shortcode($atts , $content = null) {
    /* extract(shortcode_atts(array(
      'id' => null,
      'table' => null
      ), $atts,'adore-datatables'));
     */

    $atts = shortcode_atts(
        array(
        'id' => null ,
        'table' => null ,
        ) , $atts , 'adore-datatables');

    $str_return = fn_adore_datatables_maker($atts);

    return $str_return;
}

/**
 *
 * @global type $wpdb
 * @global array $adt_global
 * @param type $atts
 * @return type
 * @author lenasterg
 */
function fn_adore_fetch_adt($atts) {
    global $wpdb , $adt_global;

    if ( !is_array($adt_global) ) {
        $adt_global = array();
    }

    $adt_id = $atts['id'];
    $adt_slug = $atts['table'];


    $str_sql = "";
    if ( !empty($adt_slug) ) {
        $adt_slug = sanitize_text_field($adt_slug);
        $str_sql = "SELECT * FROM adore_datatable_settings WHERE adt_table_slug='$adt_slug' AND adt_blog_id=". get_current_blog_id();
    } else if ( !empty($adt_id) && is_numeric($adt_id) ) {
        $str_sql = "SELECT * FROM adore_datatable_settings WHERE adt_id=$adt_id AND adt_blog_id=". get_current_blog_id();
    } else {
        return ;
    }

    $adt_datatable_dataset = $wpdb->get_row($str_sql);

    return $adt_datatable_dataset;
}

/**
 *
 * @global type $wpdb
 * @global type $post
 * @global array $adt_global
 * @global type $adt_options
 * @param type $atts
 * @return string
 * @version 2, lenasterg
 */
function fn_adore_datatables_maker($atts) {

    global $wpdb , $post , $adt_global , $adt_options;

    $adt_datatable_dataset = fn_adore_fetch_adt($atts);


    if ( empty($adt_datatable_dataset) ) {
        return '<div class="error">'.__('Error! Adore Datatable information was not found in the database. Please check the shortcode tag.','adt').'</div>';
    }

    $adt_id = $atts['id'];
    $adt_slug = $atts['table'];

    $post_id = $post->ID;

    $adt_name = '';

    $adt_table_settings = '';


    $adt_table_settings = json_decode($adt_datatable_dataset->adt_table_settings , TRUE);
    $adt_id = $adt_datatable_dataset->adt_id;
    $adt_name = $adt_datatable_dataset->adt_table_name;
    $adt_table_slug = $adt_datatable_dataset->adt_table_slug;


    //check if the same Adore Datatable instance has been called on the post earlier or not. Duplicate not allowed.
    if ( array_key_exists($post_id , $adt_global) ) {
        $adt_array = $adt_global[$post_id];
        if ( is_array($adt_array) ) {
            if ( array_key_exists($adt_table_slug , $adt_array) ) {
                return 'Adore Datatable "' . $adt_name . '" had been called earlier in the post. An Adore Datatable instance can be called only once in a page/post.';
            }
        }
    }

    $adt_global[$post_id][$adt_table_slug] = $adt_table_settings;

    $html_table_id = $adt_table_settings['html_table_id'];
    $html_table_class = $adt_table_settings['html_table_class'];
    $table_type = $adt_table_settings['table_type'];
    $database_table_name = $adt_table_settings['database_table_name'];
    $columns_array = $adt_table_settings['columns_array'];

    //datatable global options
    if ( !isset($adt_options) ) {
        $adt_admin_config = get_option('adt_admin_config');
        if ( $adt_admin_config == FALSE ) {
            $adt_admin_config_array = array();
            $adt_admin_config_array['adt_style'] = 'base_style';
            $adt_admin_config_array['jquery_theme'] = 'smoothness';
            $adt_admin_config_array['load_jquery'] = 'enabled';
            $adt_admin_config_array['load_bootstrap'] = 'enabled';

            $adt_admin_config = json_encode($adt_admin_config_array);
            $deprecated = null;
            $autoload = 'no';
            add_option('adt_admin_config' , $adt_admin_config , $deprecated , $autoload);
        }
        $adt_admin_config = json_decode($adt_admin_config , TRUE);
        //set global option for adore datatable if not available.
        $adt_options = $adt_admin_config;
    }

    $adt_style = $adt_options['adt_style'];

    switch ($adt_style) {
        case 'base_style':
            $html_table_class .= ' display';
            break;
        case 'base_style_noclass':
            break;
        case 'base_style_cell_borders':
            $html_table_class .= ' cell-border';
            break;
        case 'base_style_compact':
            $html_table_class .= ' display compact';
            break;
        case 'base_style_hover':
            $html_table_class .= ' hover';
            break;
        case 'base_style_order_column':
            $html_table_class .= ' order-column';
            break;
        case 'base_style_row_borders':
            $html_table_class .= ' row-border';
            break;
        case 'base_style_stripe':
            $html_table_class .= ' stripe';
            break;
        case 'bootstrap':
            $html_table_class .= ' table table-striped table-bordered';
            break;
        case 'jqueryui_themeroller':
            $html_table_class .= ' display';
            break;
    }

    $str_table_id = '';
    if ( empty($html_table_id) ) {
        $html_table_id = $adt_table_slug;
    }
    $str_table_id = ' id="' . $html_table_id . '"';
    $str_table = '
	<table ' . $str_table_id . ' class="' . $html_table_class . '" cellspacing="0" width="100%">
			<thead>
				<tr>

	';

    $total_columns = count($columns_array);

    foreach ( $columns_array as $column_data ) {
        $str_table .= '<th>' . $column_data['column_title'] . '</th>';
    }
    $str_table .= '
				</tr>
			</thead>
	';

    $str_table .= '
			<tbody>
	';
    if ( $table_type == 'server' ) {
        $str_table .= '
			<tr>
				<td colspan="' . $total_columns . '" class="dataTables_empty">'.__('Loading data from server','adt').'</td>
			</tr>
		';
    } else {
        $adt_table_rows = $wpdb->get_results("SELECT * FROM $database_table_name");
        if ( empty($adt_table_rows) ) {
            $str_table .= '
				<tr>
					<td colspan="' . $total_columns . '" class="dataTables_empty">'.__('Data not available in the database.','adt').'</td>
				</tr>
			';
        } else {
            foreach ( $adt_table_rows as $adt_table_row ) {
                $str_table .= '
					<tr>
				';

                foreach ( $columns_array as $column_data ) {
                    $column_name = $column_data['column_name'];
                    $str_table .= '<td>' . $adt_table_row->$column_name . '</td>';
                }

                $str_table .= '
					</tr>
				';
            }
        }
    }

    $str_table .= '
			</tbody>
		</table
	';

    return $str_table;
}
