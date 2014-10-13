<?php

class DefaultController extends Pails\Controller
{
        public $before_actions = array(
        );

        public $after_actions = array(
        );

        public function index()
        {
            Pails\Application::log("DefaultController");
        }
}