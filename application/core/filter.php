<?php



/**
 * data filter class
 */

class filter {


    /**
     * disabled autotrim input data,
     * and autotrim value for new filter objects
     */

    const NOTRIM = false;
    protected $autotrim = true;


    protected


        /**
         * input source data
         */

        $input = array(),


        /**
         * output data
         */

        $output = array(),


        /**
         * input data type
         */

        $isArray = false;


    /**
     * filter constructor
     */

    public static function input($input = null, $autotrim = true) {


        /**
         * create new self example
         */

        $example = new self();
        $example->autotrim = $autotrim;


        $example->input = $input;
        unset($input);


        if (is_array($example->input)) {


            $example->isArray = true;
            $example->output = $example->input;


            /**
             * normalize inner array data
             */

            $example->normalizeInnerFormat();


        } else {
            $example->output = array((string) $example->input);
        }


        /**
         * autotrimmer
         */

        if ($example->autotrim === true) {
            $example->trim();
        }


        return $example;


    }


    /**
     * trimmer
     */

    public function trim($pattern = null) {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = $pattern !== null ? trim($item, $pattern) : trim($item);
        }

        return $this;

    }


    /**
     * eraser
     */

    public function erase($patterns) {
        return $this->replace($patterns, "");
    }


    /**
     * replacer
     */

    public function replace($patterns, $replacement) {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = str_replace($patterns, $replacement, $item);
        }

        return $this;

    }


    /**
     * eraser with regular expressions
     */

    public function expErase($patterns) {
        return $this->expReplace($patterns, "");
    }


    /**
     * replacer with regular expressions
     */

    public function expReplace($patterns, $replacement) {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = preg_replace($patterns, $replacement, $item);
        }

        return $this;

    }


    /**
     * strip tags on data
     */

    public function stripTags() {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = strip_tags($item);
        }

        return $this;

    }


    /**
     * htmlspecialchars on data
     */

    public function htmlSpecialChars() {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = htmlspecialchars($item);
        }

        return $this;

    }


    /**
     * md5 hash of data
     */

    public function md5() {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = md5($item);
        }

        return $this;

    }


    /**
     * erase all non alpfabet or numeric symbols
     */

    public function lettersOnly() {

        foreach ($this->output as $k => $item) {
            $this->output[$k] = preg_replace("/[^\p{L}\p{M}\p{Nd}-_ ]/u", "", $item);
        }

        return $this;

    }


    /**
     * erase all non plain text symbols
     */

    public function textOnly() {

        $this->stripTags()->htmlSpecialChars();
        return $this;

    }


    /**
     * normalize inner data format
     */

    private function normalizeInnerFormat() {


        foreach ($this->output as $k => $item) {


            if (!utils::likeString($item)) {
                $item = null;
            }

            $this->output[$k] = (string) $item;


        }


    }


    /**
     * return source data
     */

    public function getSourceData() {
        return $this->input;
    }


    /**
     * return data
     */

    public function getData() {
        $this->trim();
        return (!$this->isArray) ? $this->output[0] : $this->output;
    }


    /**
     * clean html (rich) text,
     * i'm use end of pattern: \/{0,1}>
     * because my editor show
     * broken syntax highlight for end of pattern: \/?>
     * but this is fucking closed php tag! :(
     */

    public function cleanRichText() {


        foreach ($this->output as $k => $item) {


            /**
             * other clean
             */

            $this->output[$k] = preg_replace_callback("/<\w+([^>]*)\/{0,1}>/u", "cleanRichTextCallback", $item);
            $this->output[$k] = preg_replace("/<scr.*ipt>/s", "", $this->output[$k]);


            /**
             * mozilla firefox drag-n-drop base64
             */

            $this->output[$k] = preg_replace('/src="data:.+"/s', 'src=""', $this->output[$k]);


        }


        return $this;


    }


}


/**
 * clean html (rich) text callback,
 * use function because need compatible
 * for php versions older than 5.2.3
 */

function cleanRichTextCallback($args) {
    preg_match_all("/\s+(id|class|name|type|value|alt|title|src|href|allowfullscreen|allowscriptaccess|frameborder|scrolling|height|width|target|style)=\"[^\"]+\"/u", $args[1], $sub);
    return str_replace($args[1], join($sub[0]), $args[0]);
}



