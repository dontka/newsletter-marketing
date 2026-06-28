<?php

require_once __DIR__ . '/Lib/Env.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controllers/HomeController.php';
require_once __DIR__ . '/Controllers/SubscriptionController.php';
require_once __DIR__ . '/Controllers/AdminController.php';
require_once __DIR__ . '/Controllers/ConfirmationController.php';
require_once __DIR__ . '/Controllers/UnsubscribeController.php';
require_once __DIR__ . '/Controllers/NewsletterController.php';
require_once __DIR__ . '/Controllers/SubscribersController.php';
require_once __DIR__ . '/Controllers/TrackingController.php';

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
    }

    private function registerRoutes(): void
    {
        $home = new HomeController();
        $this->router->add('GET', '/', [$home, 'index']);

        $subscription = new SubscriptionController();
        $this->router->add('GET', '/subscribe', [$subscription, 'showForm']);
        $this->router->add('POST', '/subscribe', [$subscription, 'subscribe']);

        $admin = new AdminController();

        $this->router->add('GET', '/admin/login', [$admin, 'login']);
        $this->router->add('GET', '/admin/callback', [$admin, 'callback']);
        $this->router->add('GET', '/admin/dashboard', [$admin, 'dashboard']);
        $this->router->add('GET', '/admin/queue', [$admin, 'queue']);
        $this->router->add('POST', '/admin/queue/process', [$admin, 'processQueue']);
        $this->router->add('GET', '/admin/queue/status', [$admin, 'statusQueue']);
        $this->router->add('GET', '/admin/logout', [$admin, 'logout']);

        $confirmation = new ConfirmationController();
        $unsubscribe = new UnsubscribeController();

        $this->router->add('GET', '/confirm', [$confirmation, 'confirm']);
        $this->router->add('GET', '/unsubscribe', [$unsubscribe, 'unsubscribe']);

        $newsletter = new NewsletterController();
        $this->router->add('GET', '/newsletter', [$newsletter, 'list']);
        $this->router->add('GET', '/newsletter/create', [$newsletter, 'create']);
        $this->router->add('POST', '/newsletter/save', [$newsletter, 'save']);
        $this->router->add('GET', '/newsletter/edit', [$newsletter, 'edit']);
        $this->router->add('POST', '/newsletter/update', [$newsletter, 'update']);
        $this->router->add('POST', '/newsletter/delete', [$newsletter, 'delete']);

        $subscribers = new SubscribersController();
        $this->router->add('GET', '/subscribers', [$subscribers, 'list']);
        $this->router->add('GET', '/subscribers/edit', [$subscribers, 'edit']);
        $this->router->add('POST', '/subscribers/update', [$subscribers, 'update']);
        $this->router->add('POST', '/subscribers/import', [$subscribers, 'import']);
        $this->router->add('GET', '/subscribers/export', [$subscribers, 'export']);
        $this->router->add('GET', '/subscribers/template', [$subscribers, 'template']);
        $this->router->add('POST', '/subscribers/delete', [$subscribers, 'delete']);
        $this->router->add('POST', '/subscribers/bulk', [$subscribers, 'bulkAction']);

        $this->router->add('GET', '/users', [$admin, 'users']);
        $this->router->add('GET', '/users/edit', [$admin, 'editUser']);
        $this->router->add('POST', '/users/update', [$admin, 'updateUser']);
        $this->router->add('POST', '/users/update-role', [$admin, 'updateUserRole']);
        $this->router->add('POST', '/users/delete', [$admin, 'deleteUser']);

        $tracking = new TrackingController();
        $this->router->add('GET', '/open.gif', [$tracking, 'open']);
        $this->router->add('GET', '/click', [$tracking, 'click']);
    }
}
