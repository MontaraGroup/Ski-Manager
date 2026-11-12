<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // Record activity at most every 5 minutes per session: these UPDATEs ran on every page view.
        // The welcome-back grant below still sees the first request after a 48-hour absence.
        $session = session();
        if (function_exists('auth') && auth()->loggedIn() && time() - (int) $session->get('last_active_written') >= 300) {
            $session->set('last_active_written', time());
            $userId = (int) auth()->id();
            $db = db_connect();
            $db->table('users')->where('id', $userId)->update(['last_active' => date('Y-m-d H:i:s')]);

            // If the player has been away for >= 48 hours, grant a welcome back caretaker bonus
            // We use an atomic update conditional on last_active to avoid race conditions across concurrent requests
            $threshold = date('Y-m-d H:i:s', time() - 172800);
            $grant = 25000;
            $db->table('player_finances')
                ->where('user_id', $userId)
                ->where('last_active <=', $threshold)
                ->set('cash', "cash + {$grant}", false)
                ->set('last_active', date('Y-m-d H:i:s'))
                ->update();

            if ($db->affectedRows() > 0) {
                if (function_exists('notify')) {
                    notify(
                        $userId,
                        'welcome_back',
                        'Welcome Back, Manager!',
                        'Your resort was held in caretaker standby while you were away. We deposited a ' . currency($grant) . ' caretaker grant into your treasury.',
                        'fa-solid fa-gift',
                        '/dashboard'
                    );
                }
                if (function_exists('log_activity')) {
                    log_activity($userId, 'Caretaker', 'Received ' . currency($grant) . ' Caretaker Welcome Back grant upon return.', 'fa-solid fa-gift');
                }
            } else {
                $db->table('player_finances')->where('user_id', $userId)->update(['last_active' => date('Y-m-d H:i:s')]);
            }
        }
    }

    /**
     * Executes a database operation within a transaction safely.
     * Automatically rolls back on exception or failure status.
     *
     * @param callable $callback function(\CodeIgniter\Database\BaseConnection $db): mixed
     * @param string $fallbackErrorMessage
     * @return mixed Returns callback result on success, or false on failure
     */
    protected function executeTransaction(callable $callback, string $fallbackErrorMessage = 'Transaction failed. Please try again.')
    {
        $db = db_connect();
        $db->transBegin();
        try {
            $result = $callback($db);
            if ($result === false || $db->transStatus() === false) {
                $db->transRollback();
                return false;
            }
            $db->transCommit();
            return $result ?? true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Transaction aborted: ' . $e->getMessage());
            return false;
        }
    }
}
