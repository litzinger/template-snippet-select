<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Template Select Fieldtype Class
 * Based on Timothy Kelty's Template Select extension for EE 1.6
 *
 * @package     ExpressionEngine
 * @subpackage  Fieldtypes
 * @category    Template & Snippet Select
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com
 */

class Template_snippet_select_ft extends EE_Fieldtype {

    var $cache;
    var $has_array_data = true;
    var $settings_exist = 'y';
    var $settings = [];

    var $info = [
        'name'      => TEMPLATE_SNIPPET_SELECT_NAME,
        'version'   => TEMPLATE_SNIPPET_SELECT_VERSION
    ];

    public function __construct()
    {
        parent::__construct();

        // Create cache
        if (! isset(ee()->session->cache[__CLASS__])) {
            ee()->session->cache[__CLASS__] = array();
        }
        $this->cache =& ee()->session->cache[__CLASS__];
    }

    public function accepts_content_type($name = ''): bool
    {
        $acceptedTypes = [
            'blocks/1',
            'channel',
            'fluid_field',
            'grid',
        ];

        return in_array($name, $acceptedTypes);
    }

    /**
     * Normal Fieldtype settings
     * @param $data
     * @return array|string
     */
    public function display_settings($data)
    {
        return $this->_getFieldSettings($data);
    }

    /**
     * Display Matrix Cell Settings
     * @param $data
     * @return array
     */
    public function display_cell_settings($data)
    {
        return $this->_getFieldSettings($data, 'matrix');
    }

    /**
     * Display Low Variables Settings
     * @param $data
     * @return array
     */
    public function display_var_settings($data)
    {
        return $this->_getFieldSettings($data);
    }

    /**
     * Display Grid Settings
     * @param $data
     * @return array
     */
    public function grid_display_settings($data)
    {
        return $this->_getFieldSettings($data);
    }

    /**
     * Save Normal Fieldtype settings
     * @param $data
     * @return array|mixed
     */
    public function save_settings($data)
    {
        if (empty($data['tss']))
        {
            $settings = ee()->input->post('tss');
        }
        else
        {
            $settings = $data['tss'];
        }

        return [
            'template_snippet_select' => [
                'field_templates' => [
                    'show_all' => isset($settings['field_show_all_templates']) ? $settings['field_show_all_templates'] : false,
                    'show_group' => isset($settings['field_show_group_templates']) ? $settings['field_show_group_templates'] : false,
                    'show_selected' => isset($settings['field_show_selected_templates']) ? $settings['field_show_selected_templates'] : false,
                    'templates' => isset($settings['field_template_select']) ? $settings['field_template_select'] : false
                ],
                'field_snippets' => [
                    'show_all' => isset($settings['field_show_all_snippets']) ? $settings['field_show_all_snippets'] : false,
                    'show_selected' => isset($settings['field_show_selected_snippets']) ? $settings['field_show_selected_snippets'] : false,
                    'snippets' => isset($settings['field_snippet_select']) ? $settings['field_snippet_select'] : false
                ]
            ]
        ];
    }

    /**
     * Save Matrix Cell Settings
     */
    public function save_cell_settings($settings)
    {
        $settings = isset($settings['tss']) ? $settings['tss'] : [];

        return [
            'template_snippet_select' => [
                'field_templates' => [
                    'show_all' => isset($settings['field_show_all_templates']) ? $settings['field_show_all_templates'] : false,
                    'show_group' => isset($settings['field_show_group_templates']) ? $settings['field_show_group_templates'] : false,
                    'show_selected' => isset($settings['field_show_selected_templates']) ? $settings['field_show_selected_templates'] : false,
                    'templates' => isset($settings['field_template_select']) ? $settings['field_template_select'] : false
                ],
                'field_snippets' => [
                    'show_all' => isset($settings['field_show_all_snippets']) ? $settings['field_show_all_snippets'] : false,
                    'show_selected' => isset($settings['field_show_selected_snippets']) ? $settings['field_show_selected_snippets'] : false,
                    'snippets' => isset($settings['field_snippet_select']) ? $settings['field_snippet_select'] : false
                ]
            ]
        ];
    }

    /**
     * Save Low Variables Settings
     */
    public function save_var_settings($settings)
    {
        return $this->save_settings($settings);
    }

    /**
     * Save Grid Cell Settings
     */
    public function grid_validate_settings($settings)
    {
        return true;
    }

    /**
     * Normal Fieldtype Display
     */
    public function display_field($data)
    {
        $templates = $this->_createTemplateFieldOptions();
        $snippets  = $this->_createSnippetFieldOptions();

        $templates = $templates ? ['Templates' => $templates] : [];
        $snippets = $snippets ? ['Snippets' => $snippets] : [];

        $options = array_merge(
            [lang('none')],
            $templates,
            $snippets
        );

        $return = form_dropdown($this->field_name, $options, $data, 'id="'. $this->field_name .'"');

        return $return;
    }

    /**
     * Matrix Cell Display
     */
    public function display_cell($data)
    {
        $templates = $this->_createTemplateFieldOptions();
        $snippets  = $this->_createSnippetFieldOptions();

        $templates = $templates ? ['Templates' => $templates] : [];
        $snippets = $snippets ? ['Snippets' => $snippets] : [];

        $options = array_merge(
            [lang('none')],
            $templates,
            $snippets
        );

        $return['class'] = 'template-select-matrix';
        $return['data'] = form_dropdown($this->cell_name, $options, $data, 'id="'. $this->cell_name .'"');

        return $return;
    }

    /**
     * Low Variables Fieldtype Display
     */
    public function display_var_field($data)
    {
        return $this->display_field($data);
    }

    /**
     * Print the embed tag, with parameters if defined
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        // If it's numeric, then we have a Snippet ID, otherwise we have a template
        if(is_numeric($data) and $data and isset(ee()->config->_global_vars[$this->_get_snippet($data)]))
        {
            return ee()->config->_global_vars[$this->_get_snippet($data)];
        }
        elseif($data)
        {
            if($params && isset($params['embed']) && $params['embed'] == 'y')
            {
                $p = array();
                $site = '';

                foreach($params as $param => $value)
                {
                    if ($param == 'site' && $value != '')
                    {
                        $site = $value.':';
                    }
                    else
                    {
                        $p[] = $param .'="'. $value .'"';
                    }
                }

                return '{embed="'. $site.$data .'" '. implode(" ", $p) .'}';
            }
            elseif(isset($params['embed']) && $params['embed'] == 'y')
            {
                return '{embed="'. $data .'"}';
            }
            else
            {
                return $data;
            }
        }
        else
        {
            return '';
        }
    }

    /**
     * Low Variables replace tag
     */
    public function display_var_tag($data, $params = '', $tagdata = '')
    {
        $this->replace_tag($data, $params, $tagdata);
    }

    /**
    * Template Groups Multi-select
    *
    * @return string  multi-select HTML
    * @access private
    */
    private function _createTemplateFieldOptions()
    {
        ee()->lang->loadfile('template_snippet_select');
        $settings = $this->_get_settings();
        $settings = isset($settings['field_templates']) ? $settings['field_templates'] : [];
        /** @var CI_DB_result $templates */
        $templates = $this->_get_templates();

        // Get the templates (if they exist)
        if($templates->num_rows() == 0)
        {
            return [];
        }
        elseif(is_array($settings))
        {
            $template_options = [];
            foreach($templates->result_array() as $row)
            {
                // Depending on which settings show the appropriate templates
                if(isset($settings['show_all']) && $settings['show_all'] == 'y')
                {
                    $file = $row['group_name'] .'/'. $row['template_name'];
                    $template_options[$file] = $file;
                }
                elseif(isset($settings['show_group']) && $settings['show_group'] !== false && in_array($row['group_name'], $settings['show_group']))
                {
                    $file = $row['group_name'] .'/'. $row['template_name'];
                    $template_options[$file] = $file;
                }
                elseif(
                    !empty($settings['templates']) &&
                    (
                        in_array('--', $settings['templates']) ||
                        in_array($row['template_id'], $settings['templates'])
                    )
                ) {
                    $file = $row['group_name'] .'/'. $row['template_name'];
                    $template_options[$file] = $file;
                }
            }

            return count($template_options) > 0 ? $template_options : [];
        }

        return [lang('template_not_defined')];
    }

    /**
    * Snippets Multi-select
    *
    * @return string  multi-select HTML
    * @access private
    */
    private function _createSnippetFieldOptions()
    {
        ee()->lang->loadfile('template_snippet_select');
        $settings = $this->_get_settings();
        $settings = isset($settings['field_snippets']) ? $settings['field_snippets'] : [];
        /** @var CI_DB_result $snippets */
        $snippets = $this->_get_snippets();

        // Get the snippets (if they exist)
        if($snippets->num_rows() == 0)
        {
            return [];
        }
        elseif(is_array($settings))
        {
            $snippet_options = [];
            foreach($snippets->result_array() as $row)
            {
                if(
                    isset($settings['snippets']) && is_array($settings['snippets']) &&
                    (
                        !empty($settings['snippets']) && in_array($row['snippet_id'], $settings['snippets']) ||
                        (isset($settings['show_all']) && $settings['show_all'] == 'y') || // Legacy
                        in_array('--', $settings['snippets']) // EE3
                    )
                ) {
                    $snippet_options[$row['snippet_id']] = $row['snippet_name'];
                }
            }

            return count($snippet_options) > 0 ? $snippet_options : [];
        }

        return [lang('snippet_not_defined')];
    }

    /**
     * @param bool $optionsOnly
     * @return array
     */
    private function _createTemplateGroupSettingOptions($optionsOnly = false)
    {
        $options = [];

        $templateGroups = ee('Model')->get('TemplateGroup')->all();

        /** @var \EllisLab\ExpressionEngine\Model\Template\TemplateGroup $group */
        foreach ($templateGroups as $group) {
            $options[$group->group_name] = $group->group_name;
        }

        if ($optionsOnly) {
            return $options;
        }

        return [
            '--' => [
                'name' => 'Any Group',
                'children' => $options
            ]
        ];
    }

    /**
     * @param bool $optionsOnly
     * @return array
     */
    private function _createTemplateSettingOptions($optionsOnly = false)
    {
        $options = [];

        $templateGroups = ee('Model')->get('TemplateGroup')->all();

        /** @var \EllisLab\ExpressionEngine\Model\Template\TemplateGroup $group */
        foreach ($templateGroups as $group) {
            /** @var \EllisLab\ExpressionEngine\Model\Template\Template $template */
            foreach ($group->Templates as $template) {
                $options[$template->getId()] = $template->TemplateGroup->group_name .'/'. $template->template_name;
            }
        }

        if ($optionsOnly) {
            return $options;
        }

        return [
            '--' => [
                'name' => 'Any Template',
                'children' => $options
            ]
        ];
    }

    /**
     * @param bool $optionsOnly
     * @return array
     */
    private function _createSnippetSettingOptions($optionsOnly = false)
    {
        $options = [];

        $snippets = ee('Model')->get('Snippet')->all();

        /** @var \EllisLab\ExpressionEngine\Model\Template\TemplateGroup $group */
        foreach ($snippets as $snippet) {
            $options[$snippet->getId()] = $snippet->snippet_name;
        }

        if ($optionsOnly) {
            return $options;
        }

        return [
            '--' => [
                'name' => 'Any Snippet',
                'children' => $options
            ]
        ];
    }

    private function _get_templates()
    {
        if(!isset($this->cache['templates']))
        {
            // Get the current Site ID
            $site_id = ee()->config->item('site_id');

            $sql = "SELECT tg.group_name, t.template_name, t.template_id
                    FROM exp_template_groups tg, exp_templates t
                    WHERE tg.group_id = t.group_id
                    && tg.site_id = '".$site_id."'
                    ORDER BY tg.group_name, t.template_name";

            $this->cache['templates'] = ee()->db->query($sql);
        }

        return $this->cache['templates'];
    }

    private function _get_snippet($snippet_id)
    {
        if(!isset($this->cache['snippet_'. $snippet_id]))
        {
            ee()->db->select('snippet_name');
            ee()->db->where('snippet_id', $snippet_id);
            ee()->db->from('snippets');
            $query = ee()->db->get();

            $this->cache['snippet_'. $snippet_id] = $query->row('snippet_name');
        }

        return $this->cache['snippet_'. $snippet_id];
    }

    private function _get_snippets()
    {
        if(!isset($this->cache['snippets']))
        {
            // Get the current Site ID
            $site_id = ee()->config->item('site_id');

            $sql = "SELECT *
                    FROM exp_snippets
                    WHERE site_id = '".$site_id."'
                    || site_id = '0'
                    ORDER BY snippet_name";

            $this->cache['snippets'] = ee()->db->query($sql);
        }

        return $this->cache['snippets'];
    }

    private function _get_settings()
    {
        // If it's a Matrix field
        if(isset($this->settings['template_snippet_select']) && is_array($this->settings['template_snippet_select']))
        {
            return $this->settings['template_snippet_select'];
        }
        // It's not a Matrix field
        elseif(isset($this->settings['template_snippet_select']))
        {
            return unserialize(base64_decode($this->settings['template_snippet_select']));
        }
        else
        {
            return [];
        }
    }

    /**
     * @param $settings
     * @param null|string $fieldType
     * @return array
     */
    private function _getFieldSettings($settings, $fieldType = null)
    {
        ee()->lang->loadfile('template_snippet_select');

        // Legacy or new format?
        if (isset($settings['field_templates'])) {
            $legacySettings = $settings['field_templates'];

            if (!isset($legacySettings['field_templates']['templates'])) {
                $settings = [];
                $settings['field_templates']['templates'] = $legacySettings;
            }
        } else {
            $settings = (!isset($settings['template_snippet_select']) || $settings['template_snippet_select'] == '') ? [] : $settings['template_snippet_select'];
        }

        $valueTemplateGroups = (isset($settings['field_templates']['show_group']) && is_array($settings['field_templates']['show_group']) ? $settings['field_templates']['show_group'] : []);
        $valueTemplates = (isset($settings['field_templates']['templates']) && is_array($settings['field_templates']['templates']) ? $settings['field_templates']['templates'] : []);
        $valueSnippets = (isset($settings['field_snippets']['snippets']) && is_array($settings['field_snippets']['snippets']) ? $settings['field_snippets']['snippets'] : []);

        if ($fieldType === 'matrix') {
            return [
                [
                    'Template Groups',
                    form_multiselect('tss[field_show_group_templates][]', $this->_createTemplateGroupSettingOptions(true), $valueTemplateGroups),
                ],
                [
                    'Templates',
                    form_multiselect('tss[field_template_select][]', $this->_createTemplateSettingOptions(true), $valueTemplates),
                ],
                [
                    'Snippets',
                    form_multiselect('tss[field_snippet_select][]', $this->_createSnippetSettingOptions(true), $valueSnippets),
                ]
            ];
        }

        $fields = [
            [
                'title' => 'Template Groups',
                'desc' => 'Show all templates within the selected groups.',
                'fields' => [
                    'tss[field_show_group_templates]' => [
                        'type' => 'checkbox',
                        'nested' => true,
                        'wrap' => true,
                        'attrs' => 'data-any="y"',
                        'value' => $valueTemplateGroups,
                        'choices' => $this->_createTemplateGroupSettingOptions(),
                    ]
                ]
            ],
            [
                'title' => 'Templates',
                'desc' => 'Show only the selected templates.',
                'fields' => [
                    'tss[field_template_select]' => [
                        'type' => 'checkbox',
                        'nested' => true,
                        'wrap' => true,
                        'attrs' => 'data-any="y"',
                        'value' => $valueTemplates,
                        'choices' => $this->_createTemplateSettingOptions(),
                    ]
                ]
            ],
            [
                'title' => 'Snippets',
                'desc' => 'Show only the selected snippets.',
                'fields' => [
                    'tss[field_snippet_select]' => [
                        'type' => 'checkbox',
                        'nested' => true,
                        'wrap' => true,
                        'attrs' => 'data-any="y"',
                        'value' => $valueSnippets,
                        'choices' => $this->_createSnippetSettingOptions(),
                    ]
                ]
            ],
        ];

        if (in_array($this->content_type(), ['grid', 'blocks', 'blocks/1'])) {
            return ['field_options' => $fields];
        }

        return ['field_options_tss' => [
            'label' => 'field_options',
            'group' => 'template_snippet_select',
            'settings' => $fields
        ]];
    }
}
