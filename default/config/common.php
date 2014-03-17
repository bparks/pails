<?php if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) header('Location: /'); ?>
<?php
/*  This is where code that should be excuted on EVERY page load can go.
    In reality, it should probably go in a ControllerBase class that YOU
    define. This COntrollerBase class would then inherit from
    Pails\Controller and all of your other controllers inherit from IT. */