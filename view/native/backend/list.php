<?php //>

use dungeons\Config;
use dungeons\Message;
use dungeons\view\Native;
use dungeons\view\Twig;

$node = $controller->node();
$path = preg_replace('/^\/backend\/(.+)$/', '$1', $controller->path());

$result['path'] = $path;

//--

$controls = [];

if ($controller->hasPermission("{$node}/new")) {
    $controls[] = [
        'class' => Config::get('backend.new.button'),
        'icon' => Config::get('backend.new.icon'),
        'label' => Message::get('backend.new'),
        'path' => "{$path}/new",
        'ranking' => 100,
    ];
}

if ($controller->hasPermission("{$node}/delete")) {
    $controls[] = [
        'class' => Config::get('backend.multiple-delete.button'),
        'icon' => Config::get('backend.delete.icon'),
        'label' => Message::get('backend.delete'),
        'least' => 1,
        'path' => "{$node}/delete",
        'ranking' => 200,
    ];
}

$controls[] = [
    'class' => Config::get('backend.export.button'),
    'icon' => Config::get('backend.export.icon'),
    'label' => Message::get('backend.export'),
    'least' => 0,
    'parameters' => array_intersect_key($form, array_flip(['o', 'q'])) + ['t' => $controller->exportFormat()],
    'path' => $path,
    'ranking' => 300,
];

$result['controls'] = $controls;

//--

$actions = [];

if ($controller->hasPermission("{$node}/")) {
    $actions[] = [
        'class' => Config::get('backend.edit.button'),
        'icon' => Config::get('backend.edit.icon'),
        'label' => Message::get('backend.edit'),
        'ranking' => 100,
    ];
}

$result['actions'] = $actions;

//--

$table = $controller->table();
$list = $table->model()->parents($form);

$result['breadcrumbs'] = $controller->createBreadcrumbs($list);

//--

$titles = array_filter(array_column($list, '.title'), 'is_string');

$result['sub_title'] = array_pop($titles);

//--

require 'association.php';

//--

$orders = [];

foreach ($result['orders'] as $index => $name) {
    if ($name[0] === '-') {
        $orders[substr($name, 1)] = -1 - $index;
    } else {
        $orders[$name] = $index + 1;
    }
}

$result['orders'] = $orders;

//--

$labels = Message::load("table/{$table->name()}");
$styles = [];

foreach ($controller->columns() ?? $table->getColumns() as $name => $column) {
    if ($column->association() || $column->invisible()) {
        continue;
    }

    if ($column->isCounter()) {
        if (!$controller->hasPermission("{$node}/{$column->relation()['alias']}")) {
            continue;
        }
    }

    $style = [
        'column' => $column,
        'label' => $labels[$name] ?? "[{$name}]",
        'name' => $name,
        'relation' => $column->relation(),
        'type' => $column->listStyle(),
        'unordered' => $column->unordered(),
    ];

    if (empty($style['type'])) {
        $style['readonly'] = true;
        $style['type'] = $column->formStyle();

        if ($style['type'] === 'hidden') {
            continue;
        }
    }

    $options = $column->options();

    if ($options) {
        $style['options'] = Message::load("options/{$options}");
        $style['type'] = 'select';
    }

    $styles[] = $style;
}

$result['styles'] = $controller->remix($styles, $list);

//--

$filters = [];

foreach ($controller->filters() ?? [] as $name => $column) {
    $filter = [
        'label' => $labels[$name] ?? "[{$name}]",
        'name' => $name,
        'pattern' => $column->pattern(),
        'search' => $column->searchStyle(),
        'type' => $column->formStyle(),
    ];

    $options = $column->options();

    if ($options) {
        $options = Message::load("options/{$options}");
    } else if (key_exists($name, $bundles)) {
        $options = $bundles[$name];
    }

    if ($options) {
        $filter['options'] = $options;
        $filter['search'] = null;
        $filter['type'] = 'select';
    }

    $filters[] = $filter;
}

$result['filters'] = $filters;

//--

if (!$filters) {
    $selected = false;

    foreach ($result['styles'] as $style) {
        if ($style['unordered']) {
            continue;
        }

        $name = $style['name'];

        $filter = [
            'label' => $style['label'],
            'name' => $name,
            'pattern' => $style['column']->pattern(),
            'search' => $style['column']->searchStyle(),
            'type' => $style['type'],
        ];

        $options = @$style['options'];

        if ($options) {
            $filter['options'] = $options;
            $filter['search'] = null;
            $filter['type'] = 'select';
        }

        if (!$selected && $style['column']->inSearch()) {
            $filter['selected'] = true;
            $selected = true;
        }

        $filters[] = $filter;
    }

    if ($filter) {
        if (!$selected) {
            $filters[0]['selected'] = true;
        }

        $result['simple_filters'] = $filters;
    }
}

//--

$result['parameters'] = array_intersect_key($form, array_flip(['g', 'o', 'p', 'q', 's']));

if ($table->enableTime()) {
    $result['groups'] = $table->disableTime() ? [0, 1, 2, 3, 4] : [0, 1, 2, 3];
} else {
    $result['groups'] = $table->disableTime() ? [0, 1, 2, 4] : [];
}

//--

switch ($result['export']) {
case 'xlsx':
    $view = new Native('backend/export-xlsx.php');
    break;
default:
    $view = new Twig($controller->customView() ?? 'backend/list.twig');
}

$view->render($controller, $form, $result);
