<?php

    class RolesController extends AppController
    {
        public $uses = [
            'Role',
            'ListeDroit',
            'RoleDroit'
        ];


        public function index()
        {
            $this->set('title', 'Liste des profils');
            if($this->Droits->authorized([
                '13',
                '14',
                '15'
            ])
            ) {
                $roles = $this->Role->find('all', [
                    'conditions' => ['organisation_id' => $this->Session->read('Organisation.id')]
                ]);
                foreach($roles as $key => $value) {
                    $test = $this->RoleDroit->find('all', [
                        'conditions' => ['role_id' => $value['Role']['id']],
                        'contain'    => ['ListeDroit' => ['libelle']],
                        'fields'     => 'id'
                    ]);
                    $roles[$key]['Droits'] = $test;
                }
                $this->set('roles', $roles);
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
        }

        public function add()
        {
            $this->set('title', 'Ajouter un profil');
            if($this->Droits->authorized(13) || $this->Droits->isSu()) {
                if($this->request->is('post')) {
                    $this->Role->create($this->request->data);
                    if($this->Role->save()) {
                        foreach($this->request->data['Droits'] as $key => $donnee) {
                            if($donnee) {
                                $this->RoleDroit->create([
                                    'role_id'        => $this->Role->getInsertID(),
                                    'liste_droit_id' => $key
                                ]);
                                $this->RoleDroit->save();
                            }
                        }
                        $this->Session->setFlash("Le profil a bien été enregistré", 'flashsuccess');
                        $this->redirect([
                            'controller' => 'roles',
                            'action'     => 'index'
                        ]);
                    } else {
                        $this->Session->setFlash("Une erreur s'est produite lors de l'enregistrement", 'flasherror');
                        $this->redirect([
                            'controller' => 'roles',
                            'action'     => 'index'
                        ]);
                    }
                } else {
                    $this->set('listedroit', $this->ListeDroit->find('all', ['conditions' => ['NOT' => ['ListeDroit.id' => ['11']]], 'order' => 'id']));
                }
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
        }


        public function show($id)
        {
            $this->set('title', 'Voir un profil');
            if(($this->Droits->authorized(13) && $this->Droits->currentOrgaRole($id)) || $this->Droits->isSu()) {
                if(!$id) {
                    throw new NotFoundException('Ce profil n\'existe pas');
                }
                $role = $this->Role->findById($id);
                if(!$role) {
                    throw new NotFoundException('Ce profil n\'existe pas');
                }

                $this->set('listedroit', $this->ListeDroit->find('all', ['conditions' => ['NOT' => ['ListeDroit.id' => ['11']]]]));
                $resultat = $this->RoleDroit->find('all', [
                    'conditions' => ['role_id' => $id],
                    'fields'     => 'liste_droit_id'
                ]);
                $result = [];
                foreach($resultat as $donnee) {
                    array_push($result, $donnee['RoleDroit']['liste_droit_id']);
                }
                $this->set('tableDroits', $result);
            }
            if(!$this->request->data) {
                $this->request->data = $role;
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);

            }
        }


        public function edit($id = NULL)
        {
            $this->set('title', 'Editer un profil');
            if(($this->Droits->authorized(14) && $this->Droits->currentOrgaRole($id)) || $this->Droits->isSu()) {
                if(!$id) {
                    throw new NotFoundException('Ce profil n\'existe pas');
                }
                $role = $this->Role->findById($id);
                if(!$role) {
                    throw new NotFoundException('Ce profil n\'existe pas');
                }
                if($this->request->is([
                    'post',
                    'put'
                ])
                ) {
                    $this->Role->id = $id;
                    if($this->Role->save($this->request->data)) {
                        $this->RoleDroit->deleteAll(['role_id' => $id], FALSE);
                        foreach($this->request->data['Droits'] as $key => $donnee) {
                            if($donnee) {
                                $this->RoleDroit->create([
                                    'role_id'        => $id,
                                    'liste_droit_id' => $key
                                ]);
                                $this->RoleDroit->save();
                            }
                        }
                        $this->Session->setFlash('Le profil a bien été mis à jour', 'flashsuccess');
                        $this->redirect([
                            'controller' => 'Roles',
                            'action'     => 'index'
                        ]);
                    }
                    $this->Session->setFlash("Une erreur s'est produite lors de la mise à jour", 'flasherror');
                    $this->redirect([
                        'controller' => 'Roles',
                        'action'     => 'index'
                    ]);
                } else {
                    $this->set('listedroit', $this->ListeDroit->find('all', ['conditions' => ['NOT' => ['ListeDroit.id' => ['11']]]]));
                    $resultat = $this->RoleDroit->find('all', [
                        'conditions' => ['role_id' => $id],
                        'fields'     => 'liste_droit_id'
                    ]);
                    $result = [];
                    foreach($resultat as $donnee) {
                        array_push($result, $donnee['RoleDroit']['liste_droit_id']);
                    }
                    $this->set('tableDroits', $result);
                }
                if(!$this->request->data) {
                    $this->request->data = $role;
                }
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);

            }
        }


        /**
         * Suppression d'un rôle
         *
         * @param  [integer] $id [id du rôle à supprimer]
         */

        public function delete($id = NULL)
        {
            if(($this->Droits->authorized(15) && $this->Droits->currentOrgaRole($id)) || $this->Droits->isSu()) {
                $this->Role->id = $id;
                if(!$this->Role->exists()) {
                    throw new NotFoundException('Ce profil n\'existe pas');
                }
                if($this->Role->delete()) {
                    $this->Session->setFlash('Le profil a bien été supprimé', 'flashsuccess');

                    return $this->redirect(['action' => 'index']);
                }
                $this->Session->setFlash('Le profil n\'a pas été supprimé', 'flasherror');

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
        }
    }
