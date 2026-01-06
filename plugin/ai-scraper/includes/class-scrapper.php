<?php
/**
 * Class Tdx_Scrapper
 * Todo: Scrape html content from a website
 */
Class Tdx_Scrapper {
	/**
	 * Scrape html content from a website
	 * @param string $url
	 * @return string
	 */
	public static function scrape_html_content($url) {
		$request_options = [
			'method' => 'GET',
			'timeout' => 90,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
			'sslverify' => false,
		];
		$response = wp_remote_get($url, $request_options);
		if (is_wp_error($response)) {
			return false;
		}
		return wp_remote_retrieve_body($response);
	}
	// Remove unnecessary tags
	public static function remove_unnecessary_html_data($html) {
		// Keep only `meta name="description"` and `meta name="keywords"` tags
		preg_match_all('/<meta[^>]+(name="description"|name="keywords")[^>]*>/i', $html, $metaMatches);
		$metaContent = implode("\n", $metaMatches[0]);
	
		// Extract the entire <body> tag and its content
		preg_match('/<body[^>]*>.*<\/body>/is', $html, $bodyMatches);
		$bodyContent = isset($bodyMatches[0]) ? $bodyMatches[0] : '';
		// Remove unwanted attributes from all tags inside <body>
		$bodyContent = preg_replace_callback('/<[^>]+>/', function ($matches) {
			$tag = $matches[0];
			// Remove attributes: class, id, style, data-srcset, decoding
			$tag = preg_replace('/(?:class|id|style|data-srcset|decoding)="[^"]*"/i', '', $tag);
			$tag = preg_replace("/(?:class|id|style|data-srcset|decoding)='[^']*'/i", '', $tag);
			return $tag;
		}, $bodyContent);
		// Remove empty <div> tags
		$bodyContent = preg_replace('/<div\s*[^>]*>\s*<\/div>/i', '', $bodyContent);
		
		$tags_to_remove = [
			'script', 'style', 'noscript', 'iframe', 'svg',
			'canvas', 'object', 'embed', 'applet', 'frame', 'frameset', 
			'noframes', 'noembed', 'blink', 'marquee'
		];
		// Combine all <meta> tags and the full <body> content
		$html = $metaContent . "\n" . $bodyContent;
		$tags_to_remove = implode('|', $tags_to_remove);
		$html = preg_replace('/<(' . $tags_to_remove . ')[^>]*>.*?<\/\1>/is', '', $html);
		$html = preg_replace('/<!--.*?-->/', '', $html);
		// Convert mazy loading img tags to normal img tags using `data-src` attribute
		$html = preg_replace('/<img[^>]+data-src="([^"]+)"[^>]*>/i', '<img src="$1">', $html);
		// Remove all \t \n \r characters
		$html = preg_replace('/[\t\n\r]/', '', $html);
		/*
		$html = preg_replace('/<[^>]+(?:class|id|style|data-srcset|decoding)=["\'][^"\']*["\'][^>]*>/i', '', $html);
		$html = preg_replace('/<img[^>]+src=".*?base64.*?".*?>/i', '', $html);
		$html = preg_replace('/<img[^>]+src=\'.*?base64.*?\'.*?>/i', '', $html);
		// Remove `data-*` attributes
		$html = preg_replace('/<[^>]+data-[^>]+>/i', '', $html);
		*/
		return $html;
	}
	/**
	 * Send POST request to TDX_REWRITE_API to rewrite content
	 * Request body: json_encode(['url' => $url])
	 * Request headers: 'Content-Type' => 'application/json', 'x-key' => get_option('wp_scraper_gpt_api_key', '')
	 * @param string $url
	 * @return array
	 */
	public static function scrape_and_rewrite_single_url($url, $is_rewrite_competitor_info = false, $competitor_info = '') {
		try {
			// $html_content = self::scrape_html_content($url);
			$html_content = '';
			$rewrite_response = Scraper_API_Handler::rewrite_content($html_content, $is_rewrite_competitor_info, $competitor_info, $url);
			try {
				if ($rewrite_response && array_key_exists('error', $rewrite_response)) {
					return ['error' => $rewrite_response['error']];
				}
			}
			catch (Exception $e) {
				return ['error' => $e->getMessage()];
			}
			if (gettype($rewrite_response) !== 'array' || !$rewrite_response['data'] || !array_key_exists('content', $rewrite_response['data'])) {
				throw new Exception('Lỗi: ' . gettype($rewrite_response) . '----' .json_encode($rewrite_response));
			}
			return $rewrite_response['data'];
		}
		catch (Exception $e) {
			return ['error' => $e->getMessage()];
		}
		return null;
	}
}