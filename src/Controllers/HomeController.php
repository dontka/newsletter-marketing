<?php

require_once __DIR__ . '/../Lib/View.php';

class HomeController
{
    public function index(): void
    {
        View::render('home');
    }
}
