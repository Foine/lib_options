<?php

namespace Lib\Options;

class Controller_Admin_Options extends \Nos\Controller_Admin_Application
{

    protected $config = array(
        'layout' => array(),
        'fields' => array(
            'save' => array(
                'label' => '',
                'form' => array(
                    'type' => 'submit',
                    'tag' => 'button',
                    'value' => 'Save',
                    'class' => 'ui-priority-primary',
                    'data-icon' => 'check',
                ),
            ),
            'context' => array(
                'label' => '',
                'form' => array(
                    'type' => 'hidden',
                ),
            ),
        ),
        'views' => array(
            'form' => 'lib_options::admin/form',
        ),
        'tab' => array(
            'label' => 'Options',
            'url' => '',
            'iconUrl' => '',
            'defaultIconUrl' => '/static/apps/lib_options/img/cog-32.png',
            'app' => true,
            'iconSize' => 32,
            'labelDisplay' => false,
        ),
        'form_name' => '',
        'actions' => array(),
    );
    protected static $options_paths = array();

    public static function _init()
    {
        \Nos\I18n::current_dictionary(array('nos::application', 'nos::common'));
    }

    public function before()
    {
        parent::before();
        $this->_setOptionsPath();
        $this->config_build();
    }

    /*
     * This action is just here to allow user not to call the form action.
     */
    public function action_index($context = null)
    {
        return $this->action_form($context);
    }

    public function action_form($context = null)
    {
        $context = $context ? $context : \Nos\Tools_Context::defaultContext();
        $placeholders = array(
            '_context' => $context,
        );
        $this->config = \Config::placeholderReplace($this->config, $placeholders, false);

        $view_params = $this->view_params();
        $view_params['context'] = $context;
        \Arr::set($view_params, 'config.'.$view_params['context'].'.context', $view_params['context']);

        //Populate fieldset
        $fields_values = \Arr::get($view_params, 'config.'.$view_params['context']);
        foreach ($this->config['fields'] as $field_name => $field_properties) { //This foreach is for common fields.
            if (\Arr::get($field_properties, 'common_field', false)) { //Edit field properties to set the common fields configuration
                \Arr::set($field_properties, 'form.disabled', true);
                \Arr::set($field_properties, 'form.context_common_field', true);
                $allowed_contexts = \Nos\User\Permission::contexts();
                $context_labels = array();
                foreach (array_keys($allowed_contexts) as $context) {
                    $context_labels[] = \Nos\Tools_Context::contextLabel($context);
                }
                $context_labels = htmlspecialchars(\Format::forge($context_labels)->to_json());
                \Arr::set($field_properties, 'form.data-other-contexts', $context_labels);
                \Arr::set($this->config['fields'], $field_name, $field_properties);
                \Arr::set($fields_values, $field_name, \Arr::get($view_params, 'config.'.$field_name));
            }
        }
        $fields = $this->config['fields'];
        $fieldset = \Fieldset::build_from_config($fields, null , $this->build_from_config());
        $fieldset->populate($fields_values);
        $view_params['fieldset'] = $fieldset;

        // We can't do this form inside the view_params() method, because additional vars (added
        // after the reference was created) won't be available from the reference
        $view_params['view_params'] = &$view_params;

        return \View::forge('lib_options::admin/form', $view_params, false);
    }

    public function action_save($view = null)
    {
        \Nos\I18n::current_dictionary(array('lib_options::default', 'nos::common'));
        $config = \Config::load(APPPATH.self::$options_paths[get_called_class()], true);
        $context = \Fuel\Core\Input::post('context') ? \Fuel\Core\Input::post('context') : \Nos\Tools_Context::defaultContext();
        $config[$context] = array();
        \Config::save(APPPATH.self::$options_paths[get_called_class()],$config); //Empty the configuration file for the current context is needed to update fields such as checkbox
        $fields = $this->config['fields'];
        \Security::clean_input();
        foreach ($fields as $name => $properties) {
            $value = \Input::post($name);
            if (\Arr::get($properties, 'common_field', false)) {
                \Arr::set($config, $name, $value);
            } else if ($context != '') {
                \Arr::set($config, $context.'.'.$name, $value);
            }
        }
        $result = \Config::save(APPPATH.self::$options_paths[get_called_class()], $config);
        $return = array();
        if (!empty($result)) {
            $return['success'] = true;
            $return['notify'] = __('OK, les modifications ont été enregistrées');
            $return['closeDialog'] = true;
        } else {
            $return['success'] = false;
            $return['notify'] = __('Erreur dans l\'enregistrement des modifications');
            $return['closeDialog'] = true;
            $return['post'] = $_POST;
            $return['context'] = $context;
        }
        return \Fuel\Core\Format::forge($return)->to_json();
    }

    public static function getOptions($return = true) {
        if (!isset(self::$options_paths[get_called_class()]) || !self::$options_paths[get_called_class()]) self::_setOptionsPath();
        return \Config::load(APPPATH.self::$options_paths[get_called_class()], $return);
    }

    /**
     * Set params used in view
     * WARNING : As views can forge other views, it is necessary to add view_params in view_params...
     * --> every time view_params is changed, $view_params['view_params'] = &$view_params; must be written.
     * @return Array : params for views and the array itself
     */
    protected function view_params()
    {
        $view_params = array(
            'lib_options' => array(
                'config' => $this->config,
                'url_form' => $this->config['controller_url'].'/form',
                'url_save' => $this->config['controller_url'].'/save',
            ),
            'config' => \Config::load(APPPATH.self::$options_paths[get_called_class()], true),
            'form_name' => $this->config['form_name'],
            'toolbar_actions' => $this->config['toolbar_actions'],
        );

        $view_params['view_params'] = &$view_params;

        return $view_params;
    }

    /**
     * Default config for building the fieldset with \Fieldset::build_from_config.
     * @return Array : config
     */
    protected function build_from_config()
    {
        return array(
            'before_save' => array($this, 'before_save'),
            'success' => array($this, 'save'),
        );
    }

    protected static function _setOptionsPath() {
        list($application, $file_name) = \Config::configFile(get_called_class());
        self::$options_paths[get_called_class()] = 'data/apps/'.$application.'/' . str_replace('\\','_',get_called_class()) . '.config.php';
    }

    protected function config_build()
    {
        $metadata = \Config::load(self::getCurrentApplication().'::metadata');

        if (!\Arr::get($this->config, 'controller_url')) {
            \Arr::set($this->config, 'controller_url', self::get_path());
        }
        if (!\Arr::get($this->config, 'tab.url')) {
            \Arr::set($this->config, 'tab.url', $this->config['controller_url'].'/form');
        }
        if (!\Arr::get($this->config, 'tab.iconUrl')) {
            \Arr::set($this->config, 'tab.iconUrl', \Arr::get($metadata, 'icons.32') ? \Arr::get($metadata, 'icons.32') : \Arr::get($this->config, 'tab.defaultIconUrl'));
        }

        //Configure default form name
        $form_name = \Arr::get($this->config, 'form_name', false) ? \Arr::get($this->config, 'form_name') : \Arr::get($metadata, 'name').' options';
        \Arr::set($this->config, 'form_name', $form_name);

        //Translate the save button
        \Arr::set($this->config, 'fields.save.form.value', __(\Arr::get($this->config, 'fields.save.form.value', 'Save')));

        // Convert simplified layout syntax into the full syntax
        foreach (array('layout', 'layout_insert', 'layout_update') as $layout_name) {
            if (!empty($this->config[$layout_name])) {
                $layout = $this->config[$layout_name];
                $view = current($layout);
                if (!is_array($view) || empty($view['view'])) {
                    $this->config[$layout_name] = array(
                        array(
                            'view' => 'lib_options::admin/options_layout',
                            'params' =>  $layout,
                        ),
                    );
                }
            }
        }
    }
}