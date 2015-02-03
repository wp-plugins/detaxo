<?php
/*
  Plugin Name: DeTaxo
  Plugin URI:
  Description: Remove post from category/taxonomy on the fly with just one click from post list it self, No need to Quick edit.
  Author: Harish Moghe
  Version: 1.1
  Author URI: http://goo.gl/2zlk6H
 */

function dtxo_add_columns_filter($arr) {
    if (is_array($arr) AND count($arr)) {
        foreach ($arr as $ar) {
            add_filter('manage_' . $ar . '_posts_columns', 'dtxo_manage_post_posts_columns');
        }
    }
}

function dtxo_manage_post_posts_columns($columns) {
    $cate = dtxo_get_categogry_info();
    $categoryName = $cate->name;
    $columns['dtxo_manage_categories'] = esc_html__("$categoryName Category Action");
    return $columns;
}

add_action('manage_posts_custom_column', 'dtxo_add_detaxo_column');

function dtxo_add_detaxo_column($name) {
    wp_register_script('detaxo-script', plugins_url('/js/detaxo.js', __FILE__), array('jquery'));
    wp_enqueue_script('detaxo-script');
    global $post;
    $cate = dtxo_get_categogry_info();
    $categoryId = $cate->term_taxonomy_id;
    switch ($name) {
        case 'dtxo_manage_categories':
            echo "<a title='Remove this post from " . $cate->name . "' class='custom_link_detach_category' id=" . $post->ID . "_" . $categoryId . " href=''>Detach</a>";
            break;
    }
}

add_action('admin_menu', 'dtxo_add_detaxo_menu');

function dtxo_add_detaxo_menu() {
    add_options_page("DeTaxo Settings", "DeTaxo Settings", "manage_categories", "detaxo-setting", "dtxo_detaxo_setting_page");
}

add_action("init", "dtxo_add_detaxo_default_settings");
add_action("init", "dtxo_add_detaxo_filter");

function dtxo_add_detaxo_default_settings() {
    $postTypes = dtxo_get_post_types();
    $checkedPostTypes = array_values($postTypes);
    $dbOptions = (get_option("detaxo_settings")) ? get_option("detaxo_settings") : NULL;
    if (NULL == $dbOptions) {
        update_option("detaxo_settings", $checkedPostTypes);
    }
}

function dtxo_add_detaxo_filter() {
    $cate = dtxo_get_categogry_info();
    $catId = $cate->term_taxonomy_id;
    if ($catId) {
        $dbOptions = (get_option("detaxo_settings")) ? get_option("detaxo_settings") : array();
        dtxo_add_columns_filter($dbOptions);
    }
}

function dtxo_detaxo_setting_page() {
    $imageURl = plugins_url("images/detaxo.jpg", __FILE__);
    $postTypes = dtxo_get_post_types();
    $dbOptions = (get_option("detaxo_settings")) ? get_option("detaxo_settings") : array();
    $settingsSaved = 0;
    if (isset($_POST) && NULL != $_POST) {
        $checkedPostTypes = $_POST['post_type_chck'];
        update_option("detaxo_settings", $checkedPostTypes);
        $dbOptions = $_POST['post_type_chck'];
        $settingsSaved = 1;
    }
    if (NULL == $dbOptions) {
        $dbOptions = array();
    }
    ?>
    <div class="wrap">
        <h2>DeTaxo Settings</h2>
        <?php if ($settingsSaved) { ?>
            <div class="updated settings-error" id="setting-error-settings_updated"> 
                <p><strong>Settings saved.</strong></p>
            </div>
        <?php } ?>
        <div class="tool-box">

            <h3 class="title">Select the post types to apply DeTaxo</h3>
            <p>DeTaxo, if enabled for post type, Will add column to post list table to de-attach post from certain category.</p>


            <p class="description">
            </p>
        </div>

    </div>
    <form action="" method="post">
        <table>
            <tr><th>Post Type</th><th></th></tr>
            <tr><td></td><td></td></tr>
            <?php foreach ($postTypes as $type) { ?>
                <tr>
                    <td><?php echo $type; ?></td>
                    <td>
                        <input type="checkbox" name="post_type_chck[]" value="<?php echo $type; ?>"  <?php if (in_array($type, $dbOptions)) { ?>checked="true"<?php } ?> >
                    </td> </tr>
            <?php } ?>
        </table>
        <input type="hidden" name="page" value="detaxo-setting">
        <input type="submit" value="submit" class="button button-primary button-large" style="margin-top:30px;"/>
        <div id="image" style="margin-top:30px"><a href="<?php echo $imageURl; ?>"><img hight="500" width="700" src="<?php echo $imageURl; ?>"></a></div>
    </form>
    <?php
}

add_action('wp_ajax_dtxo_detach_category', 'dtxo_detach_category');

function dtxo_detach_category() {
    global $wpdb;
    if (isset($_POST) && count($_POST)) {
        $post_id = $_POST["post"];
        $cat_id = $_POST["cat"];
        $query = "DELETE FROM " . $wpdb->prefix . "term_relationships WHERE object_id = $post_id AND term_taxonomy_id = $cat_id";
        $re = $wpdb->query($query);
        $array = array("ack" => $re);
        echo json_encode($array);
        die;
    }
}

function dtxo_get_categogry_info() {
    $catId = (isset($_REQUEST["cat"])) ? $_REQUEST["cat"] : NULL;
    if ($catId > 1) {
        $terms = get_term_by("id", $catId, "category");
    } else {
        if (isset($_REQUEST['category_name'])) {
            $terms = get_term_by("slug", $_REQUEST['category_name'], "category");
            $catId = $terms->term_taxonomy_id;
        }
    }
    if ($catId > 1) {
        return $terms;
    }
    return false;
}

function dtxo_get_post_types() {
    global $wpdb;
    $query = "SELECT DISTINCT(`post_type`) FROM " . $wpdb->prefix . "posts WHERE post_type NOT IN ('attachment', 'page', 'revision')";
    $resPostTypes = $wpdb->get_results($query);
    $postTypes = array();
    if (is_array($resPostTypes) && count($resPostTypes)) {
        foreach ($resPostTypes as $res) {
            array_push($postTypes, $res->post_type);
        }
    }
    return $postTypes;
}