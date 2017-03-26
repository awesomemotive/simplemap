<?php
$categories = get_categories( array( 'taxonomy' => 'sm-category', 'hide_empty' => false, 'fields' => 'id=>name' ) );
$tags       = get_terms( array( 'taxonomy' => 'sm-tag', 'hide_empty' => false, 'fields' => 'id=>name' ) );
?>

<div id="simplemap-button-modal">
    <form id="simple_map_shortcode">
    <fieldset>
        <label for="simplemap_category"><?php _e( 'Category', 'SimpleMap' );?></label>
        <select name="categories" id="simplemap_category" class="simplemap-chosen" data-placeholder="<?php _e( 'Select Categories', 'SimpleMap' );?>" multiple>
<?php
    foreach ( $categories as $id => $name ) {
?>
        <option value="<?php echo $id;?>"><?php echo $name;?></option>
<?php
    }
?>
        </select>
    </fieldset>
    <fieldset>
        <label for="simplemap_location"><?php _e( 'Tags', 'SimpleMap' );?></label>
        <select name="tags" id="simplemap_location" class="simplemap-chosen" data-placeholder="<?php _e( 'Select Tags', 'SimpleMap' );?>" multiple>
<?php
    foreach ( $tags as $id => $name ) {
?>
        <option value="<?php echo $id;?>"><?php echo $name;?></option>
<?php
    }
?>
        </select>
    </fieldset>
    <fieldset>
        <label for="show_categories_filter"><?php _e( 'Category Filter', 'SimpleMap' );?></label>
        <label for="simplemap_category_filter_show"><?php _e( 'Show', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_category_filter_show" name="show_categories_filter" value="true" checked>
        <label for="simplemap_category_filter_hide"><?php _e( 'Hide', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_category_filter_hide" name="show_categories_filter" value="false">
    </fieldset>
    <fieldset>
        <label for="show_tags_filter"><?php _e( 'Tag Filter', 'SimpleMap' );?></label>
        <label for="simplemap_tag_filter_show"><?php _e( 'Show', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_tag_filter_show" name="show_tags_filter" value="true" checked>
        <label for="simplemap_tag_filter_hide"><?php _e( 'Hide', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_tag_filter_hide" name="show_tags_filter" value="false">
    </fieldset>
    <fieldset>
        <label for="hide_map"><?php _e( 'Map', 'SimpleMap' );?></label>
        <label for="simplemap_map_show"><?php _e( 'Show', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_map_show" name="hide_map" value="false" checked>
        <label for="simplemap_map_hide"><?php _e( 'Hide', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_map_hide" name="hide_map" value="true">
    </fieldset>
    <fieldset>
        <label for="hide_list"><?php _e( 'List of results', 'SimpleMap' );?></label>
        <label for="simplemap_list_show"><?php _e( 'Show', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_list_show" name="hide_list" value="false" checked>
        <label for="simplemap_list_hide"><?php _e( 'Hide', 'SimpleMap' );?></label>
        <input type="radio" id="simplemap_list_hide" name="hide_list" value="true">
    </fieldset>
    <fieldset>
        <label for="default_lat"><?php _e( 'Default Lat', 'SimpleMap' );?></label>
        <input type="text" id="default_lat" name="default_lat">
        <label for="default_lon"><?php _e( 'Default Lon', 'SimpleMap' );?></label>
        <input type="text" id="default_lon" name="default_lon">
    </fieldset>
    </form>
</div>