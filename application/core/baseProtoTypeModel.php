<?php




/**
 * basic prototype model
 */

class baseProtoTypeModel {


    protected $nodeID = null;
    protected $returnedFields = array();


    public function getValues($nodeID) {


        if (!$this->returnedFields) {
            return array();
        }

        if ($nodeID !== null) {

            $fields = join(",", array_keys($this->returnedFields));
            $values = db::normalizeQuery(

                "SELECT {$fields} FROM documents
                    WHERE id = %u", $nodeID

            );

            if (!$values) {

                throw new memberErrorException(
                    view::$language->error,
                    view::$language->document_dyn_props_not_found
                );

            }

            return $values;

        }

        return $this->returnedFields;


    }


    public function getProperties($nodeID) {


        $this->nodeID = $nodeID;
        $nodeProps = $this->getValues($nodeID);
        $mainProperties = array();
        $iterator = 0;

        foreach ($nodeProps as $k => $v) {

            $getter = $k . "GetData";
            $mainProperties[$k] = utils::getDefaultField($v);
            $mainProperties[$k]['sort'] = 10 + $iterator++;

            if (method_exists($this, $getter)) {
                $this->{$getter}($mainProperties[$k]);
            }

        }

        return $mainProperties;


    }


    public function getPropKeys() {
        return array_keys($this->returnedFields);
    }


}



