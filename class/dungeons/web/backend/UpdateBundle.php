<?php //>

namespace dungeons\web\backend;

use dungeons\Attachment;
use dungeons\Resource;
use dungeons\web\BackendController;

class UpdateBundle extends BackendController {

    public function __construct() {
        parent::__construct();

        $this->validationView('backend/validation.php');
        $this->view('backend/update-bundle-success.php');
    }

    public function available() {
        if ($this->method() === 'POST') {
            $info = pathinfo($this->name());
            $pattern = preg_quote($info['dirname'], '/');

            return preg_match("/^{$pattern}\/[\w]+\/{$info['basename']}\/[\w-]+$/", $this->path());
        }

        return false;
    }

    protected function getMenuName() {
        return preg_replace('/^\/backend\/(.+)\/[\w-]+$/', '$1', $this->path());
    }

    protected function init() {
        $file = "{$this->folder()}/{$this->args()[1]}";
        $data = Resource::union("{$file}.php");

        $this->data($data);
        $this->file($file);
        $this->styles($data['@'] ?? []);
    }

    protected function process($form) {
        $data = $this->data();

        if (!$data) {
            return ['error' => 'error.DataNotFound'];
        }

        if (!defined('APP_DATA') || create_folder(APP_DATA . $this->folder()) === false) {
            return ['error' => 'error.UpdateFailed'];
        }

        $file = APP_DATA . $this->file();

        if (file_exists($file)) {
            if (!is_file($file) || !is_writable($file)) {
                return ['error' => 'error.UpdateFailed'];
            }
        }

        $diff = [];

        foreach ($data as $name => $value) {
            $new = @$form[$name];

            if ($new === null) {
                continue;
            }

            if ($new instanceof Attachment) {
                $new->save();

                $diff[$name] = strval($new);
            } else if ($new !== $value) {
                $diff[$name] = $new;
            }
        }

        if ($diff) {
            file_put_contents($file, json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else if (file_exists($file)) {
            unlink($file);
        }

        return ['success' => true];
    }

    protected function validate($form) {
        $errors = [];

        foreach ($this->styles() as $name => $style) {
            $value = @$form[$name];

            if ($value === null) {
                if (@$style['required']) {
                    $errors[] = ['name' => $name, 'type' => 'required'];
                }
            } else {
                $options = new $style['column']();
                $type = validate($value, $options);

                if ($type) {
                    $errors[] = ['name' => $name, 'type' => $type];
                }
            }
        }

        return $errors;
    }

    protected function wrap() {
        $form = parent::wrap();

        foreach ($this->styles() as $name => $style) {
            switch ($style['column']) {
            case 'File':
            case 'Image':
                $form = Attachment::wrap($form, $name);
                break;
            }
        }

        return $form;
    }

}
