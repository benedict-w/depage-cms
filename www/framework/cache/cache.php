<?php

namespace depage\cache; 

class cache {
    // {{{ variables
    protected $prefix;
    protected $cachepath;
    protected $baseurl;
    protected $defaults = array(
        'cachepath' => DEPAGE_CACHE_PATH,
        'baseurl' => DEPAGE_BASE,
        'disposition' => "file",
    );
    // }}}

    // {{{ factory
    public static function factory($prefix, $options = array()) {
        if (!isset($options['disposition'])) {
            $options['disposition'] = "file";
        }

        if ($options['disposition'] == "memory" && extension_loaded("memcached")) {
            return new \depage\cache\cache_memcached($prefix, $options);
        } elseif ($options['disposition'] == "memory" && extension_loaded("memcache")) {
            return new \depage\cache\cache_memcache($prefix, $options);
        } elseif ($options['disposition'] == "uncached") {
            return new \depage\cache\cache_uncached($prefix, $options);
        } else {
            return new \depage\cache\cache($prefix, $options);
        }
    }
    // }}}

    // {{{ constructor
    protected function __construct($prefix, $options = array()) {
        $class_vars = get_class_vars('\depage\cache\cache');
        $options = array_merge($class_vars['defaults'], $options);

        $this->prefix = $prefix;
        $this->cachepath = "{$options['cachepath']}/{$this->prefix}/";
        $this->baseurl = "{$options['baseurl']}cache/{$this->prefix}/";
    }
    // }}}
    // {{{ exist
    /**
     * @brief return if a cache-item with $key exists
     *
     * @return      (bool) true if cache for $key exists, false if not
     */
    private function exist($key) {
        return file_exists($this->get_cache_path($key));
    }
    // }}}
    // {{{ age */
    /**
     * @brief returns age of cache-item with key $key
     *
     * @param       $key (string) key of cache item
     *
     * @return      (int) age as unix timestamp
     */
    public function age($key) {
        if ($this->exist($key)) {
            return filemtime($this->get_cache_path($key));
        } else {
            return false;
        }
    }
    // }}}
    // {{{ setFile */
    /**
     * @brief saves cache data for key $key to a file
     *
     * @param   $key (string) key to save data in, may include namespaces divided by a forward slash '/'
     * @param   $data (string) data to save in file
     * @param   $saveGzippedContent (bool) if true, it saves a gzip file additional to plain string, defaults to false
     *
     * @return  (bool) true if saved successfully
     */
    public function setFile($key, $data, $saveGzippedContent = false) {
        $path = $this->get_cache_path($key);

        if (!is_dir(dirname($path))) { 
            mkdir(dirname($path), 0777, true);
        }
        $success = file_put_contents($path, $data, \LOCK_EX);

        if ($saveGzippedContent) {
            $success = $success && file_put_contents($path . ".gz", gzencode($data), \LOCK_EX);
        }

        return $success;
    }
    // }}}
    // {{{ getFile */
    /**
     * @brief gets content of cache item by key $key from a file
     *
     * @param   $key (string) key of item to get
     *
     * @return  (string) content of cache item, false if the cache item does not exist
     */
    public function getFile($key) {
        if ($this->exist($key)) {
            $path = $this->get_cache_path($key);

            return file_get_contents($path);
        } else {
            return false;
        }
    }
    // }}}
    // {{{ set */
    /**
     * @brief sets data ob a cache item
     *
     * @param   $key (string) key to save under
     * @param   $data (object) object to save. $data must be serializable
     *
     * @return  (bool) true on success, false on failure
     */
    public function set($key, $data) {
        $str = serialize($data);

        return $this->setFile($key, $str);
    }
    // }}}
    // {{{ get */
    /**
     * @brief gets a cached object
     *
     * @param   $key (string) key of item to get
     *
     * @return  (object) unserialized content of cache item, false if the cache item does not exist
     */
    public function get($key) {
        $value = $this->getFile($key);

        return unserialize($value);
    }
    // }}}
    // {{{ getUrl */
    /**
     * @brief returns cache-url of cache-item for direct access through http
     *
     * @param   $key (string) key of cache item
     *
     * @return  (string) url of cache-item
     */
    public function getUrl($key) {
        if ($this->baseurl !== null) {
            return $this->baseurl . $key;
        }
    }
    // }}}
    // {{{ delete */
    /**
     * @brief deletes a cache-item by key or by namespace
     *
     * If key ends on a slash, all items in this namespace will be deleted.
     *
     * @param   $key (string) key of item
     *
     * @return  void
     */
    public function delete($key) {
        // @todo throw error if there are wildcards in identifier to be compatioble with memcached
        
        $files = array_merge(
            (array) glob($this->cachepath . $key),
            (array) glob($this->cachepath . $key . ".gz")
        );

        foreach ($files as $file) {
            $this->rmr($file);
        }
    }
    // }}}
    // {{{ rmdir */
    /**
     * @brief deletes files and direcories recursively
     *
     * @param   $path (string) path to file or directory
     *
     * @return  void
     */
    public function rmr($path) {
        if (!is_link($path) && is_dir($path)) {
            $files = glob($path . "/*");
            foreach ($files as $file) {
                $this->rmr($file);
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }
    // }}}

    // {{{ get_cache_path */
    /**
     * @brief gets file-path for a cache-item by key
     *
     * @param   key (string) key of item 
     *
     * @return  (string) file path to cache-item
     */
    private function get_cache_path($key) {
        return $this->cachepath . $key;
    }
    // }}}
}

/* vim:set ft=php sts=4 fdm=marker et : */
