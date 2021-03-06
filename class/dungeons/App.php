<?php //>

namespace dungeons;

use dungeons\Resource;

abstract class App {

    private static $instance;

    public static function getInstance() {
        return self::$instance;
    }

    public static function init() {
        if (self::$instance) {
            return;
        }

        self::$instance = new static();
    }

    protected $controller;

    public function controller() {
        return $this->controller;
    }

    public function run() {
        define('CONTROLLER_NAME', $this->controller->name());

        $this->controller->execute();
    }

    protected function find($path, $method) {
        $args = [];
        $current = '';
        $tokens = preg_split('/\//', $path, 0, PREG_SPLIT_NO_EMPTY);

        $candidates = [['/', 'index', $tokens]];

        while ($tokens) {
            $found = false;
            $token = array_shift($tokens);
            $name = "{$current}/{$token}";

            if (Resource::find("controller{$name}.php")) {
                $found = true;
                $candidates[] = [$name, '', array_merge($args, $tokens)];
            }

            if ($tokens && Resource::find("controller{$name}/")) {
                $found = true;

                if (Resource::find("controller{$name}/content.php")) {
                    $candidates[] = ["{$name}/", 'content', array_merge($args, $tokens)];
                }
            }

            if ($found) {
                $current = $name;
            } else {
                $args[] = $token;
            }
        }

        while ($candidates) {
            list($name, $file, $args) = array_pop($candidates);

            $controller = Resource::load("controller{$name}{$file}.php");

            if ($controller instanceof Controller) {
                $controller->args($args)->method($method)->name($name)->path($path);

                if ($controller->available()) {
                    return $controller;
                }
            }
        }

        return null;
    }

}
