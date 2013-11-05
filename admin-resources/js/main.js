



/**
 * return string of tree branch item
 */

function getBranchTreeItem(item) {


    item.children = parseInt(item.children);
    item.is_publish = parseInt(item.is_publish);

    var linkPrefix = variables.admin_tools_link + "/documents";
    var branchLink = linkPrefix + "/branch?id=" + item.id;
    var createLink = linkPrefix + "/create?parent=" + item.id;
    var deleteLink = linkPrefix + "/delete?id=" + item.id;
    var editLink = linkPrefix + "/edit?id=" + item.id;


    /**
     * expander
     */

    var eClass = item.children > 0 ? "expander" : "noexpand";
    var eName  = item.children > 0 ? "" : ' name="' + Math.random() + '"';
    var eLink  = item.children > 0 ? ' href="' + branchLink + '"' : "";
    var eTitle = item.children > 0
            ? ' title=" ' + language.document_tree_expand_collapse + ' "' : "";

    var expander = ' <a' + eTitle + ' class="'
            + eClass + '"' + eName + eLink + '></a> ';


    /**
     * name
     */

    var blend = ' <div class="blend"></div> ';
    var isOff = item.is_publish == 0 ? " off" : "";
    var iname = ' <a class="name' + isOff + '" href="'
            + editLink + '" title=" ' + language.edit_now
            + ' "> ' + blend + ' <span> '
            + item.node_name + ' </span> ' + ' </a> ';


    /**
     * show branch,
     * create,
     * delete
     */

    showbranch = "";
    if (item.children > 0) {

        showbranch = ' <a class="showbranch" href="'
            + branchLink + '" title=" '
            + language.document_tree_show_only_tb + ' "></a> ';

    }

    create  = ' <a class="create" href="' + createLink
        + '" title=" ' + language.document_create_new + ' "></a> ';

    idelete = ' <a class="delete" href="'
        + deleteLink + '" title=" ' + language.delete_now + ' "'
        + ' onclick="return confirmation(\''
        + language.document_delete_confirm + '\');"></a> ';


    return ' <li> ' + expander + iname
        + showbranch + create + idelete + ' </li> ';


}


/**
 * return string of parentlist branch item
 */

function getBranchParentlistItem(item, doc_id) {


    item.children = parseInt(item.children);
    item.is_off = parseInt(item.is_off);

    var linkPrefix = variables.admin_tools_link + "/documents";
    var branchLink = linkPrefix
            + "/get-available-parents?branch_id=" + item.id + "&id=" + doc_id;


    /**
     * expander
     */

    var eClass = (item.is_off == 0 && item.children > 0)
            ? "expander" : "noexpand";

    var eName  = (item.is_off == 0 && item.children > 0)
            ? "" : ' name="' + Math.random() + '"';

    var eLink  = (item.is_off == 0 && item.children > 0)
            ? ' href="' + branchLink + '"' : "";

    var eTitle = (item.is_off == 0 && item.children > 0)
            ? ' title=" ' + language.document_tree_expand_collapse + ' "' : "";

    var expander = ' <a' + eTitle + ' class="'
            + eClass + '"' + eName + eLink + '></a> ';


    /**
     * name
     */

    var dataID   = ' data-id="' + item.id + '"';
    var blend    = ' <div class="blend"></div> ';
    var isOff = item.is_off == 0 ? " selectme" : " off";

    var iName  = item.is_off == 0
            ? "" : ' name="' + Math.random() + '"';

    var iLink = item.is_off == 0
            ? ' href="' + item.page_alias + '"' : "";

    var iTitle = item.is_off == 0
            ? ' title=" ' + language.document_set_as_parent + ' "' : "";

    var dName = ' <a class="name' + isOff + '"'
            + iName + iLink + iTitle + dataID + '>' + blend
            + ' <span> ' + item.node_name + ' </span> ' + ' </a> ';


    return ' <li> ' + expander + dName + ' </li> ';


}



$(function(){


    /**
     * page alias generator
     */

    function generatePageAlias(str) {


        var str = str || "";
        var parentAlias = $("#parentalias").val();

        if (!parentAlias.match(new RegExp(/\/$/))) {
            parentAlias += '/';
        }

        str = str.replace(/['"\\]+/g, "").replace(/[\s-]+/g, "-");
        $("#pagealias").val( str ? parentAlias + str : "" );


    }


    /**
     * set viewed name of document
     */

    function setNameOfDocument(str) {
        $("#showpagename").text(str);
    }


    /**
     * refresh viewed name of document
     */

    setNameOfDocument(trim($("#pagename").val()));


    /**
     * [select all] checkboxes
     */

    $("input.primarychecker:checkbox").click(function () {
        var pattern = this.name.replace(/^checkall/, "");
        $('input[name^="' + pattern + '"]:checkbox').attr("checked", $(this).is(":checked"));
    });


    /**
     * refresh name and generate alias
     */

    $("#pagename").keyup(function(){

        var sourceName = trim($("#pagename").val());
        setNameOfDocument(sourceName);
        generatePageAlias(sourceName);

    });


    /**
     * document tree horizontal autoscroll
     * with mouse horizontal move,
     * on/off and move actions worker
     */

    function xAutoScroll(e, element) {


        var mainWidth = element.innerWidth();
        var target = element[0];
        var mainScrollWidth = target.scrollWidth - mainWidth;
        var mainGap = parseInt(mainWidth/2);
        var position = getMouseInnerCoords(e).x - mainGap;

        mainWidth -= mainGap;

        if (position > 0) {

            target.scrollLeft = Math.ceil(
                mainScrollWidth * position / mainWidth / 2
            );

        } else {
            target.scrollLeft = 0;
        }


    }

    var xAutoscrollEnabled = false;
    var tree = $("#tree");

    $("#togglexscroll").click(function(){

        if (!xAutoscrollEnabled) {

            $(this).css({color:"#ff0000"});
            xAutoscrollEnabled = true;

        } else {

            $(this).css({color:"#2276ae"});
            xAutoscrollEnabled = false;
            tree[0].scrollLeft = 0;

        }


        return false;


    });


    if (xAutoscrollEnabled) {
        tree.mousemove(function(e){xAutoScroll(e, tree);});
    }


    /**
     * document tree expand/collapse branch node
     */

    $("#tree a.expander").live("click", function(){


        var expander = $(this);
        var branchItem = expander.parents("li").eq(0);
        var childrenBranch = branchItem.find("ul");


        if (childrenBranch.length > 0) {


            childrenBranch.eq(0).toggle();


        } else if (!expander.hasClass("loading")) {


            expander.addClass("loading");
            $.ajax({

                type: "GET",
                url: expander.attr("href"),
                success: function(response){


                    if (typeof response.exception != "undefined") {
                        showException(response.exception);
                    } else {


                        var children = "";
                        for (var c in response.children) {
                            children += getBranchTreeItem(response.children[c]);
                        }

                        if (children.length > 0) {
                            branchItem.append(" <ul>" + children + "</ul> ");
                        }


                    }


                    expander.removeClass("loading");


                }


            });


        }


        return false;


    });


    /**
     * change prototype of document
     */

    $("#prototype").change(function(){

        var proto = $(this).val();
        var loc = document.location.href;

        if (proto.match(/[a-z]+/i)) {

            loc = loc.replace(
                /(prototype=)([a-z]+)/i, "$1" + proto
            );

            if (loc == document.location.href) {
                loc = document.location.href + "&prototype=" + proto;
            }

            document.location = loc;

        }

    });


    /**
     * open attached images window of document
     */

    $("#attachedimages").click(function(){


        var attachedImagesWindow = window.open(
            $(this).attr("href"), "attachedimages", "width=620,height=450,scrollbars=yes"
        );

        attachedImagesWindow.focus();
        return false;


    });


    /**
     * open features window of document
     */

    $("#documentfeatures").click(function(){


        var documentFeaturesWindow = window.open(
            $(this).attr("href"), "documentfeatures", "width=620,height=450,scrollbars=yes"
        );

        documentFeaturesWindow.focus();
        return false;


    });


});



