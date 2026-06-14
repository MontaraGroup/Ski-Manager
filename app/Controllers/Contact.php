<?php

namespace App\Controllers;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('contact/index');
    }

    public function send()
    {
        // --- Anti-spam (silent: blocked spam sees a fake success) ---
        $fakeOk = redirect()->to('/contact')->with('success', 'Thanks for your message! We\'ll get back to you soon.');
        if (!empty($this->request->getPost('website'))) { return $fakeOk; }
        $formTime = (int) $this->request->getPost('form_time');
        if ($formTime <= 0 || (time() - $formTime) < 3) { return $fakeOk; }
        if ((time() - $formTime) > 7200) { return $fakeOk; }
        $rawMsg = (string) $this->request->getPost('message');
        if (preg_match_all('#https?://#i', $rawMsg) > 2) { return $fakeOk; }
        if (preg_match('#https?://|www\.#i', (string) $this->request->getPost('name'))) { return $fakeOk; }
        $name = strip_tags(trim($this->request->getPost('name')));
        $email = strip_tags(trim($this->request->getPost('email')));
        $subject = strip_tags(trim($this->request->getPost('subject')));
        $message = strip_tags(trim($this->request->getPost('message')));

        if (empty($name) || empty($email) || empty($message)) {
            return redirect()->back()->withInput()->with('error', 'Please fill in all required fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Please enter a valid email address.');
        }

        $subjectLabels = [
            'general' => 'General Question',
            'bug' => 'Bug Report',
            'feature' => 'Feature Suggestion',
            'account' => 'Account Issue',
            'other' => 'Other',
        ];

        $emailService = \Config\Services::email();
        $emailService->setFrom('contact@ski-manager.net', 'Ski Manager');
        $emailService->setTo('contact@ski-manager.net');
        $emailService->setReplyTo($email, $name);
        $emailService->setSubject('[Ski Manager] ' . ($subjectLabels[$subject] ?? 'Contact') . ' from ' . $name);
        $emailService->setMessage(
            "Name: {$name}\n" .
            "Email: {$email}\n" .
            "Subject: " . ($subjectLabels[$subject] ?? $subject) . "\n" .
            "---\n\n" .
            $message
        );

        if ($emailService->send()) {
            return redirect()->to('/contact')->with('success', 'Thanks for your message! We\'ll get back to you soon.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to send message. Please try again or contact us on Discord.');
        }
    }
}
