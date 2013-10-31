<?php



/**
 * xml output context builder class
 */

abstract class xmlOutput {


    protected static


        /**
         * xml output object
         */

        $xmlDOM = null;


    /**
     * build XML data string
     */

    public static function buildXMLString($data, $schema, $docType) {


        /**
         * create DOMDocument class example,
         * configure example
         */

        if ($docType === null) {
            self::$xmlDOM = new DOMDocument("1.0", "utf-8");
        } else {


            $imp = new DOMImplementation();
            $dtd = $imp->createDocumentType($docType['name'], '', $docType['id']);

            self::$xmlDOM = $imp->createDocument("", "", $dtd);
            self::$xmlDOM->encoding = "utf-8";


        }


        self::$xmlDOM->formatOutput = true;
        self::$xmlDOM->substituteEntities = true;


        /**
         * main attributes for view children
         */

        $mainAttributes = array();
        if(array_key_exists("attributes", $schema)) {
            $mainAttributes = $schema['attributes'];
        }

        /**
         * normalize data
         */

        if (sizeof($data) > 1) {
            $data = array("response" => $data);
        }


        /**
         * set schema for children,
         * create children, return result
         */

        self::createXmlChildren($data, self::$xmlDOM, $mainAttributes, array($schema));
        return self::$xmlDOM->saveXML();


    }


    /**
     * create xml children with schema
     */

    private static function createXmlChildren( & $data, & $parentNode, & $parentSchema, $schemaElements = null) {


        /**
         * each data
         */

        if (is_array($data)) {


            $dataLength = sizeof($data);
            foreach ($data as $key => $value) {


                /**
                 * set defaults for current element
                 */

                $isNumericItems = true;
                $useSchemaElement = false;

                $currentSchema = array(

                    "name"       => "item",
                    "attributes" => array(),
                    "attrvalues" => array()

                );


                /**
                 * name of element can't is numeric
                 */

                if (!validate::isNumber($key)) {

                    $currentSchema['name'] = $key;
                    $isNumericItems = false;

                }


                /**
                 * exists schemas for current level
                 */

                if ($schemaElements !== null) {


                    /**
                     * get main schema from schemas
                     */

                    foreach ($schemaElements as $schemaElement) {


                        if ($isNumericItems or $currentSchema['name'] == $schemaElement['name']) {


                            /**
                             * merge current schema and schema element,
                             * break now, use first schema
                             */

                            $currentSchema = array_merge($currentSchema, $schemaElement);
                            $useSchemaElement = true;
                            break;


                        }


                    }


                }


                /**
                 * value is array, need recursive execute
                 */

                if (is_array($value)) {


                    /**
                     * create node element
                     */

                    $element = self::$xmlDOM->createElement($currentSchema['name']);


                    /**
                     * get schema for children
                     */

                    $childrenSchema = null;
                    if (array_key_exists("children", $currentSchema)) {
                        $childrenSchema = $currentSchema['children'];
                    }


                    /**
                     * now recursive make children
                     */

                    self::createXmlChildren($value, $element, $currentSchema, $childrenSchema);


                    /**
                     * set attributes of element,
                     * WARNING! SET ONLY AFTER MAKE CHILDREN,
                     * BECAUSE CHILDREN MAYBE SET THIS ATTRIBUTES!
                     *
                     * append element into parent node
                     */

                    self::setElementAttributes($element, $currentSchema);
                    $parentNode->appendChild($element);


                /**
                 * value is not array
                 */

                } else {


                    /**
                     * validate value
                     */

                    if (is_object($value) or is_resource($value)) {
                        throw new systemErrorException("Schema XML error", "Value of schema element is not string");
                    }


                    /**
                     * set parent attributes
                     */

                    $isParentAttributeSet = false;
                    foreach ($parentSchema['attributes'] as $k => $attribute) {

                        if ($currentSchema['name'] == $attribute['name']) {
                            $parentSchema['attrvalues'][$attribute['name']] = $value;
                            $isParentAttributeSet = true;
                        }

                    }


                    /**
                     * i'm not set attributes for parent?
                     * ok, append my simple string data
                     */

                    $acceptValue = (!is_bool($value) and $value !== "" and $value !== null);

                    if (!$isParentAttributeSet and $acceptValue) {


                        if ($dataLength == 1 and $parentNode->childNodes->length == 0) {
                            $parentNode->appendChild(self::$xmlDOM->createTextNode($value));
                        } else {


                            /**
                             * create single element,
                             * set element attributes
                             */

                            $element = self::$xmlDOM->createElement($currentSchema['name']);
                            self::setElementAttributes($element, $currentSchema);


                            /**
                             * append text node into element,
                             * append element into parent node
                             */

                            $element->appendChild(self::$xmlDOM->createTextNode($value));
                            $parentNode->appendChild($element);


                        }


                    }


                }


                $dataLength--;


            }


        }


    }


    /**
     * set attributes of element with schema
     */

    private static function setElementAttributes( & $element, & $data) {


        if (array_key_exists("attributes", $data)) {


            foreach ($data['attributes'] as $attribute) {


                $name  = $attribute['name'];
                $value = $attribute['value'];


                if ($value === true) {


                    /**
                     * required value from data
                     */

                    $element->setAttribute($name, $data['attrvalues'][$name]);


                } else if ($value === false) {


                    /**
                     * custom value from data
                     */

                    if (array_key_exists($name, $data['attrvalues'])) {
                        if ($data['attrvalues'][$name] != "") {
                            $element->setAttribute($name, $data['attrvalues'][$name]);
                        }
                    }


                } else {


                    /**
                     * custom value from schema
                     */

                    $element->setAttribute($attribute['name'], $attribute['value']);


                }


            }


        }


    }


}



