<?php

/**************************************************
 ************** Controller du pannel ***************
 **************************************************/
class PannelController extends AppController
{
    public $uses = array(
        'Pannel',
        'Fiche',
        'Users',
        'OrganisationUser',
        'Droit',
        'EtatFiche',
        'Commentaire',
        'Notification',
        'Historique'
    );

    public $components = array('FormGenerator.FormGen');

    /**
     *** Accueil de la page, listing des fiches et de leurs catégories
     **/

    public function index()
    {

        $this->set('title', 'Mes fiches');
// Requète récupérant les fiches en cours de rédaction
        $db = $this->EtatFiche->getDataSource();
        $subQuery = $db->buildStatement(array(
            'fields' => array('"EtatFiche2"."fiche_id"'),
            'table' => $db->fullTableName($this->EtatFiche),
            'alias' => 'EtatFiche2',
            'limit' => null,
            'offset' => null,
            'joins' => array(),
            'conditions' => array('EtatFiche2.etat_id BETWEEN 2 AND 5'),
            'order' => null,
            'group' => null
        ), $this->EtatFiche);
        $subQuery = '"Fiche"."user_id" = ' . $this->Auth->user('id') . ' AND "Fiche"."organisation_id" = ' . $this->Session->read('Organisation.id') . ' AND "EtatFiche"."fiche_id" NOT IN (' . $subQuery . ') ';
        $subQueryExpression = $db->expression($subQuery);

        $conditions[] = $subQueryExpression;
        $conditions[] = 'EtatFiche.etat_id = 1';
        $encours = $this->EtatFiche->find('all', array(
            'conditions' => $conditions,
            'contain' => array(
                'Fiche' => array(
                    'fields' => array(
                        'id',
                        'created',
                        'modified'
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    ),
                    'Valeur' => array(
                        'conditions' => array(
                            'champ_name' => 'outilnom'
                        ),
                        'fields' => array(
                            'champ_name',
                            'valeur'
                        )
                    )
                ),
                'User' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                )
            )

        ));
        $this->set('encours', $encours);


// Requète récupérant les fiches en cours de validation

        $requete = $this->EtatFiche->find('all', array(
                'conditions' => array(
                    'EtatFiche.etat_id' => 2,
                    'Fiche.user_id' => $this->Auth->user('id'),
                    'Fiche.organisation_id' => $this->Session->read('Organisation.id')
                ),
                'contain' => array(
                    'Fiche' => array(
                        'fields' => array(
                            'id',
                            'created',
                            'modified'
                        ),
                        'Valeur' => array(
                            'conditions' => array(
                                'champ_name' => 'outilnom'
                            ),
                            'fields' => array(
                                'champ_name',
                                'valeur'
                            )
                        ),
                        'User' => array(
                            'fields' => array(
                                'id',
                                'nom',
                                'prenom'
                            )
                        )
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    )
                )
            )

        );
        $this->set('encoursValidation', $requete);


// Requète récupérant les fiches refusées par un validateur

        $requete = $this->EtatFiche->find('all', array(
            'conditions' => array(
                'EtatFiche.etat_id' => 4,
                'Fiche.user_id' => $this->Auth->user('id'),
                'Fiche.organisation_id' => $this->Session->read('Organisation.id')
            ),
            'contain' => array(
                'Fiche' => array(
                    'fields' => array(
                        'id',
                        'created',
                        'modified'
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    ),
                    'Valeur' => array(
                        'conditions' => array(
                            'champ_name' => 'outilnom'
                        ),
                        'fields' => array(
                            'champ_name',
                            'valeur'
                        )
                    )
                ),
                'User' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                )
            )
        ));
        $this->set('refusees', $requete);
        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
        $this->set('consultants', $return['consultants']);
    }

    public function inbox()
    {
        $this->set('title', 'Fiches reçues');
        // Requète récupérant les fiches qui demande une validation

        $requete = $this->EtatFiche->find('all', array(
            'conditions' => array(
                'EtatFiche.etat_id' => 2,
                'EtatFiche.user_id' => $this->Auth->user('id'),
                'Fiche.organisation_id' => $this->Session->read('Organisation.id')
            ),
            'contain' => array(
                'Fiche' => array(
                    'fields' => array(
                        'id',
                        'created',
                        'modified'
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    ),
                    'Valeur' => array(
                        'conditions' => array(
                            'champ_name' => 'outilnom'
                        ),
                        'fields' => array(
                            'champ_name',
                            'valeur'
                        )
                    )
                ),
                'User' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                ),
                'PreviousUser' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'

                    )
                )
            )
        ));
        $this->set('dmdValid', $requete);


        // Requète récupérant les fiches qui demande un avis

        $requete = $this->EtatFiche->find('all', array(
            'conditions' => array(
                'EtatFiche.etat_id' => 6,
                'EtatFiche.user_id' => $this->Auth->user('id'),
                'Fiche.organisation_id' => $this->Session->read('Organisation.id')
            ),
            'contain' => array(
                'Fiche' => array(
                    'fields' => array(
                        'id',
                        'created',
                        'modified'
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    ),
                    'Valeur' => array(
                        'conditions' => array(
                            'champ_name' => 'outilnom'
                        ),
                        'fields' => array(
                            'champ_name',
                            'valeur'
                        )
                    )
                ),
                'User' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                ),
                'PreviousUser' => array(
                    'fields' => array(
                        'id',
                        'nom',
                        'prenom'

                    )
                )
            )
        ));
        $this->set('dmdAvis', $requete);
        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
        $this->set('consultants', $return['consultants']);
    }


    public function archives()
    {
        $this->set('title', 'Fiches validées');
        // Requète récupérant les fiches validées par le CIL

        $requete = $this->EtatFiche->find('all', array(
                'conditions' => array(
                    'EtatFiche.etat_id' => 5,
                    'Fiche.user_id' => $this->Auth->user('id'),
                    'Fiche.organisation_id' => $this->Session->read('Organisation.id')
                ),
                'contain' => array(
                    'Fiche' => array(
                        'fields' => array(
                            'id',
                            'created',
                            'modified'
                        ),
                        'User' => array(
                            'fields' => array(
                                'id',
                                'nom',
                                'prenom'
                            )
                        ),
                        'Valeur' => array(
                            'conditions' => array(
                                'champ_name' => 'outilnom'
                            ),
                            'fields' => array(
                                'champ_name',
                                'valeur'
                            )
                        )
                    ),
                    'User' => array(
                        'fields' => array(
                            'id',
                            'nom',
                            'prenom'
                        )
                    )
                )
            )

        );
        $this->set('validees', $requete);

    }

// Fonction appelée pour le composant parcours, permettant d'afficher le parcours parcouru par une fiche et les commentaires liés (uniquement ceux visibles par l'utilisateur)

    public function parcours($id)
    {
        $parcours = $this->EtatFiche->find('all', array(
            'conditions' => array(
                'EtatFiche.fiche_id' => $id
            ),
            'contain' => array(
                'Fiche' => array(
                    'id',
                    'organisation_id',
                    'user_id',
                    'created',
                    'modified',
                    'User' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                ),
                'User' => array(
                    'id',
                    'nom',
                    'prenom'
                ),
                'Commentaire' => array(
                    'conditions' => array(
                        'OR' => array(
                            'Commentaire.user_id' => $this->Auth->user('id'),
                            'Commentaire.destinataire_id' => $this->Auth->user('id')
                        )
                    ),
                    'User' => array(
                        'id',
                        'nom',
                        'prenom'
                    )
                )
            ),
            'order' => array(
                'EtatFiche.id DESC'
            )
        ));
        return $parcours;
    }

    public function getHistorique($id)
    {
        $historique = $this->Historique->find('all', array(
            'conditions' => array('fiche_id' => $id),
            'order' => array(
                'created DESC',
                'id DESC'
            )
        ));
        return $historique;
    }


// Fonction de suppression des notifications

    public function dropNotif()
    {
        $this->Notification->deleteAll(array(
            'Notification.user_id' => $this->Auth->user('id'),
            false
        ));
        $this->redirect($this->referer());
    }

    public function validNotif()
    {
        $this->Notification->updateAll(array(
            'Notification.vu' => true,
            'Notification.user_id' => $this->Auth->user('id')
        ));
        $this->redirect($this->referer());
    }

    protected function _listValidants()
    {
        // Requète récupérant les utilisateurs ayant le droit de consultation

        $queryConsultants = array(
            'fields' => array(
                'User.id',
                'User.nom',
                'User.prenom'
            ),
            'joins' => array(
                $this->Droit->join('OrganisationUser', array('type' => "INNER")),
                $this->Droit->OrganisationUser->join('User', array('type' => "INNER"))
            ),
            'recursive' => -1,
            'conditions' => array(
                'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id'),
                'User.id != ' . $this->Auth->user('id'),
                'Droit.liste_droit_id' => 3
            ),
        );
        $consultants = $this->Droit->find('all', $queryConsultants);
        $consultants = Hash::combine($consultants, '{n}.User.id', array(
            '%s %s',
            '{n}.User.prenom',
            '{n}.User.nom'
        ));
        $return = array('consultants' => $consultants);


// Requète récupérant les utilisateurs ayant le droit de validation
        if($this->Session->read('Organisation.cil') != null) {
            $cil = $this->Session->read('Organisation.cil');
        } else {
            $cil = 0;
        }


        $queryValidants = array(
            'fields' => array(
                'User.id',
                'User.nom',
                'User.prenom'
            ),
            'joins' => array(
                $this->Droit->join('OrganisationUser', array('type' => "INNER")),
                $this->Droit->OrganisationUser->join('User', array('type' => "INNER"))
            ),
            'conditions' => array(
                'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id'),
                'NOT' => array(
                    'User.id' => array(
                        $this->Auth->user('id'),
                        $cil
                    )
                ),
                'Droit.liste_droit_id' => 2
            )
        );
        $validants = $this->Droit->find('all', $queryValidants);
        $validants = Hash::combine($validants, '{n}.User.id', array(
            '%s %s',
            '{n}.User.prenom',
            '{n}.User.nom'
        ));
        $return['validants'] = $validants;
        return $return;
    }

}