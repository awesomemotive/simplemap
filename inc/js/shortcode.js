/**
* The file that loads and configures the button in the editor
*/
(function ($) {
    tinymce.PluginManager.add('simplemap_button', function(editor, url) {
        editor.addButton('simplemap_button', {
            icon    : 'simplemap',
            tooltip : simple_map_js_array.i10n['title_button'],
            onclick : function (e) {
                editor.windowManager.open( {
                    // unescape the html to embed it
                    title   : simple_map_js_array.i10n['title_window'],
                    html    : $( '<div/>' ).html(simple_map_js_array.html).text(),
					inline  : 1,
                    width   : 500,
                    height  : 300,
                    buttons: [{
                        text: simple_map_js_array.i10n['title_insert_button'],
                        classes: 'sm-insert-shortcode',
                        onclick: function( e ) {
                            // wrap it with a div and give it a class name
                            editor.insertContent( sm_getAttributes($) );
                            editor.windowManager.close();
                        }
                    },
                    {
                        text: simple_map_js_array.i10n['title_cancel_button'],
                        classes: 'sm-cancel-shortcode',
                        onclick: function( e ) {
                            editor.windowManager.close();
                        }
                    }],
                    onopen: function( e ) {
                        sm_initChosen($);
                    }
                });
            }
        });
    });
})(jQuery);