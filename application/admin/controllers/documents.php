<?php



/**
 * admin submodule, manage documents of site
 */

class documents extends baseController {


    private


        /**
         * member storage features and images keys
         */

        $storageImagesKey = "__stored_images",
        $storageFeaturesKey = "__stored_features",


        /**
         * based root element of document tree
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
                "permission"  => "documents_manage",
                "description" => view::$language->permission_documents_manage

            ),

            array(

                "action"      => "create",
                "permission"  => "documents_create",
                "description" => view::$language->permission_documents_create

            ),

            array(

                "action"      => "delete",
                "permission"  => "documents_delete",
                "description" => view::$language->permission_documents_delete
            ),

            array(

                "action"      => "edit",
                "permission"  => "documents_edit",
                "description" => view::$language->permission_documents_edit

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
                    view::$language->data_invalid_format
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


        view::assign("node_name", view::$language->document_tree);
        $this->setProtectedLayout("documents.html");


    }


    /**
     * view create new node form
     */

    public function create() {


        /**
         * get parent ID and prototype name
         */

        $parentID = request::shiftParam("parent");
        if (!validate::isNumber($parentID)) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid_format
            );

        }

        $protoName = request::shiftParam("prototype");
        if ($protoName === null) {
            $protoName = $this->defaultProtoType;
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
        $this->setProtectedLayout("document-new.html");

        view::assign("node_name", view::$language->document_create_new);


    }


    /**
     * view edit node form
     */

    public function edit() {


        /**
         * get node ID and prototype name
         */

        $nodeID = request::shiftParam("id");
        if (!validate::isNumber($nodeID)) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid_format
            );

        }

        $protoName = request::shiftParam("prototype");
        if ($protoName === null) {
            $protoName = $this->defaultProtoType;
        }


        /**
         * save new node, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveEditedNode();
        }


        /**
         * view edited node form,
         * assign data into view
         */

        $this->assignEditedNodeIntoView($nodeID, $protoName);
        $this->setProtectedLayout("document-edit.html");

        view::assign("node_name", view::$language->document_edit_exists);


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


            $node = db::normalizeQuery("

                SELECT

                    ('node') type,
                    d.is_publish,
                    d.id,
                    d.parent_id,
                    d.node_name,
                    COUNT(c.id) children,
                    p.node_name parent_name

                FROM documents d
                LEFT JOIN documents c ON c.parent_id = d.id
                LEFT JOIN documents p ON p.id = d.parent_id

                WHERE d.id = %u
                GROUP BY d.id

                ",

                $target

            );


        }


        /**
         * not exists target
         */

        if (!$node) {

            storage::remove("__branchParent");

            throw new memberErrorException(
                view::$language->error,
                view::$language->branch_documents_not_found
            );

        }


        return $node;


    }


    /**
     * return children array
     */

    private function branchChildren($parent) {


        return db::query("

            SELECT

                ('node') type,
                c.is_publish,
                c.id,
                c.parent_id,
                c.node_name,
                COUNT(cc.id) children

            FROM documents c
            LEFT JOIN documents cc ON cc.parent_id = c.id

            WHERE c.parent_id = %u

            GROUP BY c.id
            ORDER BY c.lk ASC, c.node_name ASC

            ",

            $parent

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
                view::$language->error, view::$language->data_invalid_format
            );

        }

        if ($this->availableProtoTypes === null) {
            $this->getAvailableProtoTypes();
        }

        if (!array_key_exists($protoName, $this->availableProtoTypes)) {

            throw new memberErrorException(
                view::$language->error, view::$language->prototype_not_found
            );

        }

        return $this->availableProtoTypes[$protoName];


    }


    /**
     * build/rebuild main prototypes array
     */

    private function getAvailableProtoTypes() {


        $this->availableProtoTypes = array();
        $path = APPLICATION . "prototypes/*.php";

        foreach (utils::glob($path) as $item) {

            $protoName = basename($item, ".php");
            if (preg_match("/ProtoModel$/", $protoName)) {
                continue;
            }

            $this->availableProtoTypes[$protoName] = new $protoName;

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
                WHERE document_id = $current
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
                "name" => "menu[{$item['id']}]", "description" => $item['name']
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


        /**
         * set defaults
         */

        $parentNode = array();
        $newNode = array(

            "id"                 => "new",
            "parent_id"          => $parentID,
            "prototype"          => $protoName,
            "children_prototype" => $this->defaultProtoType,
            "is_publish"         => 1,
            "node_name"          => "",
            "parent_alias"       => "/"

        );


        /**
         * set different values
         */

        if ($parentID == 0) {

            $newNode['parent_name']  = view::$language->root_of_site;

        } else {


            /**
             * get exists parent
             */

            $parentNode = db::normalizeQuery(

                "SELECT node_name, prototype,
                    children_prototype cpt, page_alias
                        FROM documents WHERE id = %u", $newNode['parent_id']

            );

            if (!$parentNode) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_parent_not_found
                );

            }

            $newNode['prototype']          = $parentNode['cpt'];
            $newNode['children_prototype'] = $parentNode['cpt'];
            $newNode['parent_name']        = $parentNode['node_name'];


            /**
             * get parent alias
             */

            $parentProto  = $this->getProtoType($parentNode['prototype']);
            $parentFields = $parentProto->getPublicFields();

            if (in_array("page_alias", $parentFields, true)) {

                $newNode['parent_alias'] = rawurldecode(
                    $parentNode['page_alias']
                );

            }


        }


        /**
         * build new node prototype properties,
         * assign data into view
         */

        $this->buildNodeProperties($newNode);

        view::assign("in_menu", $this->getAvailableMenuList());
        view::assign("node", $newNode);


    }


    /**
     * check, prepare and assign
     * into view edited node properties
     */

    private function assignEditedNodeIntoView($nodeID, $protoName) {


        /**
         * get defaults
         */

        $editedNode = db::normalizeQuery("

            SELECT

                d.id,
                d.parent_id,
                d.prototype,
                d.children_prototype,
                d.is_publish,
                d.node_name,
                p.prototype parent_prototype,
                p.page_alias parent_alias,
                p.node_name parent_name

            FROM documents d
            LEFT JOIN documents p ON p.id = d.parent_id
            WHERE d.id = %u

            ",

            $nodeID

        );

        if (!$editedNode) {

            throw new memberErrorException(
                view::$language->error, view::$language->document_not_found
            );

        }


        /**
         * set different values
         */

        if ($editedNode['parent_id'] == 0) {

            $editedNode['parent_alias'] = "/";
            $editedNode['parent_name']  = view::$language->root_of_site;

        } else {


            /**
             * get parent alias
             */

            if (!$editedNode['parent_prototype']) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_parent_not_found
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


        /**
         * build edited node prototype properties,
         * assign data into view
         */

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


        /**
         * id field values
         */

        $mainProperties['id']['type'] = "hidden";
        $mainProperties['id']['selector'] = "documentid";
        unset($mainProperties['id']['description']);
        unset($mainProperties['id']['editor']);


        /**
         * parent_id field values
         */

        $mainProperties['parent_id']['type'] = "hidden";
        unset($mainProperties['parent_id']['description']);
        unset($mainProperties['parent_id']['editor']);


        /**
         * parent_alias field values
         */

        $mainProperties['parent_alias']['type'] = "hidden";
        $mainProperties['parent_alias']['selector'] = "parentalias";
        unset($mainProperties['parent_alias']['description']);
        unset($mainProperties['parent_alias']['editor']);


        /**
         * node_name field values
         */

        $mainProperties['node_name']['top']  = 20;
        $mainProperties['node_name']['type'] = "longtext";
        $mainProperties['node_name']['selector'] = "pagename";
        $mainProperties['node_name']['description']
            = view::$language->document_name;


        /**
         * prototype field values
         */

        $mainProperties['prototype']['top']  = 20;
        $mainProperties['prototype']['type'] = "select";
        $mainProperties['prototype']['description']
            = view::$language->document_type;

        $mainProperties['prototype']['value'] = $this->getProtoTypesList(
            $mainProperties['prototype']['value']
        );


        /**
         * children_prototype field values
         */

        $chpt = "children_prototype";
        $mainProperties[$chpt]['type'] = "select";
        $mainProperties[$chpt]['description']
            = view::$language->document_type_of_children;

        $mainProperties[$chpt]['value'] = $this->getProtoTypesList(
            $mainProperties[$chpt]['value']
        );


        /**
         * is_publish field values
         */

        $mainProperties['is_publish']['top']  = 20;
        $mainProperties['is_publish']['required'] = false;
        $mainProperties['is_publish']['type'] = "checkbox";
        $mainProperties['is_publish']['description']
            = view::$language->publish;


        /**
         * return all builded main properties
         */

        return $mainProperties;


    }


    /**
     * build all node properties
     */

    private function buildNodeProperties( & $node, $nodeID = null) {


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


        /**
         * required properties
         */

        $requiredParams = array(
            "parent_id", "prototype", "children_prototype", "node_name"
        );


        /**
         * fragmentation form data?
         */

        $requiredData = request::getRequiredPostParams($requiredParams);
        if ($requiredData === null) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_not_enough
            );

        }


        /**
         * validate parent ID
         */

        if (!validate::isNumber($requiredData['parent_id'])) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid_format
            );

        }


        /**
         * validate node prototype
         */

        $requiredData['prototype']
            = (string) $requiredData['prototype'];

        if (!$requiredData['prototype']) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid_format
            );

        }

        $this->getPrototype($requiredData['prototype']);


        /**
         * validate node children prototype
         */

        $requiredData['children_prototype']
            = (string) $requiredData['children_prototype'];

        if (!$requiredData['children_prototype']) {

            throw new memberErrorException(
                view::$language->error, view::$language->data_invalid_format
            );

        }

        $this->getPrototype($requiredData['children_prototype']);


        /**
         * validate name of node
         */

        $requiredData['node_name'] = filter::input(
            $requiredData['node_name'])
                ->stripTags()->typoGraph(true)->getData();

        if (!$requiredData['node_name']) {

            throw new memberErrorException(
                view::$language->error,
                view::$language->document_name_invalid_format
            );

        }


        /**
         * check for exists parent
         */

        if ($requiredData['parent_id'] > 0) {


            /**
             * check exists parent
             */

            $existsParent = db::normalizeQuery(

                "SELECT (1) ex FROM documents
                    WHERE id = %u", $requiredData['parent_id']

            );

            if (!$existsParent) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_parent_not_found
                );

            }


        }


        /**
         * check for correct parent (1)
         */

        if ($nodeID !== null and $nodeID == $requiredData['parent_id']) {

            throw new memberErrorException(
                view::$language->error,
                view::$language->document_cant_itself_parent
            );

        }


        /**
         * check for exists and valid node,
         * check for correct parent (2)
         */

        if ($nodeID) {


            /**
             * node is exists?
             */

            $currentKeys = db::normalizeQuery(

                "SELECT lk, rk FROM documents
                    WHERE id = %u", $nodeID

            );

            if (!$currentKeys) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_cant_itchild_parent
                );

            }


            /**
             * children is parent?
             */

            $isBrokenParent = db::query(

                "SELECT (1) ex FROM documents
                    WHERE lk > %u AND rk < %u LIMIT 1",
                        $currentKeys['lk'], $currentKeys['rk']

            );

            if ($isBrokenParent) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_cant_itchild_parent
                );

            }


        }


        /**
         * add is_publish property
         */

        $requiredData['is_publish']
            = request::getPostParam("is_publish") ? 1 : 0;


        /**
         * get main prototype model,
         * get main properties
         */

        $mainProtoModelName = $requiredData['prototype'] . "ProtoModel";
        $mainProtoModel = new $mainProtoModelName;

        $requiredData = array_merge(
            $requiredData, $mainProtoModel->getPreparedProperties()
        );

        return $requiredData;


    }


    /**
     * save new node data
     */

    private function saveNewNode() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(
            app::config()->site->admin_tools_link . "/documents/create\?parent=\d+", true
        );


        /**
         * get and check filtered data of new node
         */

        $newNode = $this->getFilteredRequiredInputData();


        /**
         * get nested set keys for new inserted node
         * check for exists parent
         */

        $nestedSetKeys = db::normalizeQuery(
            "SELECT lk, lvl FROM documents WHERE id = %u",
            $newNode['parent_id']
        );

        if (!$nestedSetKeys) {

            $nestedSetKeys['lvl'] = 0;
            $nestedSetKeys['lk']  = db::normalizeQuery(
                "SELECT MAX(rk) rk FROM documents"
            );

        }


        /**
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

        $insertQuery = "INSERT INTO documents ("
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
         * update nested set keys before insert new node
         */

        db::set(
            "UPDATE documents SET lk = lk + 2 WHERE lk > %u",
            $nestedSetKeys['lk']
        );

        db::set(
            "UPDATE documents SET rk = rk + 2 WHERE rk > %u",
            $nestedSetKeys['lk']
        );


        /**
         * insert all static data into documents,
         * get last insert ID for other transactions
         */

        db::set($insertQuery);


        /**
         * get last insert ID of NEW DOCUMENT,
         * save menu items
         */

        $newNode['id'] = db::lastID();
        //$this->saveMenuItems($newNode['id']);


    }


}



