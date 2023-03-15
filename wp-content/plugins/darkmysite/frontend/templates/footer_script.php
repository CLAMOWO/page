<?php if(!is_admin()){ ?>
    <?php if($this->data_settings["enable_dark_mode_switch"] == "1") { ?>
        <?php if(!$this->utils->is_hidden_by_user_agent($this->data_settings["hide_on_desktop"], $this->data_settings["hide_on_mobile"], $this->data_settings["hide_on_mobile_by"])) { ?>

            <?php include DARKMYSITE_PATH . "frontend/templates/views/switch.php"; ?>

        <?php } ?>
    <?php } ?>
<?php } ?>


<?php if(is_admin()){ ?>
    <?php /* Check if block editor is on, then add the dark mode button there */ ?>
    <?php if($this->data_settings["enable_admin_dark_mode"] == "1"){ ?>
        <?php if ( class_exists( 'WP_Block_Type_Registry' ) ) { ?>
            <?php if ( get_current_screen() && 'post' === get_current_screen()->base && ('post' === get_current_screen()->post_type || 'page' === get_current_screen()->post_type) ) { ?>
                <script type="text/javascript">
                    wp.domReady( function() {
                        const observer = new MutationObserver( function( mutations ) {
                            mutations.forEach( function( mutation ) {
                                if ( mutation.addedNodes && mutation.addedNodes.length ) {
                                    for ( let i = 0; i < mutation.addedNodes.length; i++ ) {
                                        const node = mutation.addedNodes[i];
                                        if ( node.classList && node.classList.contains( 'edit-post-header-toolbar' ) ) {
                                            const button = document.createElement( 'button' );
                                            button.className = 'darkmysite_block_editor_switch darkmysite_ignore';
                                            button.innerHTML = '<div class="icon"></div>';
                                            button.onclick = function() {
                                                darkmysite_switch_trigger();
                                            };
                                            node.appendChild( button );
                                            observer.disconnect();
                                            return;
                                        }
                                    }
                                }
                            } );
                        } );
                        observer.observe( document.body, { childList: true, subtree: true } );
                    } );
                </script>
            <?php } ?>
        <?php } ?>
    <?php } ?>
<?php } ?>