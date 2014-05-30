


var isEditedMode = new RegExp(/edit/i).test(document.location.href.toString());

/**
 * string trimmer
 */

function trim(str) {
    var str = str || "";
    return str.replace(/^\s+/, "").replace(/\s+$/, "");
}


/**
 * confirm dialog wrapper
 */

function confirmation(dialog) {
    return confirm(dialog);
}


/**
 * get hash string from link
 */

function getHashAction(link) {
    var hash = link.attr("href").split("#");
    return hash[1] != "undefined" ? hash[1] : null;
}


/**
 * get mouse coordinates
 */

function getMouseInnerCoords(e) {

    if (!e) {
        e = window.event;
        e.target = e.srcElement
    }

    var coords = {x:0, y:0};

    if (e.layerX) {

        coords.x = e.layerX;
        coords.y = e.layerY;

    } else if (e.offsetX) {

        coords.x = e.offsetX;
        coords.y = e.offsetY;

    }

    return coords;

}


/**
 * generate password string
 */

function generatePassword() {

    var pass = document.getElementById("password");
    var confirmpass = document.getElementById("confirmpassword");
    var result = "", words = "0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
    var max_position = words.length - 1;
    var len = Math.floor( Math.random() * 10 ) + 4;

    for (var i = 0; i < len; ++i) {
        position = Math.floor( Math.random() * max_position );
        result += words.substring(position, position + 1);
    }

    pass.value = result;
    confirmpass.value = result;

    return false;

}


/**
 * show exception notifier
 */

function showException(exception) {


    var notifier = $("#notifier");
    var notify   = notifier.find("div.notify");
    var title    = notify.find("h3");
    var mess     = notify.find("p");


    notify.attr("class", "")
        .addClass("notify "+ exception.type);

    title.html(exception.title);
    mess.html(exception.message);

    clearTimeout(notifier.data('timer'));

    notifier.stop().css({
        opacity: 1,
        top: 20
    }).data('timer', setTimeout(function () {
        notifier.animate({
            opacity: 0
        }, {
            duration: 500,
            queue: false,
            complete: function () {
                notifier.css({
                    top: -1000
                });
            }
        });
    }, 2000));


}


/**
 * update textareas
 */

function updateTextareas(form) {


    form.find("textarea").each(function(){

        var instance = CKEDITOR.instances[$(this).attr("id")];
        if (typeof instance != "undefined") {
            $(this).val(CKEDITOR.instances[$(this).attr("id")].getData());
        }

    });


}


/**
 * hide all toggled elements
 */

function hideAllToggledElements() {
    $(".toggleme").hide(400);
}



$(function(){


    /**
     * page initialization:
     * hide notifier
     */

    $("#notifier").css({opacity: 0, top: -1000});


    /**
     * show/hide togglers
     */

    var toggledItems = $(".toggleme");
    toggledItems.hide();

    $("a.toggler").click(function(){


        var className = getHashAction($(this));
        if (className != null) {
            toggledItems.not("." + className).hide(400);
            $("." + className).toggle(400);
        }


        return false;


    });


    /**
     * all form submit wrapper
     */

    $("form.silentform input:submit").click(function(){

        var myForm = $(this).parents("form").eq(0);
        var isSilentSave = $(this).attr("name") == "silentsave";

        updateTextareas(myForm);

        var data = myForm.serializeArray();
        var formElements = myForm.find("input, textarea, button, select");

        formElements.attr("disabled", true);
        if (isSilentSave) {
            data.push({"name":"silentsave","value":1});
        }

        $.ajax({
            type: "POST",
            url: myForm.attr("action"),
            data: data,
            success: function(response){

                if (response.exception) {

                    showException(response.exception);
                    setTimeout(function() {
                        var isReloc = (!isSilentSave || (isSilentSave && !isEditedMode));
                        if (isReloc && response.exception.refresh_location) {
                            document.location.href = response.exception.refresh_location;
                        } else {
                            formElements.removeAttr("disabled");
                        }
                    }, ((response.exception.type == "success") ? 2000 : 0));

                }

            },
            cache: false
        });

        return false;

    });

    /*$("form").submit(function(){
        updateTextareas($(this));
    });*/


    /**
     * popup form
     */

    var coverblur = $("#coverblur");
    var popupformwrapper = $("#popupformwrapper");
    var popupchooseimage = $("#popupchooseimage");

    popupformwrapper.click(function(e){
        e.stopPropagation();
    });

    popupchooseimage.click(function(e){
        e.stopPropagation();
    });

    $("a.closepopupform").click(function(){

        popupchooseimage.hide();
        popupformwrapper.hide();
        coverblur.hide();

        return false;

    });

    $("body").click(function(){
        popupchooseimage.hide();
        popupformwrapper.hide();
        coverblur.hide();
    });

    popupchooseimage.hide();
    popupformwrapper.hide();
    coverblur.hide();


});



