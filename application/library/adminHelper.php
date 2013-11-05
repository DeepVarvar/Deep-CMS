<?php



/**
 * admin helper class
 */

abstract class adminHelper {


    private static $admUrl = null;
    private static $member = null;

    private static $expectedFieldTypes = array(
        "hidden",
        "longtext",
        "select",
        "checkbox",
        "textarea"
    );


    /**
     * return admin menu items array
     */

    public static function getMenuItems() {


        return array(

            array(
                "name" => "documents_tree",
                "link" => "/documents"
            ),

            array(
                "name" => "menu_of_site",
                "link" => "/menu"
            ),

            array(
                "name" => "users",
                "link" => "/users"
            ),

            array(
                "name" => "groups",
                "link" => "/groups"
            )

        );


    }


    public static function getForm($dataArray) {


        self::$admUrl = app::config()->site->admin_tools_link;
        self::$member = member::getProfile();

        $outputString = "";
        $helper = new ReflectionClass(__CLASS__);

        foreach ($dataArray as $key => $props) {

            if (!in_array($props['type'], self::$expectedFieldTypes, true)) {
                continue;
            }

            $renderMethod = $props['type'] . "Draw";
            if ($helper->hasMethod($renderMethod)) {

                $outputString .= $helper->getMethod($renderMethod)
                    ->invoke(__CLASS__, $key, $props);

            }

        }

        return $outputString;


    }


    public static function hiddenDraw($key, $props) {

        ?>

            <div>
                <input id="<?=$props['selector']?>" type="hidden"
                    name="<?=$key?>" value="<?=$props['value']?>" />
            </div>

        <?php

    }


    public static function longtextDraw($key, $props) {

        ?>

            <div class="label mt-<?=$props['top']?>">
                <?=$props['description']?>:
            </div>
            <div class="elem mt-<?=$props['top']?>">
                <input id="<?=$props['selector']?>"
                    class="<?=$props['required']?' required':''?>"
                    type="text" name="<?=$key?>" value="<?=$props['value']?>" />
            </div>

        <?php

    }


    public static function selectDraw($key, $props) {

        ?>

            <div class="label mt-<?=$props['top']?>">
                <?=$props['description']?>:
            </div>
            <div class="elem mt-<?=$props['top']?>">
                <select id="<?=$props['selector']?>"
                    class="<?=$props['required']?' required':''?>" name="<?=$key?>">
                    <?=htmlHelper::drawOptionList($props['value'])?>
                </select>
            </div>

        <?php

    }


    public static function checkboxDraw($key, $props) {

        ?>

            <div class="label mt-<?=$props['top']?>">
                <?=$props['description']?>:
            </div>
            <div class="elem mt-<?=$props['top']?>">
                <input class="<?=$props['required']?' required':''?>"
                    type="checkbox" name="<?=$key?>"
                    <?=$props['value']?' checked="checked"':''?> />
            </div>

        <?php

    }


    public static function textareaDraw($key, $props) {

        ?>

            <?php if ($props['editor']) { ?>

                <div class="label mt-<?=$props['top']?>">
                <?=$props['description']?>:</div>
                <div class="elem mt-<?=$props['top']?>">&nbsp;</div>
                <div class="longblock">

                    <textarea class="<?=$props['required']?' required':''?>"
                        id="<?=$props['selector']?>"
                        name="<?=$key?>"><?=$props['value']?></textarea>

                    <script type="text/javascript">

                        CKEDITOR.replace('<?=$props['selector']?>');
                        fm.bind({
                            targetObj  : CKEDITOR.instances['<?=$props['selector']?>'],
                            targetName : '<?=$props['selector']?>',
                            fmUrl      : '<?=self::$admUrl?>/document-images?target=<?=$props['node_id']?>',
                            language   : '<?=self::$member['language']?>'
                        });

                    </script>

                </div>

            <?php } else { ?>

                <div class="label mt-<?=$props['top']?>">
                    <?=$props['description']?>:
                </div>
                <div class="elem mt-<?=$props['top']?>">
                    <textarea class="<?=$props['required']?' required':''?>"
                        id="<?=$props['selector']?>"
                        name="<?=$key?>"><?=$props['value']?></textarea>
                </div>

            <?php } ?>

        <?php

    }


}



