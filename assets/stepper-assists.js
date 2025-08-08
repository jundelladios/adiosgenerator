(function() {

  window.DivaSetterAssist = {
    initWindow: function() {

      // STEP 2
      if( 
        (!adiosgenerator_stepper.step || adiosgenerator_stepper.step <= 2) && 
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        adiosgenerator_stepper.is_adminbar &&
        document.querySelector(`li#wp-admin-bar-view-site`)
      ) {
        const adminsitename = document.querySelector('li#wp-admin-bar-site-name');
        if( adminsitename) {
          adminsitename.classList.add('hover');
        }

        window?.divaLaunchStepper?.initStepper({
          selector: document.querySelector(`li#wp-admin-bar-view-site`), 
          label: "Step 2:", 
          title: "Launch the Homepage", 
          content: [
            "1.) From the wordpress Admin Dashboard, hover over your site title in the top-left corner of the screen.", 
            "2.) Click \"Visit Site\" to open and view your homepage."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window.DivaSetterAssist.stepperSet(3);
            }
          }
        });
        
        return true;
      }



      // STEP 3
      // edit homepage step
      if( 
        adiosgenerator_stepper.step == 3 && 
        !adiosgenerator_stepper.path.includes( 'wp-admin' ) &&
        adiosgenerator_stepper.is_adminbar &&
        document.querySelector(`li#wp-admin-bar-et-use-visual-builder`)
      ) {

        window?.divaLaunchStepper?.initStepper({
          selector: document.querySelector(`li#wp-admin-bar-et-use-visual-builder`), 
          label: "Step 3:", 
          title: "Edit the Homepage", 
          content: [
            "1.) On the homepage, click \"Enable Visual Builder\" to begin edit your content",
            `2.) To guide you from editing and building your website, go to <a href=\"${adiosgenerator_stepper.tutorial}\" target=\"_blank\">tutorials</a>.`
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window.DivaSetterAssist.stepperSet(4);
            }
          }
        });

        return true;
      }

      // STEP 4
      if( 
        adiosgenerator_stepper.step == 4
      ) {
        // const targetEl = document.body.querySelector('.et-fb-page-settings-bar__toggle-button');
        window?.divaLaunchStepper?.observeElementAndClass({
          selector: '.et-fb-page-settings-bar__toggle-button',
          onAppear: function( targetEl ) {
            window?.divaLaunchStepper?.initStepper({
              selector: targetEl, 
              label: "Step 4:", 
              title: "Edit your site content and save", 
              content: [
                `1.) To guide you from editing and building your website, go to <a href=\"${adiosgenerator_stepper.tutorial}\" target=\"_blank\">tutorials</a>.`,
                `2.) After editing click this button for additional options.`
              ],
              placement: "bottom-left",
              svgStyle: `transform: rotate(180deg);`,
              addtop: -140
            });
          },
          onClassChange: function( targetEl ) {
            if( targetEl.className.includes( "et-fb-button--active" ) ) {
              window?.divaLaunchStepper?.removeStepper();
              window?.divaLaunchStepper?.initStepper({
                selector: document.querySelector(`.et-fb-button--publish`), 
                label: "Step 4:", 
                title: "Finalize and save your site.", 
                content: [
                  `1.) After editing click this save button`,
                  `2.) You can continue edit after save`
                ],
                placement: "bottom-right",
                svgStyle: `transform: rotate(170deg) scaleX(-1);`,
                addtop: -140,
                addLeft: 30,
                buttonExec: {
                  text: "Ok",
                  callback: async () => {
                    await window?.DivaSetterAssist.stepperSet(5);
                  }
                }
              });
            } else {
              window?.divaLaunchStepper?.removeStepper();
            }
          }
        });
        return true;
      }


      // STEP 5
      if( 
        adiosgenerator_stepper.step == 5 &&
        window?.document.querySelector(`li#wp-admin-bar-et-disable-visual-builder`)
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`li#wp-admin-bar-et-disable-visual-builder`), 
          label: "Step 5:", 
          title: "Exit Visual Builder", 
          content: [
            "1.) After editing your website, exit the visual builder."
          ],
          placement: "top-left",
          addtop: 7,
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(6);
            }
          }
        });

        return true;
      }



      // STEP 6
      if( 
        adiosgenerator_stepper.step == 6 &&
        window?.document.querySelector(`li#wp-admin-bar-wp-logo`)
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`li#wp-admin-bar-wp-logo`), 
          label: "Step 6:", 
          title: "Wordpress Admin Dashboard", 
          content: [
            "1.) After editing your website, you can now go back to wp admin."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(7);
            }
          }
        });

        return true;
      }


      // STEP 7
      if( 
        adiosgenerator_stepper.step == 7 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        document.querySelector('.toplevel_page_diva-launch-options')
      ) {
        document.querySelector('.toplevel_page_diva-launch-options').classList.add( 'opensub' );
        window?.divaLaunchStepper?.initStepper({
          selector: document.querySelector(`.toplevel_page_diva-launch-options ul li a[href*="optimization.php"]`), 
          label: "Step 7:", 
          title: "Diva Launch Optimization", 
          content: [
            "1.) If you want to customize your optimization setting, visit this link."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(8);
            }
          }
        });

        return true;
      }




      // STEP 8
      if( 
        adiosgenerator_stepper.step == 8 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('.toplevel_page_diva-launch-options')
      ) {
        window?.document.querySelector('.toplevel_page_diva-launch-options').classList.add( 'opensub' );
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`.toplevel_page_diva-launch-options ul li a[href*="${adiosgenerator_admin.tutorial}"]`), 
          label: "Step 8:", 
          title: "Diva Launch Tutorial", 
          content: [
            "1.) For tutorials and helpful videos and documentations."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(9);
            }
          }
        });

        return true;
      }



        // STEP 9
      if( 
        adiosgenerator_stepper.step == 9 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('.toplevel_page_diva-launch-options')
      ) {
        window?.document.querySelector('.toplevel_page_diva-launch-options').classList.add( 'opensub' );
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`.toplevel_page_diva-launch-options ul li a[href*="${adiosgenerator_admin.support_link}"]`), 
          label: "Step 9:", 
          title: "Diva Launch Support", 
          content: [
            "1.) If you need help or encounter any issue, you can send us your support request.",
            "2.) Or you can use the Live chat at the bottom right"
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(10);
              window?.document.querySelector('.toplevel_page_diva-launch-options').classList.remove( 'opensub' );
            }
          }
        });

        return true;
      }



      // STEP 10
      if( 
        adiosgenerator_stepper.step == 10 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('#wp-admin-bar-diva_generator_menu')
      ) {
        window?.document.querySelector('#wp-admin-bar-diva_generator_menu').classList.add( 'hover' );
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`#wp-admin-bar-diva_generator_menu_clear_cache`), 
          label: "Step 10:", 
          title: "Clear Cache", 
          content: [
            "1.) If your update is still propagating, clear your cache."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(11);
            }
          }
        });

        return true;
      }



      // STEP 11
      if( 
        adiosgenerator_stepper.step == 11 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('#wp-admin-bar-diva_generator_menu')
      ) {
        window?.document.querySelector('#wp-admin-bar-diva_generator_menu').classList.add( 'hover' );
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`#wp-admin-bar-diva_generator_menu_app_force_update`), 
          label: "Step 11:", 
          title: "Application Update", 
          content: [
            "1.) To ensure your website is up to date",
            "2.) We will automatically update your website if there's an update available."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(12);
              window?.document.querySelector('#wp-admin-bar-diva_generator_menu').classList.remove( 'hover' );
            }
          }
        });

        return true;
      }



      // STEP 12
      if( 
        adiosgenerator_stepper.step == 12 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('#menu-pages')
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`#menu-pages`), 
          label: "Step 12:", 
          title: "Manage your pages", 
          content: [
            "1.) Manage your pages including SEO content, page contents etc."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(13);
            }
          }
        });

        return true;
      }



      // STEP 13
      if( 
        adiosgenerator_stepper.step == 13 &&
        adiosgenerator_stepper.path.includes( 'edit.php?post_type=page' ) && 
        window?.document.querySelector('a.page-title-action')
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`a.page-title-action`), 
          label: "Step 13:", 
          title: "Add your page", 
          content: [
            "1.) Add your page here."
          ],
          placement: "top-left",
          wrap_position: "absolute",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(14);
            }
          }
        });

        return true;
      }


      if( adiosgenerator_stepper.path.includes( 'post-new.php?post_type=page' ) ) {
        window?.divaLaunchStepper?.observeElementAndClass({
          selector: '.interface-interface-skeleton__content',
          onAppear: function(wrap) {
            
            // divi builder
            if( 
              adiosgenerator_stepper.step == 14 &&
              adiosgenerator_stepper.path.includes( 'post-new.php?post_type=page' ) && 
              window?.document.querySelector('#et-switch-to-divi')
            ) {
              
              window?.divaLaunchStepper?.initStepper({
                selector: document.querySelector('#et-switch-to-divi'), 
                label: "Step 14:", 
                title: "Start Building your Page", 
                content: [
                  "1.) Click here to start building your page via Visual Builder",
                  "2.) Scroll down below for the next step after click OK"
                ],
                placement: "top-left",
                wrap_position: "absolute",
                addtop: -50,
                appendToElement: wrap,
                buttonExec: {
                  text: "Ok",
                  callback: async () => {
                    await window?.DivaSetterAssist.stepperSet(15);
                    window?.divaLaunchStepper?.scrollToElement( ".wds-edit-meta button", ".interface-interface-skeleton__content" );
                  }
                }
              });
            }


            // edit meta
            if( adiosgenerator_stepper.step == 15 ) {
              window?.divaLaunchStepper?.initStepper({
                selector: document.querySelector( '.wds-edit-meta button' ), 
                label: "Step 15:", 
                title: "Edit page SEO Content", 
                content: [
                  "1.) Click here to start editing this page SEO content."
                ],
                placement: "top-left",
                wrap_position: "absolute",
                appendToElement: wrap,
                addtop: -50,
                elemScrollRef: wrap,
                buttonExec: {
                  text: "Ok",
                  callback: async () => {
                    await window?.DivaSetterAssist.stepperSet(16);
                    window?.divaLaunchStepper?.scrollToElement( ".wds-focus-keyword", ".interface-interface-skeleton__content" );
                  }
                }
              });
            }


            // focus keyword
            if( adiosgenerator_stepper.step == 16 ) {
              window?.divaLaunchStepper?.initStepper({
                selector: document.querySelector('.wds-focus-keyword input'), 
                label: "Step 16:", 
                title: "Focus Keyword", 
                content: [
                  "1.) Here you can enter at least 3 focus keyword"
                ],
                placement: "top-left",
                wrap_position: "absolute",
                appendToElement: wrap,
                addtop: -50,
                elemScrollRef: wrap,
                buttonExec: {
                  text: "Ok",
                  callback: async () => {
                    await window?.DivaSetterAssist.stepperSet(17);
                  }
                }
              });
            }

          }
        })

      }


      // STEP 17
      if( 
        adiosgenerator_stepper.step == 17 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('.menu-icon-post .wp-menu-name')
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`.menu-icon-post .wp-menu-name`), 
          label: "Step 17:", 
          title: "Manage your blog posts", 
          content: [
            "1.) Manage your posts the same process of managing your pages."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(18);
            }
          }
        });

        return true;
      }


      // STEP 18
      if( 
        adiosgenerator_stepper.step == 18 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('.menu-icon-project .wp-menu-name')
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`.menu-icon-project .wp-menu-name`), 
          label: "Step 18:", 
          title: "Manage your projects", 
          content: [
            "1.) Manage your projects the same process of managing your pages."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(19);
            }
          }
        });

        return true;
      }


      // STEP 18
      if( 
        adiosgenerator_stepper.step == 19 &&
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        window?.document.querySelector('.menu-icon-diva_services .wp-menu-name')
      ) {
        window?.divaLaunchStepper?.initStepper({
          selector: window?.document.querySelector(`.menu-icon-diva_services .wp-menu-name`), 
          label: "Step 19:", 
          title: "Manage your services", 
          content: [
            "1.) Manage your services the same process of managing your pages."
          ],
          placement: "top-left",
          buttonExec: {
            text: "Ok",
            callback: async () => {
              await window?.DivaSetterAssist.stepperSet(20);
            }
          }
        });

        return true;
      }

      
    },
    stepperSet: async function( step ) {
      try {
        await fetch(adiosgenerator_stepper.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams({
            action: 'wp_adiosgenerator_stepper_ajax',
            security: adiosgenerator_stepper.nonce,
            step: step
          })
        });

        adiosgenerator_stepper.step = step;
        window.DivaSetterAssist.initWindow();
        return true;
      } catch {
        return false;
      }
    }
  }

  // window by default
  window.addEventListener('load', window.DivaSetterAssist.initWindow);
  
})();