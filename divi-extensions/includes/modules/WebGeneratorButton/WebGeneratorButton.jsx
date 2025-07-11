// External Dependencies
import React, { Component, Fragment } from 'react';

class WebGeneratorButton extends Component {

  static slug = 'diex_webgenerator_button';

  render() {
    const {
      button_text,
      link_option_url
    } = this.props;

    return (
      <Fragment>
        <div class="et_pb_button_module_wrapper">
          <a href={link_option_url || "#"} class="et_pb_button">{button_text}</a>
        </div>
      </Fragment>
    );
  }
}

export default WebGeneratorButton;
