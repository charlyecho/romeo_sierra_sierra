<?php
/**
 * Class Feed
 */
class Feed {
    public $feed_title;
    public $feed_author;
    public $feed_description;
    public $feed_generator;
    public $feed_link;
    public $feed_favicon;
    public $feed_generation_date;
    public $feed_guid;
    public $is_valid = false;
    public $type = null;

    /** @var FeedItem[] $feed_items */
    public $feed_items = array();
}