<div class="users form">
    <?php
    if(isset($this->validationErrors['User']) && !empty($this->validationErrors['User'])) {
        ?>

        <div class="alert alert-danger" role="alert">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            <span class="sr-only">Error:</span>
            Ces erreurs se sont produites:
            <ul>
                <?php
                foreach($this->validationErrors as $donnees) {
                    foreach($donnees as $champ) {
                        foreach($champ as $error) {
                            echo '<li>' . $error . '</li>';
                        }
                    }
                }
                ?>
            </ul>
        </div>
    <?php
    }

    echo $this->Form->create('User', array(
        'autocomplete' => 'off',
        'inputDefaults' => array('div' => false),
        'class' => 'form-horizontal'
    ));
    ?>
    <div class="row">
        <div class="col-md-6">

            <?php
            if(empty($this->validationErrors['User']['username'])) echo '<div class="form-group">'; else echo '<div class="form-group has-error">';
            ?>
            <?php
            echo $this->Form->input('username', array(
                'class' => 'form-control',
                'placeholder' => 'Login',
                'label' => array(
                    'text' => 'Login <span class="requis">*</span>',
                    'class' => 'col-md-4 control-label'
                ),
                'between' => '<div class="col-md-8">',
                'after' => '</div>',
                'required' => false
            ));
            ?>
        </div>

        <?php
        if(empty($this->validationErrors['User']['password'])) echo '<div class="form-group">'; else echo '<div class="form-group has-error">';
        echo $this->Form->input('password', array(
            'class' => 'form-control',
            'placeholder' => 'Mot de passe',
            'label' => array(
                'text' => 'Mot de passe <span class="requis">*</span>',
                'class' => 'col-md-4 control-label'
            ),
            'between' => '<div class="col-md-8">',
            'after' => '</div>',
            'required' => false
        )); ?>
    </div>
    <?php
    if(empty($this->validationErrors['User']['passwd'])) echo '<div class="form-group">'; else echo '<div class="form-group has-error">';
    ?>

    <?php echo $this->Form->input('passwd', array(
        'class' => 'form-control',
        'placeholder' => 'Mot de passe (verification)',
        'label' => array(
            'text' => 'Vérification du mot de passe <span class="requis">*</span>',
            'class' => 'col-md-4 control-label'
        ),
        'between' => '<div class="col-md-8">',
        'after' => '</div>',
        'required' => false
    )); ?>
</div>

<div class="form-group">
    <?php
    echo $this->Form->input('nom', array(
        'class' => 'form-control',
        'placeholder' => 'Nom',
        'label' => array(
            'text' => 'Nom <span class="requis">*</span>',
            'class' => 'col-md-4 control-label'
        ),
        'between' => '<div class="col-md-8">',
        'after' => '</div>',
        'required' => false
    ));
    ?>
</div>
<div class="form-group">
    <?php
    echo $this->Form->input('prenom', array(
        'class' => 'form-control',
        'placeholder' => 'Prenom',
        'label' => array(
            'text' => 'Prénom <span class="requis">*</span>',
            'class' => 'col-md-4 control-label'
        ),
        'between' => '<div class="col-md-8">',
        'after' => '</div>',
        'required' => false
    ));
    ?>
</div>

<?php
if(empty($this->validationErrors['User']['email'])) echo '<div class="form-group">'; else echo '<div class="form-group has-error">';
echo $this->Form->input('email', array(
    'class' => 'form-control',
    'placeholder' => 'E-mail',
    'label' => array(
        'text' => 'E-mail <span class="requis">*</span>',
        'class' => 'col-md-4 control-label'
    ),
    'between' => '<div class="col-md-8">',
    'after' => '</div>',
    'required' => false
));
?>
</div>
</div>
<div class="col-md-6">
    <div class="form-group">
        <?php
        $listeOrganisations = array();
        foreach($tableau['Organisation'] as $key => $datas) {
            $listeOrganisations[$datas['infos']['id']] = $datas['infos']['raisonsociale'];
        }
        echo $this->Form->input('Organisation.Organisation_id', array(
            'options' => $listeOrganisations,
            'class' => 'form-control',
            'id' => 'deroulant',
            'label' => array(
                'text' => 'Organisations <span class="requis">*</span>',
                'class' => 'col-md-4 control-label'
            ),
            'between' => '<div class="col-md-8">',
            'after' => '</div>',
            'multiple' => 'multiple',
            'required' => 'true'
        )); ?>
    </div>


    <?php
    foreach($tableau['Organisation'] as $key => $datas) {
        $listeroles = array();
        echo "<script type='text/javascript'>";

        foreach($datas['roles'] as $clef => $value) {
            $listeroles[$value['infos']['id']] = $value['infos']['libelle'];
            echo 'var tableau_js' . $value['infos']['id'] . '= new Array();';
            foreach($value['droits'] as $k => $v) {
                echo "tableau_js" . $value['infos']['id'] . ".push(" . $v['liste_droit_id'] . ");";
            }
        }
        echo "</script>";
        ?>

        <div class="form-group droitsVille" id="droitsVille<?php echo $key; ?>">
            <div class="titreDiv text-center">
                <h4><?php echo $datas['infos']['raisonsociale']; ?></h4>
            </div>
            <div class="form-group">
                <?php
                if(!empty($listeservices[$datas['infos']['id']])) {
                    echo $this->Form->input('Service.' . $datas['infos']['id'], array(
                        'options' => $listeservices[$datas['infos']['id']],
                        'empty' => 'Choisissez un service',
                        'class' => 'usersDeroulant transformSelect form-control',
                        'label' => array(
                            'text' => 'Service <span class="requis">*</span>',
                            'class' => 'col-md-4 control-label'
                        ),
                        'between' => '<div class="col-md-8">',
                        'after' => '</div>',
                    ));
                }
                echo '</div><div class="form-group">';
                if(!empty($listeroles)) {
                    echo $this->Form->input('Role.' . $datas['infos']['id'], array(
                        'options' => $listeroles,
                        'class' => 'form-control deroulantRoles' . $key,
                        'id' => $key,
                        'label' => array(
                            'text' => 'Profils au sein de ' . $datas['infos']['raisonsociale'] . ' <span class="requis">*</span>',
                            'class' => 'col-md-4 control-label'
                        ),
                        'between' => '<div class="col-md-8">',
                        'after' => '</div>',
                        'multiple' => 'multiple'
                    ));
                } else {
                    echo "Aucun profil n'a été créé pour cette organisation";
                }
                ?>
            </div>
        </div>

    <?php
    }
    echo '</div></div>';

    echo '<div class="text-center">';


    echo '<div class="btn-group send">';
    echo $this->Html->link('<i class="fa fa-arrow-left"></i> Annuler', $referer, array(
        'class' => 'btn btn-default-default',
        'escape' => false
    ));
    echo $this->Form->button('<i class="fa fa-check"></i> Enregistrer', array(
        'type' => 'submit',
        'class' => 'btn btn-default-success'
    ));
    echo '</div>';
    ?>

</div>
</div>
</div>
</div>
<?php
echo $this->Html->script('users.js');
?>