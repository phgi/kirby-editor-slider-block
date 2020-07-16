<?php

class LocalValetDriver extends KirbyValetDriver
{

    /**
     * Get the fully resolved path to the application's front controller.
     *
     * @param  string  $sitePath
     * @param  string  $siteName
     * @param  string  $uri
     * @return string
     */
    public function frontControllerPath($sitePath, $siteName, $uri)
    {

        // Needed to force Kirby to use *.test to generate its URLs...
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];

        if (file_exists($indexPath = $sitePath.'/index.site.php')) {
            $_SERVER['SCRIPT_NAME'] = '/index.site.php';

            return $indexPath;
        }
    }
}
