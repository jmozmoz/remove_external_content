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
//		$this->check_new_session();

		#$host->add_hook($host::HOOK_FEED_PARSED, $this);
		#$host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
		$host->add_hook($host::HOOK_RENDER_ARTICLE_CDM, $this);
		$host->add_hook($host::HOOK_TOOLBAR_BUTTON, $this);
        $host->add_hook($host::HOOK_FORMAT_ENCLOSURES, $this);
	}

    function hook_format_enclosures($rv, $result, $id, $always_display_enclosures, $article_content, $hide_images) {
        if (!$this->host->get($this, 'active'))
            return $rv;
        user_error("result: " . json_encode($result), E_USER_WARNING);
        $entries = array();
        if (!empty($result)) {
            foreach ($result as $line) {
                $url = $line["content_url"];
                $ctype = $line["content_type"];
                $title = $line["title"];

#                user_error("url: " . $url, E_USER_WARNING);
#                user_error("ctype: " . $ctype, E_USER_WARNING);
#                user_error("title: " . $title, E_USER_WARNING);

                if (!$ctype) $ctype = __("unknown type");

                $filename = substr($url, strrpos($url, "/")+1);
                if (preg_match("/image/", $ctype) ||
                    preg_match("/\.(jpg|png|gif|bmp)/i", $filename)) {
#                    user_error("remove URL!", E_USER_WARNING);
                    $line["content_url"] = "";
                    $line["title"] = $line["title"] . " " . $url;
                } 
                array_push($entries, $line);
            }
        }
        return array($rv, $entries);
    }

	function get_icon_class() {
		return $this->host->get($this, 'active') ? 'icon-ban-circle' : 'icon-cloud-download';
	}

	function get_icon_text() {
		return $this->host->get($this, 'active') ? 'External content blocked' : 'External content unblocked';
	}

	function check_new_session() {
#		user_error("current session: " . $_SESSION["csrf_token"], E_USER_WARNING);
#    	user_error("stored session: " . $this->host->get($this, 'sessionid'), E_USER_WARNING);
#        user_error("active: " . $this->host->get($this, 'active'), E_USER_WARNING);

		$new_session = !($this->host->get($this, 'sessionid') == $_SESSION["csrf_token"]);
#		user_error("new session: " . $new_session, E_USER_WARNING);
		if ($new_session) {
#			user_error("started new session!: ", E_USER_WARNING);
			$this->host->set($this, 'active', true);
			$this->host->set($this, 'sessionid', $_SESSION["csrf_token"]);
		}
	} 

    function test_func() {
    	$id = trim(db_escape_string($_REQUEST['id']));
		$json_conf = $this->host->get($this, 'active') ? 'true' : 'false';
                user_error("test4 |" . $json_conf . "|", E_USER_WARNING);
//		user_error("session: " . $_SESSION["csrf_token"], E_USER_WARNING);
                $this->host->set($this, 'active', ! $this->host->get($this, 'active'));
		$icon_class = $this->get_icon_class();
		$icon_text  = $this->get_icon_text();
		print json_encode(array("class_name" => $icon_class, "title" => $icon_text));
		#echo $icon_class;
    }


	function HOOK_TOOLBAR_BUTTON() {
		$this->check_new_session();
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
