


$(function(){


    var coverblur = $("#coverblur");
    var globalcover = $("#globalcover");
    var popupformwrapper = $("#popupformwrapper");
    var popupchooseimage = $("#popupchooseimage");

    var attachedimageswrapper = $("#attachedimageswrapper");
    var uploadform = $("#uploadform");
    var uploadprogress = $("#uploadprogress");
    var progressstatus = $("#progressstatus");
    var target = $("#target_node");
    var uploadfile = $("#uploadfile");
    var showuploadform = $("a#showuploadform");

    var deleteimageconfirm = $("#innerdata").attr("data-deleteimageconfirm");
    var target_node = $("#innerdata").attr("data-target");

    function placeNodeImages(images, target_node) {

        var output = "", iLen = images.length;
        var linkPrefix = variables.admin_tools_link + '/node-images/';

        for (var i = 0; i < iLen; i++) {

            var isMaster = parseInt(images[i].is_master) > 0
                    ? ' master' : "";

            output += ' <div class="image' + isMaster + '"> ';
                output += ' <div class="imagewrapper"> ';
                    output += ' <a class="selectme" target="_blank" href="/upload/' + images[i].name + '"> ';
                        output += ' <img alt="Loading..." src="/upload/thumb_' + images[i].name + '" /> ';
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
            uploadcaption.html(language.images_upload);
        }

    }

    showuploadform.click(function(){

        coverblur.show();
        popupformwrapper.show();
        setUploadImagesForm(target_node, "add", "new");
        return false;

    });

    uploadform.submit(function(){
        return false;
    });

    uploadfile.fileupload({

        url: variables.admin_tools_link + "/node-images/upload",
        dataType: "json",
        progressInterval: 20,
        sequentialUploads: true,
        start: function (e, data) {
            globalcover.show();
            progressstatus.text("0%");
            uploadprogress.show();
            uploadform.hide();
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            progressstatus.text(progress + "%");
        },
        stop: function (e, data) {

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

                    uploadprogress.hide();
                    uploadform.show();
                    globalcover.hide();

                }

            });

        }

    }).prop("disabled", !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : "disabled");

    $("#deleteallimages").click(function(){

        if (confirmation(language.images_delete_confirm)) {
            var link = variables.admin_tools_link + "/node-images/delete-all?target=" + target.val() + "&_" + Math.random();
            $.get(link, function(response) {
                if (typeof response.exception != "undefined") {
                    showException(response.exception);
                } else {
                    placeNodeImages([], target_node);
                }
            });
        }

        return false;

    });


    globalcover.click(function(e) {
        e.stopPropagation();
    });

    setUploadImagesForm(target_node, "add", "new");
    uploadprogress.hide();
    bindAttachedImagesItems();
    globalcover.hide();


});



