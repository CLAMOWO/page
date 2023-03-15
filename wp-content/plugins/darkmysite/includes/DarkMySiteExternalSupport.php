<?php


if ( ! class_exists( 'DarkMySiteExternalSupport' ) ) {
    class DarkMySiteExternalSupport
    {

        public $base_admin;
        public function __construct($base_admin)
        {
            $this->base_admin = $base_admin;
        }

        public function getDisallowedElementsByAvailablePlugins(){
            $disallowed_elements = array();

            /* =============== Default DarkMySite Ignore =============== */
            $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByDarkMySite());

            /* =============== Logged-in as Admin =============== */
            if(function_exists("is_admin_bar_showing")){
                if(is_admin_bar_showing()){
                    $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByAdminLogin());
                }
            }

            /* =============== Is in Admin Panel =============== */
            if(function_exists("is_admin")){
                if(is_admin()){
                    $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByAdminPanel());
                }
            }

            /* =============== Elementor =============== */
            if(function_exists("is_plugin_active")){
                if (is_plugin_active( 'elementor/elementor.php' )) {
                    $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByElementor());
                }
            }

            /* =============== Beaver Builder =============== */
            if ( class_exists( 'FLBuilder' ) ) {
                $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByBeaver());
            }

            /* =============== Block Editor =============== */
            if(function_exists("has_blocks")){
                $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByBlockEditor());
            }

            /* =============== Slider Revolution =============== */
            if(class_exists('RevSliderFront')){
                $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByRevSlider());
            }

            /* =============== OneSignal Push Notifications =============== */
            if(class_exists('OneSignal_Public')){
                $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByOneSignal());
            }

            /* =============== Read Meter =============== */
            if(class_exists('BSFRT_Loader')){
                $disallowed_elements = array_merge($disallowed_elements, $this->getDisallowedElementsByReadMeter());
            }

            return $disallowed_elements;
        }


        /* =============== Default DarkMySite Ignore =============== */
        public function getDisallowedElementsByDarkMySite(){
            return array(
                ".darkmysite_ignore",
                ".darkmysite_ignore *",
                ".darkmysite_switch",
                ".darkmysite_switch *",
            );
        }


        /* =============== Logged-in as Admin =============== */
        public function getDisallowedElementsByAdminLogin(){
            return array(
                "#wpadminbar",
                "#wpadminbar *",
            );
        }

        /* =============== Is in Admin Panel =============== */
        public function getDisallowedElementsByAdminPanel(){
            return array(
                "#adminmenumain",
                "#adminmenumain *",
                ".wp-core-ui .button-primary",
                ".wp-core-ui .button-primary *",
                ".post-com-count-approved",
                ".post-com-count-approved *",
            );
        }


        /* =============== Elementor =============== */
        public function getDisallowedElementsByElementor(){
            return array(
                ".elementor-background-overlay",
                ".elementor-element-overlay",
                ".elementor-button-link",
                ".elementor-button-link *",
                ".elementor-widget-spacer",
                ".elementor-widget-spacer *",
            );
        }



        /* =============== Beaver Builder =============== */
        public function getDisallowedElementsByBeaver(){
            return array(
                ".uabb-button",
                ".uabb-button *",
            );
        }


        /* =============== Block Editor =============== */
        public function getDisallowedElementsByBlockEditor(){
            return array(
                ".wp-block-button__link",
                ".wp-block-button__link *",
            );
        }


        /* =============== Slider Revolution =============== */
        public function getDisallowedElementsByRevSlider(){
            return array(
                "rs-fullwidth-wrap",
                "rs-fullwidth-wrap *",
            );
        }


        /* =============== OneSignal Push Notifications =============== */
        public function getDisallowedElementsByOneSignal(){
            return array(
                ".onesignal-slidedown-dialog",
                ".onesignal-slidedown-dialog *",
            );
        }


        /* =============== Read Meter =============== */
        public function getDisallowedElementsByReadMeter(){
            return array(
                "#bsf_rt_progress_bar_container",
                "#bsf_rt_progress_bar_container *",
            );
        }

    }
}
