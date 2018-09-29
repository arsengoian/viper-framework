<?php

namespace Viper\Core;

use Viper\Core\Routing\HttpException;
use Viper\Template\TemplateError;
use Viper\Template\Viper;


class View extends Viper implements Viewable {

    private $own_folder;
    private $default_folder;

    private $viewname;


    function __construct(string $viewname, array $data = []) {

        $this -> viewname = $viewname;
        $master_page = root().'/views/'.Config::get('MASTER_LAYOUT').'.viper';
        if (!file_exists($master_page))
            throw new HttpException(404, 'Master layout missing');

        $this -> own_folder = root()."/views/$viewname";
        $this -> default_folder = root().'/views/'.ucfirst(Config::get('DEFAULT_CONTROLLER'));

        parent::__construct($master_page, $data);

    }


    protected function build(string $what) : string {
        if (file_exists($this -> own_folder."/$what.viper"))
            return parent::parseStatic($this -> own_folder."/$what.viper", $this -> data, $this);
        elseif (file_exists($this -> default_folder."/$what.viper") && Config::get('VIEW_FALLBACK'))
            return parent::parseStatic($this -> default_folder."/$what.viper", $this -> data, $this);
        else throw new HttpException(404, "View section $what missing");
    }


    // Those are designed to use inside views

    protected function isCurrentView(string $name) : bool {
        return $this -> viewname === $name;
    }

    protected function getProtocol() : string {
        return isset($this -> data['env']['HTTPS']) ? 'https' : 'http';
    }




    public function flush(): string {
        return $this -> parse();
    }




    public static function redirect(string $where) {
        header("Location: $where");
        exit();
    }


    public static function parseException(\Throwable $e): string {
        // build error view
        if (Config::get('DEBUG') === TRUE || Config::get('REPORT_ERRORS') === TRUE) {

            try {

                if (is_dir(root().'/views/'.Config::get('ERROR_VIEW'))) {

                    if (in_array('ob_gzhandler', ob_list_handlers())) {
                        ob_clean(); // TODO bugs on hosting  ? Test
                    }
                    $errv = new View('error', ['e' => $e]);
                    return $errv -> flush();

                } else throw $e;

            } catch (TemplateError $err) {

                throw $e;

            }

        }
        return 'Unfortunately, an error occured';
    }

}


