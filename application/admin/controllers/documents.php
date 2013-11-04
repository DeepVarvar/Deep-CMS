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
     * check and prepare new node properties
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
         * join new node prototype properties,
         * join required node properties,
         * assign data into view
         */

        $this->buildNodeProperties($newNode);
        $this->joinRequiredNodeProperties($newNode);

        dump($newNode);

        view::assign("node", $newNode);


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
            "parent_id",
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
         * parent_id field values
         */

        $mainProperties['parent_id']['type'] = "hidden";
        unset($mainProperties['parent_id']['description']);
        unset($mainProperties['parent_id']['editor']);


        /**
         * node_name field values
         */

        $mainProperties['node_name']['description']
            = view::$language->document_name;


        /**
         * prototype field values
         */

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

    }


    /**
     * join required properties of node
     */

    private function joinRequiredNodeProperties( & $node) {

        $node['prototypes'] = $this->getProtoTypesList(
            $node['prototype']
        );

        $node['children_prototypes'] = $this->getProtoTypesList(
            $node['children_prototype']
        );

    }


}



