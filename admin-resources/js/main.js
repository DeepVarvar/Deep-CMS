



/**
 * return string of tree branch item
 */

function getBranchTreeItem(item) {


    item.children = parseInt(item.children);
    item.is_publish = parseInt(item.is_publish);

    var linkPrefix = variables.admin_tools_link + "/tree";
    var branchLink = linkPrefix + "/branch?id=" + item.id;
    var createLink = linkPrefix + "/create?parent=" + item.id;
    var deleteLink = linkPrefix + "/delete?id=" + item.id;
    var editLink = linkPrefix + "/edit?id=" + item.id;


    /**
     * expander
     */

    var eClass = item.children > 0 ? "expander" : "noexpand";
    var eName  = item.children > 0 ? "" : ' name="' + branchLink + '"';
    var eLink  = item.children > 0 ? ' href="' + branchLink + '"' : "";
    var eTitle = item.children > 0
            ? ' title=" ' + language.branch_children_expand_collapse + ' "'
            : "";

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
            + language.documents_tree_only_one_branch + ' "></a> ';

    }

    create  = ' <a class="create" href="' + createLink
        + '" title=" ' + language.node_create_new + ' "></a> ';

    idelete = ' <a class="delete" href="'
        + deleteLink + '" title=" ' + language.delete_now + ' "'
        + ' onclick="return confirmation(\''
        + language.node_delete_confirm + '\');"></a> ';


    return ' <li data-tree-id="' + item.id + '"> ' + expander + iname
        + showbranch + create + idelete + ' </li> ';


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
     * set viewed name of node
     */

    function setNameOfNode(str) {
        $("#showpagename").text(str);
    }


    /**
     * refresh viewed name of node
     */

    setNameOfNode(trim($("#pagename").val()));


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
        setNameOfNode(sourceName);
        generatePageAlias(sourceName);

    });


    /**
     * documents tree horizontal autoscroll
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
     * documents tree expand/collapse branch node
     */

    function getTreeItemEnvironment(item) {

        var position = item.position();
        var data = {

            top       : position.top,
            left      : position.left,
            item_id   : item.attr("data-tree-id"),
            parent_id : item.parents("li").eq(0).attr("data-tree-id"),
            prev_id   : item.prev().attr("data-tree-id"),
            next_id   : item.next().attr("data-tree-id")

        };

        for (var i in data) {
            if (!data[i] && parseInt(data[i], 10) != 0) {
                delete data[i];
            }
        }

        return data;

    }

    function updateTreeState(bf, af) {

        var bfParent = $('li[data-tree-id="' + bf.parent_id + '"]');
        var afParent = $('li[data-tree-id="' + af.parent_id + '"]');

        if (bfParent.find("ul").length < 1) {

            var expander = bfParent.find("a.expander");
            var expHref  = expander.attr("href");

            expander

                .removeClass("expander")
                .addClass("noexpand")
                .removeAttr("href")
                .removeAttr("title")
                .attr("name", expHref);

        }

        var afLen = afParent.find("> ul > li").length;
        if (afLen > 0) {

            var expander = afParent.find("> a.expander, > a.noexpand");
            if (expander.hasClass("noexpand")) {

                var expHref = expander.attr("name");
                expander

                    .removeClass("noexpand")
                    .addClass("expander")

                    .attr("title", " " +
                        language.branch_children_expand_collapse + " ")

                    .removeAttr("name")
                    .attr("href", expHref);

            }

        }

    }

    $("#root").nestedSortable({

        protectRoot          : true,
        forcePlaceholderSize : true,
        handle               : "a.name",
        helper               :	"clone",
        items                : "li",
        opacity              : 1,
        placeholder          : "placeholder",
        revert               : 200, // скорость анимации возвращения на место
        tabSize              : 40, // сетка смещения для вложений
        tolerance            : "pointer",
        toleranceElement     : false,
        maxLevels            : 0,
        isTree               : true,
        startCollapsed       : true,

        activate: function(event, ui) {
            ui.item.data("before", getTreeItemEnvironment(ui.item));
        },

        update: function(event, ui) {

            var that = $(this);
            var env  = getTreeItemEnvironment(ui.item);
            $.ajax({

                type: "POST",
                url: variables.admin_tools_link + "/tree/move-node",
                data: env,
                success: function(response) {

                    var is_error = true;
                    if (typeof response.exception != "undefined") {
                        is_error = (response.exception.type == "error");
                    }

                    if (!is_error) {
                        updateTreeState(ui.item.data("before"), env);
                        ui.item.data("before", null);
                    } else {
                        showException(response.exception);
                        that.nestedSortable("cancel");
                    }

                }

            });

        }

    });

    $("#root a.expander").live("click", function(){

        var expander = $(this);
        var branchItem = expander.parents("li").eq(0);
        var childrenBranch = branchItem.find("ul");

        if (childrenBranch.length > 0) {

            childrenBranch.remove();
            delete childrenBranch;

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
                        } else {

                            expander.removeClass("expander")
                                .addClass("noexpand");

                        }

                    }

                    expander.removeClass("loading");

                }

            });

        }

        return false;

    });


    /**
     * change prototype of node
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
     * open attached images window of node
     */

    $("#attachedimages").click(function(){

        var attachedImagesWindow = window.open(
            $(this).attr("href"), "attachedimages", "width=620,height=450,scrollbars=yes"
        );

        attachedImagesWindow.focus();
        return false;

    });


    /**
     * open features window of node
     */

    $("#nodefeatures").click(function(){

        var nodeFeaturesWindow = window.open(
            $(this).attr("href"), "nodefeatures", "width=620,height=450,scrollbars=yes"
        );

        nodeFeaturesWindow.focus();
        return false;

    });


});



