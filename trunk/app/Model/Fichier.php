<?php

/**
 * Model File
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
 * @since       webcil v0.9.0
 * @license     http://www.cecill.info/licences/Licence_CeCILL_V2-fr.html CeCiLL V2 License
 * @version     v0.9.0
 * @package     AppModel
 */
App::uses('AppModel', 'Model');

class Fichier extends AppModel {

    public $name = 'Fichier';

    /**
     * belongsTo associations
     * 
     * @var array
     * 
     * @access public
     * @created 29/04/2015
     * @version V0.9.0
     */
    public $belongsTo = array(
        'Fiche' => array(
            'className' => 'Fiche',
            'foreignKey' => 'fiche_id'
        )
    );

    /**
     * @param type $data
     * @param int|null $id
     * @return boolean
     * 
     * @access public
     * @created 29/04/2015
     * @version V0.9.0
     */
    public function saveFichier($data, $id = null) {
        if (isset($data['Fiche']['fichiers']) && !empty($data['Fiche']['fichiers'])) {
            foreach ($data['Fiche']['fichiers'] as $key => $file) {
                if (!empty($file['name'])) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    debug($extension);

                    if ($extension == 'odt') {
                        $success = true;

                        if (!empty($file['name'])) {
                            $this->begin();

                            // On verifie si le dossier file existe. Si c'est pas le cas on le cree
                            if (!file_exists(APP . FICHIER)) {
                                mkdir(APP . FICHIER, 0777, true);
                                mkdir(APP . FICHIER . PIECE_JOINT, 0777, true);
                                mkdir(APP . FICHIER . MODELES, 0777, true);
                                mkdir(APP . FICHIER . REGISTRE, 0777, true);
                            } else {
                                if (!file_exists(APP . FICHIER . PIECE_JOINT)) {
                                    mkdir(APP . FICHIER . PIECE_JOINT, 0777, true);
                                }
                            }

                            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $name = $file['name'];
                            if (!empty($file['tmp_name'])) {
                                $url = time();
                                $success = $success && move_uploaded_file($file['tmp_name'], CHEMIN_PIECE_JOINT . $url . $key . '.' . $extension);
                                if ($success) {
                                    $this->create(array(
                                        'nom' => $name,
                                        'url' => $url . $key . '.' . $extension,
                                        'fiche_id' => $id
                                    ));
                                    $success = $success && $this->save();
                                }
                            } else {
                                $success = false;
                            }
                        }
                    } else {
                        $success = false;
                    }

                    if ($success) {
                        $this->commit();
                    } else {
                        $this->rollback();
                        return false;
                    }
                } else {
                    return true;
                }
            }
        }
        return true;
    }

    /**
     * @param int $id
     * @return boolean
     * 
     * @access public
     * @created 26/06/2015
     * @version V0.9.0
     */
    public function deleteFichier($id) {
        $success = true;
        $this->begin();

        $fichier = $this->find('first', array(
            'conditions' => array(
                'id' => $id
            )
        ));

        $success = $success && unlink(CHEMIN_PIECE_JOINT . $fichier['Fichier']['url']);
        $success = $success && $this->delete($id);

        if ($success) {
            $this->commit();
            return true;
        } else {
            $this->rollback();
            return false;
        }
    }

}
