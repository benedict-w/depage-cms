<?php

namespace depage\graphics;

class graphics {
    protected $input;
    protected $output;
    protected $queue = array();

    public static function factory($options = array()) {
        if (!isset($options['extension'])) {
            $options['extension'] = 'gd';
        }

        if ($options['extension'] == 'imagemagick') {
            return new \depage\graphics\graphics_imagemagick($options);
        } else {
            return new \depage\graphics\graphics_gd($options);
        }
    }

    public function __construct($options) {
    }

    public function addCrop($width, $height, $x = 0, $y = 0) {
        $this->queue[] = array(
            'action'    => 'crop',
            'options'   => array(
                'width'     => $width,
                'height'    => $height,
                'x'         => $x,
                'y'         => $y,
            ),
        );
    }

    public function addResize($width, $height) {
        $this->queue[] = array(
            'action'    => 'resize',
            'options'   => array(
                'width'     => $width,
                'height'    => $height,
            ),
        );
    }

    public function addThumb($width, $height) {
        $this->queue[] = array(
            'action'    => 'thumb',
            'options'   => array(
                'width'     => $width,
                'height'    => $height,
            ),
        );
    }

    protected function load() {}

    protected function save() {}

    public function render($input, $output = null) {
        $this->input    = $input;
        $this->output   = ($output == null) ? $input : $output;

        $this->load();

        foreach($this->queue as $step) {
            call_user_func(array($this, $step['action']), $step['options']);
        }

        $this->save();
    }
}
