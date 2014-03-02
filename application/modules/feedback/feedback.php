<?php


/**
 * feedback module
 */

class feedback extends baseController {


    public function index() {

        $layoutName = 'feedback.html';
        if (!layoutUtils::isExistsProtectedLayout($layoutName)) {
            throw new memberErrorException(
                'Feedback error',
                'Dependency protected layout ' . $layoutName . ' is not exists'
            );
        }
        $this->setProtectedLayout($layoutName);

    }


    public function send() {


        view::clearPublicVariables();
        view::setOutputContext('json');
        view::lockOutputContext();

        $fields = array('name', 'email', 'message', 'protection');
        $data = request::getRequiredPostParams($fields);

        if ($data === null) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_data_not_enough
            );
        }

        $data = filter::input($data)->stripTags()->getData();
        if (!$data['name']) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_name_is_empty
            );
        }

        if (mb_strlen($data['name']) < 2) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_name_is_short
            );
        }

        if (!$data['email']) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_email_is_empty
            );
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
             throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_email_invalid
            );
        }

        if (!$data['message']) {
             throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_message_is_empty
            );
        }

        if (!$data['protection']) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_captcha_is_empty
            );
        }

        if ($data['protection'] !== storage::read('captcha')) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_captcha_invalid
            );
        }

        storage::shift('captcha');
        $feedCnf = app::loadConfig('feedback.json');
        $mainCnf = app::config();

        $mailer = new PHPMailer();
        $mailer->CharSet = 'utf-8';
        $mailer->SetFrom($feedCnf->robot_name . '@' . $mainCnf->site->domain);

        $mailer->AddAddress($feedCnf->recipient);
        $mailer->Subject = view::$language->feedback_new_mail_from
                    . ' ' . $mainCnf->site->domain;

        $template = APPLICATION . 'resources/feedback/feedback.html';
        if (!file_exists($template)) {
            throw new memberErrorException(
                view::$language->feedback_error,
                view::$language->feedback_mail_tpl_not_found
            );
        }

        $mailer->MsgHTML(
            str_replace(
                array(
                    '{{name}}',
                    '{{email}}',
                    '{{message}}',
                    '{{subject}}',
                    '{{sender_is}}',
                    '{{email_is}}'
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
            view::$language->feedback_we_will_get_you
        );

    }


}


