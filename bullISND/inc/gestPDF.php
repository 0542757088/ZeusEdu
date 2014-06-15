<?php
$unAn = time() + 365*24*3600;
$etape = isset($_REQUEST['etape'])?$_REQUEST['etape']:Null;
$bulletin = isset($_REQUEST['bulletin'])?$_REQUEST['bulletin']:PERIODEENCOURS;

if (isset($_POST['classe'])) {
	$classe = $_POST['classe'];
	setcookie('classe',$classe,$unAn, null, null, false, true);
	}
	else $classe = $_COOKIE['classe'];
$smarty->assign('classe', $classe);

if (isset($_POST['matricule'])) {
	$matricule = $_POST['matricule'];
	setcookie('matricule',$matricule,$unAn, null, null, false, true);
	}
	else $matricule = $_COOKIE['matricule'];
$smarty->assign('matricule', $matricule);

if (isset($_POST['niveau'])) {
	$niveau = $_POST['niveau'];
	setcookie('niveau',$niveau,$unAn, null, null, false, true);
	}
	else $niveau = $_COOKIE['niveau'];
$smarty->assign('niveau', $niveau);

$acronyme = $_SESSION[APPLICATION]->getAcronyme();

switch ($mode) {
	case 'archive':
		$annee = isset($_POST['annee'])?$_POST['annee']:Null;

		$smarty->assign('listeAnnees', $Bulletin->anneesArchivesDispo());
		$smarty->assign('listeNiveaux', $Ecole->listeNiveaux());
		$smarty->assign('action',$action);
		$smarty->assign('mode',$mode);
		$smarty->assign('selecteur','selectAnneeNiveauEleve');
		if ($etape == 'showEleve') {
			$listeElevesArchives = $Bulletin->listeElevesArchives($annee, $niveau);
			$smarty->assign('listeEleves', $listeElevesArchives);
			$nomEleve = isset($_POST['nomEleve'])?$_POST['nomEleve']:Null;
			$anneeScolaire = isset($_POST['annee'])?$_POST['annee']:Null;
			$smarty->assign('nomEleve', $nomEleve);
			$smarty->assign('anneeScolaire', $anneeScolaire);
			$classeArchive = $Bulletin->classeArchiveEleve($matricule, $anneeScolaire);
			$smarty->assign('periodes', $Bulletin->listePeriodes(NBPERIODES));
			$smarty->assign('classeArchive', $classeArchive);
			$smarty->assign('corpsPage','bulletinsArchive');
		}
		break;
	
	case 'bulletinIndividuel':
		$listeClasses = $Ecole->listeGroupes(array('G','TT','S'));
		if ($classe != Null) 
			$listeEleves = $Ecole->listeEleves($classe,'groupe');
			else $listeEleves = Null;
		$smarty->assign('listeClasses', $listeClasses);
		$smarty->assign('listeEleves', $listeEleves);
		$smarty->assign('nbBulletins', NBPERIODES);
		$smarty->assign('bulletin', $bulletin);
		$smarty->assign('action', $action);
		$smarty->assign('mode', $mode);
		$smarty->assign('selecteur', 'selectBulletinClasseEleve');

		if ($etape == 'showEleve') {
			if ($matricule) {
				$smarty->assign('acronyme', $acronyme);
				// effacement de tous les fichiers PDF de l'utilisateur sauf pour les admins
				if ($user->userStatus($Application->repertoireActuel()) != 'admin')
					$Application->vider('./pdf/$acronyme');
				$dataEleve = array(
						'matricule'=>$matricule,
						'classe'=>$classe,
						'annee'=>$Ecole->anneeDeClasse($classe),
						'degre'=>$Ecole->degreDeClasse($classe),
						'titulaires'=>$Ecole->titusDeGroupe($classe)
						);
				$link = $Bulletin->createPDFeleve($dataEleve, $bulletin, $acronyme);
				$smarty->assign('link',$link);
				$smarty->assign('corpsPage', 'corpsPage');
				}
			}
		break;
	case 'bulletinClasse':
		// liste complète des noms des classes en rapport avec leur classe
		$listeClasses = $Ecole->listeGroupes(array('G','TT','S'));
		$smarty->assign('selecteur', 'selectBulletinClasse');
		$smarty->assign('listeClasses', $listeClasses);
		$smarty->assign('nbBulletins', NBPERIODES);
		$smarty->assign('bulletin', $bulletin);
		$smarty->assign('action', $action);
		$smarty->assign('mode', $mode);
		$smarty->assign('etape', 'showClasse');

		if ($etape == 'showClasse') {
			if ($classe) {
				// retourne la liste des élèves pour une classe donnée
				$listeEleves = $Ecole->listeEleves($classe,'groupe');
				// effacement de tous les fichiers PDF de l'utilisateur sauf pour les admins
				if ($user->userStatus($Application->repertoireActuel()) != 'admin')
					$Application->vider ('./pdf/$acronyme');

				$link = $Bulletin->createPDFclasse($listeEleves, $classe, $bulletin, $acronyme);
				$smarty->assign('acronyme', $acronyme);
				$smarty->assign('link',$link);
				$smarty->assign('corpsPage', 'corpsPage');
				}
			}
		break;
	case 'niveau':
		$smarty->assign('nbBulletins', NBPERIODES);
		$listeNiveaux = $Ecole->listeNiveaux();
		$smarty->assign('selecteur','selectBulletinNiveau');
		$smarty->assign('listeNiveaux',$listeNiveaux);
		$smarty->assign('bulletin',$bulletin);
		$smarty->assign('action',$action);
		$smarty->assign('mode', $mode);
		
		if ($etape == 'showNiveau') {
			if ($niveau) {
				$listeClasses = $Ecole->listeClassesNiveau($niveau, 'groupe', array('G','TT','S'));
				if ($user->userStatus($Application->repertoireActuel()) != 'admin')
					$Application->vider('./pdf/$acronyme');
				// accumuler tous les bulletins dans des fichiers par classe
				foreach ($listeClasses as $classe) {
					$listeEleves = $Ecole->listeEleves($classe,'groupe');
					$link = $Bulletin->createPDFclasse($listeEleves, $classe, $bulletin, $acronyme, true);
					}
				// zipper l'ensemble des fichiers
				$Application->zipFilesNiveau ('pdf/$acronyme', $listeClasses);
				$smarty->assign('acronyme', $acronyme);
				$smarty->assign('link',$niveau);
				$smarty->assign('corpsPage','corpsPage');
				}
			}
		break;
	case 'delete':
		if ($etape == 'confirmation') {
			foreach ($_POST as $nomChamp=>$value) {
				if (preg_match('/^del#/',$nomChamp)) 
					@unlink('./pdf/$acronyme/$value');
				}
			}	
		// break;  pas de break
	default: 
		$listeFichiers = $Application->scanDirectories ('./pdf/$acronyme/');
		$smarty->assign('action', $action);
		$smarty->assign('mode', 'delete');
		$smarty->assign('etape', 'confirmation');
		$smarty->assign('acronyme',$acronyme);
		$smarty->assign('listeFichiers', $listeFichiers);
		$smarty->assign('userName', $acronyme);
		$smarty->assign('corpsPage', 'tableauFichiersPDF');
		break;
	}
?>
