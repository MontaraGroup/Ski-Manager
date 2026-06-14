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
        // 6. Per-IP rate limit: max 3 submissions per hour
        $throttler = \Config\Services::throttler();
        if ($throttler->check(md5('contact_' . $this->request->getIPAddress()), 3, HOUR) === false) {
            return redirect()->to('/contact')->with('error', 'You have sent several messages recently. Please try again later.');
        }
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

        $subjectLabel = $subjectLabels[$subject] ?? 'Contact';

        // Triage metadata
        $sentAt   = date('Y-m-d H:i:s T');
        $ip       = $this->request->getIPAddress();
        $ua       = strip_tags((string) $this->request->getUserAgent()->getAgentString());
        $loggedIn = auth()->loggedIn();
        $userLine = $loggedIn
            ? esc(auth()->user()->username) . ' (ID ' . (int) auth()->id() . ')'
            : 'Not logged in';

        $safeName    = esc($name);
        $safeEmail   = esc($email);
        $safeMsgHtml = nl2br(esc($message));

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden">'
            . '<div style="background:#1e3a5f;color:#fff;padding:16px 20px">'
            . '<h2 style="margin:0;font-size:18px">⛷ Ski Manager — New Contact Message</h2>'
            . '<p style="margin:4px 0 0;font-size:13px;opacity:0.8">' . $subjectLabel . '</p>'
            . '</div>'
            . '<div style="padding:20px;color:#111">'
            . '<table style="width:100%;font-size:14px;border-collapse:collapse">'
            . '<tr><td style="padding:4px 0;color:#6b7280;width:120px">From</td><td style="padding:4px 0;font-weight:bold">' . $safeName . '</td></tr>'
            . '<tr><td style="padding:4px 0;color:#6b7280">Email</td><td style="padding:4px 0"><a href="mailto:' . $safeEmail . '">' . $safeEmail . '</a></td></tr>'
            . '<tr><td style="padding:4px 0;color:#6b7280">Subject</td><td style="padding:4px 0">' . $subjectLabel . '</td></tr>'
            . '<tr><td style="padding:4px 0;color:#6b7280">Account</td><td style="padding:4px 0">' . $userLine . '</td></tr>'
            . '</table>'
            . '<div style="margin:16px 0;padding:16px;background:#f9fafb;border-radius:8px;font-size:14px;line-height:1.6;white-space:normal">' . $safeMsgHtml . '</div>'
            . '<div style="border-top:1px solid #e5e7eb;padding-top:10px;color:#9ca3af;font-size:11px">'
            . 'Sent ' . $sentAt . ' · IP ' . esc($ip) . '<br>' . esc($ua)
            . '</div>'
            . '</div></div>';

        $text = "Ski Manager - New Contact Message\n"
            . "Subject: {$subjectLabel}\n"
            . str_repeat('-', 40) . "\n"
            . "From: {$name}\n"
            . "Email: {$email}\n"
            . "Account: " . ($loggedIn ? auth()->user()->username . ' (ID ' . (int) auth()->id() . ')' : 'Not logged in') . "\n"
            . str_repeat('-', 40) . "\n\n"
            . "{$message}\n\n"
            . str_repeat('-', 40) . "\n"
            . "Sent: {$sentAt}\nIP: {$ip}\nUA: {$ua}\n";

        $emailService = \Config\Services::email();
        $emailService->setFrom('contact@ski-manager.net', 'Ski Manager Contact');
        $emailService->setTo('contact@ski-manager.net');
        $emailService->setReplyTo($email, $name);
        $emailService->setSubject('[Ski Manager] ' . $subjectLabel . ' from ' . $name);
        $emailService->setMailType('html');
        $emailService->setMessage($html);
        $emailService->setAltMessage($text);

        if ($emailService->send()) {
            return redirect()->to('/contact')->with('success', 'Thanks for your message! We\'ll get back to you soon.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Failed to send message. Please try again or contact us on Discord.');
        }
    }
}
