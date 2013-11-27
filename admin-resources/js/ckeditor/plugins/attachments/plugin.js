

CKEDITOR.plugins.add('attachments', {
    icons: 'attachments',
    init: function( editor ) {
        editor.addCommand('attachmentsDialog', new CKEDITOR.dialogCommand( 'abbrDialog'));
        editor.ui.addButton('Attachments', {
            label: 'Insert Abbreviation',
            command: 'attachmentsDialog',
            toolbar: 'insert'
        });
        CKEDITOR.dialog.add('attachmentsDialog', this.path + 'dialogs/attachments.js');
    }
});


