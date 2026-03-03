<?php
/**
 * YTrip Font Downloader
 * 
 * Downloads Inter and Outfit fonts from Google Fonts CDN
 * and saves them locally for offline use.
 */

if (!defined('ABSPATH')) {
	exit;
}

class YTrip_Font_Downloader {

	const FONTS_DIR = YTRIP_PATH . 'assets/fonts/';
	const FONTS_URL = 'https://fonts.gstatic.com/s/';

	private static $fonts = [
		'inter' => [
			'name' => 'Inter',
			'version' => 'v13',
			'files' => [
				'Regular' => 'UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek9Ew.woff2',
				'Medium' => 'UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuI6fAZ9hiJ-Ek9Ew.woff2',
				'SemiBold' => 'UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuGKYAZ9hiJ-Ek9Ew.woff2',
				'Bold' => 'UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuI-fAZ9hiJ-Ek9Ew.woff2',
			],
		],
		'outfit' => [
			'name' => 'Outfit',
			'version' => 'v5',
			'files' => [
				'SemiBold' => 'jiz8RFfM3oLnn2dsyAWKWgwjdUA3.woff2',
				'Bold' => 'jiz8RFfM3oLnn2dsyAWKbGwjdUA3.woff2',
				'ExtraBold' => 'jiz8RFfM3oLnn2dsyAWKeGwjdUA3.woff2',
			],
		],
	];

	public static function download_fonts() {
		$results = [];
		$wp_filesystem = self::get_filesystem();

		if (!$wp_filesystem) {
			return new WP_Error('filesystem_error', 'Could not initialize filesystem');
		}

		foreach (self::$fonts as $slug => $font_data) {
			$font_dir = self::FONTS_DIR . $slug . '/';

			if (!$wp_filesystem->is_dir($font_dir)) {
				$wp_filesystem->mkdir($font_dir);
			}

			foreach ($font_data['files'] as $weight => $filename) {
				$url = self::FONTS_URL . strtolower($font_data['name']) . '/' . $font_data['version'] . '/' . $filename;
				$local_file = $font_dir . $font_data['name'] . '-' . $weight . '.woff2';

				$response = wp_remote_get($url, [
					'timeout' => 60,
					'sslverify' => false,
				]);

				if (is_wp_error($response)) {
					$results[$slug][$weight] = 'Error: ' . $response->get_error_message();
					continue;
				}

				$body = wp_remote_retrieve_body($response);
				if (empty($body)) {
					$results[$slug][$weight] = 'Error: Empty response';
					continue;
				}

				$saved = $wp_filesystem->put_contents($local_file, $body, FS_CHMOD_FILE);
				$results[$slug][$weight] = $saved ? 'Downloaded (' . size_format(strlen($body)) . ')' : 'Error: Could not save file';
			}
		}

		set_transient('ytrip_fonts_downloaded', true, YEAR_IN_SECONDS);
		return $results;
	}

	public static function fonts_exist() {
		foreach (self::$fonts as $slug => $font_data) {
			$font_dir = self::FONTS_DIR . $slug . '/';
			foreach ($font_data['files'] as $weight => $filename) {
				$file = $font_dir . $font_data['name'] . '-' . $weight . '.woff2';
				if (!file_exists($file)) {
					return false;
				}
			}
		}
		return true;
	}

	private static function get_filesystem() {
		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}
}
