<?php //>

use dungeons\Config;
use dungeons\Message;

return new class() extends dungeons\web\backend\ListController {

    public function available() {
        if ($this->method() === 'POST') {
            $info = pathinfo($this->name());
            $pattern = preg_quote($info['dirname'], '/');

            return preg_match("/^{$pattern}\/[\d]+\/{$info['basename']}$/", $this->path());
        }

        return false;
    }

    public function remix($styles, $list) {
        $data = array_pop($list);
        $sub = Config::load("module/{$data['module']}")['sub-module'];

        $fields = [];
        $labels = Message::load("module/{$sub}");
        $module = Config::load("module/{$sub}");

        foreach ($module['fields'] as $field) {
            $field['label'] = $labels[$field['name']] ?? "[{$field['name']}]";
            $field['readonly'] = true;

            $fields[] = $field;
        }

        array_splice($styles, 0, 0, $fields);

        return $styles;
    }

    protected function init() {
        $table = table('BlockItem');

        $names = [
            'enable_time',
            'disable_time',
            'ranking',
        ];

        $this->table($table);
        $this->columns($table->getColumns($names));
    }

};
