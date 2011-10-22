<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter Profiler FirePHP
 *
 * Desarrollado para PHP 5.1 o superior
 *
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Profiler for FirePHP Class
 *
 * Esta clase permite hacer un debug usando a FirePHP (compatible con Firefox
 * y Chrome) para mejorar el rendimiento de nuestra aplicacion.
 *
 */
class CI_Profiler {

    var $CI;

    protected $_available_sections = 
    array(
        'benchmarks',
        'get',
        'post',
        'queries',
        'http_headers',
        'config',
        'controller_info',
        'uri_string',
        'memory_usage'
    );

    public function __construct($config = array())
    {
        $this->CI =& get_instance();
        $this->CI->load->language('profiler');
        $this->CI->load->library('FirePHP');

        $this->CI->firephp->group('Codeigniter Development',
            array('Collapsed' => FALSE));
        foreach ($this->_available_sections as $section)
        {
            if ( ! isset($config[$section]))
            {
                $this->_compile_{$section} = TRUE;
            }
        }
        $this->CI->firephp->groupEnd();
        $this->set_sections($config);
    }

    // --------------------------------------------------------------------

    /**
     * Secciones
     *
     * Setea las secciones que se mostraran
     *
     * @param    mixed
     * @return    void
     */
    public function set_sections($config)
    {
        foreach ($config as $method => $enable)
        {
            if (in_array($method, $this->_available_sections))
            {
                if ($this->_compile_{$method} = ($enable !== FALSE)) {
                    TRUE;
                } else {
                    FALSE;
                }
                
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     *
     * @return    array
     */
    protected function _compile_benchmarks()
    {
        $profile = array();
        foreach ($this->CI->benchmark->marker as $key => $val)
        {
            if (preg_match("/(.+?)_end/i", $key, $match))
            {
                if (isset($this->CI->benchmark->marker[$match[1].'_end']) AND isset($this->CI->benchmark->marker[$match[1].'_start']))
                {
                    $profile[$match[1]] = $this->CI->benchmark->elapsed_time($match[1].'_start', $key);
                }
            }
        }

        $output = array();
        $output[] = array('Item','Tiempo');
        foreach ($profile as $key => $val)
        {
            $key = ucwords(str_replace(array('_', '-'), ' ', $key));
            $output[] = array($key, $val);
        }
        $this->CI->firephp->table($this->CI->lang->line('profiler_benchmarks'), $output);
    }

    // --------------------------------------------------------------------

    /**
     * Querys
     *
     * @return    string
     */
    protected function _compile_queries()
    {
        $dbs = array();

        foreach (get_object_vars($this->CI) as $CI_object)
        {
            if (is_object($CI_object) && is_subclass_of(get_class($CI_object), 'CI_DB') )
            {
                $dbs[] = $CI_object;
            }
        }

        //profiler_queries
        if (count($dbs) == 0)
        {
            $this->CI->firephp->info($this->CI->lang->line('profiler_no_db'));

            return true;
        }

        $this->CI->load->helper('text');

        $highlight = array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY', 'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN', 'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')');


        foreach ($dbs as $db)
        {
            $profile = array();
            $profile[] = array('tiempo', 'SQL');

            if (count($db->queries) == 0)
            {
                $this->CI->firephp->info($this->CI->lang->line('profiler_no_queries'));
            }
            else
            {
                foreach ($db->queries as $key => $val)
                {
                    $time = number_format($db->query_times[$key], 4);

                    $profile[] = array($time, $val);
                }
            }

            $this->CI->firephp->table($this->CI->lang->line('profiler_database').': '.$db->database.' '.$this->CI->lang->line('profiler_queries').': '.count($db->queries), $profile);

        }

    }


    // --------------------------------------------------------------------

    /**
     * $_GET Data
     *
     * @return    string
     */
    protected function _compile_get()
    {

        if (count($_GET) == 0)
        {
            $this->CI->firephp->info($this->CI->lang->line('profiler_no_get'));
        }
        else
        {
            $profile = array();
            $profile[] = array('indice', 'valor');

            foreach ($_GET as $key => $val)
            {
                if ( ! is_numeric($key))
                {
                    $key = "'".$key."'";
                }

                if (is_array($val))
                {
                    $profile[] = array($key, htmlspecialchars(stripslashes(print_r($val, true))));
                }
                else
                {
                    $profile[] = array($key, htmlspecialchars(stripslashes($val)));
                }
            }

            $this->CI->firephp->table($this->CI->lang->line('profiler_get_data'), $profile);
        }
    }

    // --------------------------------------------------------------------

    /**
     * $_POST Data
     *
     * @return    string
     */
    protected function _compile_post()
    {

        if (count($_POST) == 0)
        {
            $this->CI->firephp->info($this->CI->lang->line('profiler_no_post'));
        }
        else
        {
            $profile = array();
            $profile[] = array('indice', 'valor');

            foreach ($_POST as $key => $val)
            {
                if ( ! is_numeric($key))
                {
                    $key = "'".$key."'";
                }

                if (is_array($val))
                {
                    $profile[] = array($key, htmlspecialchars(stripslashes(print_r($val, TRUE))));
                }
                else
                {
                    $profile[] = array($key, htmlspecialchars(stripslashes($val)));
                }
            }

            $this->CI->firephp->table($this->CI->lang->line('profiler_post_data'), $profile);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Muestra los query's
     *
     * @return    string
     */
    protected function _compile_uri_string()
    {
        $output  = "";
        if ($this->CI->uri->uri_string == '')
        {
            $output .= "No URI";
        }
        else
        {
            $output .= $this->CI->uri->uri_string;
        }
        $this->CI->firephp->info($output, $this->CI->lang->line('profiler_uri_string'));
    }

    // --------------------------------------------------------------------

    /**
     * Muestra la informacion del controlador
     *
     * @return    string
     */
    protected function _compile_controller_info()
    {
        $output  = "";
        $output .= $this->CI->router->fetch_class()."/".$this->CI->router->fetch_method();
        $this->CI->firephp->info($output, $this->CI->lang->line('profiler_controller_info'));
    }

    // --------------------------------------------------------------------

    /**
     * memory usage
     *
     * @return    string
     */
    protected function _compile_memory_usage()
    {
        $output  = "";

        if (function_exists('memory_get_usage') && ($usage = memory_get_usage()) != '')
        {
            $output .= number_format($usage);
        }
        else
        {
            $output .= "No se ha usado memoria!";
        }
        $this->CI->firephp->info($output, $this->CI->lang->line('profiler_memory_usage'));
    }

    // --------------------------------------------------------------------

    /**
     * header information
     *
     * Lista las cabeceras HTTP
     *
     * @return    string
     */
    protected function _compile_http_headers()
    {
        $output = array();
        $output[] = array('Variable', 'Valor');

        foreach(array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD',' HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR') as $header)
        {
            $val = (isset($_SERVER[$header])) ? $_SERVER[$header] : '';
            $output[] = array($header, $val);
        }

        $this->CI->firephp->table($this->CI->lang->line('profiler_headers'), $output);
    }

    // --------------------------------------------------------------------

    /**
     * Compile config information
     *
     * Lista las variables de configuracion
     *
     * @return    string
     */
    protected function _compile_config()
    {
        $output = array();
        $output[] = array('Variable', 'Valor');

        foreach($this->CI->config->config as $config=>$val)
        {
            if (is_array($val))
            {
                $val = print_r($val, TRUE);
            }
            $output[] = array($config, $val);
        }

        $this->CI->firephp->table($this->CI->lang->line('profiler_config'), $output);
    }

    // --------------------------------------------------------------------

    /**
     * Run the Profiler
     *
     * @return    string
     */
    public function run()
    {
        $output = "<div id='codeigniter_profiler' style='clear:both;background-color:#fff;padding:10px;'>";
        $fields_displayed = 0;

        foreach ($this->_available_sections as $section)
        {
            if ($this->_compile_{$section} !== FALSE)
            {
                $func = "_compile_{$section}";
                $output .= $this->{$func}();
                $fields_displayed++;
            }
        }

        if ($fields_displayed == 0)
        {
            $output .= '<p style="border:1px solid #5a0099;padding:10px;margin:20px 0;background-color:#eee">'.$this->CI->lang->line('profiler_no_profiles').'</p>';
        }

        $output .= '</div>';

        return $output;
    }

}

// END CI_Profiler class

/* End of file Profiler.php */
/* Location: ./application/libraries/Profiler.php */