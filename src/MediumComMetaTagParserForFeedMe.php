<?php
/**
 * Medium.com Meta Tag Parser for Feed Me
 *
 * Add meta tags to Medium.com feeds - Feed Me plugin
 *
 * @link      https://www.wbmngr.agency
 * @copyright Copyright (c) 2019 Ottó Radics
 */

namespace webmenedzser\mediumcommetatagparserforfeedme;

use webmenedzser\mediumcommetatagparserforfeedme\helpers\LogHelper as LogHelper;
use webmenedzser\mediumcommetatagparserforfeedme\helpers\MediumFeedCheckerHelper as MediumFeedCheckerHelper;
use webmenedzser\mediumcommetatagparserforfeedme\helpers\UrlHelper as UrlHelper;
use webmenedzser\mediumcommetatagparserforfeedme\helpers\XmlHelper as XmlHelper;
use webmenedzser\mediumcommetatagparserforfeedme\services\MetaTagParser as MetaTagParser;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\feedme\events\FeedDataEvent;
use craft\feedme\services\DataTypes;

use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Ottó Radics
 * @package   MediumComMetaTagParserForFeedMe
 * @since     1.0.0
 */
class MediumComMetaTagParserForFeedMe extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * MediumComMetaTagParserForFeedMe::$plugin
     *
     * @var MediumComMetaTagParserForFeedMe
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /*
     * Collect article URLs into this array.
     *
     * @var array
     */
    public $urls = [];

    /*
     * Collect meta tags into this array.
     *
     * @var array
     */
    public $data = [];

    /*
     * Collect feed items into this array.
     *
     * @var array
     */
    public $items = [];

    /*
     * Count the items in the feed
     *
     * @var int
     */
    public $count = 0;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * MediumComMetaTagParserForFeedMe::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(DataTypes::class, DataTypes::EVENT_AFTER_FETCH_FEED, function(FeedDataEvent $event) {
            if ($event->response['success']) {
                $this->_processFeed($event);
            }
        });

        Event::on(DataTypes::class, DataTypes::EVENT_AFTER_PARSE_FEED, function(FeedDataEvent $event) {
            if ($event->response['success']) {
                $this->_enhanceFeed($event);
            }
        });
    }

    // Private Methods
    // =========================================================================

    private function _processFeed($event) {
        $data = $event->response['data'];
        $mediumFeed = MediumFeedCheckerHelper::isMediumFeed($event->response['data']);

        if ($mediumFeed) {
            $this->items = XmlHelper::findItems($data);
            $this->count = count($this->items);
            $this->urls = XmlHelper::findUrls($data);

            $metaTags = MetaTagParser::collectMetaTagsFromUrls($this->urls);
            foreach ($metaTags as $key => $value) {
                $this->data[] = $value;
            }
        }
    }

    private function _enhanceFeed($event) {
        for ($i = 0; $i < $this->count; $i++) {
            foreach ($this->data[$i] as $key => $value) {
                $event->response['data'][$i][$key] = $value;
            }
        }
    }
}
