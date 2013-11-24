<?php



/**
 * feedback module
 */

class feedback extends baseController {


    public function index() {


        $layoutName = "feedback.html";
        if (!utils::isExistsProtectedLayout($layoutName)) {

            throw new memberErrorException(
                "Feedback error",
                    "Dependency protected layout {$layoutName} is not exists"
            );

        }

        if (!$captchaUrl = db::normalizeQuery(
            "SELECT page_alias FROM tree WHERE
                module_name = 'captcha' AND is_publish = 1 LIMIT 1"
        )) {

            throw new memberErrorException(
                view::$language->error,
                    view::$language->captcha_mod_is_disabled
            );

        }

        view::assign("captcha_url", $captchaUrl);
        $this->setProtectedLayout($layoutName);


    }


    public function send() {


        view::clearPublicVariables();
        view::setOutputContext("json");
        view::lockOutputContext();

        $fields = array("name", "email", "message", "protection");
        $data = request::getRequiredPostParams($fields);

        if ($data === null) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->data_not_enough
            );
        }

        $data = filter::input($data)->stripTags()->getData();
        if (!$data['name']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->feedback_name_is_empty
            );
        }

        if (mb_strlen($data['name'], "UTF-8") < 2) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->feedback_name_is_short
            );
        }

        if (!$data['email']) {
            throw new memberErrorException(
                view::$language->error, view::$language->email_is_empty
            );
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
             throw new memberErrorException(
                view::$language->error, view::$language->email_invalid
            );
        }

        if (!$data['message']) {
             throw new memberErrorException(
                view::$language->error,
                    view::$language->feedback_message_is_empty
            );
        }

        if (!$data['protection']) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->captcha_pcode_is_empty
            );
        }

        if ($data['protection'] !== storage::read("captcha")) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->captcha_pcode_invalid
            );
        }

        storage::shift("captcha");
        $feedCnf = app::loadConfig("feedback.json");
        $mainCnf = app::config();

        $mailer = new PHPMailer();
        $mailer->CharSet = "utf-8";
        $mailer->SetFrom($feedCnf->robot_name . "@" . $mainCnf->site->domain);

        $mailer->AddAddress($feedCnf->recipient);
        $mailer->Subject = view::$language->feedback_new_mail_from
                    . " " . $mainCnf->site->domain;

        $template = APPLICATION
            . $mainCnf->path->resources . "phpmailer/feedback.html";

        if (!file_exists($template)) {
            throw new memberErrorException(
                view::$language->error,
                    view::$language->feedback_mail_tpl_not_found
            );
        }

        $mailer->MsgHTML(
            str_replace(
                array(
                    "{{name}}",
                    "{{email}}",
                    "{{message}}",
                    "{{subject}}",
                    "{{sender_is}}",
                    "{{email_is}}"
                ),
                array(
                    $data['name'],
                    $data['email'],
                    $data['message'],
                    $mailer->Subject,
                    view::$language->feedback_mail_sender_is,
                    view::$language->feedback_mail_email_is
                ), file_get_contents($template)
            )
        );

        $mailer->Send();
        throw new memberSuccessException(
            view::$language->feedback_thanks,
                view::$language->feedback_we_will_get
        );

    }


}



