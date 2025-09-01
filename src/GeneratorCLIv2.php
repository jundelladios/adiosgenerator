<?php

namespace WebGenerator;
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed!' );
}

# traits
use WebGenerator\WpCliTraits\ClearCache;

// WP Cli commands for adiosgenerator
class GeneratorCLIv2 extends \WP_CLI_Command {
  // cli's
  use ClearCache;
}