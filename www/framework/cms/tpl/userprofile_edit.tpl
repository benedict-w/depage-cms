<?php 
    $form = new depage\htmlform\htmlform("userprofile_edit");

    // define formdata
    $form->addText("name", array(
        'defaultValue' => $this->user->fullname,
    ));
    $form->addEmail("email", array(
        'defaultValue' => $this->user->email,
    ));
    $form->addPassword("password", array(
    ));

    // process form
    $form->process();

    if ($form->isValid()) {
        // saving formdata
        echo("<p>saving</p>");

        $form->clearSession();
    }

    echo($form);
?>
<?php // vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et :