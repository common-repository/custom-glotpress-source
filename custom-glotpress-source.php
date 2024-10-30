<?php
/*
 * Plugin Name: Custom GlotPress Source
 * Plugin URI: https://wordpress.org/plugins/custom-glotpress-source/
 * Description: Allows to manage translations from a custom GlotPress install.
 * Version: 1.5.2
 * Author: N.O.U.S. Open Useful and Simple
 * Author URI: https://apps.avecnous.eu/?mtm_campaign=wp-plugin&mtm_kwd=custom-glotpress-source&mtm_medium=dashboard
 * Text Domain: custom-glotpress-source
 */

global $Custom_GlotPress_Source;

add_action ('plugins_loaded', function(){
    global $Custom_GlotPress_Source;
    require plugin_dir_path(__FILE__).'vendor/agencenous/wp-reporting/src/wp-reporting.php';
    \WPReporting()->register('custom-glotpress-source', [
        'label' => __('Custom GlotPress Source', 'custom-glotpress-source'),
        'description' => __('Help us to improve this plugin. Send logs when a bug occurs.', 'custom-glotpress-source'),
        'category' => 'plugin',
        'to' => 'am91cm5hbGlzZXIrMjE4QGF2ZWNub3VzLmV1',
        'only_in_dir' => __DIR__,
    ]);
    $Custom_GlotPress_Source = new Custom_GlotPress_Source;
});

class Custom_GlotPress_Source{
    var $options;
    var $defaults;
    var $last_check_dates;
    var $available_translations = array();
    var $mu = false;

    function __construct(){
        require_once(ABSPATH.'/wp-admin/includes/plugin.php');
        $this->mu = is_plugin_active_for_network(basename(__DIR__).'/'.basename(__FILE__));
        add_action(($this->mu ? 'network_' : '').'admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_post_update', array($this, 'trigger_network_settings') );
        add_action('core_upgrade_preamble', array(&$this, 'core_upgrade_preamble'));
        add_action('update-core-custom_do-custom-translation-upgrade', array(&$this, 'update_core'));
        add_action( 'admin_enqueue_scripts', array($this, 'plugin_admin_scripts') );

        /* Add the wp-cli subcommand "custom list" into the command wp language */
        if (class_exists('WP_CLI')) {
            $custom_list_cli = function ($args, $assoc_args) {
                $available_translations = get_site_transient('custom-glotpress-available-translations');
                \WP_CLI::line(\WP_CLI::colorize("%3%K Projets List %n"));
                foreach ($available_translations as $translations) {
                    \WP_CLI::line($translations->path);
                }
            };
            \WP_CLI::add_command('language custom list', $custom_list_cli, array(
                'shortdesc' => 'Show the list of all language translations.'));
        }

        /* Add the wp-cli subcommand "custom update" into the command wp language */
        if (class_exists('WP_CLI')) {
            $custom_update_cli = function ($args, $assoc_args) {
                $allProjets = false;
                if (isset($assoc_args['all']) && $assoc_args['all'] == true) {
                    $allProjets = true;
                }
                if(!isset($args[0]) && !$allProjets){
                    \WP_CLI::error('You must specify a project slug or use --all');
                }
                $this->update_core([
                    $args[0] => $args[0],
                ], $allProjets);
            };
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::add_command('language custom update', $custom_update_cli);
            }
        }

        $this->defaults = array(
            'url'=>'',
        );

        $this->last_check_dates = (array) $this->get_option('custom-glotpress-last-check');
    }
    function plugin_admin_scripts(){
        wp_enqueue_script('custom-glotpress-source-selection-checkbox', plugin_dir_url(__FILE__) . 'js/script.js');
    }


    function get_option($option){
        $func = ($this->mu ? 'get_site_option' : 'get_option');
        return $func($option);
    }

    function update_option($option, $value){
        $func = ($this->mu ? 'update_site_option' : 'update_option');
        return $func($option, $value);
    }

    function Options(){
        if(!$this->options){
            $this->options = wp_parse_args((array) $this->get_option('custom-glotpress-src'), $this->defaults);
        }
        return $this->options;
    }

    function Get($setting){
        return (isset($this->Options()[$setting]) ? $this->Options()[$setting] : false);
    }

    function admin_menu() {
        add_submenu_page($this->mu ? 'settings.php' : 'options-general.php',__('Custom GlotPress Source', 'custom-glotpress-source'), __('GlotPress', 'custom-glotpress-source'), 'manage_options', 'ctm-gp-src-settings', array(&$this, 'manage_settings'));
    }

    function admin_init() {
        register_setting('ctm-gp-src-settings', 'custom-glotpress-src');

        add_settings_section(
                'ctm-gp-src-settings-basics', __('Basics', 'custom-glotpress-source'), array(&$this, 'settings_section_callback'), 'ctm-gp-src-settings'
        );

        add_settings_field(
                'custom-glotpress-src_url',
                __('API URL', 'custom-glotpress-source'),
                array(&$this, 'settings_field_default_callback'),
                'ctm-gp-src-settings',
                'ctm-gp-src-settings-basics',
                array(
                    'name' => 'url',
                    'description' => __('example: https://mysite.com/glotpress/', 'custom-glotpress-source')
                    )
        );
    }

    public function trigger_network_settings(){
        if(!$this->mu){
            return;
        }
        if(!isset($_POST['custom-glotpress-src'])){
            return;
        }
        if (!wp_verify_nonce(\filter_input(INPUT_POST, 'ctm-gp-src-settings', FILTER_SANITIZE_STRING), 'ctm-gp-src-settings')) {
            wp_die(__('Cheating uh?', 'custom-glotpress-source'));
        }
        $settings = array(
            'url'=>esc_url_raw($_POST['custom-glotpress-src']['url']),
        );
        $function = $this->mu ? 'update_site_option' : 'update_option';
        $update = $function( 'custom-glotpress-src', $settings);
        wp_redirect(
            add_query_arg(
                array(
                    'page' => 'ctm-gp-src-settings',
                    'confirm' => $update,
                ),
                (admin_url( $this->mu ? 'network/settings.php' : 'options-general.php' ))
            )
        );
        exit;
    }

    function manage_settings() {
        \WPReporting()->listen('custom-glotpress-source');
        ?>
        <div class="wrap">
            <h2><?php _e('Custom GlotPress Settings', 'custom-glotpress-source'); ?></h2>
            <form action="<?php echo admin_url($this->mu ? 'admin-post.php' : 'options.php'); ?>" method="post">
                <?php wp_nonce_field('ctm-gp-src-settings', 'ctm-gp-src-settings'); ?>
                <?php settings_fields('ctm-gp-src-settings'); ?>
                <?php do_settings_sections('ctm-gp-src-settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        \WPeporting()->stop();
    }

    function settings_section_callback($arg) {

    }

    function settings_field_default_callback($args) {
        \WPReporting()->listen('custom-glotpress-source');
        $options = $this->Options();
        $option = 'custom-glotpress-src';
        ?>
        <input name="<?php echo $option; ?>[<?php esc_attr_e($args['name']); ?>]" id="<?php esc_attr_e($args['name']); ?>" value="<?php esc_attr_e( isset($options[$args['name']]) ? $options[$args['name']] : ''); ?>" class="regular-text"/>
        <?php if (isset($args['description']) && $args['description']): ?>
            <div class="description"><?php esc_html_e($args['description']); ?></div>
        <?php endif; ?>
        <?php
        \WPReporting()->stop();
    }


    function core_upgrade_preamble(){
        \WPReporting()->listen('custom-glotpress-source');
        if(false != $count_success = filter_input(INPUT_GET, 'upgrade-custom-t9n-success', FILTER_SANITIZE_NUMBER_INT)){
            show_message(sprintf(__('Upgrade successful for %d translations', 'custom-glotpress-source'), $count_success));
        }
        if(false != $count_fail = filter_input(INPUT_GET, 'upgrade-custom-t9n-fail', FILTER_SANITIZE_NUMBER_INT)){
            show_message(sprintf(__('Upgrade failed for %d translations', 'custom-glotpress-source'), $count_fail));
        }

        $available_translations = get_site_transient('custom-glotpress-available-translations');
        if( !$available_translations || filter_input(INPUT_GET, 'force-check', FILTER_SANITIZE_NUMBER_INT)){
            $projects = $this->check_projects();
            foreach($projects as $remote_path=>$local_path){
                $this->check_translations($remote_path);
            }
            $available_translations = $this->available_translations;
        }
        $count_translations = count($available_translations);
        if(!$count_translations){
            return;
        }
        $date_format = get_option('date_format').' '.get_option('time_format');
        ?>
        <h2><?php printf(__('Custom translations %s', 'custom-glotpress-source'), '<span class="count">('.$count_translations.')</span>'); ?></h2>

        <form method="post" action="update-core.php?action=do-custom-translation-upgrade" name="custom-upgrade-translations" class="upgrade">
            <p><?php _e('New custom translations are available', 'custom-glotpress-source'); ?></p>
            <?php wp_nonce_field('ctm-gp-src-upgrade', 'ctm-gp-src-upgrade'); ?>
            <p><input class="button" type="submit" value="<?php esc_attr_e(__('Upgrade translations', 'custom-glotpress-source')); ?>" name="upgrade"></p>
            <table id="update-traductions" class="widefat updates-table">
                <thead>
                   <tr>
                       <td><input id="traductions-select-all" type="checkbox"></td>
                       <td colspan="4"><label for="traductions-select-all"><?php _e('Select all', 'custom-glotpress-source'); ?></label></td>
                   </tr>
                </thead>
                <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col"><?php _e('Project', 'custom-glotpress-source'); ?></th>
                        <th scope="col"><?php _e('Languages', 'custom-glotpress-source'); ?></th>
                        <th scope="col"><?php _e('Current version', 'custom-glotpress-source'); ?></th>
                        <th scope="col"><?php _e('Last modified', 'custom-glotpress-source'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($available_translations as $set): ?>
                            <tr>
                                <td><input type="checkbox" id="<?php echo $set->path; ?>projets-list" name="custom_glotpress_source_projects[<?php echo $set->path; ?>]" value="1"/></td>
                                <td><label for="<?php echo $set->path; ?>projets-list"><?php echo $set->path; ?></label></td>
                                <td><?php echo $set->wp_locale; ?></td>
                                <td><?php echo (isset($this->last_check_dates[$set->path]) ? date_i18n($date_format, $this->last_check_dates[$set->path]) : '--'); ?></td>
                                <td><?php echo date_i18n($date_format, strtotime($set->last_modified)); ?></td>
                            </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                   <tr>
                       <td><input id="traductions-select-all-foot" type="checkbox"></td>
                       <td colspan="4"><label for="traductions-select-all-foot"><?php _e('Select all', 'custom-glotpress-source'); ?></label></td>
                   </tr>
                </tfoot>
            </table>
            <p><input class="button" type="submit" value="<?php esc_attr_e(__('Upgrade translations', 'custom-glotpress-source')); ?>" name="upgrade"></p>
        </form>
        <?php
        \WPReporting()->stop();
    }

    /**
     * Update translations of projects
     * Called by update-core-custom_do-custom-translation-upgrade hook
     */
    function update_core( $projectsName = [], $allProjets = false){
        \WPReporting()->listen('custom-glotpress-source');
        if (php_sapi_name() !== 'cli' && !wp_verify_nonce(\filter_input(INPUT_POST, 'ctm-gp-src-upgrade', FILTER_SANITIZE_STRING), 'ctm-gp-src-upgrade')) {
            wp_die(__('Cheating uh?', 'custom-glotpress-source'));
        }
        $projects = $this->check_projects();
        /* Backoffice comportement */
        if (isset($_POST['custom_glotpress_source_projects']) && empty($projectsName)) {
            $custom_glotpress_source_projects = $_POST['custom_glotpress_source_projects'];
        }
        /*  cli comportement  */
        if (!isset($_POST['custom_glotpress_source_projects']) && !empty($projectsName)) {
            $custom_glotpress_source_projects = $projectsName;
        }
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::log('update the translations');
        }
        foreach($projects as $remote_path=>$local_path){
            $this->check_translations($remote_path);
        }
        $count_success=0;
        $count_fail=0;
        $available_translations = get_site_transient('custom-glotpress-available-translations');
        foreach($available_translations as $id=>$set){
            if (defined('WP_CLI') && WP_CLI) {
                \WP_CLI::log($set->path);
            }
            if(!$allProjets && $custom_glotpress_source_projects && !isset($custom_glotpress_source_projects[$set->path])){
                continue;
            }
            $final_mo_path = sprintf($projects[$set->path], $set->wp_locale).'.mo';
            $final_po_path = sprintf($projects[$set->path], $set->wp_locale).'.po';
            $mo_file = $this->download($set->path, $set->locale, $set->slug, 'mo', $final_mo_path);
            if($mo_file){
                $count_success++;
                unset($available_translations[$id]);
                $this->last_check_dates[$set->path] = time();
                $this->download($set->path, $set->locale, $set->slug, 'po', $final_po_path);
                if (defined('WP_CLI') && WP_CLI) {
                    \WP_CLI::log('update success');
                }
            }
            else{
                $count_fail++;
                if (defined('WP_CLI') && WP_CLI) {
                    \WP_CLI::log('update failed');
                }
            }
        }
        set_site_transient('custom-glotpress-available-translations', $available_translations, 1 * DAY_IN_SECONDS);
        $this->update_option('custom-glotpress-last-check', $this->last_check_dates);
        \WPReporting()->stop();
        if (!defined('WP_CLI') || !WP_CLI) {
            wp_safe_redirect(add_query_arg(array('upgrade-custom-t9n-success' => $count_success, 'upgrade-custom-t9n-fail' => $count_fail), admin_url(($this->mu ? 'network/' : '') . 'update-core.php')));
            exit;
        }
    }

    function is_valid_domain_path($domain_path){
        return (!empty($domain_path) && str_replace(['languages', '/'], '', $domain_path)!='');
    }

    function check_projects(){
        \WPReporting()->listen('custom-glotpress-source');
        $projects = array();
        $plugins = get_plugins();
        foreach ($plugins as $plugin) {
            if($this->is_valid_domain_path($plugin['DomainPath'])){
                $projects[$plugin['DomainPath']] = WP_LANG_DIR.'/plugins/'.$plugin['TextDomain'].'-%s';
            }
        }
        $themes = wp_get_themes();
        foreach ($themes as $theme) {
            $domain_path = $theme->get('DomainPath');
            if(!$domain_path){
                continue;
            }
            if($this->is_valid_domain_path($domain_path)){
                $projects[$domain_path] = WP_LANG_DIR.'/themes/'.$theme->get('TextDomain').'-%s';
            }
        }
        $civi_extensions = $this->get_civi_extensions();
        $custom_civicrm_l10n_path = null;
        // If extension "uplang" is installed, use the custom path
        if(isset($civi_extensions['uplang']) && method_exists('\Civi', 'paths')){
            $custom_civicrm_l10n_path = untrailingslashit(\Civi::paths()->getPath('[civicrm.private]'));
        }
        if(isset($civi_extensions['uplang']) && strstr($custom_civicrm_l10n_path, '[civicrm.private]') && method_exists('\CRM_Utils_File', 'baseFilePath')){
            $custom_civicrm_l10n_path = untrailingslashit(\CRM_Utils_File::baseFilePath());
        }
        $custom_civicrm_l10n_path = apply_filters('custom_glotpress_source_civicrm_l10n_path', $custom_civicrm_l10n_path);
        foreach($civi_extensions as $extension){
            if(isset($extension['urls']) && isset($extension['urls']['GlotPress Path'])){
                $projects[$extension['urls']['GlotPress Path']] = ($custom_civicrm_l10n_path ? $custom_civicrm_l10n_path : $extension['path']).'/l10n/%s/LC_MESSAGES/'.$extension['key'];
            }
        }
        \WPReporting()->stop();
        return apply_filters('custom_glotpress_source_projects' , $projects);
    }

    function check_translations($project_name=''){
        \WPReporting()->listen('custom-glotpress-source');
        $base_url = $this->Get('url');
        $project_url = "{$base_url}api/projects/{$project_name}";

        $request = wp_remote_get( $project_url );

        if( is_wp_error( $request ) ) {
            return;
        }
        $body = wp_remote_retrieve_body( $request );
        $data = json_decode( $body );

        if(isset($data->translation_sets) && is_array($data->translation_sets)){
            foreach($data->translation_sets as $translation_set){
                if(!isset($this->last_check_dates[$project_name]) || strtotime($translation_set->last_modified) > $this->last_check_dates[$project_name]){
                    $translation_set->path = $project_name;
                    $this->available_translations[$translation_set->id] = $translation_set;
                }
            }
        }

        $sub_projects = (empty($project_name) ? $data : (isset($data->sub_projects) ? $data->sub_projects : array()));
        if(is_array($sub_projects)){
            foreach($sub_projects as $sub_project){
                $this->check_translations($sub_project->path);
            }
        }
        set_site_transient('custom-glotpress-available-translations', $this->available_translations, 1 * DAY_IN_SECONDS);
        \WPReporting()->stop();
    }

    function download($project_path, $locale, $set='default', $format='mo', $target=null){
        \WPReporting()->listen('custom-glotpress-source');
        $base_url = $this->Get('url');
        $file_url = "{$base_url}/api/projects/{$project_path}/{$locale}/{$set}/export-translations?format={$format}";
        $file_url = apply_filters('custom_glotpress_source_fileurl' , $file_url, $project_path, $locale, $set, $format);
        $request = wp_remote_get( $file_url );
        
        if( is_wp_error( $request ) ) {
            \WPReporting()->stop();
            return;
        }
        $body = wp_remote_retrieve_body( $request );
        \WPReporting()->stop();
        if($target){
            wp_mkdir_p(dirname($target));
            if(file_put_contents($target, $body)){
                return $target;
            }
            return false;
        }
        return $body;
    }

    function get_civi_extensions(){
        \WPReporting()->listen('custom-glotpress-source');
        $extensions = [];
        // if(function_exists('civi_wp') && function_exists('civicrm_api4')){
        //     $_extensions = civicrm_api4('Extension', 'get', [
        //         'limit' => 0,
        //         'checkPermissions' => false,
        //     ]);
        //     if($_extensions->count()){
        //         $extensions = $_extensions->getArrayCopy();
        //     }
        //     return $extensions;
        // }
        if(function_exists('civi_wp') && function_exists('civicrm_api3')){
            $_extensions = civicrm_api3('Extension', 'get', [
                'sequential' => 1,
                'options' => ['limit' => 0],
            ]);
            if(isset($_extensions['values'])){
                $extensions = $_extensions['values'];
            }
        }
        // Set extension's key as array key
        $extensions = array_combine(array_column($extensions, 'key'), $extensions);
        \WPReporting()->stop();
        return $extensions;
    }
}

