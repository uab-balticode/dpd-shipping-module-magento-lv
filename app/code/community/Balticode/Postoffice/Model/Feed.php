<?php
Class Balticode_Postoffice_Model_Feed extends Mage_AdminNotification_Model_Feed {

    const XML_FEED_HOST = 'balticode.com/notifications';
    const XML_FEED_FILE = 'notifications.rss';

    private $locale;

    protected $_feedUrl;


    public function __construct(){
        $this->locale = Mage::app()->getLocale()->getLocaleCode();
    }

    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                .self::XML_FEED_HOST.'/'.$this->locale.'/'.self::XML_FEED_FILE;
        }
        $file_headers = @get_headers($this->_feedUrl);
        if($file_headers[0] == 'HTTP/1.1 404 Not Found') $this->_feedUrl = null;
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                .self::XML_FEED_HOST.'/'.self::XML_FEED_FILE;
        }
        return $this->_feedUrl;
    }

    public function checkUpdate()
    {
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $feedData[] = array(
                    'severity'      => (int)$item->severity,
                    'date_added'    => $this->getDate((string)$item->pubDate),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }

            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

        }
        $this->setLastUpdate();

        return $this;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('balticode_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return Mage_AdminNotification_Model_Feed
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'balticode_notifications_lastcheck');
        return $this;
    }
}


?>
