<?php
/**
 * WoodCon - Liên hệ
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\Contact;

class ContactController extends BaseController
{
    public function index(): void
    {
        if ($this->isPost()) {
            if (!verify_csrf($this->post('_token'))) {
                set_flash('error', 'Yêu cầu không hợp lệ.');
                redirect(BASE_URL . '/lien-he');
            }
            $errors = validate_form($_POST, [
                'name'    => ['required' => true, 'max' => 120],
                'phone'   => ['required' => true, 'max' => 20],
                'email'   => ['email' => true],
                'subject' => ['max' => 150],
                'message' => ['required' => true, 'max' => 2000],
            ]);
            if ($errors) {
                set_flash('error', reset($errors));
                redirect(BASE_URL . '/lien-he');
            }
            Contact::insert([
                'name'    => trim($this->post('name')),
                'phone'   => trim($this->post('phone')),
                'email'   => trim($this->post('email')) ?: null,
                'subject' => trim($this->post('subject')) ?: null,
                'message' => trim($this->post('message')),
            ]);
            set_flash('success', 'Cảm ơn bạn đã liên hệ. WoodCon sẽ phản hồi trong thời gian sớm nhất.');
            redirect(BASE_URL . '/lien-he');
        }

        $this->render('contact', ['pageTitle' => 'Liên hệ - WoodCon']);
    }
}