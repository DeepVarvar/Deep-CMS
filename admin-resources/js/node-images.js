


$(function(){


    var coverblur = $("#coverblur");
    var popupformwrapper = $("#popupformwrapper");
    var popupchooseimage = $("#popupchooseimage");

    var attachedimageswrapper = $("#attachedimageswrapper");
    var uploadform = $("#uploadform");
    var uploadprogress = $("#uploadprogress");
    var target = $("#target_node");
    var uploadframe = $("#" + getNewUploadFrame());

    var deleteimageconfirm = $("#innerdata").attr("data-deleteimageconfirm");
    var target_node = $("#innerdata").attr("data-target");


    /**
     * place images array from response
     */

    function placeNodeImages(images, target_node) {



        var output = "", iLen = images.length;
        var linkPrefix = variables.admin_tools_link + '/node-images/';

        for (var i = 0; i < iLen; i++) {


            var isMaster = parseInt(images[i].is_master) > 0
                    ? ' master' : "";


            output += ' <div class="image' + isMaster + '"> ';
                output += ' <div class="imagewrapper"> ';
                    output += ' <a class="selectme" target="_blank" href="/upload/' + images[i].name + '"> ';
                        output += ' <img alt="" src="/upload/thumb_' + images[i].name + '" /> ';
                    output += ' </a> ';
                output += ' </div> ';
                output += ' <div class="actions c"> ';

                    output += ' <div> ';
                        output += ' <a class="replaceaction" href="#replace" data-id="' + images[i].id + '">' + language.replace_now + '</a> ';
                    output += ' </div> ';

                    output += ' <div class="masterlink"> ';
                        output += ' <a class="masteraction" href="' + linkPrefix + 'master?id=' + images[i].id + '&target=' + target_node + '">' + language.make_is_master + '</a> ';
                    output += ' </div> ';

                    output += ' <div> ';
                        output += ' <a class="deleteaction" href="' + linkPrefix + 'delete?id=' + images[i].id + '&target=' + target_node + '">' + language.delete_now + '</a> ';
                    output += ' </div> ';

                output += ' </div> ';
            output += ' </div> ';


        }

        output += ' <div class="h-40 clear"></div> ';
        $("#attachedimageswrapper").html(output);


    }


    /**
     * attached node images
     */

    function getNewUploadFrame() {


        $(".uploadframe").each(function(){
            $(this).remove();
            delete $(this);
        });


        var fname = "iframe" + (String(Math.random()).replace(".", ""));

        var f = '<iframe class="uploadframe" id="' + fname + '" name="' + fname
                    + '" frameborder="0" src="about:blank" scrolling="yes"></frame>';

        $("body").append(f);
        uploadform.attr("target", fname);
        return fname;


    }


    function bindAttachedImagesItems() {


        attachedimageswrapper.find(".image").each(function(){


            var item = $(this);


            if (document.location.href.indexOf("CKEditor=") >= 0) {

                item.find("a.selectme").click(function(){

                    var imageLink = $(this).attr("href");

                    coverblur.show();
                    popupchooseimage.show();
                    popupchooseimage.find("a").off().on("click", function() {

                        var type = $(this).attr("data-type");
                        if (type == "thumbnail") {

                            imageLink = imageLink.replace(/[^\/]+$/, function(filename) {
                                return "thumb_" + filename;
                            });

                        } else if (type == "middle") {

                            imageLink = imageLink.replace(/[^\/]+$/, function(filename) {
                                return "middle_" + filename;
                            });

                        }

                        window.top.opener['CKEDITOR'].tools.callFunction(1, imageLink, "");
                        window.top.close();
                        window.top.opener.focus();

                        return false;

                    });

                    return false;

                });

            }


            item.find("a.deleteaction").click(function(){


                if (confirmation(deleteimageconfirm)) {


                    $.get($(this).attr("href"), function(response){


                        if (typeof response.exception != "undefined" && response.exception.type == "error") {
                            showException(response.exception);
                        } else {

                            placeNodeImages(response.images, target_node);
                            bindAttachedImagesItems();

                        }


                    });


                }


                return false;


            });


            item.find("a.masteraction").click(function(){


                $.ajax({

                    cache: false,
                    type: "GET",
                    url: $(this).attr("href"),
                    success: function(response){

                        if (typeof response.exception != "undefined") {

                            if (response.exception.type == "success") {

                                attachedimageswrapper.find(".image").removeClass("master");
                                item.addClass("master");

                            } else {
                                showException(response.exception);
                            }

                        }

                    }

                });


                return false;


            });


            item.find("a.replaceaction").click(function(){

                setUploadImagesForm(target_node, "replace", $(this).attr("data-id"));
                coverblur.show();
                popupformwrapper.show();

                return false;

            });


        });


    }


    function setUploadImagesForm(target, action, image_id) {


        $("#target_node").val(target);
        $("#action").val(action);
        $("#image_id").val(image_id);
        $("#uploadfile").val("");

        var uploadcaption = $("#uploadcaption");
        if (action == "replace") {
            uploadcaption.html(language.image_replace);
        } else {
            uploadcaption.html(language.image_upload);
        }


    }


    setUploadImagesForm(target_node, "add", "new");
    uploadprogress.hide();

    bindAttachedImagesItems();


    $("a#showuploadform").click(function(){

        coverblur.show();
        popupformwrapper.show();

        setUploadImagesForm(target_node, "add", "new");
        return false;

    });


    uploadform.submit(function(){


        $(this).hide();
        uploadprogress.show();


        uploadframe.load(function(){


            $.ajax({

                cache: false,
                type: "GET",
                url: variables.admin_tools_link + "/node-images/view?target=" + target.val(),
                success: function(response){


                    if (typeof response.exception != "undefined") {
                        showException(response.exception);
                    } else {

                        placeNodeImages(response.images, target_node);
                        popupformwrapper.hide();
                        coverblur.hide();

                        bindAttachedImagesItems();

                    }


                    uploadframe = $("#" + getNewUploadFrame());
                    setUploadImagesForm(target_node, "add", "new");

                    uploadprogress.hide();
                    uploadform.show();


                }

            });


        });


    });


});



