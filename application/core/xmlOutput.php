<?php


/**
 * xml output context builder class
 */

abstract class xmlOutput {


    /**
     * xml output object
     */

    protected static $xmlDOM = null;


    /**
     * build XML data string,
     * create DOMDocument class example, configure example,
     * return result string
     */

    public static function buildXMLString($data, $schema, $docType) {

        if ($docType === null) {
            self::$xmlDOM = new DOMDocument("1.0", "utf-8");
        } else {

            $imp = new DOMImplementation();
            $dtd = $imp->createDocumentType(
                $docType['name'], '', $docType['id']
            );

            self::$xmlDOM = $imp->createDocument("", "", $dtd);
            self::$xmlDOM->encoding = 'utf-8';

        }

        self::$xmlDOM->formatOutput = true;
        self::$xmlDOM->substituteEntities = true;

        $mainAttributes = array();
        if(array_key_exists('attributes', $schema)) {
            $mainAttributes = $schema['attributes'];
        }

        if (sizeof($data) > 1) {
            $data = array('response' => $data);
        }

        self::createXmlChildren(
            $data, self::$xmlDOM, $mainAttributes, array($schema)
        );

        return self::$xmlDOM->saveXML();

    }


    /**
     * create xml children with schema
     */

    private static function createXmlChildren(
        & $data, & $parentNode, & $parentSchema, $schemaElements = null) {

        if (is_array($data)) {

            $dataLength = sizeof($data);
            foreach ($data as $key => $value) {

                $useSchemaElement = false;
                $isNumericItems   = true;

                $currentSchema = array(
                    'name'       => 'item',
                    'attributes' => array(),
                    'attrvalues' => array(),
                    'repeat'     => false
                );

                if (!validate::isNumber($key)) {
                    $currentSchema['name'] = $key;
                    $isNumericItems = false;
                }

                if ($schemaElements !== null) {

                    foreach ($schemaElements as $schemaElement) {

                        if ($isNumericItems or
                            $currentSchema['name'] == $schemaElement['name']) {

                            $currentSchema = array_merge(
                                $currentSchema, $schemaElement
                            );

                            $useSchemaElement = true;
                            break;

                        }

                    }

                }


                /**
                 * value is array, need recursive execute
                 */

                if (is_array($value)) {

                    $childrenSchema = null;
                    if (array_key_exists('children', $currentSchema)) {
                        $childrenSchema = $currentSchema['children'];
                    }

                    if ($currentSchema['repeat'] !== true) {
                        $value = array($value);
                    }

                    foreach ($value as $vv) {

                        $element = self::$xmlDOM->createElement(
                            $currentSchema['name']
                        );

                        self::createXmlChildren(
                            $vv, $element, $currentSchema, $childrenSchema
                        );


                        /**
                         * set attributes of element,
                         * WARNING! SET ONLY AFTER MAKE CHILDREN,
                         * BECAUSE CHILDREN MAYBE SET THIS ATTRIBUTES!
                         *
                         * append element into parent node
                         */

                        self::setElementAttributes($element, $currentSchema);
                        $parentNode->appendChild($element);

                    }

                /**
                 * value is not array
                 */

                } else {

                    if (is_object($value) or is_resource($value)) {
                        throw new systemErrorException(
                            'Schema XML error',
                            'Value of schema element is not string'
                        );
                    }

                    $isParentAttributeSet = false;
                    foreach ($parentSchema['attributes'] as $k => $attribute) {
                        if ($currentSchema['name'] == $attribute['name']) {
                            $parentSchema['attrvalues'][$attribute['name']]
                                = $value;
                            $isParentAttributeSet = true;
                        }
                    }

                    $acceptValue = (!is_bool($value)
                        and $value !== '' and $value !== null);

                    if (!$isParentAttributeSet and $acceptValue) {

                        if ($dataLength == 1
                            and $parentNode->childNodes->length == 0) {

                            $parentNode->appendChild(
                                self::$xmlDOM->createTextNode($value)
                            );

                        } else {

                            $element = self::$xmlDOM->createElement(
                                $currentSchema['name']
                            );

                            self::setElementAttributes(
                                $element, $currentSchema
                            );

                            $element->appendChild(
                                self::$xmlDOM->createTextNode($value)
                            );

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

        if (array_key_exists('attributes', $data)) {

            foreach ($data['attributes'] as $attribute) {

                $name  = $attribute['name'];
                $value = $attribute['value'];

                // required value from data
                if ($value === true) {
                    $element->setAttribute($name, $data['attrvalues'][$name]);
                // custom value from data
                } else if ($value === false) {
                    if (array_key_exists($name, $data['attrvalues'])) {
                        if ($data['attrvalues'][$name] != '') {
                            $element->setAttribute(
                                $name, $data['attrvalues'][$name]
                            );
                        }
                    }
                // custom value from schema
                } else {
                    $element->setAttribute(
                        $attribute['name'], $attribute['value']
                    );
                }

            }

        }

    }


}


