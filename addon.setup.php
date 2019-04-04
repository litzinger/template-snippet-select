<?php

if (! defined('TEMPLATE_SNIPPET_SELECT_VERSION')){
    define('TEMPLATE_SNIPPET_SELECT_VERSION', '2.0');
    define('TEMPLATE_SNIPPET_SELECT_NAME', 'Template & Snippet Select');
}

$config = [
    'author'        => 'BoldMinded',
    'author_url'    => 'https://boldminded.com',
    'docs_url'      => '',
    'name'          => TEMPLATE_SNIPPET_SELECT_NAME,
    'description'   => 'A Fieldtype to select and embed a template or snippet.',
    'version'       => TEMPLATE_SNIPPET_SELECT_VERSION,
    'namespace'     => 'BoldMinded\TemplateSnippetSelect',
    'settings_exist' => false,
];

return $config;
