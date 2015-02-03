/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(document).ready(function() {
    jQuery(".custom_link_detach_category").on("click", function() {
        var id = jQuery(this).attr("id");
        var arrVal = id.split("_");
        var cat_id = arrVal[1];
        var post_ids = arrVal[0];
        jQuery.ajax({
            "url": ajaxurl,
            "type": "post",
            dataType: 'json',
            "data": {
                "post": post_ids,
                "cat": cat_id,
                "action": "dtxo_detach_category"
            },
            "success": function(resp) {
                if (resp.ack == '1') {
                    jQuery("#post-" + post_ids).remove();
                    if (!jQuery("#the-list tr").length) {
                        jQuery("#the-list").html('<tr class="no-items"><td colspan="8"class="colspanchange">No posts found.</td></tr>');
                    }
                }
            }
        });
        return false;
    });
});
