<?php



/**
 * admin submodule, manage documents tree of site
 */

class tree extends baseController {


    private


        /**
         * member storage features and images keys
         */

        $storageImagesKey = "__stored_images",
        $storageFeaturesKey = "__stored_features",


        /**
         * based root element of documents tree
         */

        $root = array(

                   "type" => "root",
             "is_publish" => 1,
                     "id" => 0,
              "parent_id" => null,
            "parent_name" => null,
              "node_name" => "Root of site",
             "page_alias" => "/",
               "children" => 0

        ),


        /**
         * available prototypes array
         */

        $availableProtoTypes = null,


        /**
         * default selected prototype
         */

        $defaultProtoType = "simplePage";


    /**
     * set permissions for this controller
     */

    public function setPermissions() {


        $this->permissions = array(

            array(

                "action"      => "branch",
                "permission"  => "documents_tree_manage",
                "description"
                    => view::$language->permission_documents_tree_manage

            ),

            array(

                "action"      => "create",
                "permission"  => "node_create",
                "description" => view::$language->permission_node_create

            ),

            array(

                "action"      => "delete",
                "permission"  => "node_delete",
                "description" => view::$language->permission_node_delete
            ),

            array(

                "action"      => "edit",
                "permission"  => "node_edit",
                "description" => view::$language->permission_node_edit

            )

        );


    }


    /**
     * always call to branch action
     */

    public function index() {
        $this->branch();
    }


    /**
     * show one branch of target
     */

    public function branch() {


        /**
         * when exists old target of tree on storage,
         * restore these value, or set default
         */

        $target = (storage::exists("__branchParent"))
            ? storage::read("__branchParent") : 0;


        /**
         * if exists custom target,
         * choise these value as current
         */

        $newTarget = request::shiftParam("id");
        if ($newTarget !== null) {


            if (!validate::isNumber($newTarget)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );

            }

            $target = $newTarget;


            /**
             * save target into storage only for html output
             */

            if (view::getOutputContext() == "html") {
                storage::write("__branchParent", $target);
            }


        }


        /**
         * get current node of tree branch
         */

        view::assign("children", $this->branchChildren($target));


        if (view::getOutputContext() == "html") {
            view::assignProtected("node", $this->branchNode($target));
        }


        view::assign("node_name", view::$language->documents_tree);
        $this->setProtectedLayout("documents-tree.html");


    }


    /**
     * view create new node form
     */

    public function create() {


        /**
         * get parent ID and prototype name
         */

        $protoName = request::shiftParam("prototype");
        $parentID  = request::shiftParam("parent");

        if (!validate::isNumber($parentID)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }



        /**
         * save new node, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveNewNode();
        }


        /**
         * view new node form,
         * assign data into view
         */

        $this->assignNewNodeIntoView($parentID, $protoName);
        $this->setProtectedLayout("node-new.html");

        view::assign("node_name", view::$language->node_create_new);


    }


    /**
     * view edit node form
     */

    public function edit() {


        /**
         * get node ID and prototype name
         */

        $protoName = request::shiftParam("prototype");
        $nodeID    = request::shiftParam("id");

        if (!validate::isNumber($nodeID)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * save new node, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveEditedNode($nodeID);
        }


        /**
         * view edited node form,
         * assign data into view
         */

        $this->assignEditedNodeIntoView($nodeID, $protoName);
        $this->setProtectedLayout("node-edit.html");

        view::assign("node_name", view::$language->node_edit_exists);


    }


    /**
     * delete node
     */

    public function delete() {


        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer(
            $adminToolsLink . "/tree(/branch\?id=\d+)?", true
        );

        $nodeID = request::shiftParam("id");
        if (!validate::isNumber($nodeID)) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }


        /**
         * get nested set keys for branch deleting,
         * check exists deleted node
         */

        $deletedNode = db::normalizeQuery(
            "SELECT id, parent_id, lk, rk, (rk - lk + 1) gap
                FROM tree WHERE id = %u", $nodeID
        );

        if (!$deletedNode) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_not_found
            );
        }


        /**
         * limited sizeof of deleted children branch
         */

        $deletedCount = db::normalizeQuery(
            "SELECT (COUNT(1) - 1) cnt
                FROM tree WHERE lk BETWEEN %u
                    AND %u", $deletedNode['lk'], $deletedNode['rk']
        );

        if ($deletedCount > 100) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_delete_count_is_over
            );
        }


        /**
         * delete all children branch data from menu items
         */

        db::set(
            "DELETE FROM menu_items WHERE node_id IN(
                SELECT id FROM tree WHERE lk BETWEEN %u AND %u
            )", $deletedNode['lk'], $deletedNode['rk']
        );


        /**
         * delete attached images
         */

        $images = db::query(
            "SELECT name FROM images WHERE node_id IN(
                SELECT id FROM tree WHERE lk BETWEEN %u AND %u
            )", $deletedNode['lk'], $deletedNode['rk']
        );

        if ($images) {

            db::set(
                "DELETE FROM images WHERE node_id IN(
                    SELECT id FROM tree WHERE lk BETWEEN %u AND %u
                )", $deletedNode['lk'], $deletedNode['rk']
            );

            foreach ($images as $image) {
                @ unlink(PUBLIC_HTML . "upload/" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/thumb_" . $image['name']);
                @ unlink(PUBLIC_HTML . "upload/middle_" . $image['name']);
            }

        }


        /**
         * delete exists features
         */

        $existsFeatureIDs = db::normalizeQuery(
            "SELECT feature_id FROM tree_features
                WHERE node_id IN(
                    SELECT id FROM tree WHERE lk BETWEEN %u AND %u
            )", $deletedNode['lk'], $deletedNode['rk']
        );

        if (!is_array($existsFeatureIDs)) {
            $existsFeatureIDs = array($existsFeatureIDs);
        }

        db::set(
            "DELETE FROM tree_features WHERE node_id IN(
                SELECT id FROM tree WHERE lk BETWEEN %u AND %u
            )", $deletedNode['lk'], $deletedNode['rk']
        );

        if ($existsFeatureIDs) {

            $existsFeatureIDs = join(",", $existsFeatureIDs);
            $lostIDs = db::normalizeQuery(
                "SELECT f.id FROM features f LEFT JOIN tree_features tf
                    ON tf.feature_id = f.id WHERE f.id IN({$existsFeatureIDs})
                        AND tf.feature_id IS NULL"
            );

            if (!is_array($lostIDs)) {
                $lostIDs = array($lostIDs);
            }

            if ($lostIDs) {
                db::set(
                    "DELETE FROM features WHERE id
                        IN(" . join(",", $lostIDs) . ")"
                );
            }

        }


        /**
         * delete branch,
         * update keys for other nodes
         */

        db::set(
            "DELETE FROM tree WHERE lk BETWEEN
                %u AND %u", $deletedNode['lk'], $deletedNode['rk']
        );

        db::set(
            "UPDATE tree SET rk = rk - %u WHERE rk > %u",
                $deletedNode['gap'], $deletedNode['rk']
        );

        db::set(
            "UPDATE tree SET lk = lk - %u WHERE lk > %u",
                $deletedNode['gap'], $deletedNode['rk']
        );


        /**
         * redirect to show message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
                view::$language->success,
                    view::$language->node_is_deleted,
                        $adminToolsLink . "/tree/branch?id="
                        . $deletedNode['parent_id']

        );


    }


    /**
     * move node action
     */

    public function move_node() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer(
            $adminToolsLink . "/tree(/branch\?id=\d+)?", true
        );

        if (!request::isPost()) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }


        /**
         * get node ID and parent ID
         */

        $nodeID = request::getPostParam("item_id");
        $parentID = request::getPostParam("parent_id");

        if (!validate::isNumber($nodeID)
                or !validate::isNumber($parentID)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * get next node ID and previous node ID
         */

        $nextID = request::getPostParam("next_id");
        $prevID = request::getPostParam("prev_id");

        if (($nextID and !validate::isNumber($nextID))
                or ($prevID and !validate::isNumber($prevID))) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );

        }


        /**
         * get and check exists node,
         * check current parent node
         */

        $movedNode = db::normalizeQuery(

            "SELECT t.lvl, t.lk, t.rk, t.parent_id,
                p.lvl parent_lvl, p.lk parent_lk, p.rk parent_rk
                    FROM tree t LEFT JOIN tree p ON p.id = t.parent_id
                        WHERE t.id = %u", $nodeID

        );

        if (!$movedNode) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_not_found
            );
        }

        if ($movedNode['parent_id'] > 0 and !$movedNode['parent_rk']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->parent_node_not_found
            );
        }


        /**
         * check previous node if exists,
         * check next node if exists
         */

        if ($nextID and !$nextNode = db::normalizeQuery(
            "SELECT lvl, lk, rk FROM tree WHERE id = %u", $nextID)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->next_node_not_found
            );

        }

        if ($prevID and !$prevNode = db::normalizeQuery(
            "SELECT lvl, lk, rk FROM tree WHERE id = %u", $prevID)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->prev_node_not_found
            );

        }

        $isPrevNode = isset($prevNode);
        $isNextNode = isset($nextNode);

        if ($isNextNode and $isPrevNode
                and $nextNode['lk'] != ($prevNode['rk'] + 1)) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_incorrect_siblings
            );

        }


        /**
         * check required for update
         */

        if (($isNextNode and $movedNode['rk'] == ($nextNode['lk'] - 1))
         or ($isPrevNode and $movedNode['lk'] == ($prevNode['rk'] + 1))) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_update_is_not_required
            );

        }


        /**
         * get and check exists new parent node
         */

        if ($parentID > 0) {


            if (!$newParent = db::normalizeQuery(
                "SELECT lvl, lk, rk FROM tree WHERE id = %u", $parentID)) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->parent_node_not_found
                );

            }

            if ($isPrevNode and ($prevNode['lk'] <= $newParent['lk']
                or $prevNode['rk'] >= $newParent['rk'])) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->node_incorrect_siblings
                );

            }

            if ($isNextNode and ($nextNode['lk'] <= $newParent['lk']
                or $nextNode['rk'] >= $newParent['rk'])) {

                throw new memberErrorException(
                    view::$language->error,
                        view::$language->node_incorrect_siblings
                );

            }


        } else {
            $newParent = array("lvl" => 0, "lk"  => 0, "rk"  => 1);
        }


        /**
         * calculate positions
         */

        $skewLevel = $newParent['lvl'] - $movedNode['lvl'] + 1;
        $newPos = $isPrevNode ? $prevNode['rk'] + 1 : (
            $isNextNode ? $nextNode['lk'] : $newParent['rk']
        );


        /**
         * drag on left
         */

        if ($newPos < $movedNode['lk']) {


            $width    = $movedNode['rk'] - $movedNode['lk'] + 1;
            $tmpPos   = $movedNode['lk'] + $width;
            $distance = $newPos - $tmpPos;
            $tmpRight = $tmpPos + $width;

            db::query("

                UPDATE tree SET lk = IF(lk >= %1\$u, lk + %2\$u, lk),
                    rk = rk + %2\$u WHERE rk >= %1\$u;
                UPDATE tree SET lvl = lvl + (%3\$s), lk = lk + (%4\$s),
                    rk = rk + (%4\$s) WHERE lk >= %5\$u AND rk <= %6\$u;
                UPDATE tree SET lk = IF(lk >= %5\$u, lk - %2\$u, lk),
                    rk = rk - %2\$u WHERE rk >= %6\$u;
                ", $newPos, $width, $skewLevel,
                        $distance, $tmpPos, $tmpRight

            );


        /**
         * drag on right
         */

        } else {


            $width    = $movedNode['rk'] - $movedNode['lk'] + 1;
            $distance = $newPos - $movedNode['lk'];
            $tmpLeft  = $movedNode['rk'] + 1;

            db::query(

                "UPDATE tree SET lk = IF(lk >= %1\$u, lk + %2\$u, lk),
                    rk = rk + %2\$u WHERE rk >= %1\$u;
                UPDATE tree SET lvl = lvl + (%3\$s), rk = rk + %4\$u,
                    lk = lk + %4\$u WHERE lk >= %5\$u AND rk <= %6\$u;
                UPDATE tree SET lk = IF(lk >= %7\$u, lk - %2\$u, lk),
                    rk = rk - %2\$u WHERE rk >= %7\$u
                ", $newPos, $width, $skewLevel, $distance,
                        $movedNode['lk'], $movedNode['rk'], $tmpLeft

            );


        }


        /**
         * update parent ID value for moved node
         */

        if ($movedNode['parent_id'] != $parentID) {
            db::set(
                "UPDATE tree SET parent_id = %u
                    WHERE id = %u", $parentID, $nodeID
            );
        }


        /**
         * send success exception
         */

        throw new memberSuccessException(
            view::$language->success,
                view::$language->node_is_moved
        );


    }


    /**
     * MORE DOWN ONLY PRIVATE FUNCTIONS
     *
     *
     * return current target object
     */

    private function branchNode($target) {

        if ($target == 0) {
            $node = $this->root;
            $node['node_name'] = view::$language->root_of_site;
        } else {

            $node = db::normalizeQuery(

                "SELECT ('node') type, t.is_publish, t.id, t.parent_id,
                    t.node_name, COUNT(c.id) children, p.node_name parent_name
                        FROM tree t LEFT JOIN tree c ON c.parent_id = t.id
                            LEFT JOIN tree p ON p.id = t.parent_id
                                WHERE t.id = %u GROUP BY t.id", $target

            );

        }

        if (!$node) {

            storage::remove("__branchParent");
            throw new memberErrorException(
                view::$language->error,
                    view::$language->branch_children_not_found
            );

        }

        return $node;

    }


    /**
     * return children array
     */

    private function branchChildren($parent) {

        return db::query(
            "SELECT ('node') type, c.is_publish, c.id, c.parent_id,
                c.node_name, COUNT(cc.id) children FROM tree c
                    LEFT JOIN tree cc ON cc.parent_id = c.id
                        WHERE c.parent_id = %u GROUP BY c.id
                            ORDER BY c.lk ASC", $parent
        );

    }


    /**
     * chech for exists and available prototype,
     * return ptototype object
     */

    private function getProtoType($protoName) {

        $protoName = $protoName !== null
            ? $protoName : $this->defaultProtoType;

        if (!preg_match("/^[a-z]+$/i", $protoName)) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }

        if ($this->availableProtoTypes === null) {
            $this->getAvailableProtoTypes();
        }

        if (!array_key_exists($protoName, $this->availableProtoTypes)) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->prototype_not_found
            );
        }

        return $this->availableProtoTypes[$protoName];

    }


    /**
     * build/rebuild main prototypes array
     */

    private function getAvailableProtoTypes() {

        $this->availableProtoTypes = array();
        foreach (utils::getAvailableProtoTypes() as $item) {
            $this->availableProtoTypes[$item] = new $item;
        }

    }


    /**
     * return array list of prototypes
     */

    private function getProtoTypesList($current) {

        if ($this->availableProtoTypes === null) {
            $this->getAvailableProtoTypes();
        }

        if (!$this->availableProtoTypes) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->prototypes_not_available
            );
        }

        $prototypes = array(
            array("value" => "---", "description" => "---")
        );

        foreach ($this->availableProtoTypes as $k => $item) {

            $prototype = array(
                "value" => $k, "description" => $item->getHumanityName()
            );

            if ($current == $k) {
                $prototype['selected'] = true;
            }

            array_push($prototypes, $prototype);

        }

        return $prototypes;

    }


    /**
     * return array list of available menu
     */

    private function getAvailableMenuList($current = -1) {

        $menuItems = array();
        $menu = db::query("SELECT id,name FROM menu");

        if ($current < 1) {
            $inMenu = array();
        } else {
            $inMenu = db::query("
                SELECT menu_id FROM menu_items
                WHERE node_id = $current
            ");
        }

        foreach ($menu as $item) {

            $checked = false;
            foreach ($inMenu as $exists) {

                if ($exists['menu_id'] == $item['id']) {
                    $checked = true;
                    break;
                }

            }

            $elem = array(
                "name" => "menu[{$item['id']}]",
                "description" => $item['name']
            );

            if ($checked) $elem['checked'] = $checked;
            array_push($menuItems, $elem);

        }

        return $menuItems;

    }


    /**
     * check, prepare and assign
     * into view new node properties
     */

    private function assignNewNodeIntoView($parentID, $protoName) {

        $parentNode = array();
        $newNode = array(

            "id"                 => "new",
            "parent_id"          => $parentID,
            "prototype"          => $this->defaultProtoType,
            "children_prototype" => $this->defaultProtoType,
            "is_publish"         => 1,
            "node_name"          => "",
            "parent_alias"       => "/"

        );

        if ($parentID == 0) {

            $newNode['parent_name'] = view::$language->root_of_site;
            if ($protoName) {
                $newNode['prototype'] = $protoName;
            }

        } else {

            $parentNode = db::normalizeQuery(
                "SELECT node_name, prototype,
                    children_prototype cpt, page_alias
                        FROM tree WHERE id = %u", $newNode['parent_id']
            );

            if (!$parentNode) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->parent_node_not_found
                );
            }

            $newNode['parent_name'] = $parentNode['node_name'];
            $newNode['prototype'] = $protoName
                ? $protoName : $parentNode['cpt'];

            $parentProto  = $this->getProtoType($parentNode['prototype']);
            $parentFields = $parentProto->getPublicFields();

            if (in_array("page_alias", $parentFields, true)) {
                $newNode['parent_alias'] = rawurldecode(
                    $parentNode['page_alias']
                );
            }

        }

        $this->buildNodeProperties($newNode);
        view::assign("in_menu", $this->getAvailableMenuList());
        view::assign("node", $newNode);

    }


    /**
     * check, prepare and assign
     * into view edited node properties
     */

    private function assignEditedNodeIntoView($nodeID, $protoName) {

        $editedNode = db::normalizeQuery(

            "SELECT t.id, t.parent_id, t.prototype, t.children_prototype,
                t.is_publish, t.node_name, p.prototype parent_prototype,
                    p.page_alias parent_alias, p.node_name parent_name
                        FROM tree t LEFT JOIN tree p ON p.id = t.parent_id
                            WHERE t.id = %u", $nodeID

        );

        if (!$editedNode) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_not_found
            );
        }

        if ($protoName) {
            $editedNode['prototype'] = $protoName;
        }

        if ($editedNode['parent_id'] == 0) {

            $editedNode['parent_alias'] = "/";
            $editedNode['parent_name']  = view::$language->root_of_site;

        } else {

            if (!$editedNode['parent_prototype']) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->parent_node_not_found
                );
            }

            $parentProto = $this->getProtoType(
                $editedNode['parent_prototype']
            );

            $parentFields = $parentProto->getPublicFields();
            if (in_array("page_alias", $parentFields, true)) {
                $editedNode['parent_alias'] = rawurldecode(
                    $editedNode['parent_alias']
                );
            }

        }

        $this->buildNodeProperties($editedNode, $nodeID);
        view::assign("in_menu", $this->getAvailableMenuList($nodeID));
        view::assign("node", $editedNode);

    }


    /**
     * get node prototype model
     */

    private function getNodeProtoModel($protoName) {

        $protoModel = $protoName . "ProtoModel";
        return (new $protoModel);

    }


    /**
     * build default node properties
     */

    private function getNodeProps($nodeProps) {


        $mainProperties = array();
        $fieldedProperties = array(
            "id",
            "parent_id",
            "parent_alias",
            "node_name",
            "prototype",
            "children_prototype",
            "is_publish"
        );

        foreach ($fieldedProperties as $iterator => $k) {

            $mainProperties[$k] = utils::getDefaultField($nodeProps[$k]);
            $mainProperties[$k]['required'] = true;
            $mainProperties[$k]['sort'] = $iterator;

        }

        $mainProperties['id']['type'] = "hidden";
        $mainProperties['id']['selector'] = "nodeid";
        unset($mainProperties['id']['description']);
        unset($mainProperties['id']['editor']);

        $mainProperties['parent_id']['type'] = "hidden";
        unset($mainProperties['parent_id']['description']);
        unset($mainProperties['parent_id']['editor']);

        $mainProperties['parent_alias']['type'] = "hidden";
        $mainProperties['parent_alias']['selector'] = "parentalias";
        unset($mainProperties['parent_alias']['description']);
        unset($mainProperties['parent_alias']['editor']);

        $mainProperties['node_name']['top']  = 20;
        $mainProperties['node_name']['type'] = "longtext";
        $mainProperties['node_name']['selector'] = "pagename";
        $mainProperties['node_name']['description']
            = view::$language->node_name;

        $mainProperties['prototype']['top']  = 20;
        $mainProperties['prototype']['type'] = "select";
        $mainProperties['prototype']['selector'] = "prototype";
        $mainProperties['prototype']['description']
            = view::$language->node_prototype;

        $mainProperties['prototype']['value'] = $this->getProtoTypesList(
            $mainProperties['prototype']['value']
        );

        $chpt = "children_prototype";
        $mainProperties[$chpt]['type'] = "select";
        $mainProperties[$chpt]['description']
            = view::$language->node_prototype_of_children;

        $mainProperties[$chpt]['value'] = $this->getProtoTypesList(
            $mainProperties[$chpt]['value']
        );

        $mainProperties['is_publish']['top']  = 20;
        $mainProperties['is_publish']['required'] = false;
        $mainProperties['is_publish']['type'] = "checkbox";
        $mainProperties['is_publish']['description']
            = view::$language->publish;

        return $mainProperties;


    }


    /**
     * build all node properties
     */

    private function buildNodeProperties( & $node, $nodeID = null) {

        $this->getPrototype($node['prototype']);
        $protoModel = $this->getNodeProtoModel($node['prototype']);

        $node = array_merge(
            $this->getNodeProps($node), $protoModel->getProperties($nodeID)
        );

        utils::loadSortArrays();
        uasort($node, "sortArrays");

    }


    /**
     * return filtered required input form data of saved node
     */

    private function getFilteredRequiredInputData($nodeID = null) {


        $requiredParams = array(
            "parent_id", "prototype", "children_prototype", "node_name"
        );

        $requiredData = request::getRequiredPostParams($requiredParams);
        if ($requiredData === null) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );
        }

        if (!validate::isNumber($requiredData['parent_id'])) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }

        $requiredData['prototype'] = (string) $requiredData['prototype'];
        if (!$requiredData['prototype']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }

        $this->getPrototype($requiredData['prototype']);

        $requiredData['children_prototype']
            = (string) $requiredData['children_prototype'];

        if (!$requiredData['children_prototype']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_invalid
            );
        }

        $this->getPrototype($requiredData['children_prototype']);

        $requiredData['node_name'] = filter::input(
            $requiredData['node_name'])
                ->stripTags()->typoGraph(true)->getData();

        if (!$requiredData['node_name']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_name_invalid
            );
        }

        if ($requiredData['parent_id'] > 0) {

            $existsParent = db::normalizeQuery(
                "SELECT lk, rk FROM tree WHERE id = %u",
                    $requiredData['parent_id']
            );

            if (!$existsParent) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->parent_node_not_found
                );
            }

        }

        if ($nodeID !== null and $nodeID == $requiredData['parent_id']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->node_cant_itself_parent
            );
        }

        if ($nodeID) {

            $currentKeys = db::normalizeQuery(
                "SELECT lk, rk FROM tree WHERE id = %u", $nodeID
            );

            if (!$currentKeys) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->node_not_found
                );
            }

            if (isset($existsParent)) {

                if ($currentKeys['lk'] < $existsParent['lk']
                        and $currentKeys['rk'] > $existsParent['rk']) {

                    throw new memberErrorException(
                        view::$language->error,
                            view::$language->node_cant_itchild_parent
                    );

                }

            }

        }

        $requiredData['is_publish']
            = request::getPostParam("is_publish") ? 1 : 0;

        $mainProtoModelName = $requiredData['prototype'] . "ProtoModel";
        $mainProtoModel = new $mainProtoModelName;

        $requiredData = array_merge(
            $requiredData, $mainProtoModel->getPreparedProperties()
        );

        return $requiredData;


    }


    /**
     * return array of selected menu
     * from input data for save changes
     */

    private function getInMenuList() {

        $menuList = request::getPostParam("menu");
        $inMenu = array();

        if ($menuList !== null) {

            if (!is_array($menuList)) {
                throw new memberErrorException(
                    view::$language->error,
                        view::$language->data_invalid
                );
            }

            foreach ($menuList as $k => $appendix) {

                if (!validate::isNumber($k)) {
                    throw new memberErrorException(
                        view::$language->error,
                            view::$language->data_invalid
                    );
                }

                array_push($inMenu, $k);

            }

        }

        return $inMenu;

    }


    /**
     * save menu items
     */

    private function saveMenuItems($nodeID) {

        db::set("DELETE FROM menu_items WHERE node_id = %u", $nodeID);
        if ($inMenu = $this->getInMenuList()) {

            $insertedRows = array();
            foreach ($inMenu as $menuID) {
                array_push($insertedRows, "($menuID, $nodeID)");
            }

            if ($insertedRows) {

                $insertedRows = join(",", $insertedRows);
                db::set(
                    "INSERT INTO menu_items (menu_id,node_id)
                        VALUES {$insertedRows}"
                );

            }

        }

    }


    /**
     * update all nested set keys on database for edited node
     */

    private function moveNestedSetKeys($nodeID, $newParentID) {


        $currentPos = db::normalizeQuery(
            "SELECT lvl, lk, rk, parent_id
                FROM tree WHERE id = %u", $nodeID
        );


        /**
         * not need update keys
         */

        if ($currentPos['parent_id'] == $newParentID) {
            return true;
        }


        /**
         * right key near and parent level
         */

        $newParentKeys = db::normalizeQuery(
            "SELECT lvl, (rk - 1) rk
                FROM tree WHERE id = %u", $newParentID
        );

        $newParentKeys['lvl'] = !isset($newParentKeys['lvl'])
            ? 0 : ((int) $newParentKeys['lvl']);

        $newParentKeys['rk'] = isset($newParentKeys['rk'])
            ? ((int) $newParentKeys['rk'])
            : db::normalizeQuery(
                "SELECT rk FROM tree ORDER BY rk DESC LIMIT 1"
            );

        $skewLevel = $newParentKeys['lvl'] - $currentPos['lvl'] + 1;
        $skewTree  = $currentPos['rk'] - $currentPos['lk'] + 1;

        if ($newParentKeys['rk'] < $currentPos['rk']) {

            $skewEdit = $newParentKeys['rk'] - $currentPos['lk'] + 1;
            db::set(

                "UPDATE tree SET

                    rk = IF(lk >= %u, rk + (%s), IF(rk < %u, rk + (%s), rk)),
                    lvl = IF(lk >= %u, lvl + (%s), lvl),
                    lk = IF(lk >= %u, lk + (%s), IF(lk > %u, lk + (%s), lk))

                WHERE rk > %u AND lk < %u",

                $currentPos['lk'],
                $skewEdit,
                $currentPos['lk'],
                $skewTree,
                $currentPos['lk'],
                $skewLevel,
                $currentPos['lk'],
                $skewEdit,
                $newParentKeys['rk'],
                $skewTree,
                $newParentKeys['rk'],
                $currentPos['rk']

            );

        } else {

            $skewEdit = $newParentKeys['rk']
                - $currentPos['lk'] + 1 - $skewTree;

            db::set(

                "UPDATE tree SET

                    lk=IF(rk <= %u, lk + (%s), IF(lk > %u, lk - (%s), lk)),
                    lvl=IF(rk <= %u, lvl + (%s), lvl),
                    rk=IF(rk <= %u, rk + (%s), IF(rk <= %u, rk - (%s), rk))

                WHERE rk > %u AND lk <= %u",

                $currentPos['rk'],
                $skewEdit,
                $currentPos['rk'],
                $skewTree,
                $currentPos['rk'],
                $skewLevel,
                $currentPos['rk'],
                $skewEdit,
                $newParentKeys['rk'],
                $skewTree,
                $currentPos['lk'],
                $newParentKeys['rk']

            );

        }


    }


    /**
     * save new node data
     */

    private function saveNewNode() {


        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer(
            $adminToolsLink . "/tree/create\?parent=\d+", true
        );


        /**
         * get and check filtered data of new node,
         * get nested set keys for new inserted node,
         * check for exists parent
         */

        $newNode = $this->getFilteredRequiredInputData();
        $nestedSetKeys = db::normalizeQuery(
            "SELECT lk, lvl FROM tree WHERE id = %u", $newNode['parent_id']
        );

        if (!$nestedSetKeys) {

            $nestedSetKeys['lvl'] = 0;
            $nestedSetKeys['lk']  = db::normalizeQuery(
                "SELECT MAX(rk) rk FROM tree"
            );

        }


        /**
         * TODO maybe add feature first/last append new node choice
         * set auto properties for new node
         */

        $newNode['lvl'] = $nestedSetKeys['lvl'] + 1;
        $newNode['lk']  = $nestedSetKeys['lk']  + 1;
        $newNode['rk']  = $nestedSetKeys['lk']  + 2;

        $newNode['modified_author']
            = $newNode['author'] = member::getID();

        $newNode['last_modified']
            = $newNode['creation_date'] = db::normalizeQuery("SELECT NOW()");


        /**
         * build inserted query string
         */

        $insertQuery = "INSERT INTO tree ("
            . join(",", array_keys($newNode)) . ") VALUES (";

        $insertedValues = array();
        foreach ($newNode as $item) {

            if ($item != "NULL" and !validate::isNumber($item)) {
                $item = "'" . db::escapeString($item) . "'";
            }

            array_push($insertedValues, $item);

        }

        $insertQuery .= join(",", $insertedValues) . ")";


        /**
         * update nested set keys before insert new node,
         * insert all data of new node
         */

        db::set(
            "UPDATE tree SET lk = lk + 2 WHERE lk > %u", $nestedSetKeys['lk']
        );

        db::set(
            "UPDATE tree SET rk = rk + 2 WHERE rk > %u", $nestedSetKeys['lk']
        );

        db::set($insertQuery);


        /**
         * get last insert ID of new node,
         * save menu items,
         * save attached images
         */

        $newNode['id'] = db::lastID();
        $this->saveMenuItems($newNode['id']);

        $attachedImages = array();
        foreach (member::getStorageData($this->storageImagesKey) as $k => $v) {

            $k = db::escapeString($k);
            array_push(
                $attachedImages,
                "(NULL, {$newNode['id']}, {$v['is_master']}, '{$k}')"
            );

        }

        if ($attachedImages) {

            $attachedImages = join(",", $attachedImages);
            db::set(
                "INSERT INTO images (id,node_id,is_master,name)
                        VALUES {$attachedImages}"
            );

        }


        /**
         * save node features
         */

        $fNames = array();
        $sourceFeatures = member::getStorageData($this->storageFeaturesKey);

        foreach ($sourceFeatures as $v) {
            array_push($fNames,  $v['name']);
        }


        $updFeatures = array();
        $existsFeatures = array();
        if ($fNames) {
            $existsFeatures = db::query(
                "SELECT id,name FROM features WHERE name IN(%s)", $fNames
            );
        }

        $insFeatures = array();
        $insNames  = array();

        foreach ($sourceFeatures as $k => $v) {

            $updated = false;
            foreach ($existsFeatures as $x => $ex) {

                if (in_array($ex['name'], $fNames)) {

                    $escaped = db::escapeString($v['value']);
                    $feature = "({$newNode['id']}, {$ex['id']}, '{$escaped}')";

                    array_push($updFeatures, $feature);
                    unset($existsFeatures[$x]);

                    $updated = true;
                    break;

                }

            }

            if (!$updated) {

                array_push(
                    $insNames,
                    "(NULL,'" . db::escapeString($v['name']) . "')"
                );

                $insFeatures[$v['name']] = db::escapeString($v['value']);

            }

        }


        /**
         * save only new values
         */

        if ($updFeatures) {
            db::set(
                "INSERT INTO tree_features (node_id, feature_id, feature_value)
                        VALUES " . join(",", $updFeatures)
            );
        }


        /**
         * save new names and values
         */

        if ($insNames) {

            db::set(
                "INSERT INTO features (id, name) VALUES " . join(",", $insNames)
            );

            $newNames = array_keys($insFeatures);
            $existsNewNames = db::query(
                "SELECT id,name FROM features WHERE name IN(%s)", $newNames
            );

            $updNewFeatures = array();
            foreach ($existsNewNames as $ex) {

                if (in_array($ex['name'], $newNames)) {

                    $feature = "({$newNode['id']}, {$ex['id']},"
                        . " '{$insFeatures[$ex['name']]}')";

                    array_push($updNewFeatures, $feature);

                }

            }

            db::set(
                "INSERT INTO tree_features (node_id, feature_id, feature_value)
                        VALUES " . join(",", $updNewFeatures)
            );

        }


        /**
         * reset member cache,
         * redirect to show message
         */

        member::setStorageData($this->storageImagesKey, array());
        member::setStorageData($this->storageFeaturesKey, array());
        $this->redirectMessage(

            SUCCESS_EXCEPTION,
                view::$language->success,
                    view::$language->node_is_created,
                        $adminToolsLink
                        . "/tree/branch?id={$newNode['parent_id']}"

        );


    }


    /**
     * save edited node data
     */

    private function saveEditedNode($nodeID) {


        $adminToolsLink = app::config()->site->admin_tools_link;
        request::validateReferer(
            $adminToolsLink . "/tree/edit\?id=\d+", true
        );

        $editedNode = $this->getFilteredRequiredInputData($nodeID);
        $parentID = $editedNode['parent_id'];
        unset($editedNode['parent_id']);

        $editedNode['modified_author'] = member::getID();
        $editedNode['last_modified']
            = db::normalizeQuery("SELECT NOW()");


        /**
         * build updated query string
         */

        $updateQuery = "UPDATE tree SET ";
        $updatedValues = array();

        foreach ($editedNode as $key => $value) {

            if ($value != "NULL" and !validate::isNumber($value)) {
                $value = "'" . db::escapeString($value) . "'";
            }

            array_push($updatedValues, $key . " = " . $value);

        }

        $updateQuery .= join(",", $updatedValues)
            . " WHERE id = " . $nodeID;


        /**
         * update edited node data,
         * save menu items,
         * redirect to show message
         */

        db::set($updateQuery);
        $this->saveMenuItems($nodeID);

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
                view::$language->success,
                    view::$language->node_is_edited,
                        $adminToolsLink . "/tree/branch?id={$parentID}"

        );


    }


}



