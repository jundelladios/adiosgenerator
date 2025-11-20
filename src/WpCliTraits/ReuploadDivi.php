<?php
namespace WebGenerator\WpCliTraits;

if ( ! defined( 'ABSPATH' ) ) { 
    die( 'No direct script access allowed!' ); 
}

trait ReuploadDivi {

    /**
     * Automatically download and install Divi theme
     *
     * @return void
     */
    public function divi() {
        error_log("🔄 ReuploadDivi: Starting Divi re-upload process...");

        // Load credentials from .env
        $env_path = '/home/adiosweb/wpgenerator-deploy/.env';
        if (!file_exists($env_path)) {
            error_log("❌ ReuploadDivi: .env file not found at {$env_path}");
            return;
        }

        $env = parse_ini_file($env_path, false, INI_SCANNER_RAW);

        if (empty($env['DIVI_USER']) || empty($env['DIVI_KEY'])) {
            error_log("❌ ReuploadDivi: DIVI_USER or DIVI_KEY missing in .env");
            return;
        }

        $divi_user = $env['DIVI_USER'];
        $divi_key  = $env['DIVI_KEY'];

        error_log("🔍 ReuploadDivi: Using Elegant Themes credentials: {$divi_user}");

        // Dynamic WordPress root
        $docroot = rtrim(ABSPATH, '/') . '/';
        $divi_zip = $docroot . 'divi-4.27.4.zip';

        $download_url = "https://www.elegantthemes.com/api/api_downloads.php"
            . "?api_update=1&theme=Divi&version=4.27.4"
            . "&username={$divi_user}&api_key={$divi_key}";

        error_log("⬇ ReuploadDivi: Downloading Divi from Elegant Themes...");

        $ch = curl_init($download_url);
        $fp = fopen($divi_zip, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP cURL');
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$result || $http_code !== 200) {
            error_log("❌ ReuploadDivi: Curl download failed, HTTP code: {$http_code}, Error: {$curl_error}");
            if (file_exists($divi_zip)) {
                unlink($divi_zip);
            }
            return;
        }

        error_log("📦 ReuploadDivi: Installing Divi theme via WP-CLI...");

        exec("wp theme install {$divi_zip} --force --path={$docroot} --allow-root", $output, $return_var);

        if ($return_var === 0) {
            unlink($divi_zip);
            error_log("✅ ReuploadDivi: Divi theme re-uploaded successfully.");
        } else {
            error_log("❌ ReuploadDivi: WP-CLI installation failed. Exit code: {$return_var}");
            error_log("Output: " . implode("\n", $output));
        }
    }
}
