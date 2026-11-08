<?php

use CodeIgniter\Config\Factories;
use CodeIgniter\Settings\Settings;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuthThrottleTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const PASSWORD = 'Str0ngPass!2026';

    protected $migrate = false;
    protected $refresh = false;

    private string $email;
    private string $username;
    private string $csrfToken = '';

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();

        $suffix         = bin2hex(random_bytes(4));
        $this->email    = 'throttle-' . $suffix . '@example.com';
        $this->username = 'throttle-' . $suffix;

        // Shield's models must use the test database, not the app's default group.
        $auth           = config('Auth');
        $auth->DBGroup  = 'tests';
        Factories::injectMock('config', 'Auth', $auth);

        $this->ensureAuthTables($auth->tables);

        // Keep Shield's settings lookups away from the application's database.
        $settingsConfig           = config('Settings');
        $settingsConfig->handlers = ['array'];
        Services::injectMock('settings', new Settings($settingsConfig));

        helper(['auth', 'setting']);

        $this->createUser();
        $this->prepareCsrfToken();
    }

    public function testLoginLocksOutAnAccountAfterFiveFailures(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $result = $this->post('/login', [
                'email'    => $this->email,
                'password' => 'wrong-password',
            ]);

            $result->assertStatus(302);
            $result->assertRedirect();
        }

        $result = $this->post('/login', [
            'email'    => $this->email,
            'password' => 'wrong-password',
        ]);

        $result->assertStatus(429);
        $result->assertHeader('Retry-After');
        $this->assertStringContainsString(
            'Too many attempts for this account.',
            $result->getBody(),
        );

        // A different identity must not inherit this account's lockout.
        $result = $this->post('/login', [
            'email'    => 'other-' . $this->email,
            'password' => 'wrong-password',
        ]);

        $result->assertStatus(302);
        $result->assertRedirect();
    }

    public function testSuccessfulLoginClearsTheAccountBucket(): void
    {
        // Leave one token in the five-attempt bucket for the successful login.
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $result = $this->post('/login', [
                'email'    => $this->email,
                'password' => 'wrong-password',
            ]);

            $result->assertStatus(302);
            $result->assertRedirect();
        }

        $result = $this->post('/login', [
            'email'    => $this->email,
            'password' => self::PASSWORD,
        ]);

        $result->assertStatus(302);
        $result->assertRedirect();
        $result->assertSessionHas('user');

        // Start the next request as a fresh client. FeatureTestTrait resets
        // $_SESSION but keeps the shared Auth service between calls.
        Services::resetSingle('auth');

        // The successful login used the fifth token. If the after filter did
        // not clear the bucket, this attempt would be the sixth and return 429.
        $result = $this->post('/login', [
            'email'    => $this->email,
            'password' => 'wrong-password',
        ]);

        $result->assertStatus(302);
        $result->assertRedirect();
    }

    private function createUser(): void
    {
        $users = model(UserModel::class);
        $user  = new User([
            'username' => $this->username,
            'email'    => $this->email,
            'password' => self::PASSWORD,
            'active'   => 1,
        ]);

        $this->assertTrue($users->save($user));
        $this->seeInDatabase(config('Auth')->tables['identities'], [
            'type'   => 'email_password',
            'secret' => $this->email,
        ]);
    }

    /** @param array<string, string> $tables */
    private function ensureAuthTables(array $tables): void
    {
        $missing = [];
        $present = [];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $present[] = $table;
            } else {
                $missing[] = $table;
            }
        }

        if ($missing === []) {
            return;
        }

        if ($present !== []) {
            $this->markTestSkipped(
                'The tests database has only part of the auth schema: ' . implode(', ', $missing),
            );
        }

        $this->migrations->setNamespace('\CodeIgniter\Shield');
        $this->migrations->latest('tests');

        if (! $this->db->tableExists($tables['users'])) {
            $this->markTestSkipped('The tests database could not be initialised with the auth tables.');
        }
    }

    private function prepareCsrfToken(): void
    {
        $security = service('security');
        $config   = config('Security');

        $this->csrfToken = $security->generateHash();

        $this->withSession([$config->tokenName => $this->csrfToken]);
        $this->withHeaders([$config->headerName => $this->csrfToken]);
    }
}
