<?php


    /**************************************************
     ************** Controller des fiches **************
     **************************************************/
    class FichesController extends AppController
    {

        public $helpers = [
            'Html',
            'Form',
            'Session'
        ];
        public $uses = [
            'Fiche',
            'Organisation',
            'File',
            'EtatFiche',
            'Historique',
            'FormGeneric',
            'FormGenerator.Champ',
            'Valeur',
            'Modele'
        ];


        /**
         *** La page d'accueil des fiches est celle du pannel général
         **/

        public function index()
        {
            $this->redirect([
                'controller' => 'pannel',
                'action'     => 'index'
            ]);
        }


        /**
         *** Gère l'ajout de fiches
         **/

        public function add($id = NULL)
        {

            $this->set('title', 'Création d\'une fiche');
            if($this->Droits->authorized(1)) {
                if($this->request->is('POST')) {
                    $this->Fiche->create([
                        'user_id'         => $this->Auth->user('id'),
                        'form_id'         => $this->request->data['Fiche']['formulaire_id'],
                        'organisation_id' => $this->Session->read('Organisation.id')
                    ]);
                    if($this->Fiche->save()) {
                        $last = $this->Fiche->getLastInsertID();
                        if($this->File->saveFile($this->request->data, $last)) {
                            foreach($this->request->data['Fiche'] as $key => $value) {
                                if($key != 'formulaire_id') {
                                    if(is_array($value)) {
                                        $value = json_encode($value);
                                    }
                                    $this->Valeur->create([
                                        'champ_name' => $key,
                                        'fiche_id'   => $last,
                                        'valeur'     => $value
                                    ]);
                                    $this->Valeur->save();
                                }
                            }
                            $this->Historique->create([
                                'Historique' => [
                                    'content'  => 'Création de la fiche par ' . $this->Auth->user('prenom') . ' ' . $this->Auth->user('nom'),
                                    'fiche_id' => $last
                                ]
                            ]);
                            $this->Historique->save();
                            $this->EtatFiche->create([
                                'EtatFiche' => [
                                    'fiche_id'         => $last,
                                    'etat_id'          => 1,
                                    'previous_user_id' => $this->Auth->user('id'),
                                    'user_id'          => $this->Auth->user('id')
                                ]
                            ]);
                            if($this->EtatFiche->save()) {
                                $this->Session->setFlash('La fiche a été enregistrée', 'flashsuccess');
                                $this->redirect([
                                    'action' => 'index'
                                ]);
                            }
                        }
                    }
                } else {
                    $champs = $this->Champ->find('all', [
                        'conditions' => ['formulaires_id' => $id],
                        'order'      => [
                            'colonne ASC',
                            'ligne ASC'
                        ]
                    ]);
                    $this->set(compact('champs'));
                    $this->set('formulaireid', $id);
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
         *** Gère la suppression de fiches
         **/

        public function delete($id = NULL)
        {
            if($this->Droits->authorized(1) && $this->Droits->isOwner($id)) {
                if(!$this->Droits->isdeletable($id)) {
                    $this->Session->setFlash('Vous n\'avez pas accès à cette fiche', 'flasherror');
                    $this->redirect([
                        'controller' => 'pannel',
                        'action'     => 'index'
                    ]);
                }
                $this->Fiche->delete($id);
                $this->Session->setFlash('La fiche a bien été supprimée', 'flashsuccess');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            } else {
                $this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
        }


        /**
         *** Gère l'édition de fiches
         **/

        public function edit($id = NULL)
        {
            $this->set('title', 'Edition d\'une fiche');
            if(!$id && !$this->request->data['Fiche']['id']) {
                $this->Session->setFlash('Cette fiche n\'existe pas', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
            if(!$id) {
                $id = $this->request->data['Fiche']['id'];
            }
            if(!$this->Droits->isEditable($id)) {
                $this->Session->setFlash('Vous n\'avez pas accès à cette fiche', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
            if($this->request->is([
                'post',
                'put'
            ])
            ) {

                foreach($this->request->data['Fiche'] as $key => $value) {
                    $this->Valeur->begin();
                    $this->Valeur->deleteAll([
                        'champ_name' => $key,
                        'fiche_id'   => $id
                    ]);
                    if($key != 'formulaire_id') {
                        if(is_array($value)) {
                            $value = json_encode($value);
                        }
                        $this->Valeur->create([
                            'champ_name' => $key,
                            'fiche_id'   => $id,
                            'valeur'     => $value
                        ]);
                        $this->Valeur->save();
                        $this->Valeur->commit();
                    }
                }
                if($this->File->saveFile($this->request->data, $id)) {
                    $this->Historique->create([
                        'Historique' => [
                            'content'  => $this->Auth->user('prenom') . ' ' . $this->Auth->user('nom') . ' modifie la fiche',
                            'fiche_id' => $id
                        ]
                    ]);
                    $this->Historique->save();
                }
                if(isset($this->request->data['delfiles']) && !empty($this->request->data['delfiles'])) {
                    foreach($this->request->data['delfiles'] as $val) {
                        $this->File->deleteFile($val);
                    }
                }
                $this->Session->setFlash('La fiche a été modifiée', 'flashsuccess');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            } else {
                $idForm = $this->Fiche->find('first', ['conditions' => ['id' => $id]]);
                $champs = $this->Champ->find('all', [
                    'conditions' => ['formulaires_id' => $idForm['Fiche']['form_id']],
                    'order'      => [
                        'colonne ASC',
                        'ligne ASC'
                    ]
                ]);
                $files = $this->File->find('all', ['conditions' => ['fiche_id' => $id]]);
                $this->set(compact('files'));
                $valeurs = $this->Valeur->find('all', ['conditions' => ['fiche_id' => $id]]);
                foreach($valeurs as $key => $value) {
                    if($this->Fiche->isJson($value['Valeur']['valeur'])) {
                        $this->request->data['Fiche'][$value['Valeur']['champ_name']] = json_decode($value['Valeur']['valeur']);
                    } else {
                        $this->request->data['Fiche'][$value['Valeur']['champ_name']] = $value['Valeur']['valeur'];
                    }
                }
                $this->set(compact('valeurs'));
                $this->set(compact('champs'));
                $this->set('id', $id);
            }
        }


        /**
         *** Gère l'affichage des fiches
         **/

        public function show($id = NULL)
        {
            $this->set('title', 'Apercu d\'une fiche');
            if(!$id) {
                $this->Session->setFlash('Cette fiche n\'existe pas', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
            if(!$this->Droits->isReadable($id)) {
                $this->Session->setFlash('Vous n\'avez pas accès à cette fiche', 'flasherror');
                $this->redirect([
                    'controller' => 'pannel',
                    'action'     => 'index'
                ]);
            }
            $idForm = $this->Fiche->find('first', ['conditions' => ['id' => $id]]);
            $champs = $this->Champ->find('all', [
                'conditions' => ['formulaires_id' => $idForm['Fiche']['form_id']],
                'order'      => [
                    'colonne ASC',
                    'ligne ASC'
                ]
            ]);
            $valeurs = $this->Valeur->find('all', ['conditions' => ['fiche_id' => $id]]);
            foreach($valeurs as $key => $value) {
                if($this->Fiche->isJson($value['Valeur']['valeur'])) {
                    $this->request->data['Fiche'][$value['Valeur']['champ_name']] = json_decode($value['Valeur']['valeur']);
                } else {
                    $this->request->data['Fiche'][$value['Valeur']['champ_name']] = $value['Valeur']['valeur'];
                }
            }
            $this->set(compact('valeurs'));
            $this->set(compact('champs'));
            $this->set('id', $id);
            $files = $this->File->find('all', ['conditions' => ['fiche_id' => $id]]);
            $this->set(compact('files'));
        }


        /**
         *** Gère le téléchargement des pieces jointes d'une fiche
         **/

        public function download($url = NULL)
        {
            $this->response->file(WWW_ROOT . 'files/' . $url, [
                'download' => TRUE,
                'name'     => 'file'
            ]);
        }


        /**
         *** Génération PDF à la volée
         **/
        function genereFusion($id)
        {
            App::uses('FusionConvBuilder', 'FusionConv.Utility');
            $data = $this->Valeur->find('all', ['conditions' => ['fiche_id' => $id]]);
            $fiche = $this->Fiche->find('first', ['conditions' => ['id' => $id]]);
            $modele = $this->Modele->find('first', ['conditions' => ['formulaires_id' => $fiche['Fiche']['form_id']]]);
            if(!empty($modele)) {
                $file = $modele['Modele']['fichier'];
            } else {
                $file = '1.odt';
            }


            $donnees = [];
            foreach($data as $key => $value) {
                $donnees['Valeur'][$value['Valeur']['champ_name']] = $value['Valeur']['valeur'];
            }
            unset($donnees['Valeur']['fichiers']);


            $types = [];
            foreach($donnees['Valeur'] as $key => $value) {
                $types['Valeur.' . $key] = 'text';
            }


            $correspondances = [];
            foreach($donnees['Valeur'] as $key => $value) {
                $correspondances['valeur_' . $key] = 'Valeur.' . $key;
            }


            $MainPart = new GDO_PartType();

            $Document = FusionConvBuilder::main($MainPart, $donnees, $types, $correspondances);

            $sMimeType = 'application/vnd.oasis.opendocument.text';

            $Template = new GDO_ContentType("", 'model.odt', "application/vnd.oasis.opendocument.text", "binary", file_get_contents(WWW_ROOT . '/files/modeles/' . $file));
            $Fusion = new GDO_FusionType($Template, $sMimeType, $Document);

            $Fusion->process();
            App::uses('FusionConvConverterCloudooo', 'FusionConv.Utility/Converter');
            $pdf = FusionConvConverterCloudooo::convert($Fusion->getContent()->binary);


            $this->response->disableCache();
            $this->response->body($pdf);
            $this->response->type('application/pdf');
            $this->response->download($id . '.pdf');

            return $this->response;
        }
    }