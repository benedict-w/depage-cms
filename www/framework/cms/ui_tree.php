<?php
/**
 * @file    framework/cms/ui_tree.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\cms;

use \html;

class ui_tree extends ui_base {
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
    
    // {{{ destructor
    public function __destruct() {
        if (isset($_REQUEST["doc_id"])) {
            $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $_REQUEST["doc_id"], 0);
            $delta_updates->discardOldChanges();
        }
    }
    // }}}

    // {{{ index
    public function index() {
        return $this->tree($this->docName);
    }
    // }}}
    // {{{ error
    public function error($error, $env) {
        parent::error($error, $env);
        //@todo return error in json format to catch from javascript
    }
    // }}}
    // {{{ tree()
    public function tree($docName) {
        $actionUrl = "project/{$this->projectName}/tree/{$docName}/";

        $doc_info = $this->xmldb->get_doc_info($docName);
        $doc_id = $doc_info->id;

        $h = new html("jstree.tpl", array(
            'actionUrl' => $actionUrl,
            'doc_id' => $doc_id,
            'root_id' => $doc_info->rootid, 
            'seq_nr' => $this->get_current_seq_nr($doc_id),
            'nodes' => $this->get_html_nodes($docName),
        ), $this->html_options); 

        return $h;
    }
    // }}}

    // {{{ create_node
    /**
     * @param $doc_id document id
     * @param $node child node data
     * @param $position position for new child in parent
     */
    public function create_node() {
        $this->auth->enforce();

        $node = $this->xmldb->build_node($_REQUEST["doc_id"], $_REQUEST["node"]["_type"], $_REQUEST["node"]);
        $id = $this->xmldb->add_node($_REQUEST["doc_id"], $node, $_REQUEST["target_id"], $_REQUEST["position"]);   
        $status = $id !== false;
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($_REQUEST["target_id"]));
        }

        return new \json(array("status" => $status, "id" => $id));
    }
    // }}}
    // {{{ rename_node
    public function rename_node() {
        $this->auth->enforce();

        $this->xmldb->set_attribute($_REQUEST["doc_id"], $_REQUEST["id"], "name", $_REQUEST["name"]);
        $parent_id = $this->xmldb->get_parentId_by_elementId($_REQUEST["doc_id"], $_REQUEST["id"]);
        $this->recordChange($_REQUEST["doc_id"], array($parent_id));

        return new \json(array("status" => 1));
    }
    // }}}
    // {{{ move_node
    public function move_node() {
        $this->auth->enforce();

        $old_parent_id = $this->xmldb->get_parentId_by_elementId($_REQUEST["doc_id"], $_REQUEST["id"]);
        $status = $this->xmldb->move_node($_REQUEST["doc_id"], $_REQUEST["id"], $_REQUEST["target_id"], $_REQUEST["position"]);
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($old_parent_id, $_REQUEST["target_id"]));
        }

        return new \json(array("status" => $status));
    }
    // }}}
    // {{{ copy_node
    public function copy_node() {
        $this->auth->enforce();

        $status = $this->xmldb->copy_node($_REQUEST["doc_id"], $_REQUEST["id"], $_REQUEST["target_id"], $_REQUEST["position"]);
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($_REQUEST["target_id"], $status));
        }

        return new \json(array("status" => $status));
    }
    // }}}
    // {{{ remove_node
    public function remove_node() {
        $this->auth->enforce();

        $parent_id = $this->xmldb->get_parentId_by_elementId($_REQUEST["doc_id"], $_REQUEST["id"]);
        $ids = $this->xmldb->unlink_node($_REQUEST["doc_id"], $_REQUEST["id"]);
        $status = $ids !== false;
        if ($status) {
            $this->recordChange($_REQUEST["doc_id"], array($parent_id));
        }

        return new \json(array("status" => $status));
    }
    // }}}

    // TODO: set icons?
    // {{{ types_settings
    public function types_settings() {
        $doc_info = $this->xmldb->get_doc_info($this->docName);
        $doc_id = $doc_info->id;
        $root_element_name = $this->xmldb->get_nodeName_by_elementId($doc_id, $doc_info->rootid);

        $permissions = $this->xmldb->get_permissions($doc_id);
        $valid_children = $permissions->valid_children();
        $settings = array(
            "typesfromurl" => array(
                "max_depth" => -2,
                "max_children" => -2,
                "valid_children" => self::valid_children_or_none($valid_children, $root_element_name),
                "types" => array(),
            ),
        );

        $known_elements = $permissions->known_elements();
        $types = &$settings["typesfromurl"]["types"];
        foreach ($known_elements as $element) {
            if ($element != $root_element_name) {
                $setting = array();

                /* TODO: disallow drags? is it better if every element is draggable even if it is not movable?
                if (!$permissions->is_element_allowed_in_any($element)) {
                    $setting["start_drag"] = false;
                    $setting["move_node"] = false;
                }
                */

                if (!$permissions->is_unlink_allowed_of($element)) {
                    $setting["delete_node"] = false;
                    $setting["remove"] = false;
                }

                if (isset($valid_children[$element])) {
                    $setting["valid_children"] = $valid_children[$element];
                } else if (isset($valid_children[\depage\xmldb\permissions::default_element])) {
                    $setting["valid_children"] = self::valid_children_or_none($valid_children, \depage\xmldb\permissions::default_element);
                }

                $types[$element] = $setting;
            }
        }

        if (!isset($types[\depage\xmldb\permissions::default_element])) {
            $types[\depage\xmldb\permissions::default_element] = array(
                "valid_children" => self::valid_children_or_none($valid_children, \depage\xmldb\permissions::default_element),
            );
        }

        return new \json($settings);
    }
    // }}}

    // TODO: disable
    // {{{ add_permissions
    public function add_permissions($doc_id, $element, $parent) {
        $permissions = $this->xmldb->get_permissions($doc_id);
        $permissions->allow_element_in($element, $parent);

        $this->xmldb->set_permissions($doc_id, $permissions);
        echo $permissions;
    }
    // }}}

    // {{{ recordChange
    protected function recordChange($doc_id, $parent_ids) {
        $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);

        $unique_parent_ids = array_unique($parent_ids);
        foreach ($unique_parent_ids as $parent_id) {
            $delta_updates->recordChange($parent_id);
        }
    }
    // }}}

    // {{{ get_html_nodes
    protected function get_html_nodes($doc_name) {
        $doc = $this->xmldb->get_doc($doc_name);
        $html = \depage\cms\jstree_xml_to_html::toHTML(array($doc));

        return current($html);
    }
    // }}}
    
    // {{{ get_current_seq_nr
    protected function get_current_seq_nr($doc_id) {
       $delta_updates = new \depage\websocket\jstree\jstree_delta_updates($this->prefix, $this->pdo, $this->xmldb, $doc_id);
       return $delta_updates->currentChangeNumber();
    }
    // }}}
    // {{{ valid_children_or_none
    static private function valid_children_or_none(&$valid_children, $element) {
        if (empty($valid_children[$element])) {
            return "none";
        } else {
            return $valid_children[$element];
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
