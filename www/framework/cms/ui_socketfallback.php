<?php

/**
 * @file    framework/websocket/jstree/jstree_fallback.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\cms;

class ui_socketfallback extends ui_base {
    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (!empty($this->urlSubArgs[0])) {
            $this->projectName = $this->urlSubArgs[0];
        }
        if (!empty($this->urlSubArgs[1])) {
            $this->docName = $this->urlSubArgs[1];
        }
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;
        $this->xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, \depage\cache\cache::factory("xmldb"));
    }
    // }}}

    // {{{ updates
    public function updates() {
        $this->auth->enforce();

        // TODO: cleanup old recorded changes based on logged in users
        $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $_REQUEST["doc_id"], $_REQUEST["seq_nr"]);
        return $delta_updates->encodedDeltaUpdate();
    }
    // }}}

    // {{{ _send_headers
    protected function send_headers($content) {
        header("HTTP/1.0 200 OK");
        header('Content-type: text/json; charset=utf-8');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Pragma: no-cache");
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
