<div class="well">
    <h1>Veuillez vous identifier</h1>
</div>

<div class="users form col-md-6 col-md-offset-3">
    <?php echo $this->Form->create('User'); ?>
    <div class="input-group login">
        <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
        <?php echo $this->Form->input('username', array(
            'class' => 'form-control',
            'placeholder' => 'Login',
            'label' => false
        )); ?>
    </div>
    <div class="input-group login">
        <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
        <?php
        echo $this->Form->input('password', array(
            'class' => 'form-control',
            'placeholder' => 'Mot de passe',
            'label' => false
        ));
        ?>
    </div>
    <?php
    echo $this->Form->submit('Connexion', array('class' => 'btn btn-lg btn-default-success'));
    echo $this->Form->end();
    ?>
</div>