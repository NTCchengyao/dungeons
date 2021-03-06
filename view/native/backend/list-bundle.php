<?php //>

use dungeons\Config;
use dungeons\Message;

$path = $controller->node();

$result['path'] = $path;

//--

$actions = [];

if ($controller->hasPermission("{$path}/")) {
    $actions[] = [
        'class' => Config::get('backend.edit.button'),
        'icon' => Config::get('backend.edit.icon'),
        'label' => Message::get('backend.edit'),
        'ranking' => 100,
    ];
}

$result['actions'] = $actions;

//--

$result['breadcrumbs'] = $controller->createBreadcrumbs([]);

//--

$result['styles'] = [
    ['label' => Message::get('bundle.name'), 'name' => 'name', 'readonly' => true, 'type' => 'text', 'unordered' => true],
    ['label' => Message::get('bundle.remark'), 'name' => 'remark', 'readonly' => true, 'type' => 'text', 'unordered' => true],
];

//--

resolve('backend/list.twig')->render($controller, $form, $result);
