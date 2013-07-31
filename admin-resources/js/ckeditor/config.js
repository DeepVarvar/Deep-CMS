/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {

    // variables.language known from global scope!
    config.language = variables.language;

    //config.emailProtection = 'encode',


    /**
     * this is fix for &lt; and &gt;
     * need defined three submasks
     * first submask like start selection marker (opened tag)
     * last submask like end selection marker (closed tag)
     * and middle mask like source viewed code
     * now configured for <code> tag
     */

    config.protectedViewedCode = [];
    config.protectedViewedCode.push( /(<code[^>]*>)([\s\S]*?)(<\/code>)/g );


    //config.removeButtons = 'Underline,Subscript,Superscript';
    config.allowedContent = true;
    config.uiColor = "#bfbdb2";
    config.removePlugins = 'entities,elementspath,save,about,forms,a11yhelp,dialogadvtab,templates,div,smiley,newpage,iframe,scayt';
    config.height = 240;

    config.toolbar = [

        [ "Source" ],
        [ "ShowBlocks", "-", "Maximize", "-", "Preview", "-", "Print" ],
        [ "Undo", "-", "Redo" ],
        [ "SelectAll", "Cut", "Copy", "Paste", "PasteText", "PasteFromWord", "Replace", "Find" ],
        [ "RemoveFormat", "-", "Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript", "-", "TextColor", "BGColor" ],
        [ "NumberedList", "BulletedList", "Blockquote", "Table", "HorizontalRule" ],
        [ "Outdent", "Indent", "-", "JustifyLeft", "JustifyCenter", "JustifyRight", "JustifyBlock", "-", "BidiLtr", "BidiRtl" ],
        [ "Link", "Unlink", "Anchor", "-", "Image", "-", "oembed", "-", "SpecialChar" ],
        [ "Styles", "Format" ]

    ];

    //config.toolbar = null;

};
