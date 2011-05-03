<?php
/**
 * @file    boolean.php
 * @brief   boolean input element
 **/

namespace depage\htmlform\elements;

use depage\htmlform\abstracts;

/**
 * @brief HTML single checkbox input type.
 **/
class boolean extends abstracts\input {
    /**
     * @brief collects initial values across subclasses.
     **/
    protected function setDefaults() {
        parent::setDefaults();
        $this->defaults['defaultValue'] = false;
        $this->defaults['errorMessage'] = 'Please check this box if you want to proceed!';
    }

    /**
     * @brief   Renders element to HTML.
     *
     * @return  (string) HTML-rendered element
     **/
    public function __toString() {
        $inputAttributes    = $this->htmlInputAttributes();
        $label              = $this->htmlLabel();
        $marker             = $this->htmlMarker();
        $wrapperAttributes  = $this->htmlWrapperAttributes();
        $errorMessage       = $this->htmlErrorMessage();

        $selected = ($this->htmlValue() === true) ? " checked=\"yes\"" : '';

        return "<p {$wrapperAttributes}>" .
            "<label>" .
                "<input type=\"checkbox\" name=\"{$this->name}\"{$inputAttributes} value=\"true\"{$selected}>" .
                "<span class=\"label\">{$label}{$marker}</span>" .
            "</label>" .
            $errorMessage .
        "</p>\n";
    }

    /**
     * @brief validates boolean input element value
     *
     * Overrides input::validate(). Checks if the value of the current input
     * element is valid according to it's validator object. In case of boolean
     * the value has to be true if field is required.
     * 
     * @return $this->valid (bool) validation result
     **/
    public function validate() {
        if (!$this->validated) {
            $this->validated = true;

            $this->valid = (($this->value !== null)
                && ($this->validator->validate($this->value) || $this->isEmpty())
                && ($this->value || !$this->required)
            );
        }

        return $this->valid;
    }

    /**
     * @brief   set the boolean element value
     *
     * Sets the current input elements' value. Converts it to boolean if
     * necessary.
     *
     * @param   $newValue       (mixed) new element value
     * @return  $this->value    (bool)  converted value
     **/
    public function setValue($newValue) {
        if (is_bool($newValue)) {
            $this->value = $newValue;
        } else if ($newValue === "true") {
            $this->value = true;
        } else {
            $this->value = false;
        }

        return $this->value;
    }
}
