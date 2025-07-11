<?php

class DIEX_WebGeneratorButton extends ET_Builder_Module {

	public $slug       = 'diex_webgenerator_button';
	public $vb_support = 'on';

  /**
   * Initialize button link module
   *
   * @return void
   */
	public function init() {
		$this->name = esc_html__( 'Button Link', 'diex-divi-extensions' );
	}

	public function get_fields() {
		return array(
      'button_text' => array(
				'label'           => esc_html__( 'Button Text', 'adiosgenerator' ),
        'type'            => 'text',
			),
      'button_custom_attributes' => array(
				'label'           => esc_html__( 'Custom attributes', 'adiosgenerator' ),
        'type'            => 'textarea',
        'placeholder'     => esc_html__( 'Custom attributes separated by new line. IE: lorem=ipsum', 'adiosgenerator' ),
        'description'     => esc_html__( 'Custom attributes separated by new line. IE: lorem=ipsum', 'adiosgenerator' ),
			),
      'aria_label' => array(
				'label'           => esc_html__( 'Aria-Label', 'adiosgenerator' ),
        'type'            => 'text',
        'description'     => esc_html__( 'Improves accessibility and SEO by labeling the button contextually.', 'adiosgenerator' ),
			),
    );
	}

  /**
   * Render button module in builder and designer
   *
   * @param mixed $attrs
   * @param mixed $content
   * @param mixed $render_slug
   * @return void
   */
	public function render( $attrs, $content, $render_slug ) {
		$button_text = $this->props['button_text'];

    $button_custom_attributes = $this->props['button_custom_attributes'];
    $button_custom_attributes = preg_replace('/<br\s*\/?>/i', '', $button_custom_attributes);
    $button_custom_attributes = explode( "\n", $button_custom_attributes );

    $button_url  = esc_url( $this->props['link_option_url'] );
    $class = esc_attr( $this->props['module_class'] );

    $attributes = array(
      "id" => $this->props['module_id'],
      "aria-label" => $this->props['aria_label'],
      "href" => $button_url
    );

    if( $this->props['link_option_url_new_window'] === "on" ) {
      $attributes["target"] = "_blank";
      $attributes["rel"] = "noopener";
    }

    foreach( $button_custom_attributes as $attrs ) {
      if( str_contains( $attrs, "=" )) {
        $attrdata = explode( "=", $attrs );
        $attributes[$attrdata[0]] = $attrdata[1];
      }
    }
    
    $output = sprintf(
      '<div class="et_pb_button_module_wrapper">
        <a class="et_pb_button %1$s" %3$s>
          %2$s
        </a>
      </div>',
      $class,
      $button_text,
      diex_array_to_html_attributes( $attributes )
    );

    return $output;
	}
}

new DIEX_WebGeneratorButton;
