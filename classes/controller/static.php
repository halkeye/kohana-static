<?php

class controller_static extends Controller
{
    public function action_css($filenames=null)
    {
        $this->handleStatic($filenames, 'css');
    }

    public function action_js($filenames=null)
    {
        $this->handleStatic($filenames, 'js');
    }

    private function handleStatic($filenames, $type) 
    {
        /* Fix to prevent debug bar from rendering on this page */
		$config = Kohana::config('debug_toolbar');
        if ($config) { $config->set('auto_render', false); }
        /* end fix */

        if ($filenames === null) 
        {
            $this->response = "/* No $type TO BE FOUND */";
            return;
        }

        if (Kohana_Core::$environment != Kohana::DEVELOPMENT && self::check(300) === FALSE) self::set(300);

        $this->response->headers('Content-Type',File::mime_by_ext($type));
        $body = "";
        $filenames = preg_replace("/\.$type\$/", '', $filenames);
        foreach (explode(',', $filenames) as $key)
        {
            $key = basename($key, ".$type");
            $file = Kohana::find_file('views/'.$type, $key, $type);
            if (!$file)
            {
                $body .= "/* No such file or directory ($key.$type) */\n";
                continue;
            }

            $body .= implode('', array('/* (', str_replace(DOCROOT, '', $file), ") */\n"));
            $body .= file_get_contents($file);
        }

        /* Play nice with minify module if its enabled */
        if ( Kohana::config('minify.enabled', false ) && class_exists('Minify')) 
        {
            $body = Minify::factory($type)->set($body)->min();
        }
        $this->response->body($body);
    }

    public function action_img($filename = null)
    {
        if (!$filename)
            throw new Kohana_Exception("No such file or directory");

        if (Kohana_Core::$environment != Kohana::DEVELOPMENT && self::check(300) === FALSE) self::set(300);

        $info = pathinfo($filename);
        $ext = $info['extension'];
        $filename = $info['filename'];

        $file = Kohana::find_file('views/images', basename($info['basename'], ".$ext"), $ext);
        if (!$file)
            throw new Kohana_Exception("No such file or directory (:filename)", array('filename'=>"$filename.$ext"));

        $this->response->send_file($file, FALSE, array('inline' => 1, 'mime_type' => File::mime_by_ext($ext)));
    }
    
    /* The following is borrowed from kohana v2 code */
    
    /**
     * Sets the amount of time before a page expires
     *
     * @param  integer Seconds before the page expires 
     * @return boolean
     */
    public static function set($seconds = 60)
    {
        if (self::check_headers())
        {
            $now = $expires = time();

            // Set the expiration timestamp
            $expires += $seconds;

            // Send headers
            header('Last-Modified: '.gmdate('D, d M Y H:i:s T', $now));
            header('Expires: '.gmdate('D, d M Y H:i:s T', $expires));
            header('Cache-Control: max-age='.$seconds);

            return $expires;
        }

        return FALSE;
    }

    /**
     * Checks to see if a page should be updated or send Not Modified status
     *
     * @param   integer  Seconds added to the modified time received to calculate what should be sent
     * @return  bool     FALSE when the request needs to be updated
     */
    public static function check($seconds = 60)
    {
        if ( ! empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) AND self::check_headers())
        {
            if (($strpos = strpos($_SERVER['HTTP_IF_MODIFIED_SINCE'], ';')) !== FALSE)
            {
                // IE6 and perhaps other IE versions send length too, compensate here
                $mod_time = substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 0, $strpos);
            }
            else
            {
                $mod_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            }

            $mod_time = strtotime($mod_time);
            $mod_time_diff = $mod_time + $seconds - time();

            if ($mod_time_diff > 0)
            {
                // Re-send headers
                header('Last-Modified: '.gmdate('D, d M Y H:i:s T', $mod_time));
                header('Expires: '.gmdate('D, d M Y H:i:s T', time() + $mod_time_diff));
                header('Cache-Control: max-age='.$mod_time_diff);
                header('Status: 304 Not Modified', TRUE, 304);

                print '';

                // Exit to prevent other output
                exit;
            }
        }

        return FALSE;
    }

    /**
     * Check headers already created to not step on download or Img_lib's feet
     *
     * @return boolean
     */
    public static function check_headers()
    {
        foreach (headers_list() as $header)
        {
            if ((session_cache_limiter() == '' AND stripos($header, 'Last-Modified:') === 0)
                OR stripos($header, 'Expires:') === 0)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

}
