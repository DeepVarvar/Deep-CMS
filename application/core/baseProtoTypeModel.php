<?php


/**
 * basic prototype model
 */

class baseProtoTypeModel {


    protected $nodeID = null;
    protected $returnedFields = array();
    protected $preparedProperties = array();


    public function getValues($nodeID) {

        if (!$this->returnedFields) {
            return array();
        }

        if ($nodeID !== null) {

            $fields = join(',', array_keys($this->returnedFields));
            $values = db::normalizeQuery(
                'SELECT ' . $fields . ' FROM tree WHERE id = %u', $nodeID
            );

            return $values ? $values : $this->returnedFields;

        }

        return $this->returnedFields;

    }


    public function getProperties($nodeID) {

        $this->nodeID = $nodeID;
        $nodeProps = $this->getValues($nodeID);
        $mainProperties = array();
        $iterator = 0;

        foreach ($nodeProps as $k => $v) {

            $getter = $k . 'GetData';
            $mainProperties[$k] = protoUtils::getDefaultField($v);
            $mainProperties[$k]['sort'] = 10 + $iterator++;

            if (method_exists($this, $getter)) {
                $this->{$getter}($mainProperties[$k]);
            } else {
                unset($mainProperties[$k]);
            }

        }

        return $mainProperties;

    }


    public function getPreparedProperties() {


        $mainProperties = array_keys($this->returnedFields);
        foreach ($mainProperties as $key) {

            $setter = $key . 'Prepare';
            $this->preparedProperties[$key] = request::getPostParam($key);

            if (method_exists($this, $setter)) {
                $this->{$setter}($this->preparedProperties[$key]);
            } else {

                if ($this->preparedProperties[$key] === null) {
                    throw new memberErrorException(
                        view::$language->error, view::$language->data_not_enough
                    );
                }

                $this->preparedProperties[$key] = filter::input(
                    $this->preparedProperties[$key])->stripTags()->getData();

            }

        }

        return $this->preparedProperties;

    }


}


