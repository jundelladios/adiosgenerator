<?php
namespace WebGenerator\WpCliTraits;

if ( ! defined( 'ABSPATH' ) ) { 
    die( 'No direct script access allowed!' ); 
}

use WP_CLI;

trait ReuploadDivi {

    public function divi() {

        error_log("🔄 ReuploadDivi: Starting Divi re-upload process...");

        $env_path = '/home/adiosweb/wpgenerator-deploy/.env';

        if (!file_exists($env_path)) {
            error_log("❌ ReuploadDivi: .env file NOT found at: {$env_path}");
            WP_CLI::error(".env file not found at {$env_path}");
        }

        $env = parse_ini_file($env_path, false, INI_SCANNER_RAW);

        if (!$env) {
            error_log("❌ ReuploadDivi: FAILED to parse .env file.");
            WP_CLI::error("Unable to parse .env file");
        }

        if (empty($env['DIVI_USER']) || empty($env['DIVI_KEY'])) {
            error_log("❌ ReuploadDivi: Missing DIVI_USER or DIVI_KEY in .env");
            WP_CLI::error("DIVI_USER or DIVI_KEY not found in .env");
        }

        $divi_user = $env['DIVI_USER'];
        $divi_key  = $env['DIVI_KEY'];

        error_log("🔍 ReuploadDivi: Using Elegant Themes credentials: {$divi_user}");

        $docroot   = ABSPATH;
        $divi_zip  = $docroot . 'divi-4.27.4.zip';

        $download_url =
            "https://www.elegantthemes.com/api/api_downloads.php?api_update=1&theme=Divi&version=4.27.4" .
            "&username={$divi_user}&api_key={$divi_key}";

        // Start curl download
        error_log("⬇ ReuploadDivi: Downloading Divi from Elegant Themes...");

        $fp = fopen($divi_zip, 'w');
        if (!$fp) {
            error_log("❌ ReuploadDivi: Failed to open file for writing: {$divi_zip}");
            WP_CLI::error("Unable to write ZIP file to {$divi_zip}");
        }

        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_USERPWD, "{$divi_user}:{$divi_key}");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $curl_result = curl_exec($ch);
        $curl_error   = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (!$curl_result) {
            error_log("❌ ReuploadDivi: Curl download FAILED: {$curl_error}");
            WP_CLI::error("Divi download failed: {$curl_error}");
        }

        error_log("✅ ReuploadDivi: Divi ZIP downloaded to {$divi_zip}");

        // Install theme via WP-CLI
        error_log("🧩 ReuploadDivi: Installing Divi via WP-CLI...");

        $cmd = "wp theme install {$divi_zip} --force --path={$docroot} --allow-root";
        exec($cmd, $output, $return_var);

        error_log("📄 WP-CLI Output: " . implode("\n", $output));

        if ($return_var !== 0) {
            error_log("❌ ReuploadDivi: WP-CLI theme install FAILED. Return code: {$return_var}");
            WP_CLI::error("Failed to install Divi theme via WP-CLI");
        }

        unlink($divi_zip);
        error_log("🗑 ReuploadDivi: ZIP file removed.");

        error_log("🎉 ReuploadDivi: Divi Theme successfully re-uploaded!");
        WP_CLI::success("Divi theme re-uploaded successfully.");
    }
}
