<?php
namespace WebGenerator\WpCliTraits;

if ( ! defined( 'ABSPATH' ) ) { 
    die( 'No direct script access allowed!' ); 
}

use WP_CLI;

trait ReuploadDivi {
    /**
     * WP CLI command to re-upload and install Divi theme
     *
     * @return void
     */
    public function divi() {
        $env_path = '/home/adiosweb/wpgenerator-deploy/.env'; // Absolute path

        if (!file_exists($env_path)) {
            WP_CLI::error(".env file not found at {$env_path}");
            return;
        }

        $env = parse_ini_file($env_path, false, INI_SCANNER_RAW);

        if (empty($env['DIVI_USER']) || empty($env['DIVI_KEY'])) {
            WP_CLI::error("DIVI_USER or DIVI_KEY not found in .env");
            return;
        }

        $divi_user = $env['DIVI_USER'];
        $divi_key  = $env['DIVI_KEY'];

        $docroot   = ABSPATH;
        $divi_zip  = $docroot . 'divi-4.27.4.zip';
        $download_url = "https://www.elegantthemes.com/api/api_downloads.php?api_update=1&theme=Divi&version=4.27.4&username={$divi_user}&api_key={$divi_key}";

        // Download Divi ZIP
        $ch = curl_init($download_url);
        $fp = fopen($divi_zip, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_USERPWD, "{$divi_user}:{$divi_key}");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        // Install Divi using WP-CLI
        exec("wp theme install {$divi_zip} --force --path={$docroot} --allow-root", $output, $return_var);
        if ($return_var === 0) {
            unlink($divi_zip);
            WP_CLI::success("✅ Divi theme re-uploaded successfully.");
        } else {
            WP_CLI::error("❌ Failed to install Divi theme via WP-CLI.");
        }
    }
}
