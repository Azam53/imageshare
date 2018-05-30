tinyMCEPopup.requireLangPack();

var ExampleDialog = {
    init : function() {
        var f = document.forms[0];

        // Get the selected contents as text and place it in the input
        f.someval.value = tinyMCEPopup.editor.selection.getContent({format : 'text'});
        f.somearg.value = tinyMCEPopup.getWindowArg('some_custom_arg');
    },

    insert : function() {
        // Insert the contents from the input into the document
        tinyMCEPopup.editor.execCommand('mceInsertContent', false, '<img title="viproses zwart logo" alt="viproses zwart logo" src="http://viproses.imageshare.app/api/get_image/19352">');
        tinyMCEPopup.close();
    }
};

tinyMCEPopup.onInit.add(ExampleDialog.init, ExampleDialog);