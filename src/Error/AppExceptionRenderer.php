<?php

namespace App\Error;

use Cake\Error\ExceptionRenderer;

class AppExceptionRenderer extends ExceptionRenderer
{
    public function missingController($error)
    {
        die('null');
    }

    public function missingAction($error)
    {
        die('null');
    }

    public function missingWidget($error)
    {
        die('null');
    }

    public function notFound($error)
    {
        die('null');
    }
}
