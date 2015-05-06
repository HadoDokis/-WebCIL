<?php

// app/Controller/UsersController.php
class UsersController extends AppController
{

    public $uses = array(
        'User',
        'Organisation',
        'Role',
        'ListeDroit',
        'OrganisationUser',
        'Droit',
        'RoleDroit',
        'OrganisationUserRole'
    );
    public $helpers = array('Controls');


    /**
     * Récupère le beforefilter de AppController (login)
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
    }


    /**
     * Index des utilisateurs. Liste les utilisateurs enregistrés
     */
    public function index()
    {
        if ( $this->Droits->authorized(array(
            '8',
            '9',
            '10'
        ))
        ) {
            if ( $this->Auth->user('id') == 1 ) {
                $conditions = array();
                if ( $this->request->is('post') ) {
                    if ( $this->request->data[ 'users' ][ 'organisation' ] != '' ) {
                        $conditions[ 'OrganisationUser.organisation_id' ] = $this->request->data[ 'users' ][ 'organisation' ];
                    }
                    if ( $this->request->data[ 'users' ][ 'nom' ] != '' ) {
                        $conditions[ 'OrganisationUser.user_id' ] = $this->request->data[ 'users' ][ 'nom' ];
                        $users = $this->OrganisationUser->find('all', array(
                            'conditions' => $conditions,
                            'contain' => array(
                                'User' => array(
                                    'id',
                                    'nom',
                                    'prenom',
                                    'created'
                                )
                            ),
                            'limit' => 1
                        ));
                    }
                    else {
                        $users = $this->OrganisationUser->find('all', array(
                            'conditions' => $conditions,
                            'contain' => array(
                                'User' => array(
                                    'id',
                                    'nom',
                                    'prenom',
                                    'created'
                                )
                            )
                        ));
                    }
                }
                else {
                    $users = $this->OrganisationUser->find('all', array(
                        'conditions' => array(
                            'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id')
                        ),
                        'contain' => array(
                            'User' => array(
                                'id',
                                'nom',
                                'prenom',
                                'created'
                            )
                        )
                    ));
                }
            }
            else {
                $users = $this->OrganisationUser->find('all', array(
                    'conditions' => array(
                        'OrganisationUser.organisation_id' => $this->Session->read('Organisation.id'),
                        'OrganisationUser.user_id !=' => 1
                    ),
                    'contain' => array(
                        'User' => array(
                            'id',
                            'nom',
                            'prenom',
                            'created'
                        )
                    )
                ));
            }
            foreach ( $users as $key => $value ) {
                $orgausers = $this->OrganisationUser->find('all', array('conditions' => array('OrganisationUser.user_id' => $value[ 'OrganisationUser' ][ 'user_id' ])));
                foreach ( $orgausers as $clef => $valeur ) {
                    $orga = $this->Organisation->find('first', array(
                        'conditions' => array('Organisation.id' => $valeur[ 'OrganisationUser' ][ 'organisation_id' ]),
                        'fields' => array('raisonsociale')
                    ));
                    $users[ $key ][ 'Organisations' ][ ] = $orga;
                }
            }
            $this->set('users', $users);
            $orgas = $this->Organisation->find('all', array(
                'fields' => array(
                    'Organisation.raisonsociale',
                    'id'
                )
            ));
            $organisations = array();
            foreach ( $orgas as $value ) {
                $organisations[ $value[ 'Organisation' ][ 'id' ] ] = $value[ 'Organisation' ][ 'raisonsociale' ];
            }
            $this->set('orgas', $organisations);

            $utils = $this->User->find('all', array(
                'fields' => array(
                    'User.nom',
                    'User.prenom',
                    'User.id'
                )
            ));
            $utilisateurs = array();
            foreach ( $utils as $value ) {
                $utilisateurs[ $value[ 'User' ][ 'id' ] ] = $value[ 'User' ][ 'prenom' ] . ' ' . $value[ 'User' ][ 'nom' ];
            }
            $this->set('utilisateurs', $utilisateurs);
        }
        else {
            $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
            $this->redirect(array(
                'controller' => 'pannel',
                'action' => 'index'
            ));
        }
    }


    /**
     * Affiche les informations sur un utilisateur
     *
     * @param  [integer] $id [id de l'utilisateur à afficher]
     */
    public function view($id = null)
    {
        if ( $this->Droits->authorized(array(
            '8',
            '9',
            '10'
        ))
        ) {
            $this->User->id = $id;
            if ( !$this->User->exists() ) {
                throw new NotFoundException('User invalide');
            }
            $this->set('user', $this->User->read(null, $id));
        }
        else {
            $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
            $this->redirect(array(
                'controller' => 'pannel',
                'action' => 'index'
            ));
        }
    }


    /**
     * Affiche le formulaire d'ajout d'utilisateur, ou enregistre l'utilisateur et ses droits
     */
    public function add()
    {
        if ( $this->Droits->authorized(8) || $this->Droits->isSu() ) {
            $this->set('idUser', $this->Auth->user('id'));
            if ( $this->request->is('post') ) {
                $this->User->create($this->request->data);
                if ( $this->User->save() ) {
                    $userId = $this->User->getInsertID();
                    foreach ( $this->request->data[ 'Organisation' ][ 'Organisation_id' ] as $value ) {
                        debug($value);
                        $this->OrganisationUser->create(array(
                            'user_id' => $userId,
                            'organisation_id' => $value
                        ));
                        $this->OrganisationUser->save();
                        $organisationUserId = $this->OrganisationUser->getInsertID();
                        foreach ( $this->request->data[ 'Droits' ][ $value ] as $key => $donnee ) {
                            if ( $donnee ) {
                                $this->Droit->create(array(
                                    'organisation_user_id' => $organisationUserId,
                                    'liste_droit_id' => $key
                                ));
                                $this->Droit->save();
                            }
                        }
                        if ( !empty($this->request->data[ 'Role' ][ 'role_ida' ]) ) {
                            foreach ( $this->request->data[ 'Role' ][ 'role_ida' ] as $key => $donnee ) {
                                if ( $donnee ) {
                                    $this->OrganisationUserRole->create(array(
                                        'organisation_user_id' => $organisationUserId,
                                        'role_id' => $donnee
                                    ));
                                    $this->OrganisationUserRole->save();
                                }
                            }
                        }
                    }
                    $this->Session->setFlash('L\'user a été sauvegardé', 'flashsuccess');
                    $this->redirect(array(
                        'controller' => 'users',
                        'action' => 'index'
                    ));
                }
                else {
                    $table = $this->_createTable();
                    $this->set('tableau', $table[ 'tableau' ]);
                    $this->set('listedroits', $table[ 'listedroits' ]);
                }
            }
            else {
                $table = $this->_createTable();
                $this->set('tableau', $table[ 'tableau' ]);
                $this->set('listedroits', $table[ 'listedroits' ]);
            }
        }
        else {
            $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
            $this->redirect(array(
                'controller' => 'pannel',
                'action' => 'index'
            ));
        }
    }


    /**
     * Modification d'un utilisateur
     *
     * @param  [integer] $id [id de l'utilisateur à modifier]
     */
    public function edit($id = null)
    {
        if ( $this->Droits->authorized(9) || $id == $this->Auth->user('id') ) {
            $this->User->id = $id;
            if ( !$this->User->exists() ) {
                throw new NotFoundException('User Invalide');
            }
            if ( $this->request->is('post') || $this->request->is('put') ) {
                if ( $this->request->data[ 'User' ][ 'new_password' ] == $this->request->data[ 'User' ][ 'new_passwd' ] ) {
                    if ( $this->request->data[ 'User' ][ 'new_password' ] != '' ) {
                        $this->request->data[ 'User' ][ 'password' ] = $this->request->data[ 'User' ][ 'new_password' ];
                    }
                    if ( $this->User->save($this->request->data) ) {
                        $this->OrganisationUser->deleteAll(array('user_id' => $id), true, false);

                        foreach ( $this->request->data[ 'Organisation' ][ 'Organisation_id' ] as $value ) {
                            $this->OrganisationUser->create(array(
                                'user_id' => $id,
                                'organisation_id' => $value
                            ));
                            $this->OrganisationUser->save();
                            $organisationUserId = $this->OrganisationUser->getInsertID();
                            foreach ( $this->request->data[ 'Droits' ][ $value ] as $key => $donnee ) {
                                if ( $donnee ) {
                                    $this->Droit->create(array(
                                        'organisation_user_id' => $organisationUserId,
                                        'liste_droit_id' => $key
                                    ));
                                    $this->Droit->save();
                                }
                            }
                            if ( !empty($this->request->data[ 'Role' ][ 'role_ida' ]) ) {
                                foreach ( $this->request->data[ 'Role' ][ 'role_ida' ] as $key => $donnee ) {
                                    if ( $this->Role->find('count', array(
                                            'conditions' => array(
                                                'Role.organisation_id' => $value,
                                                'Role.id' => $donnee
                                            )
                                        )) > 0
                                    ) {
                                        $this->OrganisationUserRole->create(array(
                                            'organisation_user_id' => $organisationUserId,
                                            'role_id' => $donnee
                                        ));
                                        $this->OrganisationUserRole->save();
                                    }
                                }
                            }
                        }
                        $this->Session->setFlash('L\'user a été sauvegardé', "flashsuccess");
                        $this->redirect(array(
                            'controller' => 'users',
                            'action' => 'index'
                        ));
                    }
                    else {
                        $this->Session->setFlash('L\'user n\'a pas été sauvegardé. Merci de réessayer.', "flasherror");
                        $this->redirect(array(
                            'controller' => 'users',
                            'action' => 'index'
                        ));
                    }
                }
                else {
                    $this->Session->setFlash('L\'user n\'a pas été sauvegardé. Merci de réessayer.', "flasherror");
                    $this->redirect(array(
                        'controller' => 'users',
                        'action' => 'index'
                    ));
                }
            }
            else {
                $table = $this->_createTable($id);
                $this->set('tableau', $table[ 'tableau' ]);
                $this->set('listedroits', $table[ 'listedroits' ]);
            }
        }
        else {
            $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
            $this->redirect(array(
                'controller' => 'pannel',
                'action' => 'index'
            ));
        }
    }


    /**
     * Suppression d'un utilisateur
     *
     * @param  [integer] $id [id de l'utilisateur à supprimer]
     */
    public function delete($id = null)
    {
        if ( $this->Droits->authorized(10) ) {
            $this->User->id = $id;
            if ( !$this->User->exists() ) {
                throw new NotFoundException('User invalide');
            }
            if ( $id != 1 ) {
                $nb = $this->OrganisationUser->find('count', array('conditions' => array('user_id' => $id)));
                if ( $nb > 1 ) {
                    $this->OrganisationUser->deleteAll(array(
                        'user_id' => $id,
                        'organisation_id' => $this->Session->read('Organisation.id')
                    ));
                }
                else {
                    if ( $this->Droits->isCil() ) {
                        $this->Organisation->updateAll(array('Organisation.cil' => null), array('Organisation.cil' => $id));
                    }
                    if ( $this->User->delete() ) {
                        $this->Session->setFlash('User supprimé', 'flashsuccess');
                        $this->redirect(array('action' => 'index'));
                    }
                }
            }
            $this->Session->setFlash('L\'user n\'a pas été supprimé', 'flasherror');
            $this->redirect(array('action' => 'index'));
        }
        else {
            $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
            $this->redirect(array(
                'controller' => 'pannel',
                'action' => 'index'
            ));
        }
    }


    /**
     * Page de login
     */
    public function login()
    {
        if ( $this->request->is('post') ) {
            if ( $this->Auth->login() ) {
                $this->_cleanSession();
                $this->redirect(array(
                    'controller' => 'organisations',
                    'action' => 'change'
                ));
            }
            else {
                $this->Session->setFlash('Nom d\'user ou mot de passe invalide, réessayer', 'flasherror');
            }
        }
    }


    /**
     * Page de deconnexion
     */
    public function logout()
    {
        $this->_cleanSession();
        $this->redirect($this->Auth->logout());
    }


    /**
     * Fonction de suppression du cache (sinon pose des problemes lors du login)
     */
    protected function _cleanSession()
    {
        $this->Session->delete('Organisation');
    }

    /**
     *  Fonction de création du tableau de droits pour le add and edit user
     */

    protected function _createTable($id = null)
    {
        $tableau = array('Organisation' => array());
        if ( $this->Auth->user('id') == 1 ) {
            $organisations = $this->Organisation->find('all');
        }
        else {
            $organisations = $this->Organisation->find('all', array('conditions' => array('id' => $this->Session->read('Organisation.id'))));
        }
        foreach ( $organisations as $key => $value ) {
            $tableau[ 'Organisation' ][ $value[ 'Organisation' ][ 'id' ] ][ 'infos' ] = array(
                'raisonsociale' => $value[ 'Organisation' ][ 'raisonsociale' ],
                'id' => $value[ 'Organisation' ][ 'id' ]
            );
            $roles = $this->Role->find('all', array(
                'recursive' => -1,
                'conditions' => array('organisation_id' => $value[ 'Organisation' ][ 'id' ])
            ));
            $tableau[ 'Organisation' ][ $value[ 'Organisation' ][ 'id' ] ][ 'roles' ] = array();
            foreach ( $roles as $clef => $valeur ) {
                $tableau[ 'Organisation' ][ $value[ 'Organisation' ][ 'id' ] ][ 'roles' ][ $valeur[ 'Role' ][ 'id' ] ] = array(
                    'infos' => array(
                        'id' => $valeur[ 'Role' ][ 'id' ],
                        'libelle' => $valeur[ 'Role' ][ 'libelle' ],
                        'organisation_id' => $valeur[ 'Role' ][ 'organisation_id' ]
                    )
                );
                $droitsRole = $this->RoleDroit->find('all', array(
                    'recursive' => -1,
                    'conditions' => array('role_id' => $valeur[ 'Role' ][ 'id' ])
                ));
                foreach ( $droitsRole as $k => $val ) {
                    $tableau[ 'Organisation' ][ $value[ 'Organisation' ][ 'id' ] ][ 'roles' ][ $valeur[ 'Role' ][ 'id' ] ][ 'droits' ][ $val[ 'RoleDroit' ][ 'id' ] ] = $val[ 'RoleDroit' ];
                }
            }
        }
        if ( $id != null ) {
            $this->set("userid", $id);

            $organisationUser = $this->OrganisationUser->find('all', array(
                'conditions' => array('user_id' => $id),
                'contain' => array('Droit')
            ));

            foreach ( $organisationUser as $key => $value ) {
                $tableau[ 'Orgas' ][ ] = $value[ 'OrganisationUser' ][ 'organisation_id' ];

                $userroles = $this->OrganisationUserRole->find('all', array('conditions' => array('OrganisationUserRole.organisation_user_id' => $value[ 'OrganisationUser' ][ 'id' ])));
                foreach ( $userroles as $cle => $val ) {
                    $tableau[ 'UserRoles' ][ ] = $val[ 'OrganisationUserRole' ][ 'role_id' ];
                }
                foreach ( $value[ 'Droit' ] as $clef => $valeur ) {
                    $tableau[ 'User' ][ $value[ 'OrganisationUser' ][ 'organisation_id' ] ][ ] = $valeur[ 'liste_droit_id' ];
                }
            }
            $this->request->data = $this->User->read(null, $id);
            unset($this->request->data[ 'User' ][ 'password' ]);
        }
        $listedroits = $this->ListeDroit->find('all', array('recursive' => -1));
        $ld = array();
        foreach ( $listedroits as $c => $v ) {
            $ld[ $v[ 'ListeDroit' ][ 'value' ] ] = $v[ 'ListeDroit' ][ 'libelle' ];
        }
        $retour = array(
            'tableau' => $tableau,
            'listedroits' => $ld
        );
        return $retour;
    }
}