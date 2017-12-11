<?php
namespace CERss;

/**
 * Created by PhpStorm.
 * User: charly
 * Date: 07/12/17
 * Time: 22:57
 */
class FeedParser {

    /**
     * load a remote http(s) file and return the parsed feed
     *
     * @param $url
     * @return \CERss\Feed
     */
    public static function parseUrl($url) {
        if(strpos($url, "https") === 0) {
            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_MAXREDIRS      => 10,
            );
            $ch = curl_init( $url );
            curl_setopt_array( $ch, $options );
            $content = curl_exec( $ch );
            curl_close( $ch );

            return self::parse($content);
        }


        return self::parseFile($url);
    }

    /**
     * load a local or remote file and return the parsed feed
     *
     * @param $path
     * @return \CERss\Feed
     */
    public static function parseFile($path) {
        $content = file_get_contents($path);
        return self::parse($content);
    }

    /**
     * parse everything
     *
     * @param $content
     * @return \CERss\Feed
     */
    public static function parse($content, $second_attempt = false) {
        $feed = new Feed();
        libxml_use_internal_errors(true);

        $data = simplexml_load_string($content, null, LIBXML_NOCDATA);
        if ($data === false) {
            if ($second_attempt && extension_loaded("tidy")) {
                $config = array(
                    'indent' => true,
                    'clean' => true,
                    'input-xml'  => true,
                    'output-xml' => true,
                    'wrap'       => false
                );

                $tidy = tidy_parse_string($content, $config, 'utf8');
                $xml = $tidy->cleanRepair();
                return self::parse($xml, true);
            }

            return $feed;
        }

        $feed->is_valid = true;
        $namespaces = $data->getNamespaces(true);

        // CHANNEL FIRST
        if (isset($data->channel) && $_channel = $data->channel) {
            $feed->type = "RSS";
            $feed->feed_title = isset($_channel->title) ? (string) $_channel->title : null;
            $feed->feed_description = isset($_channel->description) ? (string) $_channel->description : null;
            $feed->feed_generator = isset($_channel->generator) ? (string) $_channel->generator : null;
            $feed->feed_link = isset($_channel->link) ? (string) $_channel->link : null;
            $feed->feed_generation_date = isset($_channel->lastBuildDate) ? (string) $_channel->lastBuildDate : date('r', time());;
            if (isset($_channel->image)) {
                $feed->feed_favicon = (string) $_channel->image->url; // favicon
            }
        }
        else {
            $feed->type = "ATOM";
            $feed->feed_title = isset($data->title) ? (string) $data->title : null;
            $feed->feed_generation_date = isset($data->updated) ? (string) $data->updated : null;
            $feed->feed_link = isset($data->link) ? (string) $data->link->attributes()["href"] : null;
            $feed->feed_author = isset($data->author) ? (string) $data->author->name : null;
            $feed->feed_guid = isset($data->id) ? (string) $data->id : null;
        }


        // ITEMS NOW
        if (isset($data->channel->item)) {
            foreach($data->channel->item as $key => $_item) {
                $feed_item = self::parsRssFeedItem($_item, $namespaces);
                $feed->feed_items[] = $feed_item;
            }
        }
        elseif ($data->item) {
            foreach($data->item as $_item) {
                $feed_item = self::parsRssFeedItem($_item, $namespaces);
                $feed->feed_items[] = $feed_item;
            }
        }
        elseif (isset($data->entry)) {
            foreach($data->entry as $_item) {
                $feed_item = self::parsAtomFeedItem($_item, $namespaces);
                $feed->feed_items[] = $feed_item;
            }
        }


        // cleanup items
        foreach($feed->feed_items as $_item) {
            if (!$_item->guid) {
                $_item->guid = $_item->title."-".$_item->date_modification;
            }

            if (!$_item->date_publication && $_item->date_modification) {
                $_item->date_publication = $_item->date_modification;
            }

            $_item->tags = array_filter($_item->tags);
        }

        return $feed;
    }

    /**
     * parse an RSS v1 or v2 item
     *
     * @param \SimpleXMLElement $data
     * @param array $namespaces
     * @return \CERss\FeedItem
     */
    private static function parsRssFeedItem(\SimpleXMLElement $data, array $namespaces = array()) {
        $feed_item = new FeedItem();
        $feed_item->guid = isset($data->guid) ? trim((string) $data->guid) : null;
        $feed_item->title = isset($data->title) ? trim((string) $data->title) : null;
        $feed_item->link = isset($data->link) ? (string) $data->link : null;
        $feed_item->date_modification = isset($data->pubDate) ? trim((string) $data->pubDate) : null;

        if (isset($data->category)) {
            foreach($data->category as $cat) {
                $feed_item->tags[] = (string) $cat;
            }
        }

        // text
        if (isset($namespaces["content"])) {
            $media = $data->children($namespaces["content"]);
            $feed_item->text = (string) $media;
        }

        if (isset($data->description) && !$feed_item->text) {
            $feed_item->text = trim((string) $data->description);
        }

        if (isset($namespaces["media"])) {
            $media = $data->children($namespaces["media"]);
            foreach ($media as $m) {
                $feed_item->enclosures[] = (string) $m->attributes()["url"];
            }
        }
        if (isset($data->enclosure)) {
            $feed_item->enclosures[] = (string)$data->enclosure->attributes()["url"];
        }

        return $feed_item;
    }

    /**
     * parse an ATOM item
     *
     * @param \SimpleXMLElement $data
     * @param array $namespaces
     * @return \CERss\FeedItem
     */
    private static function parsAtomFeedItem(\SimpleXMLElement $data, array $namespaces = array()) {
        $feed_item = new FeedItem();
        $feed_item->guid = isset($data->id) ? (string) $data->id : null;
        $feed_item->date_modification = isset($data->updated) ? (string) $data->updated : null;
        $feed_item->date_publication = isset($data->published) ? (string) $data->published : null;
        $feed_item->title = isset($data->title) ? (string) $data->title : null;

        // text
        $feed_item->text = isset($data->content) ? (string) $data->content : null;
        if (!$feed_item->text) {
            $feed_item->text = isset($data->summary) ? (string) $data->summary : null;
        }
        // multiple links ?
        $feed_item->link = isset($data->link) ? (string) $data->link : null;
        if (!$feed_item->link && isset($data->link)) {
            foreach($data->link as $l) {
                $type = $l->attributes()["rel"];
                if ($type == "alternate") {
                    $feed_item->link = (string) $l->attributes()["href"];
                }
            }
        }
        // link as attribute
        if (!$feed_item->link) {
            $feed_item->link = urldecode((string) $data->link->attributes()["href"]);
        }

        if (isset($data->category)) {
            foreach($data->category as $cat) {
                $feed_item->tags[] = (string) $cat->attributes()["term"];
            }
        }

        // media group (youtube)
        if (isset($namespaces["media"])) {
            if (isset($data->children($namespaces["media"])->group)) {
                $media = $data->children($namespaces["media"])->group;
                if (!$feed_item->text && isset($media->description)) {
                    $feed_item->text = (string)$media->description;
                }
                if (isset($media->thumbnail)) {
                    $feed_item->enclosures[] = (string)$media->thumbnail->attributes()["url"];
                }

                if (isset($media->content)) {
                    if ($content = (string)$media->content->attributes()["url"]) {
                        $explode = explode("/", $content);
                        $video_uid = end($explode);
                        $explode_question = explode("?", $video_uid);
                        $video_uid = reset($explode_question);
                        $feed_item->text = "<iframe src=\"//www.youtube.com/embed/$video_uid?autoplay=0&amp;html5=1&amp;loop=0&amp;playlist=$video_uid\" frameBorder=\"0\" allowfullscreen></iframe><br/>" . $feed_item->text;
                    }
                }
            }
        }

        $feed_item->author = isset($data->author) ? (string) $data->author->name : null;

        return $feed_item;
    }
}