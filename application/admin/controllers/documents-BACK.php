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
         * available change frequency values for sitemap (SEO)
         */

        $availableChangefreq = array(

            "---",
            "never",
            "yearly",
            "monthly",
            "weekly",
            "daily",
            "hourly",
            "always"

        ),



        /**
         *  available priority range values for sitemap (SEO)
         */

        $searchPriorityRange = array(
            "---", "0.1", "0.2", "0.3", "0.4", "0.5", "0.6", "0.7", "0.8", "0.9", "1.0"
        );


    /**
     * override run before action
     */

    public function runBefore() {
        $this->root['node_name'] = view::$language->root_of_site;
    }


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
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
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
     * get list of available parents for change parent of document
     */

    public function get_available_parents() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/documents/((edit\?id)|(create\?parent))=\d+", true);


        /**
         * set main output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get and validate required target data
         */

        $target = array();
        $isAllParents = false;


        $required = array("branch_id", "id");
        foreach ($required as $key) {


            $target[$key] = request::shiftParam($key);

            if ($target[$key] === null) {
                throw new memberErrorException(view::$language->error, view::$language->data_not_enough);
            }


            /**
             * fix for ID of document
             */

            if ($key == "id" and $target[$key] == "new") {
                $isAllParents = true;
            }


            if (!$isAllParents and !validate::isNumber($target[$key])) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


        }


        /**
         * get available parents,
         * WARNING! this method same assign to view
         */

        $this->getAvailableParents($target['branch_id'], $target['id'], $isAllParents);


    }


    /**
     * delete document
     */

    public function delete() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(app::config()->site->admin_tools_link . "/documents(/branch\?id=\d+)?", true);


        /**
         * validate request ID
         */

        $targetID = request::shiftParam("id");
        if (!validate::isNumber($targetID)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * get target object
         */

        $target = db::normalizeQuery("

            SELECT

                d.id,
                d.parent_id,
                d.props_id,
                pt.sys_name

            FROM documents d
            INNER JOIN prototypes pt ON pt.id = d.prototype

            WHERE d.id = %u

            ",

            $targetID

        );


        if (!$target) {
            throw new memberErrorException(view::$language->error, view::$language->document_not_found);
        }


        /**
         * delete object data
         * from dynamic properties and menu_items
         */

        db::set("
            DELETE FROM {$target['sys_name']}
            WHERE id = {$target['props_id']}
        ");

        db::set("
            DELETE FROM menu_items
            WHERE document_id = {$target['id']}
        ");


        /**
         * get nested set keys for branch deleting
         */

        $nestedSetKeys = db::normalizeQuery(
            "SELECT lk, rk, (rk - lk + 1) gap
                FROM documents WHERE id = {$target['id']}"
        );


        /**
         * delete branch
         */

        db::set(
            "DELETE FROM documents WHERE lk BETWEEN %u AND %u",
            $nestedSetKeys['lk'],
            $nestedSetKeys['rk']
        );


        /**
         * update keys for other documents
         */

        db::set(
            "UPDATE documents SET rk = rk - %u WHERE rk > %u",
            $nestedSetKeys['gap'],
            $nestedSetKeys['rk']
        );

        db::set(
            "UPDATE documents SET lk = lk - %u WHERE lk > %u",
            $nestedSetKeys['gap'],
            $nestedSetKeys['rk']
        );


        /**
         * delete attached images
         */

        if ($images = db::query("SELECT name FROM images WHERE document_id = {$target['id']}")) {


            db::set("
                DELETE FROM images
                WHERE document_id = {$target['id']}
            ");


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
            "SELECT feature_id FROM document_features WHERE document_id = {$target['id']}"
        );

        if (!is_array($existsFeatureIDs)) {
            $existsFeatureIDs = array($existsFeatureIDs);
        }


        db::set(
            "DELETE FROM document_features WHERE document_id = {$target['id']}"
        );


        if ($existsFeatureIDs) {


            $existsFeatureIDs = join(",", $existsFeatureIDs);

            $lostIDs = db::normalizeQuery("

                SELECT

                    f.id

                FROM features f
                LEFT JOIN document_features df ON df.feature_id = f.id
                WHERE f.id IN({$existsFeatureIDs})
                    AND df.feature_id IS NULL

            ");

            if (!is_array($lostIDs)) {
                $lostIDs = array($lostIDs);
            }

            if ($lostIDs) {
                $lostIDs = join(",", $lostIDs);
                db::set("DELETE FROM features WHERE id IN({$lostIDs})");
            }


        }


        /**
         * redirect to show message
         */

        $location = app::config()->site->admin_tools_link
            . "/documents/branch?id=" . $target['parent_id'];

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->document_is_deleted,
            $location

        );


    }


    /**
     * view edit exists document form
     */

    public function edit() {


        /**
         * get target ID
         */

        $documentID = request::shiftParam("id");
        if (!validate::isNumber($documentID)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * update exists document, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->updateDocument($documentID);
        }


        /**
         * view edit exists document form,
         * assign data into view
         */

        $existsDocument = $this->getExistsDocumentProperties($documentID);
        $this->assignDocumentPropertiesIntoView($existsDocument);


        view::assign("page_title", view::$language->document_edit_exists);
        $this->setProtectedLayout("document-edit.html");


    }


    /**
     * view create new document form
     */

    public function create() {


        /**
         * get parent ID
         */

        $parentID = request::shiftParam("parent");
        if (!validate::isNumber($parentID)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * save new document, THROW inside, not working more
         */

        if (request::getPostParam("save") !== null) {
            $this->saveNewDocument();
        }


        /**
         * view new document form,
         * assign data into view
         */

        $newDocument = $this->getNewDocumentProperties($parentID);
        $this->assignDocumentPropertiesIntoView($newDocument);


        view::assign("page_title", view::$language->document_create_new);
        $this->setProtectedLayout("document-new.html");


    }


    /**
     * get dynamic properties
     */

    public function get_dynamic_properties() {


        /**
         * set main output context
         * and disable changes
         */

        view::setOutputContext("json");
        view::lockOutputContext();


        /**
         * get required parameters
         */

        $isNewDocument = false;

        $documentID  = request::shiftParam("id");
        $prototypeID = request::shiftParam("prototype_id");


        /**
         * is new document
         */

        if ($documentID == "new") {
            $isNewDocument = true;
        }


        /**
         * check document ID
         */

        if (!$isNewDocument and !validate::isNumber($documentID)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * check prototype ID
         */

        if (!validate::isNumber($prototypeID)) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * get static document properties
         */

        if ($isNewDocument) {
            $document = $this->getNewDocumentProperties(0);
        } else {
            $document = $this->getExistsDocumentProperties($documentID);
        }


        /**
         * assign dynamic properties into view
         */

        view::assign(
            "dynamic_properties",
            $this->getDynamicProperties($document, $prototypeID)
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
        } else {


            $node = db::normalizeQuery("

                SELECT

                    ('document') type,
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
            throw new memberErrorException(view::$language->error, view::$language->branch_documents_not_found);
        }


        return $node;


    }


    /**
     * return children array
     */

    private function branchChildren($parent) {


        return db::query("

            SELECT

                ('document') type,
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
     * get branch of new available parents
     */

    private function getAvailableParents($branchID, $documentID, $isAllParents) {


        /**
         * check for correct parent,
         * use (string) number because ( (int)0 == (string)"new" ) return true
         */

        if ((string) $branchID == $documentID) {
            throw new memberErrorException(view::$language->error, view::$language->document_cant_itself_parent);
        }


        /**
         * check for exists current target
         */

        if (!$isAllParents) {


            $existsTarget = db::query("
                SELECT (1) ex FROM documents
                WHERE id = %u", $documentID
            );

            if (!$existsTarget) {
                throw new memberErrorException(view::$language->error, view::$language->document_not_found);
            }


        }


        /**
         * check for exists branch
         */

        if ($branchID != 0) {


            $branchNode = db::normalizeQuery("
                SELECT (1) FROM documents
                WHERE id = %u", $branchID
            );


        } else {
            $branchNode = $this->root;
        }


        if (!$branchNode) {
            throw new memberErrorException(view::$language->error, view::$language->branch_parents_not_found);
        }


        /**
         * get branch children
         */

        $branchChildren = db::query("

            SELECT

                ('document') type,
                IF(c.id = %u, 1, 0) is_off,
                c.id,
                c.parent_id,
                c.node_name,
                c.page_alias,
                COUNT(cc.id) children

            FROM documents c
            LEFT JOIN documents cc ON cc.parent_id = c.id

            WHERE c.parent_id = %u

            GROUP BY c.id
            ORDER BY children DESC, c.lk ASC

            ",

            $documentID,
            $branchID

        );

        foreach ($branchChildren as $k => $v) {
            $branchChildren[$k]['page_alias'] = rawurldecode($v['page_alias']);
        }


        /**
         * assign into view
         */

        if (view::getOutputContext() == "html") {
            view::assign("node", $branchNode);
        }

        view::assign("children", $branchChildren);


    }


    /**
     * return array list of prototypes
     */

    private function getPrototypesList($current) {


        /**
         * get prototypes option list
         */

        if (!$availablePrototypes = utils::getAvailablePrototypes()) {
            throw new memberErrorException(view::$language->error, view::$language->prototypes_not_available);
        }


        $prototypes = array();
        foreach ($availablePrototypes as $item) {


            $prototype = array(

                "value"       => $item['id'],
                "description" => $item['name'],
                "selected"    => ($current == $item['id'])

            );


            array_push($prototypes, $prototype);


        }


        return $prototypes;


    }


    /**
     * return array list of search priority
     */

    private function getSearchPriorityList($current = null) {


        $priorityList = array();
        foreach ($this->searchPriorityRange as $item) {


            $priority = array(

                "description" => $item,
                "value"       => $item,
                "selected"    => ($current == $item)

            );


            array_push($priorityList, $priority);


        }


        return $priorityList;


    }


    /**
     * return array list of change frequency
     */

    private function getChangeFreqList($current = null) {


        $changefreq = array();
        foreach ($this->availableChangefreq as $item) {


            $cf = array(

                "description" => $item,
                "value"       => $item,
                "selected"    => ($current == $item)

            );


            array_push($changefreq, $cf);


        }


        return $changefreq;


    }


    /**
     * return array list of available public layouts
     */

    private function getAvailableLayoutsList($current = null) {


        $layouts = array();
        foreach (utils::getAvailablePublicLayouts() as $item) {


            $layout = array(

                "description" => $item,
                "value"       => $item,
                "selected"    => ($current == $item)

            );


            array_push($layouts, $layout);


        }


        return $layouts;


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

                "name"        => "menu[{$item['id']}]",
                "description" => $item['name'],
                "checked"     => $checked

            );


            array_push($menuItems, $elem);


        }


        return $menuItems;


    }


    /**
     * return array of dynamic properties
     */

    private function getDynamicProperties( & $document, $newPrototypeID = null) {


        /**
         * custom prototype ID,
         * if prototype is changed
         */

        $isChangedPrototype = false;
        if ($newPrototypeID !== null and $document['prototype'] != $newPrototypeID) {

            $document['old_prototype']  = $document['prototype'];
            $document['prototype']      = $newPrototypeID;
            $isChangedPrototype = true;


        }


        /**
         * get dynamic properties
         */

        $props = db::query("

            SELECT

                field_type,
                editor,
                name,
                description

            FROM field_types

            WHERE prototype = %u
            ORDER BY sort ASC

            ",

            $document['prototype']

        );

        $propsFields = array();
        foreach ($props as $field) {
            array_push($propsFields, $field['name']);
        }


        /**
         * get values of dynamic properties
         * for exists document
         * without changed prototype
         */

        if (!$isChangedPrototype and validate::isNumber($document['id'])) {


            $propsFields = join(",", $propsFields);

            /**
             * fix values array
             * for only one dynamic property
             */

            $values = array();


        }


        /**
         * get empty values for new document
         */

        if (!isset($values)) {

            $values = array();
            foreach ($propsFields as $field) {
                $values[$field] = "";
            }

        }


        /**
         * now build dynamic properties
         */

        $dynamicProperties = array();
        foreach ($props as $item) {


            $propertyID = "f" . md5(mt_rand() . microtime(true));
            $property = array(


                "editor"      => $item['editor'],
                "description" => $item['description'],


                "field" => array(

                    "type"    => $item['field_type'],
                    "id"      => $propertyID,
                    "name"    => $item['name'],
                    "value"   => $values[$item['name']]

                )


            );


            /**
             * checkbox checked attribute fix
             */

            if ($item['field_type'] == "checkbox") {
                $property['field']['checked'] = !(!$values[$item['name']]);
            }


            array_push($dynamicProperties, $property);


        }


        /**
         * return dynamic properties of document
         */

        return $dynamicProperties;


    }


    /**
     * assign into view all properties of document
     */

    private function assignDocumentPropertiesIntoView($document) {


        view::assign(
            "change_freq",
            $this->getChangeFreqList($document['change_freq'])
        );

        view::assign(
            "search_priority",
            $this->getSearchPriorityList($document['search_priority'])
        );

        view::assign(
            "children_prototypes",
            $this->getPrototypesList($document['children_prototype'])
        );

        view::assign(
            "prototypes",
            $this->getPrototypesList($document['prototype'])
        );

        view::assign(
            "in_menu",
            $this->getAvailableMenuList($document['id'])
        );

        view::assign(
            "layouts",
            $this->getAvailableLayoutsList($document['layout'])
        );

        view::assign(
            "dynamic_properties",
            $this->getDynamicProperties($document)
        );

        view::assign("confirmation", view::$language->document_change_type_confirm);
        view::assign("document", $document);


        /**
         * get available parents,
         * WARNING! this method same assign to view
         */

        $this->getAvailableParents(
            0, $document['id'], ($document['id'] == "new")
        );


    }


    /**
     * prepare exists document properties
     */

    private function getExistsDocumentProperties($documentID) {


        /**
         * attempt get document
         */

        $existsDocument = db::normalizeQuery("

            SELECT

                d.id,
                d.parent_id,
                d.prototype,
                d.children_prototype,
                d.is_publish,
                d.in_sitemap,
                d.page_alias,
                d.permanent_redirect,
                d.node_name,
                d.page_h1,
                d.page_title,
                d.meta_keywords,
                d.meta_description,
                d.layout,
                d.change_freq,
                d.search_priority,

                p.page_alias parent_alias,
                p.node_name parent_name

            FROM documents d

            LEFT JOIN documents p ON p.id = d.parent_id

            WHERE d.id = %u
            LIMIT 1

            ",

            $documentID

        );

        if (!$existsDocument) {
            throw new memberErrorException(view::$language->error, view::$language->document_not_found);
        }


        /**
         * fix document properties
         */

        if ($existsDocument['parent_id'] == 0) {

            $existsDocument['parent_alias'] = "/";
            $existsDocument['parent_name'] = view::$language->root_of_site;

        }


        /**
         * fix changefreq and search priority
         */

        if (!$existsDocument['change_freq']) {
            $existsDocument['change_freq'] = "---";
        }

        if (!$existsDocument['search_priority']) {
            $existsDocument['search_priority'] = "---";
        }


        /**
         * fix parent alias, page alias and redirect URL values
         */

        $existsDocument['parent_alias']
            = rawurldecode($existsDocument['parent_alias']);

        $existsDocument['page_alias']
            = rawurldecode($existsDocument['page_alias']);

        $existsDocument['permanent_redirect']
            = rawurldecode($existsDocument['permanent_redirect']);


        /**
         * return static properties
         */

        return $existsDocument;


    }


    /**
     * prepare new document properties
     */

    private function getNewDocumentProperties($parentID) {


        /**
         * set defaults
         */

        $newDocument = array(

            "id"              => "new",
            "parent_id"       => $parentID,
            "sort"            => 0,
            "layout"          => "",
            "change_freq"     => "---",
            "search_priority" => "---",

        );


        /**
         * get default prototype
         */

        if (!$defaultPrototype = utils::getAvailablePrototypes()) {
            throw new memberErrorException(view::$language->error, view::$language->prototypes_not_available);
        }

        $defaultPrototype = $defaultPrototype[0];


        /**
         * if parent is root of site
         */

        if ($parentID == 0) {


            $newDocument['parent_alias'] = "/";
            $newDocument['parent_name']  = view::$language->root_of_site;

            $newDocument['prototype']    = $defaultPrototype['id'];
            $newDocument['sys_name']     = $defaultPrototype['sys_name'];
            $newDocument['children_prototype']  = $defaultPrototype['id'];


        /**
         * if parent is not root of site
         */

        } else {


            /**
             * get exists parent
             */

            $existsParent = db::normalizeQuery("

                SELECT

                    p.node_name parent_name,
                    p.children_prototype prototype,
                    p.page_alias parent_alias,
                    pt.sys_name

                FROM documents p
                LEFT JOIN prototypes pt ON pt.id = p.children_prototype
                WHERE p.id = %u

                ",

                $newDocument['parent_id']

            );


            if (!$existsParent) {
                throw new memberErrorException(view::$language->error, view::$language->document_parent_not_found);
            }


            $existsParent['parent_alias'] = rawurldecode($existsParent['parent_alias']);
            $existsParent['children_prototype'] = $defaultPrototype['id'];

            $newDocument += $existsParent;


        }


        /**
         * return static properties
         */

        return $newDocument;


    }


    /**
     * return array of selected menu
     * from input data for save changes
     */

    private function getInMenuList() {


        /**
         * validate and stored menulist optional property
         */

        $menuList = request::getPostParam("menu");
        $inMenu = array();

        if ($menuList !== null) {


            if (!is_array($menuList)) {
                throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
            }


            foreach ($menuList as $k => $appendix) {

                if (!validate::isNumber($k)) {
                    throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
                }

                array_push($inMenu, $k);

            }


        }


        return $inMenu;


    }


    /**
     * get system name of prototype with ID
     */

    private function getSystemNameOfProrotype($id) {


        /**
         * check for exists object prototype
         */

        if (!$sysName = db::normalizeQuery("SELECT sys_name FROM prototypes WHERE id = %u", $id)) {
            throw new memberErrorException(view::$language->error, view::$language->prototype_not_found);
        }


        return $sysName;


    }


    /**
     * normalize URL string
     */

    private function normalizeInputUrl($str, $errorMessage) {


        if ($str and $str != "/") {


            $patterns = array("/['\"\\\]+/", "/[-\s]+/");
            $replace  = array("", "-");
            $str = substr(preg_replace($patterns, $replace, $str), 0, 2048);


            $domain = "(?P<domain>(?:(?:f|ht)tps?:\/\/[-a-z0-9]+(?:\.[-a-z0-9]+)*)?)";
            $path   = "(?P<path>(?:[^\?]*)?)";
            $params = "(?P<params>(?:\?[^=&]+=[^=&]+(?:&[^=&]+=[^=&]+)*)?)";
            $hash   = "(?P<hash>(?:#.*)?)";

            preg_match("/^{$domain}\/{$path}{$params}{$hash}$/s", $str, $m);

            if (!$m) {
                throw new memberErrorException(view::$language->error, $errorMessage);
            }


            $cParts = array();
            $sParts = trim(preg_replace("/\/+/", "/", $m['path']), "/");

            foreach (explode("/", $sParts) as $part) {
                array_push($cParts, rawurlencode($part));
            }

            $m['path'] = "/" . join("/" , $cParts);


            if ($m['domain'] and stristr(app::config()->site->domain, $m['domain'])) {
                $m['domain'] = "";
            }


            if ($m['params'] and $m['domain']) {


                $cParts = array();
                $sParts = trim(preg_replace("/&+/", "&", $m['params']), "&");

                foreach (explode("&", $sParts) as $part) {
                    array_push($cParts, rawurlencode($part));
                }

                $m['params'] = "?" . join("&" , $cParts);


            } else {
                $m['params'] = "";
            }


            if ($m['hash']) {
                $m['hash'] = rawurlencode(trim($m['hash'], "#"));
            }


            $str = $m['domain'] . $m['path'] . $m['params'] . $m['hash'];


        }


        return $str;


    }


    /**
     * return filtered required input form data of document
     */

    private function getFilteredRequiredInputData($documentID = null) {


        /**
         * get required static properties
         */

        $requiredParams = array(

            "parent_id",
            "prototype",
            "children_prototype",
            "node_name",
            "page_h1",
            "page_title",
            "meta_keywords",
            "meta_description",
            "change_freq",
            "search_priority",
            "sort",
            "page_alias",
            "permanent_redirect",
            "layout"

        );


        /**
         * fragmentation form data
         */

        $inputDocument = request::getRequiredPostParams($requiredParams);
        if ($inputDocument === null) {
            throw new systemErrorException(view::$language->error, view::$language->data_not_enough);
        }


        /**
         * validate parent ID
         */

        if (!validate::isNumber($inputDocument['parent_id'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * validate object ID and children objets ID
         */

        if (!validate::isNumber($inputDocument['prototype'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }

        if (!validate::isNumber($inputDocument['children_prototype'])) {
            throw new memberErrorException(view::$language->error, view::$language->data_invalid_format);
        }


        /**
         * validate name of document
         */

        if (!$inputDocument['node_name'] = filter::input($inputDocument['node_name'])->textOnly()->getData()) {
            throw new memberErrorException(view::$language->error, view::$language->document_name_invalid_format);
        }


        /**
         * validate change frequency value
         */

        $inputDocument['change_freq']
            = filter::input($inputDocument['change_freq'])->lettersOnly()->getData();

        if ($inputDocument['change_freq'] == "---") {
            $inputDocument['change_freq'] = "NULL";
        } else if (!in_array($inputDocument['change_freq'], $this->availableChangefreq)) {
            throw new memberErrorException(view::$language->error, view::$language->document_cf_invalid_format);
        }


        /**
         * validate search priority
         */

        $inputDocument['search_priority'] = (string) $inputDocument['search_priority'];

        if ($inputDocument['search_priority'] == "---") {
            $inputDocument['search_priority'] = "NULL";
        } else if (!in_array($inputDocument['search_priority'], $this->searchPriorityRange)) {
            throw new memberErrorException(view::$language->error, view::$language->document_sp_invalid_format);
        }


        /**
         * validate sort value,
         * for this error filter returned empty string,
         * see core/filter
         */

        $inputDocument['sort'] = filter::input($inputDocument['sort'])->getData();
        if (!validate::isNumber($inputDocument['sort'])) {
            throw new memberErrorException(view::$language->error, view::$language->document_sort_invalid_format);
        }


        /**
         * validate URL alias
         */

        $inputDocument['page_alias']
            = filter::input($inputDocument['page_alias'])
                ->stripTags()
                ->getData();

        if (!$inputDocument['page_alias']) {
            throw new memberErrorException(view::$language->error, view::$language->document_alias_invalid_format);
        }

        $inputDocument['page_alias'] = $this->normalizeInputUrl(

            $inputDocument['page_alias'],
            view::$language->document_alias_invalid_format

        );


        /**
         * validate layout filename,
         * check for exists layout file
         */

        if (!$inputDocument['layout'] = filter::input($inputDocument['layout'])->textOnly()->getData()) {
            throw new memberErrorException(view::$language->error, view::$language->document_layout_invalid_format);
        }

        if (!in_array($inputDocument['layout'], utils::getAvailablePublicLayouts())) {
            throw new memberErrorException(view::$language->error, view::$language->layout_not_found);
        }


        /**
         * filtered permanent redirect URL,
         * filtered h1, title, keywords, description
         */

        $inputDocument['permanent_redirect']
            = filter::input($inputDocument['permanent_redirect'])
                ->stripTags()
                ->getData();

        $inputDocument['permanent_redirect'] = $this->normalizeInputUrl(

            $inputDocument['permanent_redirect'],
            view::$language->document_redirect_invalid_format

        );


        $expectedSEOStringProps = array("page_h1", "page_title", "meta_keywords", "meta_description");
        foreach ($expectedSEOStringProps as $key) {

            $inputDocument[$key]
                = filter::input($inputDocument[$key])->textOnly()->getData();

        }


        /**
         * get optional boolean static properties,
         * request returned NULL if key not exists
         */

        $booleanOptionalProps = array("is_publish", "in_sitemap");
        foreach ($booleanOptionalProps as $key) {
            $inputDocument[$key] = request::getPostParam($key) ? 1 : 0;
        }


        /**
         * check for exists parent of document
         */

        if ($inputDocument['parent_id'] > 0) {

            $existsParent = db::query("
                SELECT (1) ex FROM documents
                WHERE id = %u", $inputDocument['parent_id']
            );

            if (!$existsParent) {
                throw new memberErrorException(view::$language->error, view::$language->document_parent_not_found);
            }

        }


        /**
         * check for correct parent
         */

        if ($documentID == $inputDocument['parent_id']) {
            throw new memberErrorException(view::$language->error, view::$language->document_cant_itself_parent);
        }


        if ($documentID) {


            $currentKeys = db::normalizeQuery(
                "SELECT lk, rk FROM documents WHERE id = {$documentID}"
            );

            $isBrokenParent = db::query(

                "SELECT (1) ex FROM documents
                    WHERE lk > %u AND rk < %u LIMIT 1",

                $currentKeys['lk'],
                $currentKeys['rk']

            );

            if ($isBrokenParent) {
                throw new memberErrorException(view::$language->error, view::$language->document_cant_itchild_parent);
            }


        }


        /**
         * check for exists prototype of document children
         */

        $existsChildrenPrototype = db::query("
            SELECT (1) ex FROM prototypes
            WHERE id = %u", $inputDocument['children_prototype']
        );

        if(!$existsChildrenPrototype) {
            throw new memberErrorException(view::$language->error, view::$language->prototype_c_not_found);
        }


        return $inputDocument;


    }


    /**
     * update all nested set keys on database for edited document
     */

    private function moveNestedSetKeys($documentID, $newParentID) {


        $currentPos = db::normalizeQuery(
            "SELECT lvl, lk, rk, parent_id
                FROM documents WHERE id = %u", $documentID
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
                FROM documents WHERE id = %u", $newParentID
        );

        $newParentKeys['lvl'] = !isset($newParentKeys['lvl'])
            ? 0 : ((int) $newParentKeys['lvl']);

        $newParentKeys['rk'] = isset($newParentKeys['rk'])
            ? ((int) $newParentKeys['rk'])
            : db::normalizeQuery("SELECT rk FROM documents ORDER BY rk DESC LIMIT 1");


        $skewLevel = $newParentKeys['lvl'] - $currentPos['lvl'] + 1;
        $skewTree  = $currentPos['rk'] - $currentPos['lk'] + 1;


        if ($newParentKeys['rk'] < $currentPos['rk']) {


            $skewEdit = $newParentKeys['rk'] - $currentPos['lk'] + 1;
            db::set("

                UPDATE documents SET

                    rk = IF(lk >= %u, rk + (%s), IF(rk < %u, rk + (%s), rk)),
                    lvl = IF(lk >= %u, lvl + (%s), lvl),
                    lk = IF(lk >= %u, lk + (%s), IF(lk > %u, lk + (%s), lk))

                WHERE rk > %u AND lk < %u

                ",

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


            $skewEdit = $newParentKeys['rk'] - $currentPos['lk'] + 1 - $skewTree;
            db::set("

                UPDATE documents SET

                    lk=IF(rk <= %u, lk + (%s), IF(lk > %u, lk - (%s), lk)),
                    lvl=IF(rk <= %u, lvl + (%s), lvl),
                    rk=IF(rk <= %u, rk + (%s), IF(rk <= %u, rk - (%s), rk))

                WHERE rk > %u AND lk <= %u

                ",

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
     * save menu items
     */

    private function saveMenuItems($documentID) {


        /**
         * delete exists rows from menu_items,
         * insert new inMenu data
         */

        db::set("
            DELETE FROM menu_items
            WHERE document_id = %u", $documentID
        );


        if ($inMenu = $this->getInMenuList()) {


            $insertedRows = array();
            foreach ($inMenu as $menuID) {
                array_push($insertedRows, "($menuID, $documentID)");
            }

            if ($insertedRows) {

                $insertedRows = join(",", $insertedRows);
                db::set("
                    INSERT INTO menu_items (menu_id,document_id)
                    VALUES {$insertedRows}"
                );

            }


        }


    }


    /**
     * save dynamic properties
     */

    private function saveDynamicProperties( & $document) {


        /**
         * exists previous dynamic properties
         */

        $insertNewPropsForExistsDocument = false;
        if (array_key_exists("old_prototype", $document)) {


            /**
             * new prototype of document is different
             */

            if ($document['old_prototype'] != $document['prototype']) {


                /**
                 * get system name of previous document prototype,
                 * delete previous dynamic properties
                 */

                $oldSysName = $this->getSystemNameOfProrotype($document['old_prototype']);
                $insertNewPropsForExistsDocument = true;

                db::set("
                    DELETE FROM {$oldSysName}
                    WHERE id = %u", $document['props_id']
                );


            }


        }


        /**
         * get system name of document prototype,
         * get and check optional dynamic properties
         */

        $sysName = $this->getSystemNameOfProrotype($document['prototype']);
        $dynamicProperties = array();

        $dynProps = db::query("
            SELECT field_type,editor,name FROM field_types
            WHERE prototype = %u ORDER BY sort ASC", $document['prototype']
        );


        foreach ($dynProps as $property) {


            /**
             * get value from request,
             * create filter object
             */

            $value  = request::getPostParam($property['name']);
            $filter = filter::input($value);


            /**
             * switch filter type
             */

            switch (true) {


                case ($property['field_type'] == "textarea" or $property['editor'] == 1):
                    $filter->cleanRichText()->typoGraph();
                break;


                case ($property['field_type'] == "checkbox"):

                    $value  = ($value !== null) ? 1 : 0;
                    $filter = filter::input($value);

                break;


                default:
                    $filter->textOnly();
                break;


            }


            /**
             * set filtered data
             */

            $dynamicProperties[$property['name']] = $filter->getData();


        }


        /**
         * update exists properties
         */

        if (!$insertNewPropsForExistsDocument and isset($document['id'], $document['props_id'])) {


            $sets = array();
            foreach ($dynamicProperties as $key => $value) {
                array_push($sets, $key . " = '" . db::escapeString($value) . "'");
            }

            $sets = join(",", $sets);


            db::set("
                UPDATE {$sysName} SET {$sets}
                WHERE id = %u", $document['props_id']
            );


        /**
         * insert new properties
         */

        } else {


            $dKeys = join(",", array_keys($dynamicProperties));
            $dValues = array_values($dynamicProperties);

            db::set("
                INSERT INTO {$sysName} ($dKeys)
                VALUES (%s)", $dValues
            );


            /**
             * signal for get new ID of dynamic properties
             */

            return true;


        }


        /**
         * signal of only updated dynamic properties
         */

        return false;


    }


    /**
     * save new document
     */

    public function saveNewDocument() {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(
            app::config()->site->admin_tools_link . "/documents/create\?parent=\d+", true
        );


        /**
         * get filtered static data of document,
         * insert new dynamic properties
         */

        $newDocument = $this->getFilteredRequiredInputData();
        $this->saveDynamicProperties($newDocument);


        /**
         * set default properties of new document,
         * get last insert ID from properties table
         */

        $newDocument['props_id'] = db::lastID();
        $newDocument['author']   = member::getID();

        $cfPlace = $newDocument['change_freq'] == "NULL"
            ? "%s" : "'%s'";

        $spPlace = $newDocument['search_priority'] == "NULL"
            ? "%s" : "'%01.1f'";


        /**
         * get nested set keys for new inserted document
         */

        $nestedSetKeys = db::normalizeQuery(
            "SELECT lk, lvl FROM documents WHERE id = %u",
            $newDocument['parent_id']
        );

        if (!$nestedSetKeys) {

            $nestedSetKeys['lvl'] = 0;
            $nestedSetKeys['lk']  = db::normalizeQuery(
                "SELECT MAX(rk) rk FROM documents"
            );

        }


        /**
         * update nested set keys before insert new document
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

        db::set("

            INSERT INTO documents (

                id,
                parent_id,
                lvl,
                lk,
                rk,
                prototype,
                children_prototype,
                props_id,
                is_publish,
                in_sitemap,
                sort,
                page_alias,
                permanent_redirect,
                node_name,
                page_h1,
                page_title,
                meta_keywords,
                meta_description,
                layout,
                author,
                last_modified,
                creation_date,
                change_freq,
                search_priority

            ) VALUES (

                NULL,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
                %u,
               '%s',
               '%s',
               '%s',
               '%s',
               '%s',
               '%s',
               '%s',
               '%s',
                %u,
                NOW(),
                NOW(),
               {$cfPlace},
               {$spPlace}

            )",

            $newDocument['parent_id'],
            $nestedSetKeys['lvl'] + 1,
            $nestedSetKeys['lk'] + 1,
            $nestedSetKeys['lk'] + 2,
            $newDocument['prototype'],
            $newDocument['children_prototype'],
            $newDocument['props_id'],
            $newDocument['is_publish'],
            $newDocument['in_sitemap'],
            $newDocument['sort'],
            $newDocument['page_alias'],
            $newDocument['permanent_redirect'],
            $newDocument['node_name'],
            $newDocument['page_h1'],
            $newDocument['page_title'],
            $newDocument['meta_keywords'],
            $newDocument['meta_description'],
            $newDocument['layout'],
            $newDocument['author'],
            $newDocument['change_freq'],
            $newDocument['search_priority']

        );


        /**
         * get last insert ID of NEW DOCUMENT,
         * save menu items
         */

        $newDocument['id'] = db::lastID();
        $this->saveMenuItems($newDocument['id']);


        /**
         * save attached images
         */

        $attachedImages = array();
        foreach (member::getStorageData($this->storageImagesKey) as $k => $v) {
            $k = db::escapeString($k);
            array_push($attachedImages, "(NULL, {$newDocument['id']}, {$v['is_master']}, '{$k}')");
        }

        if ($attachedImages) {

            $attachedImages = join(",", $attachedImages);

            db::set("

                INSERT INTO images
                    (id,document_id,is_master,name)
                VALUES {$attachedImages}

            ");

        }


        /**
         * save document features
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
                    $feature = "({$newDocument['id']}, {$ex['id']}, '{$escaped}')";

                    array_push($updFeatures, $feature);
                    unset($existsFeatures[$x]);

                    $updated = true;
                    break;


                }


            }


            if (!$updated) {

                array_push($insNames, "(NULL,'" . db::escapeString($v['name']) . "')");
                $insFeatures[$v['name']] = db::escapeString($v['value']);

            }


        }


        /**
         * save only new values
         */

        if ($updFeatures) {

            db::set("

                INSERT INTO document_features
                    (document_id, feature_id, feature_value)
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

                    $feature = "({$newDocument['id']}, {$ex['id']}, '{$insFeatures[$ex['name']]}')";
                    array_push($updNewFeatures, $feature);

                }

            }

            db::set("

                INSERT INTO document_features
                    (document_id, feature_id, feature_value)
                VALUES " . join(",", $updNewFeatures)

            );


        }


        /**
         * reset member cache
         */

        member::setStorageData($this->storageImagesKey, array());
        member::setStorageData($this->storageFeaturesKey, array());


        /**
         * redirect to show message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->document_is_created,
            app::config()->site->admin_tools_link . "/documents/branch?id={$newDocument['parent_id']}"

        );


    }


    /**
     * update exists document
     */

    private function updateDocument($documentID) {


        /**
         * validate referer of possible CSRF attack
         */

        request::validateReferer(
            app::config()->site->admin_tools_link . "/documents/edit\?id=\d+", true
        );


        /**
         * get exists update document
         */

        $existsDocument = db::normalizeQuery("
            SELECT id,props_id,prototype old_prototype FROM documents
            WHERE id = %u", $documentID
        );

        if (!$existsDocument) {
            throw new memberErrorException(view::$language->error, view::$language->document_not_found);
        }


        /**
         * get filtered static data of document,
         * update dynamic properties
         */

        $updateDocument = $this->getFilteredRequiredInputData($documentID);
        $updateDocument += $existsDocument;

        $cfPlace = $updateDocument['change_freq'] == "NULL"
            ? "%s" : "'%s'";

        $spPlace = $updateDocument['search_priority'] == "NULL"
            ? "%s" : "'%01.1f'";


        /**
         * get new ID of dynamic properties
         */

        if ($this->saveDynamicProperties($updateDocument)) {
            $updateDocument['props_id'] = db::lastID();
        }


        /**
         * move nested set keys for updated document
         */

        $this->moveNestedSetKeys(
            $updateDocument['id'], $updateDocument['parent_id']
        );


        /**
         * update all static data into documents
         */


        db::set("

            UPDATE documents SET

                parent_id          = %u,
                prototype          = %u,
                children_prototype        = %u,
                props_id           = %u,
                is_publish         = %u,
                in_sitemap         = %u,
                sort               = %u,
                page_alias         = '%s',
                permanent_redirect = '%s',
                node_name          = '%s',
                page_h1            = '%s',
                page_title         = '%s',
                meta_keywords      = '%s',
                meta_description   = '%s',
                layout             = '%s',
                last_modified      = NOW(),
                change_freq        = {$cfPlace},
                search_priority    = {$spPlace}

            WHERE id = %u

            ",

            $updateDocument['parent_id'],
            $updateDocument['prototype'],
            $updateDocument['children_prototype'],
            $updateDocument['props_id'],
            $updateDocument['is_publish'],
            $updateDocument['in_sitemap'],
            $updateDocument['sort'],
            $updateDocument['page_alias'],
            $updateDocument['permanent_redirect'],
            $updateDocument['node_name'],
            $updateDocument['page_h1'],
            $updateDocument['page_title'],
            $updateDocument['meta_keywords'],
            $updateDocument['meta_description'],
            $updateDocument['layout'],
            $updateDocument['change_freq'],
            $updateDocument['search_priority'],

            $updateDocument['id']

        );


        /**
         * save menu items
         */

        $this->saveMenuItems($updateDocument['id']);


        /**
         * redirect to show message
         */

        $this->redirectMessage(

            SUCCESS_EXCEPTION,
            view::$language->success,
            view::$language->document_is_edited,
            app::config()->site->admin_tools_link . "/documents/branch?id={$updateDocument['parent_id']}"

        );


    }


}



