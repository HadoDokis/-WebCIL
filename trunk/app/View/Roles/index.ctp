<?php
echo $this->Html->script('roles.js');
?>

<?php
if(!empty($roles)) {
    ?>
    <table class="table ">
    <thead>
    <th class="thleft col-md-2">Profil</th>
    <th class="thleft col-md-8">Droits</th>
    <th class='thleft col-md-2'>Actions</th>
    </thead>
    <tbody>
    <?php
    foreach($roles as $donnees) {
        ?>
        <tr>
            <td class="tdleft col-md-2">
                <?php echo $donnees['Role']['libelle']; ?>
            </td>
            <td class="tdleft col-md-8">
                <ul>
                    <?php
                    foreach($donnees['Droits'] as $key => $value) {
                        echo '<li>' . $value['ListeDroit']['libelle'] . '</li>';
                    }

                    ?>
                </ul>
            </td>
            <td class="tdleft">
                <div class="btn-group">
                    <?php
                    if($this->Autorisation->authorized(14, $droits)) {
                        echo $this->Html->link('<span class="glyphicon glyphicon-pencil"></span>', array(
                            'controller' => 'roles',
                            'action' => 'edit',
                            $donnees['Role']['id']
                        ), array(
                            'class' => 'btn btn-default-default boutonEdit btn-sm',
                            'escapeTitle' => false
                        ));
                    }
                    if($this->Autorisation->authorized(15, $droits)) {
                        echo $this->Html->link('<span class="glyphicon glyphicon-trash"></span>', array(
                            'controller' => 'roles',
                            'action' => 'delete',
                            $donnees['Role']['id']
                        ), array(
                            'class' => 'btn btn-default-danger boutonDelete btn-sm',
                            'escapeTitle' => false
                        ), 'Voulez vous vraiment supprimer le profil ' . $donnees['Role']['libelle']);
                    }
                    ?>
                </div>
            </td>
        </tr>
    <?php
    }
    echo "</tbody>";
    echo "</table>";
}
if(empty($roles)) {
    echo "<div class='text-center'><h3>Il n'existe aucun profil <small>pour cette entité</small></h3></div>";
}
if($this->Autorisation->authorized(13, $droits)) {
    echo '<div class="text-center">';
    echo $this->Html->link('<span class="glyphicon glyphicon-plus"></span> Ajouter un profil', array(
        'controller' => 'roles',
        'action' => 'add'
    ), array(
        'class' => 'btn btn-default-primary sender',
        'escapeTitle' => false
    ));
    echo '</div>';
}
?>