<?php
class RegistresController extends AppController {
	public $uses=array('EtatFiche', 'Fiche');

	public function index(){

		if(!empty($this->request->data['Registre']['search'])){
			$condition = array(
				'EtatFiche.etat_id' => array(5,7),
				'Fiche.outilnom LIKE'  => "%".$this->request->data['Registre']['search']."%",
				'Fiche.organisation_id' => $this->Session->read('Organisation.id')
				);
		}
		else{
			$condition = array(
				'EtatFiche.etat_id' => array(5,7),
				'Fiche.organisation_id' => $this->Session->read('Organisation.id')
				);
		}


		if($this->Droits->authorized(array('4','5','6'))){
			$fichesValid = $this->EtatFiche->find('all', array(
				'conditions' => $condition,
				'contain' => array(
					'Fiche' => array(
						'id',
						'outilnom',
						'created',
						'User' => array(
							'nom',
							'prenom'
							)
						)
					)
				)
			);
			foreach ($fichesValid as $key => $value) {
				if($this->Droits->isReadable($value['Fiche']['id'])){
					$fichesValid[$key]['Readable']=true;
				}
				else{
					$fichesValid[$key]['Readable']=false;
				}
			}

			$this->set('fichesValid', $fichesValid);
		}
		else
		{
			$this->Session->setFlash('Vous n\'avez pas le droit d\'acceder à cette page', 'flasherror');
			$this->redirect(array('controller'=>'pannel', 'action'=>'index'));
		}

	}
}