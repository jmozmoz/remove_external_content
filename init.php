<?php
class remove_external_content extends Plugin {

	private $host;

	function about() {
		return array(1.0,
			"Remove  remove external content (e.g. pictures, videos) in news articles",
			"jmozmoz");
	}

	function init($host) {
		$this->host = $host;

		#$host->add_hook($host::HOOK_FEED_PARSED, $this);
		#$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
		$host->add_hook($host::HOOK_RENDER_ARTICLE_CDM, $this);
		$host->add_hook($host::HOOK_TOOLBAR_BUTTON, $this);
	}

        function test_func() {
		$id = trim(db_escape_string($_REQUEST['id']));
		$json_conf = $this->host->get($this, 'active') ? 'true' : 'false';
                user_error("test4 |" . $json_conf . "|", E_USER_WARNING);
                $this->host->set($this, 'active', ! $this->host->get($this, 'active'));
		$icon_class = $this->host->get($this, 'active') ? 'icon-ban-circle' : 'icon-cloud-download';
		$icon_text  = $this->host->get($this, 'active') ? 'External content blocked' : 'External content unblocked';
		print json_encode(array("class_name" => $icon_class, "title" => $icon_text));
		#echo $icon_class;
        }


	function HOOK_TOOLBAR_BUTTON() {
		require_once dirname(__FILE__) . "/toolbar.php";
	}


	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/toolbar.js");
	}


	function hook_article_left_button($article) {
		return $this->hook_article_filter($article);
	}

	function hook_render_article_cdm($article) {
		return $this->hook_article_filter($article);
	}

	function hook_article_filter($article) {
		if (!$this->host->get($this, 'active'))
			return $article;
		$owner_uid = $article["owner_uid"];
		$rss_link = get_self_url_prefix();

		if (strpos($article["plugin_data"], "remove_external_content,$owner_uid:") === FALSE) {

			$doc = new DOMDocument();

			$charset_hack = '<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			</head>';

			@$doc->loadHTML($charset_hack . $article["content"]);

			if ($doc) {
				$xpath = new DOMXPath($doc);
				$entries = $xpath->query('//img');

				foreach ($entries as $entry) {
					$src = $entry->getAttribute("src");
					if ((strpos($src, $rss_link) === FALSE) &&
 					    (preg_match("/^http/", $src))){
						$replacement = $doc->createDocumentFragment();
						$replacement->appendXML($src);
						$entry->parentNode->replaceChild($replacement, $entry);
						#$entry->parentNode->removeChild($entry);
					}
				}

				$article["content"] = $doc->saveXML($basenode);
				$article["plugin_data"] = "remove_external_content,$owner_uid:" . $article["plugin_data"];

			}
		} else if (isset($article["stored"]["content"])) {
			$article["content"] = $article["stored"]["content"];
		}

		return $article;
	}

	function api_version() {
		return 2;
	}

}
?>