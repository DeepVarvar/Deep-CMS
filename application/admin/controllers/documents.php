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

        //$newDocument = $this->getNewDocumentProperties($parentID);
        //$this->assignDocumentPropertiesIntoView($newDocument);


        view::assign("node_name", view::$language->document_create_new);
        $this->setProtectedLayout("document-new.html");


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


}



