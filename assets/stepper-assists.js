(function() {

  var DivaSetterAssist = {
    initWindow: function() {

      // STEP 2
      if( 
        (!adiosgenerator_stepper.step || adiosgenerator_stepper.step <= 2) && 
        adiosgenerator_stepper.path.includes( 'wp-admin' ) && 
        adiosgenerator_stepper.is_adminbar
      ) {
        const adminsitename = document.querySelector('li#wp-admin-bar-site-name');
        if( adminsitename) {
          adminsitename.classList.add('hover');
        }

        if( document.querySelector(`li#wp-admin-bar-view-site`)) {
          window?.divaLaunchStepper?.initStepper({
            selector: `li#wp-admin-bar-view-site`, 
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
                await DivaSetterAssist.stepperSet(3);
                if( adminsitename) {
                  adminsitename.classList.remove('hover');
                }
              }
            }
          });
        }
        
        return true;
      }



      // STEP 3
      // edit homepage step
      if( 
        adiosgenerator_stepper.step == 3 && 
        !adiosgenerator_stepper.path.includes( 'wp-admin' ) &&
        adiosgenerator_stepper.is_adminbar
      ) {

        if( document.querySelector(`li#wp-admin-bar-et-use-visual-builder`)) {
          window?.divaLaunchStepper?.initStepper({
            selector: `li#wp-admin-bar-et-use-visual-builder`, 
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
                await DivaSetterAssist.stepperSet(4);
              }
            }
          });

          return true;
        }
      }


      // STEP 4
      let targetEl = null;
      let targetObserver = null;
      // let disableStepper = true;

      // Step 1: Watch body for the element to appear
      const bodyObserver = new MutationObserver(mutations => {
        if (!targetEl) {
          targetEl = document.body.querySelector('.et-fb-page-settings-bar__toggle-button');
          if (
            targetEl &&
            adiosgenerator_stepper.step == 4 
          ) {

            window?.divaLaunchStepper?.initStepper({
              selector: `.et-fb-page-settings-bar__toggle-button`, 
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


            // Stop body observer once found
            bodyObserver.disconnect();

            // Step 2: Watch for class changes on the target
            targetObserver = new MutationObserver(muts => {
              muts.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                  if( adiosgenerator_stepper.step == 4  && targetEl.className.includes( "et-fb-button--active" ) ) {
                    window?.divaLaunchStepper?.removeStepper();
                    window?.divaLaunchStepper?.initStepper({
                      selector: `.et-fb-button--publish`, 
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
                          await DivaSetterAssist.stepperSet(5);
                        }
                      }
                    });
                  } else {
                    window?.divaLaunchStepper?.removeStepper();
                  }
                }
              });
            });

            targetObserver.observe(targetEl, {
              attributes: true,
              attributeFilter: ['class']
            });

            return true;
          }
        }
      });

      bodyObserver.observe(document.body, {
        childList: true,
        subtree: true
      });


      // STEP 5
      if( 
        adiosgenerator_stepper.step == 5 && 
        adiosgenerator_stepper.is_adminbar
      ) {
        if( document.querySelector(`li#wp-admin-bar-wp-logo`)) {
          window?.divaLaunchStepper?.initStepper({
            selector: `li#wp-admin-bar-wp-logo`, 
            label: "Step 5:", 
            title: "Go Back to WP Admin", 
            content: [
              "1.) To manage other settings after editing your site, go back to WP Admin"
            ],
            placement: "top-left",
            buttonExec: {
              text: "Ok",
              callback: async () => {
                await DivaSetterAssist.stepperSet(6);
              }
            }
          });

          return true;
        }
      }



      window?.divaLaunchStepper?.removeStepper();
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
        window?.divaLaunchStepper?.removeStepper();
        DivaSetterAssist.initWindow();
        return true;
      } catch {
        return false;
      }
    }
  }

  // dom if manipulation is in the visual builder
  // document.addEventListener('DOMContentLoaded', DivaSetterAssist.initWindow);

  // window by default
  window.addEventListener('load', DivaSetterAssist.initWindow);
  
})();