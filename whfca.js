jQuery(document).ready(function($){

    //Prepopulating our quick-edit post info
    var $inline_editor = inlineEditPost.edit;
    inlineEditPost.edit = function(id){

        //call old copy 
        $inline_editor.apply( this, arguments);

        //our custom functionality below
        var post_id = 0;
        if( typeof(id) == 'object'){
            post_id = parseInt(this.getId(id));
        }

        //if we have our post
        if(post_id != 0){

            //find our row
            $row = $('#edit-' + post_id);
            $wsr_woo_hidecart = $('#wsr_woo_hidecart_' + post_id);
            if ($wsr_woo_hidecart.text() == 'true'){
                $row.find('input[name="wsr_woo_hidecart"]').prop('checked', true);
            }

        }

    }

});