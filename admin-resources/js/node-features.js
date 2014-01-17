


$(function(){


    /**
     * node features
     */

    var body = $("html, body");
    var featureCaption = $("div.featurecaption").filter(".topfcap");
    var featurelist = $("#featurelist");
    var newFeatureForm = $("#newfeatureform");
    var newFeatureName = $("#new-feature-name");
    var newFeatureTargetForm = newFeatureForm.find(".feature");
    var deleteFeatureConfirm = $("#innerdata").attr("data-deletefeatureconfirm");


    function placeNodeFeatures(features) {


        var output = "";

        for (var i in features) {


            output += ' <form class="feature" action="'
                            + variables.admin_tools_link + '/node-features/save" method="post"> ';

                output += ' <div class="name"> ';

                    output += ' <input type="text" name="name" value="'
                                    + features[i].fname + '" data-value="' + features[i].fvalue + '" autocomplete="off" /> ';

                    output += ' <div class="autocomplete"></div> ';

                output += ' </div> ';

                output += ' <div class="value"> ';

                    output += ' <input type="text" name="value" value="'
                                    + features[i].fvalue + '" data-value="' + features[i].fvalue + '" autocomplete="off" /> ';

                    output += ' <div class="autocomplete"></div>';

                output += ' </div> ';

                output += ' <div class="save"> ';
                    output += ' <input type="hidden" name="node_id" value="' + features[i].node_id + '" /> ';
                    output += ' <input type="submit" name="silentsave" value=" &raquo; " title=" ' + language.node_features_save + ' " /> ';
                output += ' </div> ';

                output += ' <div class="delete"> ';

                    output += ' <a href="' + variables.admin_tools_link + '/node-features/delete?id='
                                + features[i].feature_id + '&target=' + features[i].node_id
                                + '" title=" ' + language.node_features_delete + ' ">✖</a> ';

                output += ' </div> ';

            output += ' <div class="clear"></div> </form> ';


        }


        featurelist.html(output);


    }


    function showNewFeatureForm() {

        newFeatureForm.show();
        featureCaption.css({opacity:0});
        newFeatureName.focus();

        if (newFeatureForm.length > 0) {
            body.animate({scrollTop:body.outerHeight()}, 800);
        }


    }

    function hideNewFeatureForm() {

        newFeatureForm.hide();

        newFeatureTargetForm.find("input[name=name]").val("");
        newFeatureTargetForm.find("input[name=value]").val("");

        featureCaption.css({opacity:1});

    }


    function keyUpFeatureInput(input, autoComplete, type) {


        var newValue = trim(input.val());
        if (input.attr("data-value") != newValue) {

            input.attr("data-value", newValue);
            input.val(newValue);

            $.post(

                variables.admin_tools_link + "/node-features/" + type + "-autocomplete",
                {value: newValue},

                function (response) {

                    if (typeof response.exception != "undefined") {
                        showException(response.exception);
                    } else {


                        var items = "";
                        for (var i in response.items) {
                            items += ' <a href="#">' + response.items[i].fvalue + '</a> ';
                        }

                        autoComplete.html(items);
                        autoComplete.find("a").each(function(){

                            $(this).click(function(){

                                input.val($(this).text());
                                autoComplete.html("");
                                return false;

                            });

                        });


                    }

                }

            );


        }


    }


    function bindFeatureForm(form, isNewFeature) {


        var fname  = form.find("input[name=name]");
        var fvalue = form.find("input[name=value]");
        var dlink  = form.find("div.delete a");

        var nAutoComplete  = form.find("div.name div.autocomplete");
        var vAutoComplete  = form.find("div.value div.autocomplete");


        if (!isNewFeature) {


            fname.focus(function(){

                body.animate({scrollTop:form.offset().top - 100}, 300, function(){
                    hideNewFeatureForm();
                });

            });


            fvalue.focus(function(){

                body.animate({scrollTop:form.offset().top - 100}, 300, function(){
                    hideNewFeatureForm();
                });

            });


            dlink.click(function(){


                if (confirmation(deleteFeatureConfirm)) {

                    $.get($(this).attr("href"), function(response){

                        if (typeof response.exception != "undefined") {
                            showException(response.exception);
                        } else {

                            form.remove();
                            delete form;

                            if (trim(featurelist.html()) == "") {
                                showNewFeatureForm();
                            }

                        }

                    });


                }


                return false;


            });


        }


        fname.blur(function(){

            setTimeout(function(){
                nAutoComplete.html("");
            }, 600);

        });


        fvalue.blur(function(){

            setTimeout(function(){
                vAutoComplete.html("");
            }, 600);

        });


        fname.keyup(function(){
            keyUpFeatureInput($(this), nAutoComplete, "name");
        });

        fvalue.keyup(function(){
            keyUpFeatureInput($(this), vAutoComplete, "value");
        });


    }


    function bindFeatures() {


        featurelist.find(".feature").each(function(){


            bindFeatureForm($(this), false);

            $(this).submit(function(){

                $.post($(this).attr("action"), $(this).serializeArray(), function(response){

                    if (typeof response.exception != "undefined") {
                        showException(response.exception);
                    } else {

                        placeNodeFeatures(response.features);
                        bindFeatures();

                    }

                });

                return false;

            });


        });


    }


    if (trim(featurelist.html()) == "") {
        showNewFeatureForm();
    } else {
        hideNewFeatureForm();
    }

    bindFeatures();


    $("a#showfeatureform").click(function(){

        showNewFeatureForm();
        return false;

    });


    bindFeatureForm(newFeatureTargetForm, true);
    newFeatureTargetForm.submit(function(){

        data = $(this).serializeArray();
        data.push({save:"save"});

        $.post($(this).attr("action"), data, function(response){

            if (typeof response.exception != "undefined") {
                showException(response.exception);
            } else {

                hideNewFeatureForm();
                placeNodeFeatures(response.features);
                bindFeatures();

            }

        });

        return false;

    });


});



