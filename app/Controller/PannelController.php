<?php

/**
 * PannelController
 * Controller du pannel
 *
 * WebCIL : Outil de gestion du Correspondant Informatique et Libertés.
 * Cet outil consiste à accompagner le CIL dans sa gestion des déclarations via 
 * le registre. Le registre est sous la responsabilité du CIL qui doit en 
 * assurer la communication à toute personne qui en fait la demande (art. 48 du décret octobre 2005).
 * 
 * Copyright (c) Adullact (http://www.adullact.org)
 *
 * Licensed under The CeCiLL V2 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 * 
 * @copyright   Copyright (c) Adullact (http://www.adullact.org)
 * @link        https://adullact.net/projects/webcil/
 * @since       webcil V1.0.0
 * @license     http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html CeCiLL V2 License
 * @version     V1.0.0
 * @package     App.Controller
 */
App::uses('EtatFiche', 'Model');
App::uses('ListeDroit', 'Model');

class PannelController extends AppController {

    public $uses = [
        'Pannel',
        'Fiche',
        'Users',
        'OrganisationUser',
        'Droit',
        'EtatFiche',
        'Commentaire',
        'Modification',
        'Notification',
        'Historique',
        'Organisation',
        'Valeur'
    ];
    public $components = [
        'FormGenerator.FormGen',
        'Droits',
        'Banettes'
    ];
    public $helpers = [
        'Banettes'
    ];
    
    /**
     * Accueil de la page, listing des fiches et de leurs catégories
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function index() {
        $banettes = [];
        $limit = 5;
        
        $this->set('title', __d('pannel', 'pannel.titreTraitement'));
        
        if ($this->Droits->authorized(ListeDroit::REDIGER_TRAITEMENT)) {
            // En cours de rédaction
            $query = $this->Banettes->queryEnCoursRedaction();
            $banettes['encours_redaction'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];

            // En attente
            $query = $this->Banettes->queryAttente();
            $banettes['attente'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];

            // Traitement refusés
            $query = $this->Banettes->queryRefuser();
            $banettes['refuser'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];
            
            // Mes traitements validés et insérés au registre
            $query = $this->Banettes->queryArchives();
            $banettes['archives'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];
        }
        

        // Etat des traitements passés en ma possession
        if ($this->Droits->authorized([ListeDroit::VALIDER_TRAITEMENT, ListeDroit::VISER_TRAITEMENT])) {
            $query = $this->Banettes->queryConsulte();
            $banettes['consulte'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];
        }
        
        // Traitement reçu pour validation
        if ($this->Droits->authorized(ListeDroit::VALIDER_TRAITEMENT)) {
            $query = $this->Banettes->queryRecuValidation();
            $banettes['recuValidation'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query),
            ];
        }
        
        // Traitement reçu pour consultation
        if ($this->Droits->authorized(ListeDroit::VISER_TRAITEMENT)) {
            $query = $this->Banettes->queryRecuConsultation();
            $banettes['recuConsultation'] = [
                'results' => $this->Fiche->find('all', $query + ['limit' => $limit]),
                'count' => $this->Fiche->find('count', $query)
            ];
        }
        
        $this->set(compact('banettes'));
        
        $notifications = $this->Notification->find('all', array(
            'conditions' => array(
                'Notification.user_id' => $this->Auth->user('id'),
                'Notification.vu' => false,
                'Notification.afficher' => false
            ),
            'contain' => array(
                'Fiche' => array(
                    'Valeur' => array(
                        'conditions' => array(
                            'champ_name' => 'outilnom'
                        ),
                        'fields' => array('champ_name', 'valeur')
                    )
                )
            ),
            'order' => array(
                'Notification.content'
            )
        ));
        $this->set('notifications', $notifications);

        $nameOrganisation = [];

        foreach ($notifications as $key => $value) {
            $nameOrganisation[$key] = $this->Organisation->find('first', [
                'conditions' => ['id' => $value['Fiche']['organisation_id']],
                'fields' => ['raisonsociale']
            ]);
        }
        $this->set('nameOrganisation', $nameOrganisation);
        
        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
        $this->set('consultants', $return['consultants']);
    }

    /**
     * Fonction qui récupère tous les traitements en cours de rédaction
     * 
     * @access public
     * @created 13/02/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    public function encours_redaction() {
        if (true !== $this->Droits->authorized(ListeDroit::REDIGER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        $this->set('title', __d('pannel', 'pannel.titreTraitementEnCoursRedaction'));
        
        // En cours de rédaction
        $query = $this->Banettes->queryEnCoursRedaction();
        $banettes['encours_redaction'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);

        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
        $this->set('consultants', $return['consultants']);
    }

    /**
     * Fonction qui récupère tous les traitements en attente
     * 
     * @access public
     * @created 13/02/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    public function attente() {
        if (true !== $this->Droits->authorized(ListeDroit::REDIGER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        $this->set('title', __d('pannel', 'pannel.titreTraitementEnAttente'));

        // En attente
        $query = $this->Banettes->queryAttente();
        $banettes['attente'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);
        
        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
    }

    /**
     * Fonction qui récupère tous les traitements refusés
     * 
     * @access public
     * @created 13/02/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    public function refuser() {
        if (true !== $this->Droits->authorized(ListeDroit::REDIGER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        $this->set('title', __d('pannel', 'pannel.titreTraitementRefuser'));

        // Traitement refusés
        $query = $this->Banettes->queryRefuser();
        $banettes['refuser'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);
    }

    /**
     * Fonction qui récupère tous les traitements reçus pour validation
     * 
     * @access public
     * @created 13/02/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    public function recuValidation() {
        if (true !== $this->Droits->authorized(ListeDroit::VALIDER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        $this->set('title', __d('pannel', 'pannel.titreTraitementRecuValidation'));

        // Traitement reçu pour validation
        $query = $this->Banettes->queryRecuValidation();
        $banettes['recuValidation'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);
        
        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
        $this->set('consultants', $return['consultants']);
    }

    /**
     * Fonction qui récupère tous les traitements reçus pour consultation
     * 
     * @access public
     * @created 13/02/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    public function recuConsultation() {
        if (true !== $this->Droits->authorized(ListeDroit::VISER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        $this->set('title', __d('pannel', 'pannel.titreTraitementConsultation'));

        // Traitement reçu pour consultation
        $query = $this->Banettes->queryRecuConsultation();
        $banettes['recuConsultation'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);
    }

    /**
     * Requète récupérant les fiches validées par le CIL
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function archives() {
        if (true !== $this->Droits->authorized(ListeDroit::REDIGER_TRAITEMENT)) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }

        $this->set('title', __d('pannel', 'pannel.titreTraitementValidee'));

        // Mes traitements validés et insérés au registre
        $query = $this->Banettes->queryArchives();
        $banettes['archives'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);
    }

    /**
     * Fonction appelée pour le composant parcours, permettant d'afficher le parcours parcouru par une fiche et les commentaires liés (uniquement ceux visibles par l'utilisateur)
     * 
     * @param int $id
     * @return type
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function parcours($id) {
        $parcours = $this->EtatFiche->find('all', [
            'conditions' => [
                'EtatFiche.fiche_id' => $id,
            ],
            'contain' => [
                'Modification' => [
                    'id',
                    'modif',
                    'created'
                ],
                'Fiche' => [
                    'id',
                    'organisation_id',
                    'user_id',
                    'created',
                    'modified',
                    'User' => [
                        'id',
                        'nom',
                        'prenom'
                    ],
                ],
                'User' => [
                    'id',
                    'nom',
                    'prenom'
                ],
                'Commentaire' => [
                    'User' => [
                        'id',
                        'nom',
                        'prenom'
                    ]
                ],
            ],
            'order' => [
                'EtatFiche.id DESC'
            ]
        ]);

        return $parcours;
    }

    /**
     * Fonction permettant d'afficher tout les traitements passer par le CIL 
     * ou le valideur ou l'administrateur
     * 
     * @access public
     * @created 10/05/2016
     * @version V1.0.0
     */
    public function consulte() {
        if (true !== $this->Droits->authorized([ListeDroit::VALIDER_TRAITEMENT, ListeDroit::VISER_TRAITEMENT])) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        // Superadmin non autorisé
        if ($this->Droits->isSu() == true) {
            throw new ForbiddenException(__d('default', 'default.flasherrorPasDroitPage'));
        }
        
        $this->set('title', __d('pannel', 'pannel.titreTraitementVu'));

        // Etat des traitements passés en ma possession
        $query = $this->Banettes->queryConsulte();
        $banettes['consulte'] = [
            'results' => $this->Fiche->find('all', $query + ['limit' => 0]),
            'count' => $this->Fiche->find('count', $query)
        ];
        $this->set('banettes', $banettes);

        $return = $this->_listValidants();
        $this->set('validants', $return['validants']);
    }

    /**
     * @param int $id
     * @return type
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function getHistorique($id) {
        $historique = $this->Historique->find('all', [
            'conditions' => ['fiche_id' => $id],
            'order' => [
                'created DESC',
                'id DESC'
            ]
        ]);

        return $historique;
    }

    /**
     * Fonction de suppression de toute les notifications d'un utilisateur
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function dropNotif() {
        $success = true;
        $this->Notification->begin();

        $success = $success && $this->Notification->deleteAll([
                    'Notification.user_id' => $this->Auth->user('id'),
                    false
        ]);

        if ($success == true) {
            $this->Notification->commit();
        } else {
            $this->Notification->rollback();
        }

        $this->redirect($this->referer());
    }

    /**
     * Fonction de suppression d'une notification d'un utilisateur
     * 
     * @access public
     * @created 20/01/2016
     * @version V1.0.0
     */
    public function supprimerLaNotif($idFiche) {
        $success = true;
        $this->Notification->begin();

        $success = $success && $this->Notification->deleteAll([
                    'Notification.fiche_id' => $idFiche,
                    'Notification.user_id' => $this->Auth->user('id')
        ]);

        if ($success == true) {
            $this->Notification->commit();
        } else {
            $this->Notification->rollback();
        }
    }

    /**
     * Permet de mettre dans la base de donner les notifications deja afficher 
     * quand on fermer la pop-up avec le bouton FERMER
     * 
     * @access public
     * @created 02/12/2015
     * @version V1.0.0
     */
    public function validNotif() {
        $success = true;
        $this->Notification->begin();

        $success = $success && $this->Notification->updateAll([
                    'Notification.afficher' => true
                        ], [
                    'Notification.user_id' => $this->Auth->user('id')
                ]) !== false;

        if ($success == true) {
            $this->Notification->commit();
        } else {
            $this->Notification->rollback();
        }

        $this->redirect($this->referer());
    }

    /**
     * Permet de mettre en base les notifs deja afficher
     * 
     * @param int $idFicheEnCourAffigage
     * 
     * @access public
     * @created 20/01/2016
     * @version V1.0.0
     */
    public function notifAfficher($idFicheEnCourAffigage = 0) {
        $success = true;
        $this->Notification->begin();

        $success = $success && $this->Notification->updateAll([
                    'Notification.afficher' => true
                        ], [
                    'Notification.user_id' => $this->Auth->user('id'),
                    'Notification.fiche_id' => $idFicheEnCourAffigage
                ]) !== false;

        if ($success == true) {
            $this->Notification->commit();
        } else {
            $this->Notification->rollback();
        }
    }

    /**
     * @return type
     * 
     * @access protected
     * @created 02/12/2015
     * @version V1.0.0
     */
    protected function _listValidants() {
        // Requète récupérant les utilisateurs ayant le droit de consultation
        $queryConsultants = [
            'fields' => [
                'User.id',
                'User.nom',
                'User.prenom'
            ],
            'joins' => [
                $this->Droit->join('OrganisationUser', ['type' => "INNER"]),
                $this->Droit->OrganisationUser->join('User', ['type' => "INNER"])
            ],
            'recursive' => -1,
            'conditions' => [
                'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id'),
                'User.id != ' . $this->Auth->user('id'),
                'Droit.liste_droit_id' => ListeDroit::VISER_TRAITEMENT
            ],
        ];
        $consultants = $this->Droit->find('all', $queryConsultants);
        $consultants = Hash::combine($consultants, '{n}.User.id', [
                    '%s %s',
                    '{n}.User.prenom',
                    '{n}.User.nom'
        ]);
        $return = ['consultants' => $consultants];


        // Requète récupérant les utilisateurs ayant le droit de validation
        if ($this->Session->read('Organisation.cil') != null) {
            $cil = $this->Session->read('Organisation.cil');
        } else {
            $cil = 0;
        }

        $queryValidants = [
            'fields' => [
                'User.id',
                'User.nom',
                'User.prenom'
            ],
            'joins' => [
                $this->Droit->join('OrganisationUser', ['type' => "INNER"]),
                $this->Droit->OrganisationUser->join('User', ['type' => "INNER"])
            ],
            'conditions' => [
                'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id'),
                'NOT' => [
                    'User.id' => [
                        $this->Auth->user('id'),
                        $cil
                    ]
                ],
                'Droit.liste_droit_id' => ListeDroit::VALIDER_TRAITEMENT
            ]
        ];
        $validants = $this->Droit->find('all', $queryValidants);
        $validants = Hash::combine($validants, '{n}.User.id', [
                    '%s %s',
                    '{n}.User.prenom',
                    '{n}.User.nom'
        ]);
        $return['validants'] = $validants;

        return $return;
    }

    /**
     * 
     * @param type $id
     * 
     * @access protected
     * @created 07/03/2017
     * @version V1.0.0
     * @author Théo GUILLON <theo.guillon@libriciel.coop>
     */
    protected function _typeDeclarationRemplie($id) {
        $typeDeclaration = $this->Valeur->find('first', [
            'conditions' => [
                'fiche_id' => $id,
                'champ_name' => 'typedeclaration'
            ]
        ]);

        if (!empty($typeDeclaration)) {
            if ($typeDeclaration['Valeur']['valeur'] != ' ') {
                $remplie = 'true';
            } else {
                $remplie = 'false';
            }
        } else {
            $remplie = 'false';
        }

        return($remplie);
    }

}
